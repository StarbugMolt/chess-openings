<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Dotenv\Dotenv;

require '/app/backend/vendor/autoload.php';
$database = getenv('MIGRATION_TEST_DATABASE');
if (!is_string($database) || !preg_match('/\Achess_migration_test_[0-9_]+\z/', $database)) {
    throw new RuntimeException('Disposable database required.');
}
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
$client = static fn (): KernelBrowser => new KernelBrowser(new Kernel('test', true));
$get = static function (KernelBrowser $browser, string $path): array {
    $browser->request('GET', $path);
    return json_decode($browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
};
$csrf = static fn (KernelBrowser $browser): string => $get($browser, '/api/auth/csrf')['csrfToken'];
$send = static function (KernelBrowser $browser, string $method, string $path, array $body, ?string $token = null) use ($csrf): array {
    $browser->request($method, $path, server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $token ?? $csrf($browser)], content: json_encode((object) $body, JSON_THROW_ON_ERROR));
    return json_decode($browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
};
$status = static fn (KernelBrowser $browser): int => $browser->getResponse()->getStatusCode();
$private = static fn (KernelBrowser $browser): bool => $browser->getResponse()->headers->hasCacheControlDirective('no-store') && $browser->getResponse()->headers->hasCacheControlDirective('private') && 'no-referrer' === $browser->getResponse()->headers->get('Referrer-Policy');
$password = 'Admin area! with spaces 2026';
$login = static function (KernelBrowser $browser, string $email, bool $remember = false, ?string $secret = null) use ($send, $assert, $password): array {
    $body = $send($browser, 'POST', '/api/auth/login', ['email' => $email, 'password' => $secret ?? $password, 'rememberMe' => $remember]);
    $assert(200 === $browser->getResponse()->getStatusCode(), 'Test account could not sign in: '.$email);
    return $body;
};
$console = static function (array $arguments) use ($url): array {
    $process = proc_open([PHP_BINARY, 'bin/console', ...$arguments, '--no-interaction'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/app/backend', array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']));
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    return [proc_close($process), $output];
};
$recent = static fn (mixed $value): bool => is_string($value) && abs(strtotime($value.' UTC') - time()) < 120;
$at = static fn (int $secondsAgo): string => gmdate('Y-m-d H:i:s', time() - $secondsAgo);
$hash = password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 1024, 'time_cost' => 3]);

try {
    [$code, $output] = $console(['app:database:prepare', '--source-directory=/app/backend/tests/Fixtures/Lessons/source']);
    $assert(0 === $code, 'Fresh bootstrap failed: '.$output);
    $assert(1 === (int) $db->fetchOne('SELECT COUNT(*) FROM doctrine_schema_versions WHERE version = ?', ['AppMigrations\\Schema\\Version20261016000440']), 'Admin schema migration was not recorded.');
    require_once '/app/backend/migrations/Schema/Version20261016000440.php';
    try {
        (new AppMigrations\Schema\Version20261016000440($db, new Psr\Log\NullLogger()))->down(new Doctrine\DBAL\Schema\Schema());
        $assert(false, 'Admin migration down() was reversible.');
    } catch (Doctrine\Migrations\Exception\IrreversibleMigration) {
        $assert(true, 'Irreversible admin migration.');
    }

    // Exact parity: approved proposal SQL on a pre-000440 user_account equals the permanent migration.
    $normalise = static fn (string $ddl): string => preg_replace('/ AUTO_INCREMENT=\d+/', '', $ddl);
    $migrated = $normalise($db->fetchAssociative('SHOW CREATE TABLE user_account')['Create Table']);
    $assert(str_contains($migrated, '`admin_granted_at` datetime DEFAULT NULL') && str_contains($migrated, '`last_login_at` datetime DEFAULT NULL'), 'Migrated columns are missing or differ.');
    $db->executeStatement('ALTER TABLE user_account DROP COLUMN admin_granted_at, DROP COLUMN last_login_at');
    $preMigration = $normalise($db->fetchAssociative('SHOW CREATE TABLE user_account')['Create Table']);
    $others = [];
    foreach ($db->createSchemaManager()->listTableNames() as $table) {
        if ('user_account' !== $table) $others[$table] = $db->fetchAssociative('SHOW CREATE TABLE `'.$table.'`')['Create Table'];
    }
    $proposal = trim(preg_replace('/^--.*$/m', '', file_get_contents('/app/docs/ADMIN_SCHEMA_PROPOSAL.sql')));
    $db->executeStatement(rtrim($proposal, ';'));
    $afterProposal = $normalise($db->fetchAssociative('SHOW CREATE TABLE user_account')['Create Table']);
    $assert($migrated === $afterProposal, "Permanent migration differs from the approved proposal SQL:\n".$migrated."\n".$afterProposal);
    $assert($preMigration === preg_replace('/^  `(?:admin_granted_at|last_login_at)`[^\n]*\n/m', '', $afterProposal), 'Proposal changed existing account columns, keys or constraints.');
    foreach ($others as $table => $ddl) {
        $assert($ddl === $db->fetchAssociative('SHOW CREATE TABLE `'.$table.'`')['Create Table'], 'Proposal altered another table: '.$table);
    }

    // Seed accounts. 1/2 admins, 3/5/6 verified learners, 4 unverified; search and paging fixtures.
    $accounts = [
        1 => ['admin1@example.test', 86400, 86000, true],
        2 => ['admin2@example.test', 2 * 86400, 2 * 86000, true],
        3 => ['learner3@example.test', 3 * 86400, 3 * 86000, false],
        4 => ['unverified4@example.test', 4 * 86400, null, false],
        5 => ['learner5@example.test', 20 * 86400, 19 * 86400, false],
        6 => ['learner6@example.test', 100 * 86400, 50 * 86400, false],
        7 => ['per%cent@example.test', 200 * 86400, null, false],
        8 => ['perxcent@example.test', 201 * 86400, null, false],
        9 => ['under_score@example.test', 202 * 86400, null, false],
        10 => ['underxscore@example.test', 203 * 86400, null, false],
        11 => ['bang!x@example.test', 204 * 86400, null, false],
        12 => ['back\\slash@example.test', 205 * 86400, null, false],
    ];
    for ($i = 0; $i < 120; ++$i) {
        $accounts[100 + $i] = [sprintf('bulk%03d@example.test', $i), 300 * 86400 - $i * 60, null, false];
    }
    $now = time();
    foreach ($accounts as $id => [$email, $created, $verified, $isAdmin]) {
        $db->insert('user_account', ['id' => $id, 'email' => $email, 'password' => $hash, 'created_at' => gmdate('Y-m-d H:i:s', $now - $created), 'verified_at' => null === $verified ? null : gmdate('Y-m-d H:i:s', $now - $verified), 'admin_granted_at' => $isAdmin ? '2026-10-16 08:00:00' : null]);
    }
    $assert(0 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account WHERE last_login_at IS NOT NULL'), 'Existing accounts were backfilled with a sign-in time.');
    $shared = $db->fetchAssociative('SELECT lesson_id, id, MIN(revision) AS old_revision, MAX(revision) AS new_revision FROM lesson_line GROUP BY lesson_id, id HAVING COUNT(*) > 1 ORDER BY lesson_id, id LIMIT 1');
    $other = $db->fetchAssociative('SELECT l.lesson_id, l.revision, l.id FROM lesson_line l JOIN lesson c ON c.id = l.lesson_id AND c.current_revision = l.revision WHERE l.lesson_id <> ? ORDER BY l.lesson_id, l.sort_order LIMIT 1', [$shared['lesson_id']]);
    $assert(false !== $shared && false !== $other, 'Bootstrap lacks lesson lines for the statistics fixture.');
    $clean = '{"version":1,"usedHint":false,"usedRetry":false,"usedUndo":false,"decisions":[{"ply":0,"attempts":1,"hinted":false}]}';
    $hinted = '{"version":1,"usedHint":true,"usedRetry":false,"usedUndo":false,"decisions":[{"ply":0,"attempts":1,"hinted":true}]}';
    $completions = [
        [3, $shared['lesson_id'], $shared['new_revision'], $shared['id'], 'w', 3600, $clean],
        [3, $shared['lesson_id'], $shared['old_revision'], $shared['id'], 'b', 2 * 86400, $hinted],
        [3, $shared['lesson_id'], $shared['new_revision'], $shared['id'], 'w', 10 * 86400, null],
        [5, $other['lesson_id'], $other['revision'], $other['id'], 'b', 40 * 86400, $clean],
        [6, $other['lesson_id'], $other['revision'], $other['id'], 'w', 5 * 86400, null],
    ];
    foreach ($completions as $index => [$user, $lesson, $revision, $line, $color, $ago, $assessment]) {
        $db->insert('user_line_completion', ['user_id' => $user, 'attempt_id' => sprintf('a0000000-0000-4000-8000-%012d', $index), 'lesson_id' => $lesson, 'revision' => $revision, 'line_id' => $line, 'playing_color' => $color, 'hint_count' => 0, 'completed_at' => gmdate('Y-m-d H:i:s', $now - $ago), 'assessment_json' => $assessment]);
    }
    $db->insert('account_email_token', ['token_hash' => hash('sha256', 'learner3 reset'), 'user_id' => 3, 'purpose' => 'reset', 'created_at' => gmdate('Y-m-d H:i:s'), 'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600)]);

    $endpoints = [['GET', '/api/admin/users'], ['GET', '/api/admin/stats'], ['DELETE', '/api/admin/users/3']];
    $anonymous = $client();
    foreach ($endpoints as [$method, $path]) {
        $body = 'GET' === $method ? $get($anonymous, $path) : $send($anonymous, $method, $path, ['password' => $password]);
        $assert(401 === $status($anonymous) && is_string($body['error'] ?? null) && $anonymous->getResponse()->headers->hasCacheControlDirective('no-store'), 'Anonymous admin request was not a private 401: '.$path);
    }

    // Password sign-in records last_login_at; /api/me exposes the admin flag.
    $admin = $client();
    $signedIn = $login($admin, 'admin1@example.test');
    $assert(true === $signedIn['user']['admin'], 'Sign-in response omitted the admin flag.');
    $assert($recent($db->fetchOne('SELECT last_login_at FROM user_account WHERE id = 1')), 'Password sign-in did not record last_login_at.');
    $me = $get($admin, '/api/me')['user'];
    $assert(['id', 'email', 'verified', 'createdAt', 'admin'] === array_keys($me) && true === $me['admin'] && '1' === $me['id'], '/api/me contract changed or lost admin.');
    $assert(null === $db->fetchOne('SELECT last_login_at FROM user_account WHERE id = 2'), 'Another account received a sign-in time.');

    $learner = $client(); $learnerElsewhere = $client();
    $login($learner, 'learner3@example.test', true);
    $login($learnerElsewhere, 'learner3@example.test');
    $assert(false === $get($learner, '/api/me')['user']['admin'], 'Non-admin was reported as admin.');
    foreach ($endpoints as [$method, $path]) {
        $body = 'GET' === $method ? $get($learner, $path) : $send($learner, $method, $path, ['password' => $password]);
        $assert(403 === $status($learner) && is_string($body['error'] ?? null) && $learner->getResponse()->headers->hasCacheControlDirective('no-store'), 'Signed-in non-admin was not refused with 403: '.$path);
    }
    $assert(1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account WHERE id = 3'), 'Non-admin deletion attempt changed data.');

    // Remember-me restore also records sign-in; ordinary session requests do not.
    $db->update('user_account', ['last_login_at' => '2020-01-01 00:00:00'], ['id' => 3]);
    $get($learner, '/api/me');
    $assert('2020-01-01 00:00:00' === $db->fetchOne('SELECT last_login_at FROM user_account WHERE id = 3'), 'An ordinary session request rewrote the sign-in time.');
    $learnerRemember = clone ($learner->getCookieJar()->get('CHESS_REMEMBER') ?? throw new RuntimeException('Remember cookie missing.'));
    $rememberOnly = $client();
    $rememberOnly->getCookieJar()->set(clone $learnerRemember);
    $get($rememberOnly, '/api/me');
    $assert(200 === $status($rememberOnly), 'Remember-me restore failed.');
    $assert($recent($db->fetchOne('SELECT last_login_at FROM user_account WHERE id = 3')), 'Remember-me restore did not record last_login_at.');

    // Users list: newest first, 50 per page, exact row contract, literal search.
    $page = $get($admin, '/api/admin/users');
    $assert(200 === $status($admin) && $private($admin), 'Users list was not a private 200.');
    $total = count($accounts);
    $assert(['items', 'page', 'limit', 'total', 'search'] === array_keys($page) && 1 === $page['page'] && 50 === $page['limit'] && $total === $page['total'] && '' === $page['search'], 'Users list envelope differs from the contract.');
    $assert(50 === count($page['items']) && '1' === $page['items'][0]['id'] && '2' === $page['items'][1]['id'], 'Users list is not newest first.');
    $row = $page['items'][2];
    $assert(['id', 'email', 'createdAt', 'verifiedAt', 'lastSignInAt', 'admin', 'completions', 'lastPracticeAt'] === array_keys($row), 'User row keys differ from the contract.');
    $assert('3' === $row['id'] && 'learner3@example.test' === $row['email'] && false === $row['admin'] && 3 === $row['completions'] && gmdate(DATE_ATOM, $now - 3600) === $row['lastPracticeAt'] && is_string($row['lastSignInAt']) && str_ends_with($row['createdAt'], '+00:00'), 'User row values are wrong.');
    $assert(true === $page['items'][0]['admin'] && 0 === $page['items'][0]['completions'] && null === $page['items'][0]['lastPracticeAt'], 'Admin row values are wrong.');
    $assert(null === $page['items'][3]['verifiedAt'] && null === $page['items'][3]['lastSignInAt'], 'Unverified row did not use null dates.');
    $raw = $admin->getResponse()->getContent();
    $assert(!str_contains($raw, '$argon2') && !str_contains($raw, 'password'), 'Users list exposed password hashes.');
    $ids = array_column($page['items'], 'id');
    $page2 = $get($admin, '/api/admin/users?page=2');
    $page3 = $get($admin, '/api/admin/users?page=3');
    $assert(50 === count($page2['items']) && $total - 100 === count($page3['items']) && 3 === $page3['page'], 'Paging returned the wrong page sizes.');
    $allIds = [...$ids, ...array_column($page2['items'], 'id'), ...array_column($page3['items'], 'id')];
    $assert($total === count(array_unique($allIds)), 'Pages overlap or skip accounts.');
    $created = array_map(static fn (string $id): int => $accounts[(int) $id][1], $allIds);
    $sorted = $created; sort($sorted);
    $assert($sorted === $created, 'Paging order is not newest first.');
    $last = $get($admin, '/api/admin/users?page=9999');
    $assert(200 === $status($admin) && [] === $last['items'] && $total === $last['total'], 'Page beyond the end was not an empty page.');
    $search = static function (string $term) use ($get, $admin): array {
        $result = $get($admin, '/api/admin/users?'.http_build_query(['search' => $term]));
        return array_column($result['items'], 'email');
    };
    $assert(['per%cent@example.test'] === $search('per%cent'), 'Percent was treated as a wildcard.');
    $assert(['per%cent@example.test'] === $search('%'), 'Literal percent search failed.');
    $assert(['under_score@example.test'] === $search('_'), 'Underscore was treated as a wildcard.');
    $assert(['under_score@example.test'] === $search('  UNDER_Score '), 'Search is not trimmed and case-insensitive.');
    $assert(['bang!x@example.test'] === $search('g!'), 'Escape character was not literal.');
    $assert(['back\\slash@example.test'] === $search('k\\s'), 'Backslash was not literal.');
    $assert(['admin1@example.test', 'admin2@example.test'] === $search('ADMIN'), 'Substring search failed.');
    $assert([] === $search('nobody-matches'), 'Unmatched search returned rows.');
    $counted = $get($admin, '/api/admin/users?search=bulk&page=3');
    $assert(120 === $counted['total'] && 20 === count($counted['items']) && 'bulk' === $counted['search'], 'Search total/paging was wrong.');
    $get($admin, '/api/admin/users?search='.str_repeat('a', 254));
    $assert(200 === $status($admin), '254-character search was refused.');
    foreach (['search='.str_repeat('a', 255), 'page=0', 'page=10000', 'page=01', 'page=abc', 'page=-1', 'page=1.5', 'page[]=1', 'search[]=a', 'search=%FF'] as $query) {
        $get($admin, '/api/admin/users?'.$query);
        $assert(400 === $status($admin), 'Invalid users query was accepted: '.$query);
    }

    // Statistics against seeded rows (rolling UTC windows; Monday weeks; zero-filled series).
    $db->update('user_account', ['last_login_at' => $at(7200)], ['id' => 1]);
    $db->update('user_account', ['last_login_at' => $at(3 * 86400)], ['id' => 2]);
    $db->update('user_account', ['last_login_at' => $at(20 * 86400)], ['id' => 3]);
    $db->update('user_account', ['last_login_at' => $at(60 * 86400)], ['id' => 5]);
    $stats = $get($admin, '/api/admin/stats');
    $assert(200 === $status($admin) && $private($admin), 'Stats was not a private 200.');
    $assert(['generatedAt', 'accounts', 'signIns', 'practice'] === array_keys($stats) && str_ends_with($stats['generatedAt'], '+00:00'), 'Stats envelope differs from the contract.');
    $assert(['total' => $total, 'verified' => 5, 'unverified' => $total - 5, 'admins' => 2] === array_diff_key($stats['accounts'], ['weekly' => true]), 'Account totals are wrong.');
    $assert(['last1Day' => 1, 'last7Days' => 2, 'last30Days' => 3, 'neverRecorded' => $total - 4] === $stats['signIns'], 'Sign-in activity is wrong.');
    $weekOf = static fn (int $ts): string => gmdate('Y-m-d', $ts - ((int) gmdate('N', $ts) - 1) * 86400);
    $expectedWeeks = [];
    for ($i = 11; $i >= 0; --$i) $expectedWeeks[$weekOf($now - 7 * 86400 * $i)] = ['signups' => 0, 'verifications' => 0];
    foreach ($accounts as [, $created, $verified]) {
        if (isset($expectedWeeks[$weekOf($now - $created)])) ++$expectedWeeks[$weekOf($now - $created)]['signups'];
        if (null !== $verified && isset($expectedWeeks[$weekOf($now - $verified)])) ++$expectedWeeks[$weekOf($now - $verified)]['verifications'];
    }
    $weekly = [];
    foreach ($expectedWeeks as $week => $counts) $weekly[] = ['weekStart' => $week, ...$counts];
    $assert(12 === count($stats['accounts']['weekly']) && $weekly === $stats['accounts']['weekly'], 'Weekly sign-up/verification series is wrong.');
    $assert(5 === array_sum(array_column($weekly, 'signups')) && 5 === array_sum(array_column($weekly, 'verifications')), 'Weekly fixture does not cover the intended accounts.');
    $practice = $stats['practice'];
    $assert(5 === $practice['total'] && 3 === $practice['last7Days'] && 4 === $practice['last30Days'] && 2 === $practice['learners7Days'] && 2 === $practice['learners30Days'], 'Practice totals/windows are wrong.');
    $assert(['w' => 3, 'b' => 2] === $practice['byColor'] && 3 === $practice['assessed'] && 2 === $practice['cleanAssessed'], 'Colour split or assessed/clean counts are wrong.');
    $expectedDays = [];
    for ($i = 29; $i >= 0; --$i) $expectedDays[gmdate('Y-m-d', $now - 86400 * $i)] = 0;
    foreach ($completions as [, , , , , $ago]) {
        if (isset($expectedDays[gmdate('Y-m-d', $now - $ago)])) ++$expectedDays[gmdate('Y-m-d', $now - $ago)];
    }
    $daily = [];
    foreach ($expectedDays as $date => $count) $daily[] = ['date' => $date, 'completions' => $count];
    $assert(30 === count($practice['daily']) && $daily === $practice['daily'], 'Daily completion series is wrong.');
    $courses = $db->fetchAllAssociative('SELECT l.id, r.title FROM lesson l JOIN lesson_revision r ON r.lesson_id = l.id AND r.revision = l.current_revision ORDER BY l.sort_order, l.id');
    $assert(array_column($courses, 'id') === array_column($practice['byCourse'], 'lessonId') && array_column($courses, 'title') === array_column($practice['byCourse'], 'title'), 'byCourse does not list every current course in sort order.');
    foreach ($practice['byCourse'] as $course) {
        $expected = match ($course['lessonId']) { $shared['lesson_id'] => [3, 1], $other['lesson_id'] => [2, 2], default => [0, 0] };
        $assert(['lessonId', 'title', 'completions', 'learners'] === array_keys($course) && $expected === [$course['completions'], $course['learners']], 'Course counts are wrong: '.$course['lessonId']);
    }
    $titles = static fn (string $lesson, int|string $revision, string $line): array => $db->fetchAssociative('SELECT r.title AS course, l.title AS line FROM lesson_revision r JOIN lesson_line l ON l.lesson_id = r.lesson_id AND l.revision = r.revision WHERE r.lesson_id = ? AND r.revision = ? AND l.id = ?', [$lesson, $revision, $line]);
    $sharedTitles = $titles($shared['lesson_id'], $shared['new_revision'], $shared['id']);
    $otherTitles = $titles($other['lesson_id'], $other['revision'], $other['id']);
    $assert([
        ['lessonId' => $shared['lesson_id'], 'lineId' => $shared['id'], 'courseTitle' => $sharedTitles['course'], 'lineTitle' => $sharedTitles['line'], 'completions' => 3],
        ['lessonId' => $other['lesson_id'], 'lineId' => $other['id'], 'courseTitle' => $otherTitles['course'], 'lineTitle' => $otherTitles['line'], 'completions' => 2],
    ] === $practice['topLines'], 'Top lines are wrong or split across revisions.');
    $statsRaw = $admin->getResponse()->getContent();
    $assert(!str_contains($statsRaw, '@example.test'), 'Statistics exposed an individual account.');

    // Deletion: CSRF, exact body, password, refusals, unknown.
    $db->executeStatement('DELETE FROM account_request_limit');
    $send($admin, 'DELETE', '/api/admin/users/3', ['password' => $password], '');
    $assert(403 === $status($admin), 'Deletion accepted a missing CSRF token.');
    $send($admin, 'DELETE', '/api/admin/users/3', ['password' => $password], $csrf($learner));
    $assert(403 === $status($admin), 'Deletion accepted another browser CSRF token.');
    foreach ([[], ['password' => null], ['password' => ''], ['password' => str_repeat('x', 257)], ['password' => $password, 'id' => 3]] as $body) {
        $send($admin, 'DELETE', '/api/admin/users/3', $body);
        $assert(400 === $status($admin), 'Malformed deletion body was accepted.');
    }
    $admin->request('GET', '/api/admin/users/3');
    $assert(405 === $status($admin), 'GET on a user resource was not refused.');
    $admin->request('DELETE', '/api/admin/users/abc', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $csrf($admin)], content: '{"password":"x"}');
    $assert(404 === $status($admin), 'Non-numeric user id matched the deletion route.');
    $assert(0 === (int) $db->fetchOne('SELECT COUNT(*) FROM account_request_limit'), 'Rejected malformed requests consumed confirmation limits.');
    $wrong = $send($admin, 'DELETE', '/api/admin/users/3', ['password' => 'wrong password']);
    $assert(400 === $status($admin) && 'Check your current password and try again.' === $wrong['error'] && $private($admin), 'Wrong admin password was not a private 400.');
    $send($admin, 'DELETE', '/api/admin/users/1', ['password' => $password]);
    $assert(409 === $status($admin), 'Admin could delete their own account here.');
    $send($admin, 'DELETE', '/api/admin/users/2', ['password' => $password]);
    $assert(409 === $status($admin), 'Admin could delete another administrator.');
    $unknown = $send($admin, 'DELETE', '/api/admin/users/999999', ['password' => $password]);
    $assert(404 === $status($admin) && is_string($unknown['error']), 'Unknown account was not 404.');
    $assert($total === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account'), 'Refused deletion changed accounts.');
    $db->executeStatement('DELETE FROM account_request_limit');

    // A database failure rolls back the cascade and reports 503.
    $db->executeStatement("CREATE TRIGGER fail_admin_deletion AFTER DELETE ON user_account FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Disposable deletion failure'");
    $send($admin, 'DELETE', '/api/admin/users/3', ['password' => $password]);
    $assert(503 === $status($admin), 'Injected database failure was not 503.');
    $assert(1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account WHERE id = 3') && 3 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion WHERE user_id = 3') && 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM account_email_token WHERE user_id = 3'), 'Failed deletion partly removed data.');
    $db->executeStatement('DROP TRIGGER fail_admin_deletion');
    $db->executeStatement('DELETE FROM account_request_limit');

    $deleted = $send($admin, 'DELETE', '/api/admin/users/3', ['password' => $password]);
    $assert(200 === $status($admin) && ['deleted' => true] === $deleted && $private($admin), 'Confirmed admin deletion failed.');
    foreach (['user_account' => 'id', 'user_line_completion' => 'user_id', 'account_email_token' => 'user_id'] as $table => $key) {
        $assert(0 === (int) $db->fetchOne('SELECT COUNT(*) FROM '.$table.' WHERE '.$key.' = 3'), 'Deletion left '.$table.' rows.');
    }
    $assert(2 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion') && $total - 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account'), 'Deletion affected other accounts.');
    foreach ([$learner, $learnerElsewhere, $rememberOnly] as $browser) {
        $get($browser, '/api/me');
        $assert(401 === $status($browser), "Deleted account's other session still worked.");
    }
    $replay = $client(); $replay->getCookieJar()->set(clone $learnerRemember);
    $get($replay, '/api/me');
    $assert(401 === $status($replay), 'Deleted account restored through remember me.');
    $get($admin, '/api/me');
    $assert(200 === $status($admin), 'Deleting another account signed the admin out.');
    $send($admin, 'DELETE', '/api/admin/users/3', ['password' => $password]);
    $assert(404 === $status($admin), 'Repeated deletion was not 404.');

    // Confirmation limits are shared with Account download/deletion (5 per admin per 15 minutes).
    $db->executeStatement('DELETE FROM account_request_limit');
    for ($i = 0; $i < 5; ++$i) {
        $send($admin, 'DELETE', '/api/admin/users/5', ['password' => 'incorrect']);
        $assert(400 === $status($admin), 'Admin confirmation limit fired too early.');
    }
    $send($admin, 'POST', '/api/me/data-export', ['password' => $password]);
    $assert(429 === $status($admin) && (int) $admin->getResponse()->headers->get('Retry-After') > 0, 'Admin deletions do not share the account confirmation limit.');
    $send($admin, 'DELETE', '/api/admin/users/5', ['password' => $password]);
    $assert(429 === $status($admin) && 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account WHERE id = 5'), 'Limited admin deletion still ran.');
    $db->executeStatement('DELETE FROM account_request_limit');
    $send($admin, 'POST', '/api/me/data-export', ['password' => $password]);
    $assert(200 === $status($admin), 'Account download did not recover after the limit window.');
    $db->executeStatement('DELETE FROM account_request_limit');
    for ($i = 0; $i < 5; ++$i) {
        $send($admin, 'POST', '/api/me/data-export', ['password' => 'incorrect']);
    }
    $send($admin, 'DELETE', '/api/admin/users/5', ['password' => $password]);
    $assert(429 === $status($admin) && 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account WHERE id = 5'), 'Account confirmations do not count toward admin deletion.');
    $db->executeStatement('DELETE FROM account_request_limit');

    // CLI grants: refused for unknown/unverified, idempotent, listed without secrets.
    $five = $client(); $fiveRemembered = $client();
    $login($five, 'learner5@example.test');
    $login($fiveRemembered, 'learner5@example.test', true);
    [$code, $output] = $console(['app:admin:grant', 'nobody@example.test']);
    $assert(0 !== $code && str_contains($output, 'No account uses'), 'Unknown account grant did not fail clearly.');
    [$code, $output] = $console(['app:admin:grant', 'unverified4@example.test']);
    $assert(0 !== $code && str_contains($output, 'not confirmed') && null === $db->fetchOne('SELECT admin_granted_at FROM user_account WHERE id = 4'), 'Unverified account was granted.');
    [$code, $output] = $console(['app:admin:grant', '  Learner5@EXAMPLE.test ']);
    $grantedAt = $db->fetchOne('SELECT admin_granted_at FROM user_account WHERE id = 5');
    $assert(0 === $code && $recent($grantedAt), 'Verified account grant failed: '.$output);
    $db->update('user_account', ['admin_granted_at' => '2026-10-16 09:30:00'], ['id' => 5]);
    [$code, $output] = $console(['app:admin:grant', 'learner5@example.test']);
    $assert(0 === $code && str_contains($output, 'already') && '2026-10-16 09:30:00' === $db->fetchOne('SELECT admin_granted_at FROM user_account WHERE id = 5'), 'Repeated grant was not idempotent.');
    [$code, $output] = $console(['app:admin:list']);
    $assert(0 === $code && str_contains($output, 'admin1@example.test') && str_contains($output, 'admin2@example.test') && str_contains($output, 'learner5@example.test') && str_contains($output, '2026-10-16T09:30:00+00:00'), 'Admin list is incomplete: '.$output);
    $assert(!str_contains($output, '$argon2') && !str_contains($output, 'unverified4') && !str_contains($output, 'learner6'), 'Admin list exposed secrets or non-admins.');
    // Symfony re-checks roles on refresh: a grant ends existing sessions, and clears remember-me, on their next request.
    foreach ([$five, $fiveRemembered] as $browser) {
        $get($browser, '/api/admin/stats');
        $assert(401 === $status($browser) && null === $browser->getCookieJar()->get('CHESS_REMEMBER'), 'Granted role silently changed an existing session.');
    }
    $login($five, 'learner5@example.test');
    $get($five, '/api/admin/stats');
    $assert(200 === $status($five) && true === $get($five, '/api/me')['user']['admin'], 'Granted admin could not use the admin API after signing in.');

    // CLI revoke takes effect on the next request of every existing session, remembered or not.
    $two = $client(); $twoRemembered = $client();
    $login($two, 'admin2@example.test');
    $login($twoRemembered, 'admin2@example.test', true);
    $get($two, '/api/admin/users');
    $get($twoRemembered, '/api/admin/users');
    $assert(200 === $status($two) && 200 === $status($twoRemembered), 'Second admin could not use the admin API.');
    [$code, $output] = $console(['app:admin:revoke', 'ADMIN2@example.test']);
    $assert(0 === $code && null === $db->fetchOne('SELECT admin_granted_at FROM user_account WHERE id = 2'), 'Revoke failed: '.$output);
    foreach ([$two, $twoRemembered] as $browser) {
        $get($browser, '/api/admin/users');
        $assert(401 === $status($browser) && null === $browser->getCookieJar()->get('CHESS_REMEMBER'), 'Revoked admin kept a session.');
    }
    $login($twoRemembered, 'admin2@example.test', true);
    $get($twoRemembered, '/api/admin/users');
    $assert(403 === $status($twoRemembered) && false === $get($twoRemembered, '/api/me')['user']['admin'], 'Revoked admin regained access after signing in.');
    [$code, $output] = $console(['app:admin:revoke', 'admin2@example.test']);
    $assert(0 === $code && str_contains($output, 'not an administrator'), 'Repeated revoke was not idempotent.');
    [$code, $output] = $console(['app:admin:revoke', 'nobody@example.test']);
    $assert(0 !== $code && str_contains($output, 'No account uses'), 'Unknown revoke did not fail.');
    // A revoked admin is an ordinary account and can now be deleted by an admin.
    $send($admin, 'DELETE', '/api/admin/users/2', ['password' => $password]);
    $assert(200 === $status($admin) && 0 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account WHERE id = 2'), 'Revoked admin could not be deleted.');
    $get($twoRemembered, '/api/me');
    $assert(401 === $status($twoRemembered), 'Deleted former admin kept a session.');
    // An admin revoked mid-session cannot delete even with a correct password and its own CSRF token.
    $db->executeStatement('DELETE FROM account_request_limit');
    $token = $csrf($five);
    $console(['app:admin:revoke', 'learner5@example.test']);
    $send($five, 'DELETE', '/api/admin/users/6', ['password' => $password], $token);
    $assert(401 === $status($five) && 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account WHERE id = 6'), 'Revoked admin could still delete.');
    [$code, $output] = $console(['app:admin:list']);
    $assert(0 === $code && str_contains($output, 'admin1@example.test') && !str_contains($output, 'learner5@example.test'), 'Admin list did not reflect revocations.');
    printf("Admin integration passed (%d checks): proposal/migration parity, 401/403 access, private responses, literal search and paging, seeded statistics, CSRF/password/self/admin/unknown deletion rules, rollback, cascades and session revocation, shared confirmation limits, password and remember-me last sign-in, CLI grant/list/revoke.\n", $checks);
} finally {
    $db->close();
}
