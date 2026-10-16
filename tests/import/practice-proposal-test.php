<?php

declare(strict_types=1);

use App\Service\OpeningImport\PChessMoveReplayer;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

require '/app/backend/vendor/autoload.php';

$database = getenv('PRACTICE_PROPOSAL_DATABASE');
if (!is_string($database) || !preg_match('/\Achess_practice_proposal_[0-9_]+\z/', $database)) {
    throw new RuntimeException('A disposable proposal database is required.');
}
$url = preg_replace('~/[^/?]*(?=\?|$)~', '/'.$database, (string) getenv('DATABASE_URL'));
$db = DriverManager::getConnection((new DsnParser(['mysql' => 'pdo_mysql']))->parse($url));
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
};

try {
    // Schema only: independent in-progress curriculum never participates here.
    $process = proc_open([PHP_BINARY, 'bin/console', 'app:schema:migrate', '--no-interaction'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/app/backend', array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']));
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $assert(0 === proc_close($process), 'Baseline schema failed: '.$output);
    $migratedDdl = null;
    if (isset($db->createSchemaManager()->listTableColumns('user_line_completion')['assessment_json'])) {
        $migratedDdl = $db->fetchAssociative('SHOW CREATE TABLE user_line_completion')['Create Table'];
        // Recreate the pre-migration table only in this disposable DB, then
        // compare the exact reviewed ALTER with the permanent migration's DDL.
        $db->executeStatement('ALTER TABLE user_line_completion DROP CONSTRAINT ck_completion_assessment, DROP COLUMN assessment_json');
    }
    $db->insert('user_account', ['id' => 1, 'email' => 'proposal@example.test', 'password' => 'unused-disposable-fixture', 'created_at' => '2026-10-15 10:00:00', 'verified_at' => '2026-10-15 10:00:00']);
    $path = (new PChessMoveReplayer())->replay(['e4', 'e5']);
    $db->insert('chess_position', ['id' => 1, 'position_key' => $path->initialKey]);
    foreach ($path->moves as $index => $move) {
        $db->insert('chess_position', ['id' => $index + 2, 'position_key' => $move->key]);
        $db->insert('chess_move', ['id' => $index + 1, 'from_position_id' => $index + 1, 'to_position_id' => $index + 2, 'uci' => $move->uci, 'san' => $move->san]);
    }
    $db->insert('lesson', ['id' => 'proposal', 'current_revision' => null, 'sort_order' => 1]);
    $db->insert('lesson_revision', ['lesson_id' => 'proposal', 'revision' => 1, 'title' => 'Disposable proposal fixture', 'introduction' => 'Schema test only.', 'start_position_id' => 1, 'published_at' => '2026-10-15 10:00:00', 'recommended_color' => 'w']);
    $db->insert('lesson_line', ['lesson_id' => 'proposal', 'revision' => 1, 'id' => 'line', 'title' => 'Schema test only', 'description' => 'Schema test only.', 'sort_order' => 1, 'ply_count' => 2]);
    foreach ([1, 2] as $ply) {
        $db->insert('lesson_line_step', ['lesson_id' => 'proposal', 'revision' => 1, 'line_id' => 'line', 'ply' => $ply, 'chess_move_id' => $ply, 'hint' => 'Schema test only.', 'explanation' => 'Schema test only.']);
    }
    $db->update('lesson', ['current_revision' => 1], ['id' => 'proposal']);
    $row = ['user_id' => 1, 'attempt_id' => '00000000-0000-4000-8000-000000000000', 'lesson_id' => 'proposal', 'revision' => 1, 'line_id' => 'line', 'playing_color' => 'w', 'hint_count' => 0, 'completed_at' => '2026-10-15 10:00:00'];
    $db->insert('user_line_completion', $row);
    $before = [];
    foreach ($db->createSchemaManager()->listTableNames() as $table) {
        $before[$table] = $db->fetchAssociative('SHOW CREATE TABLE `'.$table.'`')['Create Table'];
    }
    $sql = preg_replace('/^--.*$/m', '', file_get_contents('/app/docs/M5B_SCHEMA_PROPOSAL.sql'));
    $db->executeStatement(trim($sql));
    $assert(null === $db->fetchOne('SELECT assessment_json FROM user_line_completion'), 'Existing history was assessed or lost.');
    $assert(array_keys($before) === $db->createSchemaManager()->listTableNames(), 'Proposal added or removed tables.');
    foreach ($before as $table => $ddl) {
        if ('user_line_completion' !== $table) {
            $assert($ddl === $db->fetchAssociative('SHOW CREATE TABLE `'.$table.'`')['Create Table'], 'Proposal altered another table: '.$table);
        }
    }
    $afterDdl = $db->fetchAssociative('SHOW CREATE TABLE user_line_completion')['Create Table'];
    if (null !== $migratedDdl) {
        $assert($migratedDdl === $afterDdl, 'Permanent migration differs from the approved proposal SQL.');
    }
    $withoutAssessment = preg_replace('/^  (?:`assessment_json`|CONSTRAINT `ck_completion_assessment`)[^\n]*\n/m', '', $afterDdl);
    $withoutAssessment = preg_replace('/,\n\) ENGINE/', "\n) ENGINE", $withoutAssessment);
    $assert($before['user_line_completion'] === $withoutAssessment, 'Existing completion columns, keys or constraints changed.');
    $valid = ['version' => 1, 'usedHint' => false, 'usedRetry' => false, 'usedUndo' => false, 'decisions' => [['ply' => 0, 'attempts' => 1, 'hinted' => false]]];
    $serial = 0;
    $insert = static function (?string $assessment) use ($db, $row, &$serial): void {
        $db->insert('user_line_completion', [...$row, 'attempt_id' => sprintf('%08x-0000-4000-8000-000000000000', ++$serial), 'assessment_json' => $assessment]);
    };
    $insert(null);
    $insert(json_encode($valid, JSON_THROW_ON_ERROR));
    $assert(3 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion'), 'Nullable legacy or valid measured summary rejected.');
    $thirtyTwo = [...$valid, 'decisions' => array_fill(0, 32, $valid['decisions'][0])];
    $insert(json_encode($thirtyTwo, JSON_THROW_ON_ERROR));
    $assert(4 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion'), '32-decision bound rejected.');
    $invalid = ['{invalid', '[]', 'null', 'true', '1', '{}'];
    foreach (array_keys($valid) as $key) {
        $missing = $valid;
        unset($missing[$key]);
        $invalid[] = json_encode($missing, JSON_THROW_ON_ERROR);
    }
    foreach ([2, '1', 1.0, null] as $version) {
        $invalid[] = json_encode([...$valid, 'version' => $version], JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }
    foreach (['usedHint', 'usedRetry', 'usedUndo'] as $flag) {
        foreach ([1, 'false', null] as $value) {
            $invalid[] = json_encode([...$valid, $flag => $value], JSON_THROW_ON_ERROR);
        }
    }
    foreach ([[], array_fill(0, 33, $valid['decisions'][0]), (object) ['ply' => 0], null] as $decisions) {
        $invalid[] = json_encode([...$valid, 'decisions' => $decisions], JSON_THROW_ON_ERROR);
    }
    $invalid[] = json_encode([...$valid, 'padding' => str_repeat('x', 4096)], JSON_THROW_ON_ERROR);
    foreach ($invalid as $candidate) {
        $previous = (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion');
        try {
            $insert($candidate);
            throw new RuntimeException('Invalid JSON/shape/bounds were accepted: '.$candidate);
        } catch (\Doctrine\DBAL\Exception) {
            $assert($previous === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion'), 'Rejected assessment left a row.');
        }
    }
    $assert(0 === (int) $db->fetchOne('SELECT COUNT(*) FROM opening_import_batch'), 'Proposal populated the source catalogue.');
    $db->delete('user_account', ['id' => 1]);
    $assert(0 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion'), 'Private assessments did not cascade with account deletion.');
    $assert(1 === (int) $db->fetchOne('SELECT COUNT(*) FROM lesson'), 'Account deletion changed curriculum.');
    printf("Practice schema proposal passed (%d checks): legacy NULL, bounded JSON/version/flags/decisions, unchanged source/curriculum/keys and account-delete cascade. Disposable database only.\n", $checks);
} finally {
    $db->close();
}
