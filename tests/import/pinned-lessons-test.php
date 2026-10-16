<?php

declare(strict_types=1);

use App\Service\OpeningImport\CatalogueSynchronizer;
use App\Service\OpeningImport\CatalogueReader;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

require '/app/backend/vendor/autoload.php';

$database = getenv('MIGRATION_TEST_DATABASE');
if (!is_string($database) || !preg_match('/\Achess_migration_test_[0-9_]+\z/', $database)) {
    throw new RuntimeException('Only a disposable test database is allowed.');
}
$url = preg_replace('~/[^/?]*(?=\?|$)~', '/'.$database, (string) getenv('DATABASE_URL'));
if (!is_string($url)) {
    throw new RuntimeException('Cannot select the disposable database.');
}
$environment = array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']);
$connection = DriverManager::getConnection((new DsnParser(['mysql' => 'pdo_mysql']))->parse($url));
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
};
$prepare = static function () use ($environment, $assert): void {
    // Inherit output streams so a migration failure remains visible without buffering large output.
    $process = proc_open([PHP_BINARY, 'bin/console', 'app:database:prepare', '--no-interaction', '--no-ansi'], [0 => ['file', '/dev/null', 'r'], 1 => STDOUT, 2 => STDERR], $pipes, '/app/backend', $environment);
    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start database preparation.');
    }
    $assert(0 === proc_close($process), 'Fresh bootstrap against the complete pinned catalogue failed.');
};

try {
    $prepare();
    $state = $connection->fetchAssociative('SELECT b.id,b.upstream_sha,b.content_checksum,s.catalogue_revision FROM opening_catalogue_state s JOIN opening_import_batch b ON b.id=s.active_batch_id WHERE s.id=1');
    $assert(false !== $state && CatalogueSynchronizer::BOOTSTRAP_SHA === $state['upstream_sha'], 'Bootstrap did not import the immutable baseline.');
    $assert('32b6eadeecd0a9620100f19613b55595e96e5cb72d1addd8cbd49fa9f7b009e3' === $state['content_checksum'], 'The complete source checksum differs from the approved baseline.');
    $assert(3865 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_entry WHERE batch_id=?', [$state['id']]), 'The complete source catalogue was replaced by a reduced fixture.');
    $assert(5 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_import_file WHERE batch_id=?', [$state['id']]), 'Bootstrap did not preserve all five source files.');
    $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM lesson l LEFT JOIN lesson_revision r ON r.lesson_id=l.id AND r.revision=l.current_revision WHERE l.current_revision IS NULL OR r.lesson_id IS NULL'), 'A lesson has no published revision.');
    $assert((int) $connection->fetchOne('SELECT COUNT(*) FROM lesson') >= 2, 'Bootstrap did not publish the initial reviewed courses.');
    $expectedMigrations = count(glob('/app/backend/migrations/LessonData/Version*.php') ?: []);
    $assert($expectedMigrations === (int) $connection->fetchOne('SELECT COUNT(*) FROM doctrine_lesson_versions'), 'Bootstrap skipped a committed lesson migration.');

    $page = (new CatalogueReader($connection))->search('London');
    $assert(count($page['items']) > 0, 'The complete catalogue cannot find London variations.');
    foreach ($page['items'] as $item) {
        $row = $connection->fetchAssociative('SELECT source_uid,eco,full_name FROM opening_entry WHERE id=? AND batch_id=?', [$item['id'], $state['id']]);
        $assert(false !== $row && $row['source_uid'] === $item['sourceId'] && $row['eco'] === $item['eco'] && $row['full_name'] === $item['name'], 'Grouped catalogue metadata combines identities from different source records.');
    }

    $before = [
        'state' => $state,
        'lessons' => $connection->fetchAllAssociative('SELECT * FROM lesson ORDER BY id'),
        'steps' => $connection->fetchAllAssociative('SELECT * FROM lesson_line_step ORDER BY lesson_id,revision,line_id,ply'),
    ];
    $prepare();
    $assert(1 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_import_batch'), 'A repeated preparation downloaded or imported another catalogue.');
    $assert($before['lessons'] === $connection->fetchAllAssociative('SELECT * FROM lesson ORDER BY id'), 'A repeated preparation changed the curriculum.');
    $assert($before['steps'] === $connection->fetchAllAssociative('SELECT * FROM lesson_line_step ORDER BY lesson_id,revision,line_id,ply'), 'A repeated preparation changed authored paths.');
    $assert((int) $before['state']['catalogue_revision'] === (int) $connection->fetchOne('SELECT catalogue_revision FROM opening_catalogue_state WHERE id=1'), 'A repeated preparation changed the catalogue revision.');
    printf("Pinned-source lesson bootstrap passed (%d checks): complete immutable PHP catalogue, all schema/lesson migrations, published revisions and idempotent repeat preparation.\n", $checks);
} finally {
    $connection->close();
}
