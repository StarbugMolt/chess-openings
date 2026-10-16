<?php

declare(strict_types=1);

use Symfony\Component\HttpClient\HttpClient;

/** Real HTTP response framing against a deliberately stalled, isolated SMTP server. */
return static function (array $environment, string $email, Closure $assert): void {
    $workspace = sys_get_temp_dir().'/chess-account-response-'.bin2hex(random_bytes(8));
    mkdir($workspace, 0700);
    $smtp = $http = null;
    $smtpPipes = [];
    try {
        $smtpScript = <<<'PHP'
<?php
$server = stream_socket_server('tcp://127.0.0.1:0');
if (false === $server) exit(2);
echo stream_socket_get_name($server, false)."\n";
flush();
$client = stream_socket_accept($server, 15);
if (false === $client) exit(3);
file_put_contents(__DIR__.'/smtp-connected', 'yes');
sleep(4);
fwrite($client, "421 Deliberate test transport delay\r\n");
fclose($client);
fclose($server);
PHP;
        file_put_contents($workspace.'/smtp.php', $smtpScript);
        $smtp = proc_open([PHP_BINARY, $workspace.'/smtp.php'], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['file', $workspace.'/smtp.log', 'a']], $smtpPipes);
        if (!is_resource($smtp)) throw new RuntimeException('Could not start isolated SMTP test server.');
        $smtpAddress = trim(fgets($smtpPipes[1]));
        if (!preg_match('/\A127\.0\.0\.1:[0-9]+\z/', $smtpAddress)) throw new RuntimeException('Invalid SMTP test listener.');
        $reservation = stream_socket_server('tcp://127.0.0.1:0');
        if (false === $reservation) throw new RuntimeException('Could not reserve HTTP test port.');
        $httpAddress = stream_socket_get_name($reservation, false);
        fclose($reservation);
        $httpEnvironment = array_merge($environment, ['APP_ENV' => 'dev', 'APP_DEBUG' => '0', 'MAILER_DSN' => 'smtp://'.$smtpAddress, 'MAIL_FROM' => 'no-reply@example.test', 'DEFAULT_URI' => 'http://localhost']);
        $http = proc_open([PHP_BINARY, '-S', $httpAddress, '-t', '/app/backend/public'], [0 => ['file', '/dev/null', 'r'], 1 => ['file', $workspace.'/http.log', 'a'], 2 => ['file', $workspace.'/http.log', 'a']], $httpPipes, '/app/backend', $httpEnvironment);
        if (!is_resource($http)) throw new RuntimeException('Could not start isolated account HTTP server.');
        for ($attempt = 0; $attempt < 30; ++$attempt) {
            $socket = @stream_socket_client('tcp://'.$httpAddress, $errno, $error, 0.1);
            if (is_resource($socket)) { fclose($socket); break; }
            usleep(50000);
        }
        $client = HttpClient::create(['timeout' => 10]);
        $csrfResponse = $client->request('GET', 'http://'.$httpAddress.'/api/auth/csrf');
        $csrf = $csrfResponse->toArray()['csrfToken'];
        $cookies = [];
        foreach ($csrfResponse->getHeaders()['set-cookie'] ?? [] as $header) $cookies[] = explode(';', $header, 2)[0];
        $started = microtime(true);
        $response = $client->request('POST', 'http://'.$httpAddress.'/api/auth/request-reset', ['headers' => ['Cookie' => implode('; ', $cookies), 'X-CSRF-Token' => $csrf], 'json' => ['email' => $email]]);
        $body = $response->getContent();
        $duration = microtime(true) - $started;
        $assert(202 === $response->getStatusCode(), 'Slow-mail HTTP request failed: '.$body);
        $assert($duration < 3, 'HTTP response waited for the deliberately delayed SMTP greeting.');
        $assert((string) strlen($body) === ($response->getHeaders()['content-length'][0] ?? ''), 'Accepted HTTP response omitted exact body framing.');
        $assert(str_contains($response->getHeaders()['cache-control'][0] ?? '', 'no-transform'), 'Account HTTP response permits intermediary transformation.');
        for ($attempt = 0; $attempt < 20 && !is_file($workspace.'/smtp-connected'); ++$attempt) usleep(25000);
        $assert(is_file($workspace.'/smtp-connected') && proc_get_status($smtp)['running'], 'Response completed without exercising the delayed SMTP delivery.');
        printf("Account HTTP timing check passed: %.3fs response while isolated SMTP waits 4s.\n", $duration);
    } finally {
        foreach ([$http, $smtp] as $process) {
            if (!is_resource($process)) continue;
            proc_terminate($process);
            proc_close($process);
        }
        foreach ($smtpPipes as $pipe) if (is_resource($pipe)) fclose($pipe);
        foreach (glob($workspace.'/*') ?: [] as $file) unlink($file);
        rmdir($workspace);
    }
};
