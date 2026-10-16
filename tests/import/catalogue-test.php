<?php

declare(strict_types=1);

use App\Kernel;
use App\Service\Migrations\CatalogueOperationLock;
use App\Service\OpeningImport\CatalogueMaintenance;
use App\Service\OpeningImport\CataloguePromoter;
use App\Service\OpeningImport\CatalogueReader;
use App\Service\OpeningImport\CatalogueStager;
use App\Service\OpeningImport\CatalogueSynchronizer;
use App\Service\OpeningImport\OpeningGraphBuilder;
use App\Service\OpeningImport\OpeningSourceFetcher;
use App\Service\OpeningImport\OpeningSourceLoader;
use App\Service\OpeningImport\PChessMoveReplayer;
use App\Service\OpeningImport\PgnMovetextParser;
use App\Service\OpeningImport\TsvOpeningParser;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

require '/app/backend/vendor/autoload.php';
$database = getenv('MIGRATION_TEST_DATABASE');
if (!is_string($database) || !preg_match('/\Achess_migration_test_[0-9_]+\z/', $database)) {
    throw new RuntimeException('Only a disposable test database is allowed.');
}
$url = preg_replace('~/[^/?]*(?=\?|$)~', '/'.$database, (string) getenv('DATABASE_URL'));
$environment = array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']);
$params = (new DsnParser(['mysql' => 'pdo_mysql']))->parse($url);
$connection = DriverManager::getConnection($params);
$fixtures = '/app/backend/tests/Fixtures/Openings/source';
$sha = CatalogueSynchronizer::BOOTSTRAP_SHA;
$workspace = sys_get_temp_dir().'/catalogue-test-'.bin2hex(random_bytes(8));
mkdir($workspace, 0700);
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
        throw new RuntimeException('Cannot start Console.');
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
        $assert(str_contains($error->getMessage(), $message), 'Unexpected failure: '.$error->getMessage());

        return;
    }
    throw new RuntimeException('Expected failure: '.$message);
};
$makeSync = static function (Doctrine\DBAL\Connection $db, ?MockHttpClient $http = null, ?Psr\Log\LoggerInterface $logger = null): CatalogueSynchronizer {
    $logger ??= new NullLogger();
    $lock = new CatalogueOperationLock($db);
    $builder = new OpeningGraphBuilder(new TsvOpeningParser(), new PgnMovetextParser(), new PChessMoveReplayer());
    $loader = new OpeningSourceLoader(new OpeningSourceFetcher($http ?? HttpClient::create()), $builder);
    $stager = new CatalogueStager($db, $lock);
    $promoter = new CataloguePromoter($db, $stager, $lock);
    $maintenance = new CatalogueMaintenance($db, $lock, $promoter, $logger);

    return new CatalogueSynchronizer($db, $lock, $loader, $stager, $promoter, $maintenance, $logger);
};
$copy = static function (string $name) use ($workspace, $fixtures): string {
    $directory = $workspace.'/'.$name;
    mkdir($directory, 0700);
    foreach (glob($fixtures.'/*.tsv') as $file) {
        copy($file, $directory.'/'.basename($file));
    }

    return $directory;
};
$rename = static function (string $directory, string $name): void {
    $path = $directory.'/a.tsv';
    $file = fopen($path, 'rb');
    $rows = [];
    while (false !== $row = fgetcsv($file, null, "\t", '"', '')) {
        $rows[] = $row;
    }
    fclose($file);
    $rows[1][1] = $name;
    $file = fopen($path, 'wb');
    foreach ($rows as $row) {
        fputcsv($file, $row, "\t", '"', '');
    }
    fclose($file);
};
$kernel = null;
try {
    $cli(['app:schema:migrate']);
    $before = $connection->fetchAssociative('SELECT * FROM opening_catalogue_state');
    $dry = json_decode($cli(['app:openings:sync', '--source-ref='.$sha, '--source-directory='.$fixtures, '--dry-run']), true, flags: JSON_THROW_ON_ERROR);
    $assert('dry-run' === $dry['result'] && 10 === $dry['entries'], 'Dry-run did not fully validate source.');
    $assert($before === $connection->fetchAssociative('SELECT * FROM opening_catalogue_state'), 'Dry-run changed state/status timestamps.');
    $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_import_batch'), 'Dry-run persisted a batch.');
    $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM chess_position'), 'Dry-run persisted chess facts.');
    $cli(['app:database:prepare', '--source-directory='.$workspace.'/missing'], false);
    $assert(!$connection->createSchemaManager()->tablesExist(['doctrine_lesson_versions']), 'Failed bootstrap continued to lesson migrations.');
    // The small importer spike fixtures omit the complete taught paths: source activates, lesson authoring must fail safely.
    $cli(['app:database:prepare', '--source-directory='.$fixtures], false);
    $assert(10 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_entry'), 'Bootstrap did not import pinned PHP fixtures.');
    $assert(81 === (int) $connection->fetchOne('SELECT COUNT(*) FROM chess_position'), 'Graph node count differs from spike.');
    $assert(80 === (int) $connection->fetchOne('SELECT COUNT(*) FROM chess_move'), 'Graph edge count differs from spike.');
    $assert(93 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_entry_step'), 'Source paths lost steps.');
    $firstBatch = (int) $connection->fetchOne('SELECT active_batch_id FROM opening_catalogue_state');
    $assert($firstBatch > 0 && 1 === (int) $connection->fetchOne('SELECT catalogue_revision FROM opening_catalogue_state'), 'First activation revision must be one.');
    $assert($connection->createSchemaManager()->tablesExist(['doctrine_lesson_versions']), 'Bootstrap did not reach lesson phase.');
    $cli(['app:database:prepare', '--source-directory='.$workspace.'/does-not-exist'], false);
    $assert(1 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_import_batch'), 'Existing deployment catalogue was refreshed.');
    $statusOutput = json_decode($cli(['app:openings:status']), true, flags: JSON_THROW_ON_ERROR);
    $assert(10 === $statusOutput['catalogue']['entryCount'], 'Status command omitted catalogue.');
    $sync = $makeSync($connection);
    $reader = new CatalogueReader($connection);
    $unchanged = $sync->sync(str_repeat('b', 40), $fixtures);
    $assert('unchanged' === $unchanged['result'] && 1 === $unchanged['catalogueRevision'], 'Same content at a changed SHA must be a no-op.');
    $assert($sha === $reader->status()['sourceSha'], 'No-op relabelled immutable snapshot provenance.');
    $assert(1 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_import_batch'), 'No-op duplicated a snapshot.');

    $connection->executeStatement('CREATE TABLE sync_test_lesson (id INT PRIMARY KEY, batch_id BIGINT UNSIGNED NOT NULL, move_id BIGINT UNSIGNED NOT NULL, FOREIGN KEY(batch_id) REFERENCES opening_import_batch(id), FOREIGN KEY(move_id) REFERENCES chess_move(id)) ENGINE=InnoDB');
    $connection->executeStatement('CREATE TABLE sync_test_progress (id INT PRIMARY KEY, lesson_id INT NOT NULL, completions INT NOT NULL, FOREIGN KEY(lesson_id) REFERENCES sync_test_lesson(id)) ENGINE=InnoDB');
    $edge = $connection->fetchOne("SELECT id FROM chess_move WHERE uci='e2e4' ORDER BY id LIMIT 1");
    $connection->insert('sync_test_lesson', ['id' => 1, 'batch_id' => $firstBatch, 'move_id' => $edge]);
    $connection->insert('sync_test_progress', ['id' => 1, 'lesson_id' => 1, 'completions' => 7]);
    $privateBefore = $connection->fetchAllAssociative('SELECT * FROM sync_test_progress');
    $lessonBefore = $connection->fetchAllAssociative('SELECT * FROM sync_test_lesson');
    $changed = $copy('renamed');
    $rename($changed, 'Amar Opening: revised source label');
    $expectException(static fn () => $sync->sync($sha, $changed), 'thresholds exceeded');
    $assert(1 === $reader->status()['catalogueRevision'], 'Safeguard failure changed active revision.');
    $forcedDry = $sync->sync($sha, $changed, true, true);
    $assert(1 === $forcedDry['diff']['removed'] && 1 === $forcedDry['diff']['added'] && $forcedDry['forceApplied'], 'Rename/drop preview is not explicit.');
    $assert(1 === count($forcedDry['diff']['renameCandidates']), 'Rename review candidate missing.');
    $reordered = $copy('reordered');
    $lines = file($reordered.'/a.tsv');
    file_put_contents($reordered.'/a.tsv', $lines[0].$lines[2].$lines[1]);
    $assert('unchanged' === $sync->sync($sha, $reordered)['result'], 'Source row order incorrectly changed semantic identity.');
    $reduced = $copy('reduced');
    file_put_contents($reduced.'/a.tsv', $lines[0].$lines[1]);
    $drop = $sync->sync($sha, $reduced, true, true);
    $assert(str_contains(implode(';', $drop['diff']['safeguards']), 'a.tsv entry count'), 'Per-family safety threshold was not reported.');
    $updated = $sync->sync($sha, $changed, false, true);
    $assert(2 === $updated['catalogueRevision'], 'Changed source was not activated.');
    $assert(1 === $reader->search('Amar Opening: revised')['total'], 'Active source name did not change.');
    $assert(1 === $reader->search('Amar Opening', 'A', 2, 20)['total'], 'Unexpected search result count.');

    $new = $copy('added');
    $rename($new, 'Amar Opening: revised source label');
    file_put_contents($new.'/a.tsv', "A00\tAdditional opening\t1. e3\n", FILE_APPEND);
    $connection->executeStatement("CREATE TRIGGER sync_test_bad_fact BEFORE INSERT ON chess_move FOR EACH ROW SET NEW.to_position_id=IF(NEW.uci='e2e3',NEW.from_position_id,NEW.to_position_id)");
    $expectException(static fn () => $sync->sync($sha, $new), 'conflicts with replayed');
    $assert(2 === $reader->status()['catalogueRevision'], 'Fact conflict changed active source.');
    $connection->executeStatement('DROP TRIGGER sync_test_bad_fact');
    $connection->executeStatement("DELETE FROM chess_move WHERE uci='e2e3'"); // remove only the test-injected corrupt fact
    $connection->executeStatement("CREATE TRIGGER sync_test_stage_fail BEFORE INSERT ON opening_entry_step FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture staging interruption'");
    $expectException(static fn () => $sync->sync($sha, $new), 'fixture staging interruption');
    $connection->executeStatement('DROP TRIGGER sync_test_stage_fail');
    $assert(2 === $reader->status()['catalogueRevision'] && 10 === $reader->status()['entryCount'], 'Partial staged snapshot became visible.');
    $connection->executeStatement("CREATE TRIGGER sync_test_activate_fail BEFORE UPDATE ON opening_catalogue_state FOR EACH ROW BEGIN IF NOT (NEW.active_batch_id <=> OLD.active_batch_id) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture activation interruption'; END IF; END");
    $expectException(static fn () => $sync->sync($sha, $new), 'fixture activation interruption');
    $connection->executeStatement('DROP TRIGGER sync_test_activate_fail');
    $assert(2 === $reader->status()['catalogueRevision'], 'Failed cutover committed a revision.');
    $assert('active' === $connection->fetchOne('SELECT status FROM opening_import_batch WHERE id=?', [$updated['batchId']]), 'Activation failure retired the prior source.');
    $networkSync = $makeSync($connection, new MockHttpClient(static fn () => new MockResponse('', ['http_code' => 503])));
    $expectException(static fn () => $networkSync->sync($sha), 'upstream HTTP 503');
    $assert(2 === $reader->status()['catalogueRevision'], 'Network failure changed revision.');
    $capturedLog = new class extends Psr\Log\AbstractLogger {
        public array $records = [];

        public function log($level, string|Stringable $message, array $context = []): void
        {
            $context['logLevel'] = $level;
            $this->records[] = $context;
        }
    };
    $diagnosticSync = $makeSync($connection, new MockHttpClient(static fn () => throw new RuntimeException('HTTP timeout https://operator:private-password@example.test?token=private-token Authorization: Bearer private-bearer')), $capturedLog);
    $expectException(static fn () => $diagnosticSync->sync($sha), 'HTTP timeout');
    $diagnostic = $capturedLog->records[0]['errorMessage'] ?? '';
    $assert(str_contains($diagnostic, 'HTTP timeout') && !str_contains($diagnostic, 'private-password') && !str_contains($diagnostic, 'private-token') && !str_contains($diagnostic, 'private-bearer'), 'Private structured logs omitted useful bounded diagnostics or exposed credentials.');
    $bad = $copy('bad');
    file_put_contents($bad.'/b.tsv', "eco\tname\tpgn\nB00\tIllegal\t1. e5\n");
    $expectException(static fn () => $sync->sync($sha, $bad, false, true), 'b.tsv:2: ply 1: illegal');
    $assert(2 === $reader->status()['catalogueRevision'], '--force bypassed illegal chess.');
    $missing = $copy('missing');
    unlink($missing.'/e.tsv');
    $expectException(static fn () => $sync->sync($sha, $missing, false, true), 'e.tsv: missing');
    $assert(2 === $reader->status()['catalogueRevision'], 'Missing source changed revision.');
    $unicode = $copy('unicode');
    file_put_contents($unicode.'/b.tsv', "eco\tname\tpgn\nB00\tMalformed\t1. ".str_repeat('x', 39)."é\n");
    $expectException(static fn () => $sync->sync($sha, $unicode), 'malformed SAN token');
    $failureState = $connection->fetchAssociative('SELECT last_check_status,last_error_summary FROM opening_catalogue_state');
    $assert('failed' === $failureState['last_check_status'] && mb_check_encoding($failureState['last_error_summary'], 'UTF-8'), 'Truncated Unicode diagnostics left status stuck at checking.');
    $other = DriverManager::getConnection($params);
    $otherLock = new CatalogueOperationLock($other);
    $stateBeforeLock = $connection->fetchAssociative('SELECT * FROM opening_catalogue_state');
    $otherLock->run(static fn () => $expectException(static fn () => $sync->sync($sha, $new), 'holds the database lock'));
    $assert($stateBeforeLock === $connection->fetchAssociative('SELECT * FROM opening_catalogue_state'), 'Competing sync changed another operation status.');
    $connectionBeforeDownload = $connection->fetchOne('SELECT CONNECTION_ID()');
    $downloadWithoutLock = $makeSync($connection, new MockHttpClient(static function (string $method, string $url) use ($otherLock, $changed, $connection): MockResponse {
        // Model an idle connection expiring while the source is being fetched/replayed.
        $connection->close();
        $otherLock->run(static fn () => null);

        return new MockResponse(file_get_contents($changed.'/'.basename($url)));
    }));
    $assert('unchanged' === $downloadWithoutLock->sync($sha)['result'], 'Source download unexpectedly held the database mutation lock.');
    $assert($connectionBeforeDownload !== $connection->fetchOne('SELECT CONNECTION_ID()'), 'Idle-connection replacement fixture did not reconnect before locking.');
    $result = $sync->sync($sha, $new);
    $assert(3 === $result['catalogueRevision'] && 11 === $reader->status()['entryCount'], 'Safe retry failed to activate complete snapshot.');

    // A request that began before activation stays on one snapshot even while another connection imports.
    $otherSync = $makeSync($other);
    $next = $copy('next');
    $rename($next, 'Amar Opening: source version three');
    file_put_contents($next.'/a.tsv', "A00\tAdditional opening\t1. e3\n", FILE_APPEND);
    $reader->snapshot(static function (array $snapshot) use ($connection, $otherSync, $sha, $next, $assert): void {
        $old = $connection->fetchAllAssociative('SELECT source_uid,full_name FROM opening_entry WHERE batch_id=? ORDER BY source_uid', [$snapshot['batchId']]);
        $otherSync->sync($sha, $next, false, true);
        $again = $connection->fetchAllAssociative('SELECT source_uid,full_name FROM opening_entry WHERE batch_id=? ORDER BY source_uid', [$snapshot['batchId']]);
        $assert($old === $again && 3 === $snapshot['catalogueRevision'], 'Reader mixed two activations.');
    });
    $assert(4 === $reader->status()['catalogueRevision'], 'New request did not see activated source.');

    // API is exercised through the real Symfony kernel against this disposable database.
    putenv('DATABASE_URL='.$url);
    $_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $url;
    foreach (['MAIL_FROM' => 'Chess Openings <no-reply@example.test>', 'DEFAULT_URI' => 'http://localhost', 'MAILER_DSN' => 'null://null'] as $key => $value) {
        putenv($key.'='.$value);
        $_SERVER[$key] = $_ENV[$key] = $value;
    }
    $kernel = new Kernel('test', true);
    $browser = new KernelBrowser($kernel);
    $browser->request('GET', '/api/openings/catalogue-status');
    $response = $browser->getResponse();
    $assert(200 === $response->getStatusCode(), 'Public status API failed.');
    $payload = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    $assert(4 === $payload['catalogueRevision'] && 11 === $payload['entryCount'], 'Status API revision/count mismatch.');
    $assert($response->headers->hasCacheControlDirective('public') && $response->headers->hasCacheControlDirective('must-revalidate'), 'Catalogue cache policy is unsafe.');
    $assert(!$response->headers->has('Set-Cookie') && !isset($payload['last_error_summary']), 'Public API exposed operational/session state.');
    $etag = $response->getEtag();
    $browser->request('GET', '/api/openings/catalogue-status', server: ['HTTP_IF_NONE_MATCH' => $etag]);
    $assert(304 === $browser->getResponse()->getStatusCode() && '' === $browser->getResponse()->getContent(), 'Conditional status response failed.');
    $browser->request('GET', '/api/openings?search=Amar&eco=A&limit=1&page=1');
    $list = json_decode($browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    $assert(1 === $list['total'] && is_string($list['items'][0]['id']) && 4 === $list['catalogueRevision'], 'Typed search response is wrong.');
    $searchEtag = $browser->getResponse()->getEtag();
    $browser->request('GET', '/api/openings?search=Amar&eco=A&limit=1&page=1', server: ['HTTP_IF_NONE_MATCH' => $searchEtag]);
    $assert(304 === $browser->getResponse()->getStatusCode(), 'Conditional search response failed.');
    $browser->request('GET', '/api/openings?search=%25');
    $assert(0 === json_decode($browser->getResponse()->getContent(), true)['total'], 'LIKE wildcard was not escaped.');
    foreach (['limit=51', 'page=0', 'page=1e2', 'search[]=bad', 'eco=F10', 'search=%00bad'] as $query) {
        $browser->request('GET', '/api/openings?'.$query);
        $assert(400 === $browser->getResponse()->getStatusCode(), 'Invalid query was accepted: '.$query);
    }
    $browser->request('POST', '/api/openings');
    $assert(405 === $browser->getResponse()->getStatusCode(), 'Public catalogue allowed mutation.');
    $browser->request('GET', '/api/openings?search=Opening');
    $interior = json_decode($browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    $assert($interior['total'] > 0 && false !== mb_stripos($interior['items'][0]['name'], 'Opening'), 'Search failed to match a word inside an opening name.');
    $rollback = json_decode($cli(['app:openings:rollback', (string) $firstBatch]), true, flags: JSON_THROW_ON_ERROR);
    $assert(5 === $rollback['catalogueRevision'] && $firstBatch === $rollback['batchId'], 'Rollback did not increment monotonic revision.');
    $browser->request('GET', '/api/openings/catalogue-status', server: ['HTTP_IF_NONE_MATCH' => $etag]);
    $assert(200 === $browser->getResponse()->getStatusCode() && $etag !== $browser->getResponse()->getEtag(), 'Rollback failed to invalidate cached status.');
    $assert(10 === $reader->status()['entryCount'], 'Rollback did not restore prior source labels.');
    $assert(null !== $connection->fetchOne('SELECT automatic_updates_held_at FROM opening_catalogue_state'), 'Rollback did not durably hold unattended activation.');
    // A different connection represents the next cron process: the hold must survive CLI exit.
    $capturedLog->records = [];
    $heldSync = $makeSync($other, null, $capturedLog);
    $heldResult = $heldSync->sync($sha, $next);
    $assert('held' === $heldResult['result'] && $heldResult['automaticUpdatesHeld'], 'Unattended rollback hold was reported as an import failure.');
    $heldState = $connection->fetchAssociative('SELECT last_check_status,last_error_summary FROM opening_catalogue_state');
    $assert('held' === $heldState['last_check_status'] && null === $heldState['last_error_summary'], 'Expected rollback hold left failure diagnostics.');
    $assert('notice' === ($capturedLog->records[0]['logLevel'] ?? null) && 'held' === ($capturedLog->records[0]['result'] ?? null), 'Expected rollback hold did not produce a distinct notice.');
    $heldCli = json_decode($cli(['app:openings:sync', '--source-ref='.$sha, '--source-directory='.$next]), true, flags: JSON_THROW_ON_ERROR);
    $assert('held' === $heldCli['result'], 'Held cron check did not exit successfully with a distinct result.');
    $assert(5 === $reader->status()['catalogueRevision'], 'Unattended sync silently undid rollback.');
    $heldPreview = $heldSync->sync($sha, $next, true, true);
    $assert($heldPreview['automaticUpdatesHeld'] && $heldPreview['forceApplied'], 'Dry-run omitted rollback hold or its force requirement.');
    $assert(null !== $connection->fetchOne('SELECT automatic_updates_held_at FROM opening_catalogue_state'), 'Dry-run force cleared the rollback hold.');
    $expectException(static fn () => $heldSync->sync($sha, $bad, false, true), 'illegal');
    $assert(null !== $connection->fetchOne('SELECT automatic_updates_held_at FROM opening_catalogue_state'), 'Failed forced import cleared rollback hold.');
    $assert('unchanged' === $heldSync->sync($sha, $fixtures)['result'], 'Unchanged restored source should remain a no-op during hold.');
    $assert(null !== $connection->fetchOne('SELECT automatic_updates_held_at FROM opening_catalogue_state'), 'Ordinary no-op cleared rollback hold.');
    $lostLock = new CatalogueOperationLock($connection);
    $lostStager = new CatalogueStager($connection, $lostLock);
    $lostPromoter = new CataloguePromoter($connection, $lostStager, $lostLock);
    $lostLock->run(static function () use ($connection, $lostPromoter, $firstBatch, $result, $expectException): void {
        $lockName = 'chess.catalogue.'.substr(hash('sha256', (string) $connection->getDatabase()), 0, 40);
        $connection->fetchOne('SELECT RELEASE_LOCK(?)', [$lockName]);
        $expectException(static fn () => $lostPromoter->activate($result['batchId'], $firstBatch), 'ownership was lost');
    });
    $assert(5 === $reader->status()['catalogueRevision'], 'Lost-lock promoter changed the active revision.');

    // A retained batch with balanced total counts still cannot pass wrong per-file counts.
    $connection->executeStatement("UPDATE opening_import_file SET row_count=row_count+IF(filename='a.tsv',1,-1) WHERE batch_id=? AND filename IN ('a.tsv','b.tsv')", [$result['batchId']]);
    $badRollback = $cli(['app:openings:rollback', (string) $result['batchId']], false);
    $assert(str_contains($badRollback, 'source-file counts'), 'Rollback failed for an unexpected reason.');
    $assert(5 === $reader->status()['catalogueRevision'], 'Rollback accepted mismatched per-file counts.');
    $connection->executeStatement("UPDATE opening_import_file SET row_count=row_count-IF(filename='a.tsv',1,-1) WHERE batch_id=? AND filename IN ('a.tsv','b.tsv')", [$result['batchId']]);
    $resumedNoOp = $heldSync->sync($sha, $fixtures, false, true);
    $assert('unchanged' === $resumedNoOp['result'] && 5 === $resumedNoOp['catalogueRevision'] && !$resumedNoOp['automaticUpdatesHeld'] && $resumedNoOp['forceApplied'], 'Explicit forced no-op did not resume updates while preserving the revision.');

    // Retention preserves active + two prior successful snapshots and FK-referenced older provenance.
    for ($version = 4; $version <= 7; ++$version) {
        $rename($next, 'Amar Opening: source version '.$version);
        $sync->sync($sha, $next, false, true);
    }
    $assert(null === $connection->fetchOne('SELECT automatic_updates_held_at FROM opening_catalogue_state'), 'Successful explicitly forced activation did not resume unattended updates.');
    $assert($privateBefore === $connection->fetchAllAssociative('SELECT * FROM sync_test_progress'), 'Source refresh changed private progress.');
    $assert($lessonBefore === $connection->fetchAllAssociative('SELECT * FROM sync_test_lesson'), 'Source refresh changed authored content.');
    $assert(10 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_entry WHERE batch_id=?', [$firstBatch]), 'Retention partially removed a referenced snapshot.');
    $assert(3 <= (int) $connection->fetchOne("SELECT COUNT(*) FROM opening_import_batch WHERE status IN ('active','retired')"), 'Retention discarded rollback history.');
    $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_import_batch WHERE id=?', [$updated['batchId']]), 'Old unreferenced successful snapshot was not pruned.');
    $connection->executeStatement("UPDATE opening_import_batch SET created_at=UTC_TIMESTAMP(6)-INTERVAL 8 DAY WHERE status='failed'");
    $connection->executeStatement("INSERT INTO opening_import_batch (upstream_sha,content_checksum,importer_version,status,created_at) VALUES (REPEAT('f',40),REPEAT('e',64),'test-orphan','staging',UTC_TIMESTAMP(6)-INTERVAL 9 DAY)");
    $orphan = (int) $connection->lastInsertId();
    $sync->sync($sha, $next);
    $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_import_batch WHERE id=?', [$orphan]), 'Expired interrupted-process staging batch was not cleaned.');
    $failedBefore = (int) $connection->fetchOne("SELECT COUNT(*) FROM opening_import_batch WHERE status='failed'");
    $sync->sync($sha, $next);
    $assert($failedBefore > (int) $connection->fetchOne("SELECT COUNT(*) FROM opening_import_batch WHERE status='failed'"), 'Expired failed staging snapshot was not cleaned.');
    $assert(0 === (int) $connection->fetchOne("SELECT COUNT(*) FROM opening_import_batch WHERE status='ready'"), 'Successful sync left an unactivated ready snapshot.');
    $brokenLogger = new class extends Psr\Log\AbstractLogger {
        public function log($level, string|Stringable $message, array $context = []): void
        {
            throw new RuntimeException('fixture log transport unavailable');
        }
    };
    $logFailureSync = $makeSync($connection, null, $brokenLogger);
    $rename($next, 'Amar Opening: source version eight');
    $logResult = $logFailureSync->sync($sha, $next, false, true);
    $assert('updated' === $logResult['result'] && isset($logResult['logWarning']), 'Logging failure hid a committed successful activation.');
    $assert('updated' === $connection->fetchOne('SELECT last_check_status FROM opening_catalogue_state'), 'Logging failure falsely marked a committed import as failed.');
    $expectException(static fn () => $logFailureSync->sync($sha, $bad, false, true), 'b.tsv:2: ply 1: illegal');
    $connection->executeStatement("INSERT INTO opening_import_batch (upstream_sha,content_checksum,importer_version,status,created_at) VALUES (REPEAT('f',40),REPEAT('e',64),'test-failed-cleanup','staging',UTC_TIMESTAMP(6)-INTERVAL 10 DAY)");
    $failedCleanup = (int) $connection->lastInsertId();
    $expectException(static fn () => $sync->sync($sha, $bad), 'illegal');
    $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM opening_import_batch WHERE id=?', [$failedCleanup]), 'Repeated validation failures prevented expired snapshot cleanup.');
    printf("Catalogue integration passed (%d checks): bootstrap, PHP staging, no-op/dry-run, failures, safeguards, lock, atomic reads/rollback, API/ETags and preserved lesson/progress provenance.\n", $checks);
} finally {
    $kernel?->shutdown();
    $connection->close();
    foreach (glob($workspace.'/*/*.tsv') as $file) {
        unlink($file);
    }
    foreach (glob($workspace.'/*') as $directory) {
        rmdir($directory);
    }
    rmdir($workspace);
}
