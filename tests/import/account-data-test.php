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
$password = 'AccountData! with spaces 2026';
$login = static function (KernelBrowser $browser, string $email, string $secret) use ($send, $assert): void {
    $send($browser, 'POST', '/api/auth/login', ['email' => $email, 'password' => $secret, 'rememberMe' => true]);
    $assert(200 === $browser->getResponse()->getStatusCode(), 'Test account could not sign in.');
};
$copyCookies = static function (KernelBrowser $browser) use ($client): KernelBrowser {
    $copy = $client();
    foreach ($browser->getCookieJar()->all() as $cookie) $copy->getCookieJar()->set(clone $cookie);
    return $copy;
};

try {
    $process = proc_open([PHP_BINARY, 'bin/console', 'app:database:prepare', '--source-directory=/app/backend/tests/Fixtures/Lessons/source', '--no-interaction'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/app/backend', array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']));
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $assert(0 === proc_close($process), 'Fresh bootstrap failed: '.$output);
    $sourceBefore = $db->fetchAllAssociative('SELECT * FROM opening_catalogue_state');
    $curriculumBefore = $db->fetchAllAssociative('SELECT * FROM lesson_revision ORDER BY lesson_id,revision');
    foreach ([1, 2, 3] as $id) {
        $db->insert('user_account', ['id' => $id, 'email' => 'data'.$id.'@example.test', 'password' => password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 1024, 'time_cost' => 3]), 'created_at' => '2026-10-15 08:00:00', 'verified_at' => '2026-10-15 09:00:00']);
    }
    $a = $client(); $b = $client(); $anonymous = $client();
    foreach ([['POST', '/api/me/data-export'], ['DELETE', '/api/me']] as [$method, $path]) {
        $send($anonymous, $method, $path, ['password' => $password]);
        $assert(401 === $anonymous->getResponse()->getStatusCode(), 'Anonymous sensitive action was accepted.');
        $assert($anonymous->getResponse()->headers->hasCacheControlDirective('no-store'), 'Anonymous sensitive response was cacheable.');
    }
    $login($a, 'data1@example.test', $password);
    $login($b, 'data2@example.test', $password);
    $send($a, 'POST', '/api/me/data-export', ['password' => $password], '');
    $assert(403 === $a->getResponse()->getStatusCode(), 'Export accepted missing CSRF.');
    $send($a, 'DELETE', '/api/me', ['password' => $password], $csrf($b));
    $assert(403 === $a->getResponse()->getStatusCode(), 'Deletion accepted another browser CSRF.');
    foreach ([[], ['password' => null], ['password' => ''], ['password' => str_repeat('x', 257)], ['password' => $password, 'userId' => 2]] as $body) {
        foreach ([['POST', '/api/me/data-export'], ['DELETE', '/api/me']] as [$method, $path]) {
            $send($a, $method, $path, $body);
            $assert(400 === $a->getResponse()->getStatusCode(), 'Malformed or client-selected confirmation was accepted.');
        }
    }
    foreach ([['POST', '/api/me/data-export'], ['DELETE', '/api/me']] as [$method, $path]) {
        $body = $send($a, $method, $path, ['password' => 'wrong password']);
        $assert(400 === $a->getResponse()->getStatusCode() && str_contains($body['error'], 'current password'), 'Wrong password was not actionable.');
    }
    $assert(3 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account'), 'Rejected deletion changed an account.');
    $db->executeStatement('DELETE FROM account_request_limit');

    // Exercise real measured and legacy writes plus a retained earlier revision.
    $lesson = $get($a, '/api/lessons/italian')['lesson'];
    $line = $lesson['lines'][0];
    $decisions = [];
    foreach ($line['steps'] as $ply => $step) {
        if ('b' === explode(' ', $step['fromKey'])[1]) $decisions[] = ['ply' => $ply, 'attempts' => 1, 'hinted' => false];
    }
    foreach (['w', 'b'] as $index => $color) {
        $body = ['lessonId' => 'italian', 'revision' => $lesson['revision'], 'lineId' => $line['id'], 'playingColor' => $color, 'hintCount' => 0, 'path' => array_column($line['steps'], 'uci'), 'attemptId' => sprintf('f0000000-0000-4000-8000-%012d', $index + 1)];
        if ('b' === $color) $body['assessment'] = ['version' => 1, 'usedHint' => false, 'usedRetry' => false, 'usedUndo' => false, 'decisions' => $decisions];
        $send($a, 'POST', '/api/me/completions', $body);
        $assert(200 === $a->getResponse()->getStatusCode(), 'Real practice fixture was not accepted.');
    }
    $old = $db->fetchAssociative('SELECT l.lesson_id,l.revision,l.id FROM lesson_line l JOIN lesson c ON c.id=l.lesson_id WHERE l.revision<c.current_revision ORDER BY l.lesson_id,l.revision,l.id LIMIT 1');
    $db->insert('user_line_completion', ['user_id' => 1, 'attempt_id' => 'f0000000-0000-4000-8000-000000000003', 'lesson_id' => $old['lesson_id'], 'revision' => $old['revision'], 'line_id' => $old['id'], 'playing_color' => 'w', 'hint_count' => 0, 'completed_at' => '2026-10-15 10:00:00']);
    $db->insert('user_line_completion', ['user_id' => 2, 'attempt_id' => 'f0000000-0000-4000-8000-000000000004', 'lesson_id' => 'italian', 'revision' => $lesson['revision'], 'line_id' => $line['id'], 'playing_color' => 'b', 'hint_count' => 0, 'completed_at' => '2026-10-15 10:00:00']);
    $reset = bin2hex(random_bytes(32));
    $db->insert('account_email_token', ['token_hash' => hash('sha256', $reset), 'user_id' => 1, 'purpose' => 'reset', 'created_at' => '2026-10-15 09:00:00', 'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600)]);
    $db->insert('account_email_token', ['token_hash' => hash('sha256', 'other token'), 'user_id' => 2, 'purpose' => 'reset', 'created_at' => '2026-10-15 09:00:00', 'expires_at' => gmdate('Y-m-d H:i:s', time() + 3600)]);
    $get($a, '/api/me/data-export');
    $assert(405 === $a->getResponse()->getStatusCode(), 'GET could bypass password confirmation.');
    $export = $send($a, 'POST', '/api/me/data-export', ['password' => $password]);
    $assert(200 === $a->getResponse()->getStatusCode(), 'Confirmed export failed.');
    $assert(1 === $export['formatVersion'] && str_ends_with($export['exportedAt'], '+00:00'), 'Export format/time is not explicit.');
    $lastSignIn = $db->fetchOne('SELECT last_login_at FROM user_account WHERE id=1');
    $assert(is_string($lastSignIn) && abs(strtotime($lastSignIn.' UTC') - time()) < 300, 'Password sign-in did not record the latest sign-in time.');
    $assert(['id' => '1', 'email' => 'data1@example.test', 'createdAt' => '2026-10-15T08:00:00+00:00', 'verifiedAt' => '2026-10-15T09:00:00+00:00', 'lastSignInAt' => (new DateTimeImmutable($lastSignIn, new DateTimeZone('UTC')))->format(DATE_ATOM)] === $export['account'], 'Account export omitted or changed owned metadata.');
    $assert(3 === count($export['completions']), 'Export lost history or included another account.');
    $assert(1 === count(array_filter($export['completions'], static fn (array $row): bool => null !== $row['assessment'])), 'Legacy recall was invented or measured evidence lost.');
    $assert(1 === count($export['emailLinks']) && 'reset' === $export['emailLinks'][0]['purpose'], 'Email-link metadata is not user-scoped.');
    foreach ($export['completions'] as $row) {
        $assert('' !== $row['lessonTitle'] && '' !== $row['lineTitle'] && is_int($row['revision']) && str_ends_with($row['completedAt'], '+00:00'), 'Practice export lost retained labels, revision or UTC time.');
    }
    $raw = $a->getResponse()->getContent();
    foreach (['password', '$argon2', 'token_hash', 'pending_password_hash', $reset, hash('sha256', $reset), 'data2@example.test', 'CHESS_REMEMBER', '_security_'] as $secret) {
        $assert(!str_contains($raw, $secret), 'Export exposed a secret or another identity.');
    }
    $headers = $a->getResponse()->headers;
    $assert(str_starts_with($headers->get('Content-Disposition'), 'attachment;') && str_contains($headers->get('Content-Disposition'), '.json'), 'Export was not a JSON download.');
    $assert('nosniff' === $headers->get('X-Content-Type-Options') && str_starts_with($headers->get('Content-Type'), 'application/json'), 'Export has unsafe MIME headers.');
    $assert($headers->hasCacheControlDirective('private') && $headers->hasCacheControlDirective('no-store') && 'no-referrer' === $headers->get('Referrer-Policy'), 'Export privacy headers are absent.');
    $other = $send($b, 'POST', '/api/me/data-export', ['password' => $password]);
    $assert('2' === $other['account']['id'] && 1 === count($other['completions']), 'Second account received first account data.');

    // A failed database deletion rolls back cascades and keeps the browser signed in.
    $db->executeStatement("CREATE TRIGGER fail_account_deletion AFTER DELETE ON user_account FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Disposable deletion failure'");
    $send($a, 'DELETE', '/api/me', ['password' => $password]);
    $assert(500 === $a->getResponse()->getStatusCode(), 'Injected database failure did not fail closed.');
    $assert(1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account WHERE id=1') && 3 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion WHERE user_id=1') && 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM account_email_token WHERE user_id=1'), 'Failed deletion partly removed owned data.');
    $get($a, '/api/me');
    $assert(200 === $a->getResponse()->getStatusCode(), 'Failed deletion signed out the account.');
    $db->executeStatement('DROP TRIGGER fail_account_deletion');
    $db->executeStatement('DELETE FROM account_request_limit');
    $secondBrowser = $client();
    $login($secondBrowser, 'data1@example.test', $password);
    $remember = clone $secondBrowser->getCookieJar()->get('CHESS_REMEMBER');
    $oldSession = $copyCookies($secondBrowser);
    $replayedSession = $copyCookies($a);
    $deleted = $send($a, 'DELETE', '/api/me', ['password' => $password]);
    $assert(200 === $a->getResponse()->getStatusCode() && ['deleted' => true] === $deleted, 'Confirmed deletion failed.');
    $assert(null === $a->getCookieJar()->get('CHESS_REMEMBER'), 'Deletion left this browser remembered.');
    foreach (['user_account' => 'id', 'user_line_completion' => 'user_id', 'account_email_token' => 'user_id'] as $table => $key) {
        $assert(0 === (int) $db->fetchOne('SELECT COUNT(*) FROM '.$table.' WHERE '.$key.'=1'), 'Deletion left owned '.$table.' data.');
    }
    foreach ([$a, $secondBrowser, $replayedSession] as $browser) {
        foreach (['/api/me', '/api/me/progress', '/api/me/practice', '/api/lessons/italian'] as $path) {
            $get($browser, $path);
            $assert(401 === $browser->getResponse()->getStatusCode(), 'Deleted account restored access through a session.');
        }
    }
    $rememberOnly = $client(); $rememberOnly->getCookieJar()->set(clone $remember);
    $get($rememberOnly, '/api/me');
    $assert(401 === $rememberOnly->getResponse()->getStatusCode(), 'Deleted account restored access through remember me.');
    $send($anonymous, 'POST', '/api/auth/reset-password', ['token' => $reset, 'password' => 'new account data password']);
    $assert(400 === $anonymous->getResponse()->getStatusCode(), 'Deleted reset link still worked.');
    $send($anonymous, 'POST', '/api/auth/login', ['email' => 'data1@example.test', 'password' => $password]);
    $assert(401 === $anonymous->getResponse()->getStatusCode(), 'Deleted account could sign in.');
    $get($b, '/api/me');
    $assert(200 === $b->getResponse()->getStatusCode() && 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion WHERE user_id=2') && 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM account_email_token WHERE user_id=2'), 'Deletion affected another account/session.');
    $assert($sourceBefore === $db->fetchAllAssociative('SELECT * FROM opening_catalogue_state') && $curriculumBefore === $db->fetchAllAssociative('SELECT * FROM lesson_revision ORDER BY lesson_id,revision'), 'Deletion changed public catalogue/curriculum.');

    // Registering the same email/password must not revive old session/remember credentials.
    $db->insert('user_account', ['email' => 'data1@example.test', 'password' => password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 1024, 'time_cost' => 3]), 'created_at' => gmdate('Y-m-d H:i:s'), 'verified_at' => gmdate('Y-m-d H:i:s')]);
    $get($oldSession, '/api/me');
    $assert(401 === $oldSession->getResponse()->getStatusCode(), 'Recreated account revived a deleted session.');
    $rememberAgain = $client(); $rememberAgain->getCookieJar()->set(clone $remember);
    $get($rememberAgain, '/api/me');
    $assert(401 === $rememberAgain->getResponse()->getStatusCode(), 'Recreated account revived a deleted remember cookie.');

    // The confirmation ceiling is shared by both actions and all browsers of one account.
    $db->executeStatement('DELETE FROM account_request_limit');
    $limited = $client(); $sameUser = $client();
    $login($limited, 'data3@example.test', $password);
    $login($sameUser, 'data3@example.test', $password);
    for ($i = 0; $i < 5; ++$i) {
        $send($limited, 'POST', '/api/me/data-export', ['password' => 'incorrect']);
        $assert(400 === $limited->getResponse()->getStatusCode(), 'Confirmation limit fired too early.');
    }
    $send($sameUser, 'DELETE', '/api/me', ['password' => $password]);
    $assert(429 === $sameUser->getResponse()->getStatusCode() && (int) $sameUser->getResponse()->headers->get('Retry-After') > 0 && 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_account WHERE id=3'), 'Confirmation rate limit was bypassed through another action/browser.');
    $db->executeStatement('UPDATE account_request_limit SET expires_at = UNIX_TIMESTAMP() - 1');
    $send($sameUser, 'POST', '/api/me/data-export', ['password' => $password]);
    $assert(200 === $sameUser->getResponse()->getStatusCode(), 'Expired confirmation limit did not recover.');
    printf("Account data integration passed (%d checks): private password/CSRF download, full retained practice, secrets/ownership, atomic deletion/cascades, session/remember/reset revocation, recreation and persistent confirmation limits.\n", $checks);
} finally {
    $db->close();
}
