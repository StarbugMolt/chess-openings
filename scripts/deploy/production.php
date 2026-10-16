<?php
/** Private host preparation helpers. Never print configuration values. */
declare(strict_types=1);

final class ProductionPreparation
{
    public static function validate(array $env): void
    {
        if (($env['APP_ENV'] ?? '') !== 'prod' || ($env['APP_DEBUG'] ?? '') !== '0') {
            throw new RuntimeException('Production requires APP_ENV=prod and APP_DEBUG=0.');
        }
        if (!preg_match('/^[a-f0-9]{64}$/iD', $env['APP_SECRET'] ?? '')) {
            throw new RuntimeException('Set APP_SECRET to 64 random hexadecimal characters.');
        }
        $uri = parse_url($env['DEFAULT_URI'] ?? '');
        if ($uri === false || ($uri['scheme'] ?? '') !== 'https' || empty($uri['host']) || isset($uri['user']) || self::placeholder($uri['host'])) {
            throw new RuntimeException('Set DEFAULT_URI to the real public HTTPS origin.');
        }
        $mailer = parse_url($env['MAILER_DSN'] ?? '');
        $allowLocal = ($env['ALLOW_LOCAL_MAIL_TRANSPORT'] ?? '') === '1';
        $mailHost = $mailer['host'] ?? '';
        $localSmtp = in_array(strtolower(trim($mailHost, '[]')), ['localhost', '127.0.0.1', '::1'], true);
        $sendmail = $allowLocal && ($mailer['scheme'] ?? '') === 'sendmail' && $mailHost === 'default';
        if ($mailer === false || (!$sendmail && (!in_array($mailer['scheme'] ?? '', ['smtp', 'smtps'], true) || $mailHost === ''
                || ($localSmtp && !$allowLocal) || (self::placeholder($mailHost) && !($localSmtp && $allowLocal))))
            || self::placeholder(rawurldecode(($mailer['user'] ?? '').($mailer['pass'] ?? '')))) {
            throw new RuntimeException('Configure a real SMTP MAILER_DSN before deployment.');
        }
        preg_match('/^(?:[^<>]*<)?([^<>\s]+@[^<>\s]+)>?$/D', $env['MAIL_FROM'] ?? '', $sender);
        if (!filter_var($sender[1] ?? '', FILTER_VALIDATE_EMAIL) || self::placeholder($sender[1])) {
            throw new RuntimeException('Configure MAIL_FROM with a real sender address.');
        }
        $database = parse_url($env['DATABASE_URL'] ?? '');
        if ($database === false || !in_array($database['scheme'] ?? '', ['mysql', 'mariadb'], true)
            || empty($database['host']) || empty($database['user']) || empty($database['pass']) || empty(trim($database['path'] ?? '', '/'))
            || self::placeholder($database['user']) || self::placeholder($database['pass']) || self::placeholder($database['path'])) {
            throw new RuntimeException('Configure the production MariaDB DATABASE_URL.');
        }
    }

    private static function placeholder(string $value): bool
    {
        return (bool) preg_match('/example\.(?:test|com|org|net)|localhost|mailpit|replace_|cpanel_|url_encoded|placeholder|change[-_]?me/i', $value);
    }

    public static function backup(array $env, string $shared, string $dumpBinary = 'mariadb-dump'): string
    {
        $directory = "$shared/backups";
        if (is_link($directory) || (!is_dir($directory) && !mkdir($directory, 0700)) || !chmod($directory, 0700)) {
            throw new RuntimeException('Cannot prepare private backup directory.');
        }
        $database = parse_url($env['DATABASE_URL']);
        $options = ['host' => $database['host'], 'port' => (string) ($database['port'] ?? 3306), 'user' => rawurldecode($database['user']), 'password' => rawurldecode($database['pass']), 'protocol' => 'tcp'];
        $defaults = tempnam($directory, '.client-');
        $temporary = tempnam($directory, '.dump-');
        if ($defaults === false || $temporary === false) throw new RuntimeException('Cannot create private backup files.');
        $destination = "$directory/".gmdate('Ymd-His').'-'.sprintf('%06d', ((int) (microtime(true) * 1_000_000)) % 1_000_000).'-'.bin2hex(random_bytes(4)).'.sql';
        try {
            chmod($defaults, 0600);
            chmod($temporary, 0600);
            $contents = "[client]\n";
            foreach ($options as $name => $value) {
                $contents .= $name.'="'.strtr($value, ["\\" => "\\\\", '"' => '\\"', "\n" => '\\n', "\r" => '\\r']).'"'."\n";
            }
            if (file_put_contents($defaults, $contents) === false) throw new RuntimeException('Cannot write private backup options.');
            $process = proc_open([$dumpBinary, '--defaults-extra-file='.$defaults, '--single-transaction', '--quick', '--skip-lock-tables', '--hex-blob', '--', rawurldecode(ltrim($database['path'], '/'))],
                [0 => ['file', '/dev/null', 'r'], 1 => ['file', $temporary, 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
            if (!is_resource($process) || proc_close($process) !== 0 || filesize($temporary) === 0 || !rename($temporary, $destination)) {
                throw new RuntimeException('Database backup failed; migrations were not started. Check dump tool, database access and disk space.');
            }
            // Keep the last three successful deployment snapshots. Partial backups never rotate good ones.
            $backups = glob("$directory/*.sql");
            rsort($backups, SORT_STRING);
            foreach (array_slice($backups, 3) as $old) {
                if (is_link($old) || !unlink($old)) throw new RuntimeException('Cannot rotate private database backups.');
            }
            return $destination;
        } finally {
            if (is_file($defaults)) unlink($defaults);
            if (is_file($temporary)) unlink($temporary);
        }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        [$script, $release, $shared] = $argv;
        require "$release/vendor/autoload.php";
        $env = (new Symfony\Component\Dotenv\Dotenv())->parse(file_get_contents("$shared/.env.local"), "$shared/.env.local");
        ProductionPreparation::validate($env);
        ProductionPreparation::backup($env, $shared);
        echo "Production configuration validated; private database snapshot saved.\n";
    } catch (Throwable) {
        fwrite(STDERR, "Production preparation refused: verify private configuration, SMTP/sender, HTTPS, random secret, dump client, database access and backup disk space. Migrations were not started.\n");
        exit(1);
    }
}
