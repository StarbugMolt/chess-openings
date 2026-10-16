<?php

declare(strict_types=1);

use App\Command\MigrateLessonsCommand;
use App\EventSubscriber\ManagedSchemaSubscriber;
use App\Service\Migrations\CatalogueOperationLock;
use App\Service\Migrations\LessonMigrationFactory;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Yaml\Yaml;

require '/app/backend/vendor/autoload.php';
$database = getenv('MIGRATION_TEST_DATABASE');
if (!is_string($database) || !preg_match('/\Achess_migration_test_[0-9_]+\z/', $database)) {
    throw new RuntimeException('Only a disposable test database is allowed.');
}
$url = preg_replace('~/[^/?]*(?=\?|$)~', '/'.$database, (string) getenv('DATABASE_URL'));
$environment = array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']);
// DBAL 4 no longer parses URL in DriverManager; use its URL parser explicitly.
$params = (new Doctrine\DBAL\Tools\DsnParser(['mysql' => 'pdo_mysql']))->parse($url);
$connection = DriverManager::getConnection($params);
$layout = sys_get_temp_dir().'/chess-migrations-'.bin2hex(random_bytes(8));
mkdir($layout.'/migrations/Schema', 0700, true);
mkdir($layout.'/migrations/LessonData', 0700, true);
$schemaFiles = glob('/app/backend/migrations/Schema/Version*.php');
foreach ($schemaFiles as $file) {
    copy($file, $layout.'/migrations/Schema/'.basename($file));
}
$schemaVersionCount = count($schemaFiles);
copy('/app/backend/tests/Fixtures/Migrations/Version20990101000000.php', $layout.'/migrations/LessonData/Version20990101000000.php');
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
};
$cli = static function (array $arguments, bool $success = true) use ($environment, $assert): string {
    $process = proc_open([PHP_BINARY, 'bin/console', ...$arguments, '--no-interaction', '--no-ansi'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/app/backend', $environment);
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start Symfony Console.');
    }
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    $assert($success ? 0 === $code : 0 !== $code, 'Unexpected console status: '.implode(' ', $arguments)."\n".$output);

    return $output;
};
$expectException = static function (callable $operation, string $message) use ($assert): void {
    try {
        $operation();
    } catch (Throwable $error) {
        $assert(str_contains($error->getMessage(), $message), 'Unexpected exception: '.$error->getMessage());

        return;
    }
    throw new RuntimeException('Expected failure: '.$message);
};

try {
    $cli(['app:lessons:migrate'], false);
    $assert([] === $connection->createSchemaManager()->listTableNames(), 'Lesson guard wrote to an empty database.');
    $cli(['app:schema:migrate']);
    $cli(['doctrine:migrations:migrate'], false);
    $cli(['app:database:prepare', '--lock-timeout=601'], false);
    $assert(18 === count($connection->createSchemaManager()->listTableNames()), 'Seven catalogue, three account, five lesson/progress, two authored-provenance and one schema history table expected.');
    $assert($schemaVersionCount === (int) $connection->fetchOne('SELECT COUNT(*) FROM doctrine_schema_versions'), 'Schema versions not recorded.');
    foreach ($connection->createSchemaManager()->listTableNames() as $table) {
        if ('user_account' !== $table) {
            $assert(0 === preg_match(ManagedSchemaSubscriber::FILTER, $table), 'DBAL-managed table is unprotected from ORM tools: '.$table);
        }
    }
    $cli(['doctrine:schema:validate']);
    $state = $connection->fetchAssociative('SELECT active_batch_id,catalogue_revision FROM opening_catalogue_state WHERE id=1');
    $assert(null === $state['active_batch_id'] && 0 === (int) $state['catalogue_revision'], 'Initial catalogue must be inactive.');
    $connection->executeStatement('DELETE FROM opening_catalogue_state WHERE id=1');
    $missingState = $cli(['app:database:prepare', '--lock-timeout=0'], false);
    $assert(str_contains($missingState, 'state row is missing') && !str_contains($missingState, 'Existing catalogue retained'), 'Missing state row was misreported as an active catalogue.');
    $connection->insert('opening_catalogue_state', ['id' => 1, 'catalogue_revision' => 0]);
    // Compare actual migrated DDL (including CHECKs/collations/FKs) with the approved proposal.
    $tables = ['opening_import_batch', 'opening_import_file', 'chess_position', 'chess_move', 'opening_entry', 'opening_entry_step', 'opening_catalogue_state'];
    $proposal = preg_replace('/^--.*$/m', '', file_get_contents('/app/docs/IMPORTER_SCHEMA_PROPOSAL.sql'));
    foreach ($tables as $table) {
        $proposal = str_replace(['CREATE TABLE '.$table.' ', 'REFERENCES '.$table.'(', 'INSERT INTO '.$table.' '], ['CREATE TABLE review_'.$table.' ', 'REFERENCES review_'.$table.'(', 'INSERT INTO review_'.$table.' '], $proposal);
    }
    $proposal = preg_replace('/\bCONSTRAINT ([a-z_]+)/', 'CONSTRAINT review_$1', $proposal);
    foreach (array_filter(array_map('trim', explode(';', $proposal))) as $statement) {
        $connection->executeStatement($statement);
    }
    foreach ($tables as $table) {
        $actual = $connection->fetchAssociative('SHOW CREATE TABLE '.$table)['Create Table'];
        if ('opening_catalogue_state' === $table) {
            // The approved initial DDL stays unchanged; 00410 adds only the rollback hold.
            $assert(str_contains($actual, '`automatic_updates_held_at` datetime(6) DEFAULT NULL'), 'Rollback hold additive migration missing.');
            $actual = preg_replace('/^  `automatic_updates_held_at`[^\n]+\n/m', '', $actual);
        }
        $expected = $connection->fetchAssociative('SHOW CREATE TABLE review_'.$table)['Create Table'];
        $assert($actual === str_replace('review_', '', $expected), 'Migration differs from approved SQL: '.$table);
    }
    foreach (array_reverse($tables) as $table) {
        $connection->executeStatement('DROP TABLE review_'.$table);
    }
    $cli(['app:schema:migrate']);
    $assert($schemaVersionCount === (int) $connection->fetchOne('SELECT COUNT(*) FROM doctrine_schema_versions'), 'Schema rerun was not a no-op.');
    $cli(['app:lessons:migrate'], false);
    $assert(!$connection->createSchemaManager()->tablesExist(['doctrine_lesson_versions']), 'Rejected lesson migration created history.');

    $config = Yaml::parseFile('/app/backend/config/packages/doctrine_migrations.yaml')['doctrine_migrations'];
    $paths = array_map(static fn (string $path): string => str_replace('%kernel.project_dir%', $layout, $path), $config['migrations_paths']);
    $doctrineConfig = ['migrations_paths' => $paths, 'table_storage' => $config['storage']['table_storage'], 'all_or_nothing' => $config['all_or_nothing'], 'transactional' => $config['transactional']];
    file_put_contents($layout.'/schema.php', '<?php return '.var_export($doctrineConfig, true).';');
    $schema = DependencyFactory::fromConnection(new ConfigurationArray($doctrineConfig), new ExistingConnection($connection));
    $assert($schemaVersionCount === count($schema->getMigrationRepository()->getMigrations()), 'Schema discovery leaked the adjacent lesson migration.');
    $lessonFactory = new LessonMigrationFactory($connection, $layout);
    $lesson = $lessonFactory->create();
    $assert(1 === count($lesson->getMigrationRepository()->getMigrations()), 'Lesson discovery did not find the fixture.');

    $before = glob($layout.'/migrations/Schema/*.php');
    $diff = $cli(['doctrine:migrations:diff', '--allow-empty-diff', '--configuration='.$layout.'/schema.php']);
    $assert(str_contains($diff, 'No changes detected'), 'Unmapped catalogue tables appeared in the ORM diff.');
    $assert($before === glob($layout.'/migrations/Schema/*.php'), 'Empty diff generated a destructive migration.');
    $cli(['doctrine:migrations:diff', '--filter-expression=.*', '--configuration='.$layout.'/schema.php'], false);
    $connection->executeStatement('CREATE TABLE migration_test_diff_probe (id INT PRIMARY KEY)');
    $cli(['doctrine:migrations:diff', '--configuration='.$layout.'/schema.php']);
    $generated = array_values(array_diff(glob($layout.'/migrations/Schema/*.php'), $before));
    $assert(1 === count($generated), 'Filter also hid an ordinary ORM diff table.');
    $generatedSql = file_get_contents($generated[0]);
    $assert(str_contains($generatedSql, 'public function isTransactional(): bool') && str_contains($generatedSql, 'return false;'), 'Generated MariaDB DDL incorrectly claims to be transactional.');
    $assert(str_contains($generatedSql, 'DROP TABLE migration_test_diff_probe'), 'Expected unprotected probe-table diff.');
    $assert(!preg_match('/DROP TABLE (?:chess_|opening_|doctrine_)/', $generatedSql), 'Generated diff drops protected data.');
    unlink($generated[0]);
    $connection->executeStatement('DROP TABLE migration_test_diff_probe');

    $lock = new CatalogueOperationLock($connection);
    $otherConnection = DriverManager::getConnection($params);
    $otherLock = new CatalogueOperationLock($otherConnection);
    $lock->run(static function () use ($otherLock, $expectException): void {
        $expectException(static fn () => $otherLock->run(static fn () => null), 'holds the database lock');
    });
    $waitStart = hrtime(true);
    $lock->run(static fn () => $expectException(static fn () => $otherLock->run(static fn () => null, 1), 'holds the database lock'));
    $assert((hrtime(true) - $waitStart) / 1e9 >= 0.9, 'Bounded lock wait did not wait for the requested timeout.');
    $lock->run(static function () use ($lock, $connection, $assert, $otherLock, $expectException): void {
        $lock->run(static fn () => $lock->assertHeld());
        $lockName = 'chess.catalogue.'.substr(hash('sha256', (string) $connection->getDatabase()), 0, 40);
        $assert(1 === (int) $connection->fetchOne('SELECT RELEASE_LOCK(?)', [$lockName]), 'Nested operation reacquired the advisory lock instead of sharing ownership.');
        $otherLock->run(static fn () => $expectException(static fn () => $lock->assertHeld(), 'ownership was lost'));
        $expectException(static fn () => $lock->run(static fn () => null), 'ownership was lost');
    });
    $assert('released' === $otherLock->run(static fn () => 'released'), 'Lock was not released after success.');
    $expectException(static fn () => $lock->run(static fn () => throw new RuntimeException('fixture failure')), 'fixture failure');
    $assert('released' === $otherLock->run(static fn () => 'released'), 'Lock was not released after failure.');
    $expectException(static fn () => $lock->run(static function () use ($connection): void {
        $connection->close();
        throw new RuntimeException('original error after connection loss');
    }), 'original error after connection loss');
    $assert('released' === $otherLock->run(static fn () => 'released'), 'Lost DB connection kept a catalogue lock.');

    // Minimal persistence fixture, not the still-unimplemented production source sync.
    $connection->executeStatement("INSERT INTO opening_import_batch (id,upstream_sha,content_checksum,importer_version,status,created_at) VALUES (1,REPEAT('a',40),REPEAT('b',64),'test-v1','active',UTC_TIMESTAMP(6))");
    $connection->executeStatement("INSERT INTO opening_import_file VALUES (1,'a.tsv',REPEAT('c',64),100,1)");
    $connection->executeStatement('UPDATE opening_catalogue_state SET active_batch_id=1,catalogue_revision=1 WHERE id=1');
    $connection->executeStatement('DELETE FROM doctrine_schema_versions WHERE version=?', ['AppMigrations\\Schema\\Version20261015000410']);
    $pendingLesson = new CommandTester(new MigrateLessonsCommand($connection, $lessonFactory, $lock, $schema));
    $expectException(static fn () => $pendingLesson->execute([]), 'pending schema migrations');
    $assert(!$connection->createSchemaManager()->tablesExist(['doctrine_lesson_versions']), 'Pending-schema guard initialized lesson history.');
    $connection->insert('doctrine_schema_versions', ['version' => 'AppMigrations\\Schema\\Version20261015000410']);
    $connection->executeStatement('CREATE TABLE migration_test_lesson_result (id INT PRIMARY KEY, move_id BIGINT UNSIGNED NOT NULL, FOREIGN KEY(move_id) REFERENCES chess_move(id)) ENGINE=InnoDB');
    $lessonCommand = new CommandTester(new MigrateLessonsCommand($connection, $lessonFactory, $lock, $schema));
    $expectException(static fn () => $lessonCommand->execute([]), 'requires the imported e2e4 edge');
    $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM doctrine_lesson_versions'), 'Failed lesson was recorded.');
    $connection->insert('chess_position', ['id' => 1, 'position_key' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -']);
    $connection->insert('chess_position', ['id' => 2, 'position_key' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -']);
    $connection->insert('chess_move', ['id' => 1, 'from_position_id' => 1, 'to_position_id' => 2, 'uci' => 'e2e4', 'san' => 'e4']);
    $connection->executeStatement("INSERT INTO opening_entry VALUES (1,1,REPEAT('d',64),'a.tsv',2,'A00','Fixture source','1. e4',2)");
    $connection->executeStatement('INSERT INTO opening_entry_step VALUES (1,1,1)');
    $lessonCommand = new CommandTester(new MigrateLessonsCommand($connection, $lessonFactory, $lock, $schema));
    $assert(0 === $lessonCommand->execute([]), 'Ordered schema/catalogue/lesson bootstrap failed.');
    $assert(1 === (int) $connection->fetchOne('SELECT move_id FROM migration_test_lesson_result WHERE id=1'), 'Lesson did not resolve the imported edge.');
    $assert(1 === (int) $connection->fetchOne('SELECT COUNT(*) FROM doctrine_lesson_versions'), 'Lesson metadata was not recorded separately.');
    $assert($schemaVersionCount === (int) $connection->fetchOne('SELECT COUNT(*) FROM doctrine_schema_versions'), 'Lesson migration changed schema history.');
    $lessonCommand = new CommandTester(new MigrateLessonsCommand($connection, $lessonFactory, $lock, $schema));
    $assert(0 === $lessonCommand->execute([]), 'Lesson retry failed.');
    $assert(1 === (int) $connection->fetchOne('SELECT COUNT(*) FROM migration_test_lesson_result'), 'Lesson retry duplicated data.');
    copy('/app/backend/tests/Fixtures/Migrations/Version20990101000001.php', $layout.'/migrations/LessonData/Version20990101000001.php');
    $failingLesson = new CommandTester(new MigrateLessonsCommand($connection, $lessonFactory, $lock, $schema));
    $expectException(static fn () => $failingLesson->execute([]), 'foreign key constraint fails');
    $assert(1 === (int) $connection->fetchOne('SELECT COUNT(*) FROM migration_test_lesson_result'), 'Failed lesson left a partial write.');
    $assert(1 === (int) $connection->fetchOne('SELECT COUNT(*) FROM doctrine_lesson_versions'), 'Failed lesson advanced history.');
    $assert('released' === $otherLock->run(static fn () => 'released'), 'Failed lesson did not release the operation lock.');
    $cli(['app:schema:migrate']);
    $assert(1 === (int) $connection->fetchOne('SELECT COUNT(*) FROM migration_test_lesson_result'), 'Schema command changed lesson data.');
    $cli(['doctrine:migrations:execute', '--down', 'AppMigrations\\Schema\\Version20261015000100'], false);
    $expectException(static fn () => (new AppMigrations\Schema\Version20261015000100($connection, new Psr\Log\NullLogger()))->down(new Doctrine\DBAL\Schema\Schema()), 'Shared chess facts and catalogue history must be retained');
    $assert(1 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_entry'), 'Schema down destroyed source records.');
    printf("Fresh MariaDB migration checks passed (%d): approved DDL, separate discovery/history, bootstrap order/retry, transaction rollback, shared lock, safe ORM diff and irreversible schema.\n", $checks);
} finally {
    $connection->close();
    foreach (glob($layout.'/migrations/*/*.php') as $file) {
        unlink($file);
    }
    foreach (glob($layout.'/*.php') as $file) {
        unlink($file);
    }
    rmdir($layout.'/migrations/Schema');
    rmdir($layout.'/migrations/LessonData');
    rmdir($layout.'/migrations');
    rmdir($layout);
}
