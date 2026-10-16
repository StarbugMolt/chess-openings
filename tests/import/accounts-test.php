<?php

declare(strict_types=1);

use App\Kernel;
use App\Security\AccountRateLimiter;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

require '/app/backend/vendor/autoload.php';
$database = getenv('MIGRATION_TEST_DATABASE');
if (!is_string($database) || !preg_match('/\Achess_migration_test_[0-9_]+\z/', $database)) {
    throw new RuntimeException('Only a disposable test database is allowed.');
}
$url = preg_replace('~/[^/?]*(?=\?|$)~', '/'.$database, (string) getenv('DATABASE_URL'));
putenv('DATABASE_URL='.$url);
$_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $url;
(new Dotenv())->bootEnv('/app/backend/.env');
$connection = DriverManager::getConnection((new DsnParser(['mysql' => 'pdo_mysql']))->parse($url));
$environment = array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']);
$process = proc_open([PHP_BINARY, 'bin/console', 'app:schema:migrate', '--no-interaction'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/app/backend', $environment);
$output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
if (proc_close($process) !== 0) throw new RuntimeException($output);
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void {
    if (!$ok) throw new RuntimeException($message);
    ++$checks;
};
$client = static fn (): KernelBrowser => new KernelBrowser(new Kernel('test', true));
$csrf = static function (KernelBrowser $browser): string {
    $browser->request('GET', '/api/auth/csrf');
    $data = json_decode($browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
    return $data['csrfToken'];
};
$post = static function (KernelBrowser $browser, string $action, array $data = [], ?string $token = null): array {
    $browser->request('POST', '/api/auth/'.$action, server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $token ?? ''], content: json_encode((object) $data, JSON_THROW_ON_ERROR));
    return json_decode($browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
};
$submit = static function (KernelBrowser $browser, string $action, array $data = []) use ($csrf, $post): array {
    return $post($browser, $action, $data, $csrf($browser));
};
$mail = HttpClient::create();
$emailToken = static function (string $email, string $kind) use ($mail, $assert): string {
    $messages = $mail->request('GET', 'http://mailpit:8025/api/v1/messages')->toArray()['messages'];
    foreach ($messages as $message) {
        if (($message['To'][0]['Address'] ?? '') !== $email || !str_contains($message['Subject'], $kind)) continue;
        $body = $mail->request('GET', 'http://mailpit:8025/api/v1/message/'.$message['ID'])->toArray()['Text'];
        $assert(str_contains($body, 'http://localhost:5173/') && !str_contains($body, 'evil.example'), 'Email did not use canonical DEFAULT_URI.');
        if (!preg_match('/#token=([a-f0-9]{64})/', $body, $match)) throw new RuntimeException('Missing email token.');
        return $match[1];
    }
    throw new RuntimeException('Mailpit did not capture the expected test email.');
};
$suffix = bin2hex(random_bytes(6));
$first = 'account-'.$suffix.'@example.test';
$second = 'other-'.$suffix.'@example.test';
$password = 'a sufficiently long test password';
$a = $client(); $b = $client();
try {
    $a->request('GET', '/api/me');
    $assert(401 === $a->getResponse()->getStatusCode(), 'Anonymous private account endpoint was accessible.');
    $assert($a->getResponse()->headers->hasCacheControlDirective('no-store'), 'Private response was cacheable.');
    $post($a, 'register', ['email' => $first, 'password' => $password]);
    $assert(403 === $a->getResponse()->getStatusCode() && 0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM user_account'), 'Missing CSRF created an account.');
    $stolen = $csrf($b);
    $post($a, 'register', ['email' => $first, 'password' => $password], $stolen);
    $assert(403 === $a->getResponse()->getStatusCode(), 'CSRF was not bound to the browser session.');
    $submit($a, 'register', ['email' => $first, 'password' => 'short']);
    $assert(400 === $a->getResponse()->getStatusCode(), 'Short password accepted.');
    $registration = $submit($a, 'register', ['email' => strtoupper($first), 'password' => $password]);
    $assert(202 === $a->getResponse()->getStatusCode(), 'Registration failed.');
    $row = $connection->fetchAssociative('SELECT * FROM user_account WHERE email = ?', [$first]);
    $assert(is_array($row) && $row['email'] === $first && password_verify($password, $row['password']) && str_starts_with($row['password'], '$argon2id$'), 'Password hashing or email normalisation failed.');
    $submit($a, 'login', ['email' => $first, 'password' => $password]);
    $assert(401 === $a->getResponse()->getStatusCode(), 'Unverified account signed in.');
    $a->request('GET', '/api/me');
    $assert(401 === $a->getResponse()->getStatusCode(), 'Failed unverified login left an authenticated session.');
    $verify = $emailToken($first, 'Verify');
    $assert(hash('sha256', $verify) === $connection->fetchOne('SELECT token_hash FROM account_email_token WHERE user_id = ?', [$row['id']]), 'Token was not stored as a hash.');
    $submit($a, 'verify-email', ['token' => $verify]);
    $assert(200 === $a->getResponse()->getStatusCode() && password_verify($password, $connection->fetchOne('SELECT password FROM user_account WHERE id = ?', [$row['id']])), 'Token-only verification did not activate the registration password.');
    $submit($a, 'verify-email', ['token' => $verify]);
    $assert(400 === $a->getResponse()->getStatusCode(), 'Verification token reused.');
    $duplicate = $submit($a, 'register', ['email' => $first, 'password' => $password]);
    $assert($registration === $duplicate && 202 === $a->getResponse()->getStatusCode(), 'Registration reveals existing accounts.');
    $oldCsrf = $csrf($a);
    $cookie = $a->getCookieJar()->all()[0];
    $oldSession = $cookie->getValue();
    $signed = $post($a, 'login', ['email' => $first, 'password' => $password], $oldCsrf);
    $assert(200 === $a->getResponse()->getStatusCode() && $signed['user']['email'] === $first, 'Verified account could not sign in.');
    $assert(null === $a->getCookieJar()->get('CHESS_REMEMBER'), 'Unchecked remember me created a persistent cookie.');
    $assert($oldSession !== $a->getCookieJar()->get($cookie->getName())->getValue(), 'Login did not rotate session ID.');
    $post($a, 'logout', [], $oldCsrf);
    $assert(403 === $a->getResponse()->getStatusCode(), 'Login did not rotate CSRF.');
    $a->request('GET', '/api/me?userId=999999');
    $me = json_decode($a->getResponse()->getContent(), true);
    $assert($me['user']['id'] === (string) $row['id'] && !isset($me['user']['password']), 'Account endpoint accepted a client-selected identity or exposed password.');
    $assert($a->getResponse()->headers->hasCacheControlDirective('private') && $a->getResponse()->headers->hasCacheControlDirective('no-store'), 'Signed-in response could be shared.');

    $submit($b, 'register', ['email' => $second, 'password' => $password]);
    $expired = $emailToken($second, 'Verify');
    $connection->executeStatement('UPDATE account_email_token SET expires_at = UTC_TIMESTAMP() - INTERVAL 1 SECOND WHERE token_hash = ?', [hash('sha256', $expired)]);
    $submit($b, 'verify-email', ['token' => $expired]);
    $assert(400 === $b->getResponse()->getStatusCode(), 'Expired verification accepted.');
    $submit($b, 'request-verification', ['email' => $second]);
    $submit($b, 'verify-email', ['token' => $emailToken($second, 'Verify')]);
    $submit($b, 'login', ['email' => $second, 'password' => $password]);
    $b->request('GET', '/api/me?userId='.$row['id']);
    $other = json_decode($b->getResponse()->getContent(), true);
    $assert($other['user']['email'] === $second && $other['user']['id'] !== (string) $row['id'], 'Second account accessed first account data.');
    date_default_timezone_set('Pacific/Auckland');
    $b->request('GET', '/api/me');
    $utcUser = json_decode($b->getResponse()->getContent(), true)['user'];
    $assert(str_ends_with($utcUser['createdAt'], '+00:00') && 'UTC' === date_default_timezone_get(), 'Account UTC DATETIME hydration depended on the host timezone.');

    // A previous registration must not bind the owner's verified account to an attacker's password.
    $connection->executeStatement('DELETE FROM account_request_limit');
    $claimed = 'claimed-'.$suffix.'@example.test';
    $attackerPassword = 'attacker preclaimed long password';
    $ownerPassword = 'owner selected new long password';
    $claimer = $client(); $owner = $client();
    $submit($claimer, 'register', ['email' => $claimed, 'password' => $attackerPassword]);
    $attackerVerify = $emailToken($claimed, 'Verify');
    $submit($owner, 'register', ['email' => $claimed, 'password' => $ownerPassword]);
    $ownerVerify = $emailToken($claimed, 'Verify');
    $assert($attackerVerify !== $ownerVerify, 'Re-registration did not issue its own verification token.');
    $assert(password_verify($attackerPassword, $connection->fetchOne('SELECT password FROM user_account WHERE email = ?', [$claimed])), 'Unconfirmed registration changed the stored account password.');
    $assert(2 === (int) $connection->fetchOne('SELECT COUNT(*) FROM account_email_token t JOIN user_account u ON u.id = t.user_id WHERE u.email = ?', [$claimed]), 'Re-registration invalidated an earlier verification link.');
    $ownerPending = $connection->fetchAssociative('SELECT pending_password_hash, issued_sequence FROM account_email_token WHERE token_hash = ?', [hash('sha256', $ownerVerify)]);
    $assert(password_verify($ownerPassword, $ownerPending['pending_password_hash']), 'Owner token did not bind the owner registration password.');
    // Equal timestamps must not make resend choose an arbitrary candidate.
    $connection->executeStatement('UPDATE account_email_token t JOIN user_account u ON u.id = t.user_id SET t.created_at = ? WHERE u.email = ?', ['2026-10-15 00:00:00', $claimed]);
    $submit($owner, 'request-verification', ['email' => $claimed]);
    $resendVerify = $emailToken($claimed, 'Verify');
    $resent = $connection->fetchAssociative('SELECT pending_password_hash, issued_sequence FROM account_email_token WHERE token_hash = ?', [hash('sha256', $resendVerify)]);
    $assert($resent['pending_password_hash'] === $ownerPending['pending_password_hash'] && (int) $resent['issued_sequence'] > (int) $ownerPending['issued_sequence'], 'Resend did not select the most recently issued password independently of equal timestamps.');
    $submit($owner, 'verify-email', ['token' => $ownerVerify]);
    $assert(200 === $owner->getResponse()->getStatusCode(), 'Owner could not confirm their own registration link.');
    $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM account_email_token t JOIN user_account u ON u.id = t.user_id WHERE u.email = ?', [$claimed]), 'Verification did not delete all competing verification links.');
    $submit($owner, 'verify-email', ['token' => $attackerVerify]);
    $assert(400 === $owner->getResponse()->getStatusCode(), 'Consumed owner link left a competing attacker link active.');
    $submit($owner, 'verify-email', ['token' => $resendVerify]);
    $assert(400 === $owner->getResponse()->getStatusCode(), 'Consumed owner link left a resend active.');
    $submit($claimer, 'login', ['email' => $claimed, 'password' => $attackerPassword]);
    $assert(401 === $claimer->getResponse()->getStatusCode(), 'Preclaiming attacker could sign in after owner verification.');
    $submit($owner, 'login', ['email' => $claimed, 'password' => $ownerPassword]);
    $assert(200 === $owner->getResponse()->getStatusCode(), 'Verification did not activate the owner registration password.');

    // A later registrant must not invalidate the owner's earlier email or change its password binding.
    $late = 'late-'.$suffix.'@example.test';
    $submit($owner, 'register', ['email' => $late, 'password' => $ownerPassword]);
    $earlyOwnerVerify = $emailToken($late, 'Verify');
    $submit($claimer, 'register', ['email' => $late, 'password' => $attackerPassword]);
    $lateAttackerVerify = $emailToken($late, 'Verify');
    $submit($owner, 'verify-email', ['token' => $earlyOwnerVerify]);
    $assert(200 === $owner->getResponse()->getStatusCode() && password_verify($ownerPassword, $connection->fetchOne('SELECT password FROM user_account WHERE email = ?', [$late])), 'A later attacker registration invalidated the owner link or changed its bound password.');
    $submit($claimer, 'verify-email', ['token' => $lateAttackerVerify]);
    $assert(400 === $claimer->getResponse()->getStatusCode(), 'Later attacker verification survived owner confirmation.');

    // Accepted owner policy: an unsolicited link still activates exactly the hash issued with it.
    $unsolicited = 'unsolicited-'.$suffix.'@example.test';
    $submit($claimer, 'register', ['email' => $unsolicited, 'password' => $attackerPassword]);
    $unsolicitedToken = $emailToken($unsolicited, 'Verify');
    $unsolicitedHash = $connection->fetchOne('SELECT pending_password_hash FROM account_email_token WHERE token_hash = ?', [hash('sha256', $unsolicitedToken)]);
    $submit($owner, 'register', ['email' => $unsolicited, 'password' => $ownerPassword]);
    $submit($owner, 'verify-email', ['token' => $unsolicitedToken]);
    $assert(200 === $owner->getResponse()->getStatusCode() && $unsolicitedHash === $connection->fetchOne('SELECT password FROM user_account WHERE email = ?', [$unsolicited]), 'An unsolicited link activated a hash other than its own issued candidate.');

    $legacy = 'legacy-'.$suffix.'@example.test';
    $submit($owner, 'register', ['email' => $legacy, 'password' => $ownerPassword]);
    $legacyVerify = $emailToken($legacy, 'Verify');
    $legacyHash = $connection->fetchOne('SELECT password FROM user_account WHERE email = ?', [$legacy]);
    $connection->executeStatement('UPDATE account_email_token SET pending_password_hash = NULL WHERE token_hash = ?', [hash('sha256', $legacyVerify)]);
    $submit($owner, 'request-verification', ['email' => $legacy]);
    $legacyResend = $emailToken($legacy, 'Verify');
    $assert($legacyHash === $connection->fetchOne('SELECT pending_password_hash FROM account_email_token WHERE token_hash = ?', [hash('sha256', $legacyResend)]), 'Legacy resend did not bind the current account hash.');
    $submit($owner, 'verify-email', ['token' => $legacyVerify]);
    $assert(200 === $owner->getResponse()->getStatusCode() && $legacyHash === $connection->fetchOne('SELECT password FROM user_account WHERE email = ?', [$legacy]), 'Legacy verification without a binding changed the account password.');
    $connection->executeStatement('DELETE FROM account_request_limit');

    $c = $client();
    $submit($c, 'login', ['email' => $first, 'password' => $password, 'rememberMe' => true]);
    $remember = $c->getCookieJar()->get('CHESS_REMEMBER');
    $assert(null !== $remember && $remember->getExpiresTime() > time() + 2591900, 'Remember me did not create a 30-day cookie.');
    $rememberHeader = array_values(array_filter($c->getResponse()->headers->getCookies(), static fn ($cookie) => 'CHESS_REMEMBER' === $cookie->getName()))[0];
    $assert($rememberHeader->isHttpOnly() && !$rememberHeader->isSecure() && 'lax' === $rememberHeader->getSameSite(), 'Local remember-me cookie flags wrong.');
    $d = $client(); $d->getCookieJar()->set(clone $remember);
    $d->request('GET', '/api/me');
    $assert(200 === $d->getResponse()->getStatusCode() && json_decode($d->getResponse()->getContent(), true)['user']['email'] === $first, 'Remember cookie alone did not restore sign-in after browser restart.');
    $assert(is_int($d->getRequest()->getSession()->get('account_started')), 'Remember-me restoration did not start a bounded session.');
    $tampered = $client();
    $tampered->getCookieJar()->set(new Symfony\Component\BrowserKit\Cookie('CHESS_REMEMBER', $remember->getValue().'x', time() + 3600, '/', 'localhost'));
    $tampered->request('GET', '/api/me');
    $assert(401 === $tampered->getResponse()->getStatusCode(), 'Tampered remember cookie authenticated.');
    $known = $submit($c, 'request-reset', ['email' => $first]);
    $resetOld = $emailToken($first, 'Reset');
    $unknown = $submit($c, 'request-reset', ['email' => 'missing-'.$suffix.'@example.test']);
    $assert($known === $unknown && 202 === $c->getResponse()->getStatusCode(), 'Reset request enumerated accounts.');
    $submit($c, 'request-reset', ['email' => $first]);
    $reset = $emailToken($first, 'Reset');
    $submit($c, 'reset-password', ['token' => $resetOld, 'password' => 'another long test password']);
    $assert(400 === $c->getResponse()->getStatusCode(), 'Replaced reset link remained valid.');
    $connection->executeStatement('UPDATE account_email_token SET expires_at = UTC_TIMESTAMP() - INTERVAL 1 SECOND WHERE token_hash = ?', [hash('sha256', $reset)]);
    $submit($c, 'reset-password', ['token' => $reset, 'password' => 'another long test password']);
    $assert(400 === $c->getResponse()->getStatusCode(), 'Expired reset accepted.');
    $submit($c, 'request-reset', ['email' => $first]);
    $reset = $emailToken($first, 'Reset');
    $submit($c, 'reset-password', ['token' => $reset, 'password' => 'another long test password']);
    $assert(200 === $c->getResponse()->getStatusCode(), 'Password reset failed.');
    $submit($c, 'reset-password', ['token' => $reset, 'password' => 'another long test password']);
    $assert(400 === $c->getResponse()->getStatusCode(), 'Reset token reused.');
    $a->request('GET', '/api/me'); $assert(401 === $a->getResponse()->getStatusCode(), 'Reset did not invalidate first old session.');
    $c->request('GET', '/api/me'); $assert(401 === $c->getResponse()->getStatusCode(), 'Reset did not invalidate second old session.');
    $d->request('GET', '/api/me'); $assert(401 === $d->getResponse()->getStatusCode(), 'Reset did not invalidate restored session.');
    $replay = $client(); $replay->getCookieJar()->set(clone $remember);
    $replay->request('GET', '/api/me');
    $assert(401 === $replay->getResponse()->getStatusCode(), 'Password reset did not invalidate old remember cookie.');
    $submit($a, 'login', ['email' => $first, 'password' => $password]);
    $assert(401 === $a->getResponse()->getStatusCode(), 'Old password still works.');
    $submit($a, 'login', ['email' => $first, 'password' => 'another long test password']);
    $assert(200 === $a->getResponse()->getStatusCode(), 'New password cannot sign in.');
    $post($a, 'logout'); $assert(403 === $a->getResponse()->getStatusCode(), 'Logout lacks CSRF protection.');
    $submit($a, 'logout'); $assert(200 === $a->getResponse()->getStatusCode(), 'Logout failed.');
    $a->request('GET', '/api/me'); $assert(401 === $a->getResponse()->getStatusCode(), 'Logged-out account remained accessible.');

    // Change the test session clock without waiting two/eight hours.
    $b->request('GET', '/api/me');
    $session = $b->getRequest()->getSession();
    $session->set('account_active', time() - 7201); $session->save();
    $b->request('GET', '/api/me'); $assert(401 === $b->getResponse()->getStatusCode(), 'Idle session did not expire.');
    $submit($b, 'login', ['email' => $second, 'password' => $password]);
    $b->request('GET', '/api/me'); $session = $b->getRequest()->getSession();
    $session->set('account_started', time() - 28801); $session->save();
    $b->request('GET', '/api/me'); $assert(401 === $b->getResponse()->getStatusCode(), 'Absolute session did not expire.');

    $connection->executeStatement('DELETE FROM account_request_limit');
    $x = $client();
    for ($i=0; $i<10; ++$i) $submit($x, 'login', ['email' => $second, 'password' => 'wrong password']);
    $submit($client(), 'login', ['email' => $second, 'password' => $password]);
    // Check with the retained browser too; limits are database-wide, not session-specific.
    $submit($x, 'login', ['email' => $second, 'password' => $password]);
    $assert(429 === $x->getResponse()->getStatusCode() && $x->getResponse()->headers->has('Retry-After'), 'Login throttle was bypassed.');
    // A remote attacker cannot consume the owner's hard email/network limit.
    $isolated = $client();
    $token = $csrf($isolated);
    $isolated->request('POST', '/api/auth/login', server: ['REMOTE_ADDR' => '192.0.2.10', 'CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $token], content: json_encode(['email' => $second, 'password' => $password]));
    $assert(200 === $isolated->getResponse()->getStatusCode(), 'One client network exhausted another network login bucket.');
    $connection->executeStatement('UPDATE account_request_limit SET expires_at = ?', [time() - 1]);
    $submit($x, 'login', ['email' => $second, 'password' => $password]);
    $assert(200 === $x->getResponse()->getStatusCode(), 'Expired throttle did not reset.');
    $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM account_request_limit WHERE expires_at < ?', [time()]), 'Expired limits were retained.');
    $assert(0 === (int) $connection->fetchOne('SELECT COUNT(*) FROM account_request_limit WHERE bucket_hash = ?', [hash_hmac('sha256', 'login-email-ip'."\0".$second."\0".'127.0.0.1', 'local-development-only')]), 'Successful login did not clear the email/network throttle.');

    $ipv6 = $client(); $token = $csrf($ipv6);
    foreach (['2001:db8:1234:5678::1', '2001:db8:1234:5678::2'] as $ip) {
        $ipv6->request('POST', '/api/auth/login', server: ['REMOTE_ADDR' => $ip, 'CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $token], content: json_encode(['email' => $second, 'password' => 'wrong password']));
    }
    $ipv6Key = hash_hmac('sha256', 'login-email-ip'."\0".$second."\0".'2001:db8:1234:5678::/64', 'local-development-only');
    $assert(2 === (int) $connection->fetchOne('SELECT attempts FROM account_request_limit WHERE bucket_hash = ?', [$ipv6Key]), 'IPv6 privacy addresses bypassed the shared login network limit.');
    $dailyEmail = 'daily-'.$suffix.'@example.test';
    $connection->insert('account_request_limit', ['bucket_hash' => hash_hmac('sha256', 'mail-email-day'."\0".$dailyEmail, 'local-development-only'), 'attempts' => 10, 'expires_at' => time() + 86400]);
    $daily = $client();
    $submit($daily, 'request-reset', ['email' => $dailyEmail]);
    $assert(429 === $daily->getResponse()->getStatusCode() && (int) $daily->getResponse()->headers->get('Retry-After') > 86000, 'Daily email cap was bypassed by a fresh hourly bucket.');

    // More than one cleanup batch must not carry a target bucket's expired attempts forward.
    $connection->executeStatement('DELETE FROM account_request_limit');
    $rateSecret = 'isolated-rate-limit-secret';
    $identity = 'expired-target';
    $rateKey = hash_hmac('sha256', 'test-expiry'."\0".$identity, $rateSecret);
    for ($i = 0; $i < 150; ++$i) $connection->insert('account_request_limit', ['bucket_hash' => str_pad(dechex($i), 64, '0', STR_PAD_LEFT), 'attempts' => 99, 'expires_at' => time() - 10]);
    $connection->insert('account_request_limit', ['bucket_hash' => $rateKey, 'attempts' => 99, 'expires_at' => time() - 10]);
    (new AccountRateLimiter($connection, $rateSecret))->consume('test-expiry', $identity, 1, 900);
    $limit = $connection->fetchAssociative('SELECT attempts, expires_at FROM account_request_limit WHERE bucket_hash = ?', [$rateKey]);
    $assert(1 === (int) $limit['attempts'] && (int) $limit['expires_at'] > time(), 'Expired target did not reset beyond the bounded cleanup batch.');
    $connection->executeStatement('DELETE FROM account_request_limit');
    $submit($x, 'login', ['email' => $second, 'password' => $password, 'rememberMe' => true]);
    $logoutRemember = $x->getCookieJar()->get('CHESS_REMEMBER');
    $assert(null !== $logoutRemember, 'Remember cookie missing before logout test.');
    $restart = $client(); $restart->getCookieJar()->set(clone $logoutRemember);
    $submit($restart, 'logout');
    $assert(200 === $restart->getResponse()->getStatusCode() && null === $restart->getCookieJar()->get('CHESS_REMEMBER'), 'Logout did not clear remember cookie after browser restart.');
    $restart->request('GET', '/api/me'); $assert(401 === $restart->getResponse()->getStatusCode(), 'Remember me signed the browser back in after logout.');

    // Real prod configuration over HTTPS uses Secure; local HTTP keeps development usable.
    $local = $client(); $csrf($local);
    $localCookie = $local->getResponse()->headers->getCookies()[0];
    $assert(!$localCookie->isSecure() && $localCookie->isHttpOnly() && 'lax' === $localCookie->getSameSite(), 'Local HTTP cookie flags wrong.');
    // Prod has no debug freshness checks; rebuild it after changes made during development.
    $process = proc_open([PHP_BINARY, 'bin/console', 'cache:clear', '--env=prod', '--no-debug'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/app/backend', $environment);
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    if (proc_close($process) !== 0) throw new RuntimeException($output);
    $prod = new KernelBrowser(new Kernel('prod', false));
    $prod->request('GET', 'https://localhost/api/auth/csrf');
    $prodCookie = $prod->getResponse()->headers->getCookies()[0];
    $assert($prodCookie->isSecure() && $prodCookie->isHttpOnly() && 'lax' === $prodCookie->getSameSite(), 'Production HTTPS cookie flags wrong.');
    $prodCsrf = json_decode($prod->getResponse()->getContent(), true)['csrfToken'];
    $prod->request('POST', 'https://localhost/api/auth/login', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $prodCsrf], content: json_encode(['email' => $second, 'password' => $password, 'rememberMe' => true]));
    $assert(200 === $prod->getResponse()->getStatusCode(), 'Production HTTPS login failed.');
    $prodRemember = array_values(array_filter($prod->getResponse()->headers->getCookies(), static fn ($cookie) => 'CHESS_REMEMBER' === $cookie->getName()))[0];
    $assert($prodRemember->isSecure() && $prodRemember->isHttpOnly() && 'lax' === $prodRemember->getSameSite(), 'Production remember-me cookie flags wrong.');
    $connection->executeStatement('DELETE FROM account_request_limit');
    (require '/app/tests/import/account-response-test.php')($environment, $second, $assert);
    printf("Account integration checks passed (%d): real Mailpit registration/verification/reset, user isolation, session/CSRF rotation, expiry, persistent limits and HTTP/HTTPS cookies.\n", $checks);
} finally {
    $connection->close();
}
