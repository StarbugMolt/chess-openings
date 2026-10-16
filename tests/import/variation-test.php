<?php

declare(strict_types=1);

use App\Kernel;
use App\Service\Lesson\LessonReader;
use App\Service\OpeningImport\PChessMoveReplayer;
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
$environment = array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']);
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void {
    if (!$ok) {
        throw new RuntimeException($message);
    }
    ++$checks;
};
$cli = static function (array $args) use ($environment, $assert): void {
    $process = proc_open([PHP_BINARY, 'bin/console', ...$args, '--no-interaction'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/app/backend', $environment);
    $out = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $assert(0 === proc_close($process), 'Console failed: '.$out);
};
$client = static fn (): KernelBrowser => new KernelBrowser(new Kernel('test', true));
$get = static function (KernelBrowser $browser, string $path): array {
    $browser->request('GET', $path);

    return json_decode($browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
};
$post = static function (KernelBrowser $browser, string $path, array $body, bool $csrf = true) use ($get): array {
    $token = $csrf ? $get($browser, '/api/auth/csrf')['csrfToken'] : '';
    $browser->request('POST', $path, server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $token], content: json_encode((object) $body, JSON_THROW_ON_ERROR));

    return json_decode($browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
};
$attemptNumber = 0;
$attempt = static function () use (&$attemptNumber): string {
    return sprintf('%08x-0000-4000-8000-000000000000', ++$attemptNumber);
};
$expect = static function (KernelBrowser $browser, array $body, int $status, string $message, bool $csrf = true) use ($post, $assert): array {
    $response = $post($browser, '/api/me/completions', $body, $csrf);
    $assert($status === $browser->getResponse()->getStatusCode(), $message.' (HTTP '.$browser->getResponse()->getStatusCode().')');
    if (200 === $status) {
        $assert(['saved' => true] === $response, 'Unexpected completion response.');
        $assert($browser->getResponse()->headers->hasCacheControlDirective('private') && $browser->getResponse()->headers->hasCacheControlDirective('no-store'), 'Completion response could be shared.');
    }

    return $response;
};

try {
    $cli(['app:database:prepare', '--source-directory=/app/backend/tests/Fixtures/Lessons/source']);
    $public = $client();
    $expect($public, [], 401, 'Anonymous variation completion allowed.', false);
    $password = 'a long variation test password';
    foreach ([1, 2] as $id) {
        $db->insert('user_account', ['id' => $id, 'email' => 'variation'.$id.'@example.test', 'password' => password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 8192, 'time_cost' => 1]), 'created_at' => gmdate('Y-m-d H:i:s'), 'verified_at' => gmdate('Y-m-d H:i:s')]);
    }
    $a = $client();
    $b = $client();
    foreach ([$a, $b] as $index => $browser) {
        $post($browser, '/api/auth/login', ['email' => 'variation'.($index + 1).'@example.test', 'password' => $password]);
        $assert(200 === $browser->getResponse()->getStatusCode(), 'Disposable test account login failed.');
    }
    $lesson = $get($a, '/api/lessons/italian')['lesson'];
    [$main, $quiet] = $lesson['lines'];
    $mainPath = array_column($main['steps'], 'uci');
    $quietPath = array_column($quiet['steps'], 'uci');
    $body = ['attemptId' => $attempt(), 'lessonId' => 'italian', 'revision' => $lesson['revision'], 'lineId' => $main['id'], 'playingColor' => 'w', 'hintCount' => 1, 'path' => $mainPath];
    $expect($a, $body, 403, 'Completion lost its CSRF requirement.', false);
    $expect($a, $body, 200, 'Existing guided request without mode stopped working.');
    $expect($a, [...$body, 'attemptId' => $attempt(), 'mode' => 'guided'], 200, 'Explicit guided mode failed.');
    foreach (['unknown', null, false] as $mode) {
        $expect($a, [...$body, 'mode' => $mode], 400, 'Unknown completion mode accepted.');
    }
    $variation = [...$body, 'attemptId' => $attempt(), 'mode' => 'variation', 'selectedLineIds' => [$main['id'], $quiet['id']]];
    $missingScope = $variation;
    unset($missingScope['selectedLineIds']);
    $expect($a, $missingScope, 400, 'Variation without selected lines accepted.');
    foreach ([null, 'all', [], ['line' => $main['id']], [1], [''], [str_repeat('a', 49)], [$main['id'], $main['id']], array_map(static fn (int $n): string => 'line-'.$n, range(1, 33)), [$main['id'], 'missing'], [$main['id'], 'first-setup']] as $scope) {
        $expect($a, [...$variation, 'selectedLineIds' => $scope], 400, 'Malformed or unknown selected scope accepted.');
    }
    $expect($a, [...$variation, 'selectedLineIds' => [$quiet['id']]], 400, 'Unselected credited line accepted.');
    $expect($a, [...$variation, 'userId' => 2], 200, 'Selected authored main path rejected.');
    $assert(1 === (int) $db->fetchOne('SELECT user_id FROM user_line_completion WHERE attempt_id=?', [$variation['attemptId']]), 'Caller chose another progress owner.');
    $beforeRetry = (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion');
    $expect($a, $variation, 200, 'Valid variation retry failed.');
    $assert($beforeRetry === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion'), 'Variation retry double counted.');
    $expect($a, [...$variation, 'selectedLineIds' => [$main['id']]], 200, 'Valid scope change altered existing line-practice idempotency.');
    $expect($a, [...$variation, 'mode' => 'guided'], 200, 'Mode change altered existing line-practice idempotency.');
    $expect($a, [...$variation, 'hintCount' => 2], 409, 'Attempt identity reused with different progress facts.');
    $expect($a, [...$variation, 'attemptId' => $attempt(), 'lineId' => $quiet['id'], 'path' => $quietPath], 200, 'Selected alternative response rejected.');
    $expect($a, [...$variation, 'path' => $quietPath], 400, 'Alternative was credited as an unplayed target line.');
    $upstreamOnly = $mainPath;
    $upstreamOnly[6] = 'e1g1';
    $assert(false !== $db->fetchOne('SELECT id FROM chess_move WHERE uci=? AND from_position_id=(SELECT from_position_id FROM chess_move WHERE id=(SELECT chess_move_id FROM lesson_line_step WHERE lesson_id=? AND revision=? AND line_id=? AND ply=7))', ['e1g1', 'italian', $lesson['revision'], $main['id']]), 'Upstream-only castling fixture is absent.');
    $expect($a, [...$variation, 'path' => $upstreamOnly], 400, 'A source-only continuation was credited.');
    foreach ([[], array_slice($mainPath, 0, -1), array_fill(0, 65, 'g1f3'), array_fill(0, 64, 'g1f3'), ['first' => 'e2e4'], ['e2e4', 12]] as $path) {
        $expect($a, [...$variation, 'path' => $path], 400, 'Malformed, incomplete or overlong path accepted.');
    }
    $expect($a, [...$variation, 'revision' => '1'], 400, 'Untyped lesson revision accepted.');
    $expect($a, [...$variation, 'hintCount' => 65], 400, 'Unbounded hint count accepted.');
    $expect($a, [...$variation, 'revision' => 99], 404, 'Unknown revision silently reinterpreted.');
    $expect($a, [...$variation, 'lineId' => 'missing', 'selectedLineIds' => ['missing']], 400, 'Unknown target line accepted.');

    $caro = $get($a, '/api/lessons/caro-kann')['lesson'];
    [$shortCaro, $longCaro] = $caro['lines'];
    $black = [...$variation, 'attemptId' => $attempt(), 'lessonId' => 'caro-kann', 'revision' => $caro['revision'], 'playingColor' => 'b', 'lineId' => $longCaro['id'], 'path' => array_column($longCaro['steps'], 'uci'), 'selectedLineIds' => [$shortCaro['id'], $longCaro['id']]];
    $expect($a, $black, 200, 'Complete Black variation practice rejected.');
    $assert('b' === $db->fetchOne('SELECT playing_color FROM user_line_completion WHERE user_id=1 AND attempt_id=?', [$black['attemptId']]), 'Black practice color changed.');
    $shortBlack = [...$black, 'attemptId' => $attempt(), 'lineId' => $shortCaro['id'], 'path' => array_column($shortCaro['steps'], 'uci')];
    $expect($a, $shortBlack, 200, 'A divergent longer Caro-Kann path prevented its short authored branch ending.');
    $expect($a, [...$shortBlack, 'selectedLineIds' => [$shortCaro['id']]], 200, 'A single selected short Caro-Kann line cannot end.');

    // Build genuinely transposing paths only inside this disposable database. Their
    // shared position must not let path A acquire path B's incompatible suffix.
    $db->transactional(static function () use ($db, $assert): void {
        $replayer = new PChessMoveReplayer();
        $paths = [
            'short-a' => ['Nf3', 'Nf6', 'g3', 'g6'],
            'long-a' => ['Nf3', 'Nf6', 'g3', 'g6', 'Bg2', 'Bg7'],
            'long-b' => ['g3', 'g6', 'Nf3', 'Nf6', 'd3', 'd6'],
        ];
        foreach (range(1, 32) as $number) {
            $paths[sprintf('choice-%02d', $number)] = $paths['short-a'];
        }
        $position = static function (string $key) use ($db): int {
            $existing = $db->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [$key]);
            if (false !== $existing) {
                return (int) $existing;
            }
            $db->insert('chess_position', ['position_key' => $key]);

            return (int) $db->lastInsertId();
        };
        $initial = $replayer->replay([]);
        $db->insert('lesson', ['id' => 'variation-fixture', 'current_revision' => null, 'sort_order' => 100]);
        $db->insert('lesson_revision', ['lesson_id' => 'variation-fixture', 'revision' => 1, 'title' => 'Disposable transposition fixture', 'introduction' => 'Synthetic test only.', 'start_position_id' => $position($initial->initialKey), 'published_at' => gmdate('Y-m-d H:i:s'), 'recommended_color' => 'w']);
        foreach ($paths as $id => $sans) {
            $replayed = $replayer->replay($sans);
            $db->insert('lesson_line', ['lesson_id' => 'variation-fixture', 'revision' => 1, 'id' => $id, 'title' => $id, 'description' => 'Synthetic test only.', 'sort_order' => 1, 'ply_count' => count($sans)]);
            $from = $position($replayed->initialKey);
            foreach ($replayed->moves as $index => $move) {
                $to = $position($move->key);
                $moveId = $db->fetchOne('SELECT id FROM chess_move WHERE from_position_id=? AND uci=?', [$from, $move->uci]);
                if (false === $moveId) {
                    $db->insert('chess_move', ['from_position_id' => $from, 'to_position_id' => $to, 'uci' => $move->uci, 'san' => $move->san]);
                    $moveId = $db->lastInsertId();
                }
                $db->insert('lesson_line_step', ['lesson_id' => 'variation-fixture', 'revision' => 1, 'line_id' => $id, 'ply' => $index + 1, 'chess_move_id' => $moveId, 'hint' => 'Synthetic test hint.', 'explanation' => 'Synthetic test explanation.']);
                $from = $to;
            }
        }
        $assert($replayer->replay($paths['long-a'])->moves[3]->key === $replayer->replay($paths['long-b'])->moves[3]->key, 'Synthetic paths do not really transpose.');
        $db->update('lesson', ['current_revision' => 1], ['id' => 'variation-fixture']);
    });
    $fixture = (new LessonReader($db))->lesson('variation-fixture');
    $paths = [];
    foreach ($fixture['lines'] as $line) {
        $paths[$line['id']] = array_column($line['steps'], 'uci');
    }
    $prefix = [...$variation, 'attemptId' => $attempt(), 'lessonId' => 'variation-fixture', 'revision' => 1, 'lineId' => 'short-a', 'path' => $paths['short-a'], 'selectedLineIds' => ['short-a', 'long-a']];
    $expect($a, $prefix, 400, 'Synthetic selected prefix stopped despite a compatible continuation.');
    $expect($a, [...$prefix, 'selectedLineIds' => ['short-a']], 200, 'Isolated prefix line did not end.');
    $expect($a, $prefix, 400, 'A saved attempt bypassed fresh selected-prefix validation on retry.');
    $expect($a, [...$prefix, 'attemptId' => $attempt(), 'selectedLineIds' => ['short-a', 'long-b']], 200, 'A transposed but prefix-incompatible line prevented completion.');
    foreach (['long-a', 'long-b'] as $id) {
        $expect($a, [...$prefix, 'attemptId' => $attempt(), 'lineId' => $id, 'path' => $paths[$id], 'selectedLineIds' => ['short-a', 'long-a', 'long-b']], 200, 'A selected complete transposing path was rejected.');
    }
    $stitched = [...$paths['short-a'], ...array_slice($paths['long-b'], 4)];
    $expect($a, [...$prefix, 'lineId' => 'long-a', 'path' => $stitched, 'selectedLineIds' => ['long-a', 'long-b']], 400, 'Spliced transposition suffix was credited.');
    $expect($a, [...$prefix, 'attemptId' => $attempt(), 'selectedLineIds' => ['short-a'], 'hintCount' => 64], 200, 'Hints from an undone longer branch invalidated a shorter variation endpoint.');
    $expect($a, [...$prefix, 'mode' => 'guided', 'hintCount' => 5], 400, 'Guided hint/path bound changed.');
    $allChoices = array_map(static fn (int $n): string => sprintf('choice-%02d', $n), range(1, 32));
    $expect($a, [...$prefix, 'attemptId' => $attempt(), 'lineId' => $allChoices[0], 'selectedLineIds' => $allChoices], 200, 'Maximum 32 valid selected lines rejected.');

    // Publication and catalogue changes cannot rewrite a revision-pinned attempt.
    $nextRevision = $lesson['revision'] + 1;
    $db->transactional(static function () use ($db, $lesson, $nextRevision, $main): void {
        $db->executeStatement("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) SELECT lesson_id,?,title,introduction,start_position_id,published_at,recommended_color FROM lesson_revision WHERE lesson_id='italian' AND revision=?", [$nextRevision, $lesson['revision']]);
        $db->executeStatement("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) SELECT lesson_id,?,id,title,description,sort_order,ply_count FROM lesson_line WHERE lesson_id='italian' AND revision=?", [$nextRevision, $lesson['revision']]);
        $db->executeStatement("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) SELECT lesson_id,?,line_id,ply,chess_move_id,hint,explanation FROM lesson_line_step WHERE lesson_id='italian' AND revision=?", [$nextRevision, $lesson['revision']]);
        $db->executeStatement("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) SELECT lesson_id,revision,'revision-next-only',title,description,sort_order,ply_count FROM lesson_line WHERE lesson_id='italian' AND revision=? AND id=?", [$nextRevision, $main['id']]);
        $db->executeStatement("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) SELECT lesson_id,revision,'revision-next-only',ply,chess_move_id,hint,explanation FROM lesson_line_step WHERE lesson_id='italian' AND revision=? AND line_id=?", [$nextRevision, $main['id']]);
        $db->update('lesson', ['current_revision' => $nextRevision], ['id' => 'italian']);
    });
    $expect($a, [...$variation, 'attemptId' => $attempt()], 200, 'Retained old revision variation rejected after publication.');
    $expect($a, [...$variation, 'selectedLineIds' => [$main['id'], 'revision-next-only']], 400, 'Scope from a different revision was silently accepted.');
    $assert($nextRevision === $get($a, '/api/lessons/italian')['lesson']['revision'], 'New navigation missed the newly published revision.');
    $expect($a, [...$variation, 'attemptId' => $attempt(), 'revision' => $nextRevision, 'lineId' => 'revision-next-only', 'selectedLineIds' => ['revision-next-only']], 200, 'A scoped path from the new exact revision was rejected.');
    $db->executeStatement('UPDATE opening_catalogue_state SET catalogue_revision=catalogue_revision+1');
    $expect($a, $variation, 200, 'Catalogue refresh invalidated an authored variation retry.');
    $assert([] === $get($b, '/api/me/progress?userId=1')['progress'], 'Another user could read variation progress.');
    $expect($b, $variation, 200, 'Separate users could not reuse their own attempt identity.');
    $assert(2 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion WHERE attempt_id=?', [$variation['attemptId']]), 'Idempotency identity leaked between users.');
    $row = $db->fetchAssociative('SELECT * FROM user_line_completion WHERE user_id=1 AND attempt_id=?', [$variation['attemptId']]);
    $assert($lesson['revision'] === (int) $row['revision'] && !array_key_exists('path', $row) && !array_key_exists('mode', $row) && !array_key_exists('selected_line_ids', $row), 'Practice facts were reinterpreted or raw scope/logs persisted.');
    $db->delete('user_account', ['id' => 2]);
    $assert(0 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion WHERE user_id=2'), 'Account deletion left private variation practice.');
    printf("Variation integration passed (%d checks): selected scopes, guided compatibility, finite prefix endpoints, transposition anti-splicing, Black practice, CSRF/ownership/retries and retained revisions.\n", $checks);
} finally {
    $db->close();
}
