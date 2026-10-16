<?php
/** Host-independent checks for private production configuration and backup lifecycle. */
declare(strict_types=1);
require __DIR__.'/../../scripts/deploy/production.php';
require __DIR__.'/../../scripts/o2switch/deploy.php';

function assertProduction(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}
$env = ['APP_ENV' => 'prod', 'APP_DEBUG' => '0', 'APP_SECRET' => bin2hex(random_bytes(32)), 'DEFAULT_URI' => 'https://chess-club.fr', 'MAILER_DSN' => 'smtp://sender:safe-password@smtp.chess-club.fr:587?require_tls=true', 'MAIL_FROM' => 'Chess Openings <hello@chess-club.fr>', 'DATABASE_URL' => 'mysql://chess:pa%25ss%24%22%5Cword@db:3306/chess?charset=utf8mb4'];
assertProduction(file_get_contents(__DIR__.'/../../backend/.env.prod.example') === file_get_contents(__DIR__.'/../../scripts/o2switch/app.env.example'), 'Production environment templates must stay in sync');
ProductionPreparation::validate($env);
foreach (['smtp://localhost:25', 'smtp://127.0.0.1:25', 'sendmail://default'] as $local) {
    try { ProductionPreparation::validate(array_replace($env, ['MAILER_DSN' => $local])); throw new LogicException('Expected default local mail rejection'); }
    catch (RuntimeException) {}
    ProductionPreparation::validate(array_replace($env, ['MAILER_DSN' => $local, 'ALLOW_LOCAL_MAIL_TRANSPORT' => '1']));
}
foreach (['null://null', 'smtp://mailpit:1025', 'smtp://smtp.REPLACE_HOST:587'] as $invalid) {
    try { ProductionPreparation::validate(array_replace($env, ['MAILER_DSN' => $invalid, 'ALLOW_LOCAL_MAIL_TRANSPORT' => '1'])); throw new LogicException('Expected placeholder mail rejection even with opt-in'); }
    catch (RuntimeException) {}
}
foreach (['APP_SECRET' => 'local-development-only', 'APP_ENV' => 'dev', 'APP_DEBUG' => '1', 'DEFAULT_URI' => 'http://chess-club.fr', 'MAILER_DSN' => 'null://null', 'MAIL_FROM' => 'Chess Openings <no-reply@example.test>'] as $key => $value) {
    try { ProductionPreparation::validate(array_replace($env, [$key => $value])); throw new LogicException("Expected invalid $key rejection"); }
    catch (RuntimeException) {}
}
$root = sys_get_temp_dir().'/chess-production-'.bin2hex(random_bytes(6));
mkdir($root, 0700);
try {
    $binary = "$root/dump";
    // A faithful process boundary: credentials are read only from a private file,
    // stdout is the SQL snapshot, and argv contains no database password.
    file_put_contents($binary, '#!'.PHP_BINARY."\n". <<<'DUMP'
<?php
$options = substr($argv[1], strlen('--defaults-extra-file='));
if ((fileperms($options) & 0777) !== 0600 || !str_contains(file_get_contents($options), 'password="pa%ss$\"\\\\word"')) exit(7);
if (!in_array('--single-transaction', $argv, true) || !in_array('--skip-lock-tables', $argv, true)) exit(8);
echo "-- disposable database snapshot\nCREATE TABLE example (id INT);\n";
DUMP);
    chmod($binary, 0700);
    for ($i = 0; $i < 5; ++$i) ProductionPreparation::backup($env, $root, $binary);
    $backups = glob("$root/backups/*.sql");
    assertProduction(count($backups) === 3, 'Keep exactly three successful snapshots');
    assertProduction((fileperms("$root/backups") & 0777) === 0700, 'Backup directory is private');
    foreach ($backups as $backup) {
        assertProduction((fileperms($backup) & 0777) === 0600 && str_contains(file_get_contents($backup), 'CREATE TABLE'), 'SQL snapshots are private and complete');
    }
    file_put_contents($binary, "#!/bin/sh\nexit 1\n");
    try { ProductionPreparation::backup($env, $root, $binary); throw new LogicException('Expected failed dump rejection'); }
    catch (RuntimeException) {}
    assertProduction(glob("$root/backups/*.sql") === $backups, 'A failed backup preserves the previous good snapshots');
    assertProduction(count(glob("$root/backups/.*")) === 2, 'Transient password and partial dump files are removed');
} finally {
    HostingDeployer::removeTree($root);
}
echo "Production preparation checks passed: required SMTP/sender/HTTPS/secret, private dump options, bounded snapshots and fail-closed backup cleanup.\n";
