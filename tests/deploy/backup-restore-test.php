<?php
/** Run only with a uniquely created local Compose disposable database. */
declare(strict_types=1);
require __DIR__.'/../../scripts/deploy/production.php';
require __DIR__.'/../../scripts/o2switch/deploy.php';
$name = getenv('MIGRATION_TEST_DATABASE') ?: '';
if (!preg_match('/^chess_migration_test_[0-9_]+$/D', $name)) throw new RuntimeException('A disposable local migration database is required.');
$db = new PDO('mysql:host=db;dbname='.$name.';charset=utf8mb4', 'chess', 'local-chess-only', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$root = sys_get_temp_dir().'/chess-backup-restore-'.bin2hex(random_bytes(6));
mkdir($root, 0700);
try {
    $db->exec('CREATE TABLE backup_probe (id INT PRIMARY KEY, note VARCHAR(100)) ENGINE=InnoDB');
    $db->exec("INSERT INTO backup_probe VALUES (1, 'snapshot value')");
    $snapshot = ProductionPreparation::backup(['DATABASE_URL' => 'mysql://chess:local-chess-only@db:3306/'.$name], $root);
    $db->exec("UPDATE backup_probe SET note = 'changed after snapshot'");
    $options = "$root/restore.cnf";
    file_put_contents($options, "[client]\nhost=db\nuser=chess\npassword=local-chess-only\nprotocol=tcp\n");
    chmod($options, 0600);
    $process = proc_open(['mariadb', '--defaults-extra-file='.$options, '--', $name], [0 => ['file', $snapshot, 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    if (!is_resource($process) || proc_close($process) !== 0 || $db->query('SELECT note FROM backup_probe WHERE id = 1')->fetchColumn() !== 'snapshot value') {
        throw new RuntimeException('A real MariaDB snapshot must restore the original row.');
    }
    echo "MariaDB backup/restore passed on a disposable database: real private dump and recovered original row.\n";
} finally {
    HostingDeployer::removeTree($root);
}
