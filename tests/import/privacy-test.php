<?php

declare(strict_types=1);

use App\Command\PrivacyCleanupCommand;
use App\Kernel;
use App\Service\Privacy\PrivacyCleanup;
use App\Service\Privacy\PrivacyConfiguration;
use App\Service\Privacy\RetentionFiles;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require '/app/backend/vendor/autoload.php';
$database = getenv('MIGRATION_TEST_DATABASE');
if (!is_string($database) || !preg_match('/\Achess_migration_test_[0-9_]+\z/', $database)) throw new RuntimeException('Disposable database required.');
$url = preg_replace('~/[^/?]*(?=\?|$)~', '/'.$database, (string) getenv('DATABASE_URL'));
putenv('DATABASE_URL='.$url);
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $url;
(new Dotenv())->bootEnv('/app/backend/.env');
$db = DriverManager::getConnection((new DsnParser(['mysql' => 'pdo_mysql']))->parse($url));
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void {
    if (!$ok) throw new RuntimeException($message);
    ++$checks;
};
$root = sys_get_temp_dir().'/chess-privacy-integration-'.bin2hex(random_bytes(6));
mkdir($root.'/public', 0700, true);
try {
    $process = proc_open([PHP_BINARY, 'bin/console', 'app:database:prepare', '--source-directory=/app/backend/tests/Fixtures/Lessons/source', '--no-interaction'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/app/backend', array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']));
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $assert(0 === proc_close($process), 'Fresh bootstrap failed: '.$output);
    $source = $db->fetchAllAssociative('SELECT * FROM opening_catalogue_state');
    $curriculum = $db->fetchAllAssociative('SELECT * FROM lesson_revision ORDER BY lesson_id,revision');
    foreach ([1 => '2020-01-02 00:00:00', 2 => null] as $id => $verified) {
        $db->insert('user_account', ['id' => $id, 'email' => 'private'.$id.'@example.test', 'password' => 'private-password-hash', 'created_at' => '2020-01-01 00:00:00', 'verified_at' => $verified]);
    }
    $line = $db->fetchAssociative('SELECT lesson_id,revision,id FROM lesson_line LIMIT 1');
    $db->insert('user_line_completion', ['user_id' => 1, 'attempt_id' => 'f0000000-0000-4000-8000-000000000001', 'lesson_id' => $line['lesson_id'], 'revision' => $line['revision'], 'line_id' => $line['id'], 'playing_color' => 'w', 'hint_count' => 0, 'completed_at' => '2026-10-15 10:00:00']);
    $accounts = $db->fetchAllAssociative('SELECT * FROM user_account ORDER BY id');
    $progress = $db->fetchAllAssociative('SELECT * FROM user_line_completion');
    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    foreach ([-3600, 0, 3600] as $i => $offset) {
        $db->insert('account_email_token', ['token_hash' => hash('sha256', 'private token '.$i), 'user_id' => 1, 'purpose' => 'reset', 'created_at' => $now->modify('-2 hours')->format('Y-m-d H:i:s'), 'expires_at' => $now->modify(sprintf('%+d seconds', $offset))->format('Y-m-d H:i:s')]);
        $db->insert('account_request_limit', ['bucket_hash' => hash('sha256', 'private bucket '.$i), 'attempts' => 1, 'expires_at' => $now->getTimestamp() + $offset]);
    }
    $cleanup = new PrivacyCleanup($db);
    $dry = $cleanup->run(false, 1, $now);
    foreach (['emailTokens', 'requestLimits'] as $category) {
        $assert(['eligible' => 2, 'removed' => 0] === $dry[$category], 'Dry run changed data or missed the exact expiry boundary.');
    }
    $assert(3 === (int) $db->fetchOne('SELECT COUNT(*) FROM account_email_token') && 3 === (int) $db->fetchOne('SELECT COUNT(*) FROM account_request_limit'), 'Dry run deleted private records.');
    $first = $cleanup->run(true, 1, $now);
    foreach (['emailTokens', 'requestLimits'] as $category) $assert(1 === $first[$category]['removed'], 'Per-category removal limit was not enforced.');
    $second = $cleanup->run(true, 1000, $now);
    foreach (['emailTokens', 'requestLimits'] as $category) $assert(1 === $second[$category]['removed'], 'Remaining expired data was not drained.');
    $assert(1 === (int) $db->fetchOne('SELECT COUNT(*) FROM account_email_token') && 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM account_request_limit'), 'Live email link or request limit was deleted.');
    $assert(hash('sha256', 'private token 2') === $db->fetchOne('SELECT token_hash FROM account_email_token'), 'Wrong token survived.');
    $assert(hash('sha256', 'private bucket 2') === $db->fetchOne('SELECT bucket_hash FROM account_request_limit'), 'Wrong request limit survived.');
    $again = $cleanup->run(true, 1000, $now);
    foreach ($again as $counts) $assert(['eligible' => 0, 'removed' => 0] === $counts, 'Cleanup was not idempotent or auto-purged an account.');

    // Command applies only to disposable files and this disposable DB, never shared session/log paths.
    mkdir($root.'/var/sessions/test', 0700, true);
    mkdir($root.'/logs', 0700);
    mkdir($root.'/backups', 0700);
    $files = ['var/sessions/test/sess_disposable', 'logs/prod-2026-01-01.log', 'backups/20260101-120000-123456-aabbccdd.sql'];
    foreach ($files as $file) { file_put_contents($root.'/'.$file, 'private disposable data'); touch($root.'/'.$file, time() - 40 * 86400); }
    $configuration = new PrivacyConfiguration('StarbugStone', 'chessopenings@starbugstone.com', 'O2Switch', 'test');
    $command = new CommandTester(new PrivacyCleanupCommand($cleanup, $configuration, new RetentionFiles(), $root, $root.'/logs', 'test', $root.'/backups'));
    $assert(0 === $command->execute([]), 'Default command dry run failed.');
    $report = json_decode($command->getDisplay(), true, flags: JSON_THROW_ON_ERROR);
    $assert(true === $report['dryRun'] && true === $report['deploymentBackupsConfigured'], 'Dry run or explicit backup configuration was misreported.');
    foreach ($files as $file) $assert(file_exists($root.'/'.$file), 'Default command deleted a file.');
    $assert(0 === $command->execute(['--apply' => true]), 'Explicit bounded cleanup command failed.');
    foreach ($files as $file) $assert(!file_exists($root.'/'.$file), 'Expired disposable file was retained.');
    foreach ([$root, 'private1@example.test', 'private-password-hash', 'private bucket', 'sess_disposable'] as $private) $assert(!str_contains($command->getDisplay(), $private), 'Cleanup report exposed private data.');
    $assert($accounts === $db->fetchAllAssociative('SELECT * FROM user_account ORDER BY id'), 'Cleanup changed verified or unverified accounts.');
    $assert($progress === $db->fetchAllAssociative('SELECT * FROM user_line_completion'), 'Cleanup changed practice history.');
    $assert($source === $db->fetchAllAssociative('SELECT * FROM opening_catalogue_state'), 'Cleanup changed the imported catalogue.');
    $assert($curriculum === $db->fetchAllAssociative('SELECT * FROM lesson_revision ORDER BY lesson_id,revision'), 'Cleanup changed curriculum.');

    $browser = new KernelBrowser(new Kernel('test', true));
    $browser->request('GET', '/api/privacy');
    $response = $browser->getResponse();
    $notice = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    $assert(200 === $response->getStatusCode() && true === $notice['configured'], 'Public notice requires sign-in or is not configured.');
    $assert('StarbugStone' === $notice['publisher'] && 'chessopenings@starbugstone.com' === $notice['contactEmail'] && 'O2Switch' === $notice['emailProvider'], 'Public identity differs from owner-approved details.');
    $assert(30 === $notice['hostingBackupDays'] && 30 === $notice['deploymentBackupDays'] && 14 === $notice['applicationLogDays'] && 0 === $notice['unverifiedAccountDays'], 'Retention differs from chosen policy.');
    $assert([] === $response->headers->getCookies(), 'Public privacy notice created an unnecessary session.');
    $assert($response->headers->hasCacheControlDirective('public') && 'nosniff' === $response->headers->get('X-Content-Type-Options'), 'Public notice headers missing.');
    foreach (['DATABASE_URL', 'MAILER_DSN', 'APP_SECRET', 'password', 'private1@example.test'] as $private) $assert(!str_contains($response->getContent(), $private), 'Public notice leaked configuration or identity.');
    $browser->request('POST', '/api/privacy');
    $assert(405 === $browser->getResponse()->getStatusCode(), 'Privacy endpoint accepts mutation.');
    echo "Privacy retention integration: $checks checks passed.\n";
} finally {
    $db->close();
    (new Filesystem())->remove($root);
}
