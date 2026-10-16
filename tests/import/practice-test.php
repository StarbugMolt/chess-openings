<?php

declare(strict_types=1);

use App\Kernel;
use App\Service\Lesson\LessonReader;
use App\Service\Practice\AssessmentValidator;
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
$assert = static function (bool $condition, string $message) use (&$checks): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    ++$checks;
};
$get = static function (KernelBrowser $browser, string $path): array {
    $browser->request('GET', $path);

    return json_decode($browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
};
$post = static function (KernelBrowser $browser, string $path, array $body, bool $csrf = true) use ($get): array {
    $token = $csrf ? $get($browser, '/api/auth/csrf')['csrfToken'] : '';
    $browser->request('POST', $path, server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $token], content: json_encode((object) $body, JSON_THROW_ON_ERROR));

    return json_decode($browser->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
};
$practice = static function (KernelBrowser $browser) use ($get, $assert): array {
    $data = $get($browser, '/api/me/practice');
    $assert(200 === $browser->getResponse()->getStatusCode(), 'Private practice read failed.');
    $assert($browser->getResponse()->headers->hasCacheControlDirective('private') && $browser->getResponse()->headers->hasCacheControlDirective('no-store'), 'Private practice response could be shared.');

    return $data;
};
$find = static function (array $data, string $id, string $lineId, string $color): array {
    foreach ($data['courses'] as $course) {
        if ($course['id'] === $id) {
            foreach ($course['lines'] as $line) {
                if ($line['lineId'] === $lineId && $line['playingColor'] === $color) {
                    return $line;
                }
            }
        }
    }
    throw new RuntimeException('Expected practice identity absent.');
};
$assessment = static function (array $line, string $color): array {
    $decisions = [];
    foreach ($line['steps'] as $ply => $step) {
        if (explode(' ', $step['fromKey'])[1] === $color) {
            $decisions[] = ['ply' => $ply, 'attempts' => 1, 'hinted' => false];
        }
    }

    return ['version' => 1, 'usedHint' => false, 'usedRetry' => false, 'usedUndo' => false, 'decisions' => $decisions];
};
$nonce = 0;
$attempt = static function () use (&$nonce): string {
    return sprintf('e0000000-0000-4000-8000-%012x', ++$nonce);
};
$save = static function (KernelBrowser $browser, array $body, int $status) use ($post, $assert): void {
    $response = $post($browser, '/api/me/completions', $body);
    $assert($status === $browser->getResponse()->getStatusCode(), 'Unexpected completion HTTP '.$browser->getResponse()->getStatusCode().'; expected '.$status.'.');
    if (200 === $status) {
        $assert(['saved' => true] === $response, 'Completion success shape changed.');
    }
};

try {
    $process = proc_open([PHP_BINARY, 'bin/console', 'app:database:prepare', '--source-directory=/app/backend/tests/Fixtures/Lessons/source', '--no-interaction'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, '/app/backend', array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']));
    $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $assert(0 === proc_close($process), 'Curriculum bootstrap failed: '.$output);
    $assert(isset($db->createSchemaManager()->listTableColumns('user_line_completion')['assessment_json']), 'Approved permanent migration was not applied on fresh bootstrap.');
    $migratedDdl = $db->fetchAssociative('SHOW CREATE TABLE user_line_completion')['Create Table'];
    // Only this disposable DB recreates the pre-migration shape for compatibility.
    $db->executeStatement('ALTER TABLE user_line_completion DROP CONSTRAINT ck_completion_assessment, DROP COLUMN assessment_json');
    $assert(!isset($db->createSchemaManager()->listTableColumns('user_line_completion')['assessment_json']), 'Pre-column phase still has assessment storage.');
    $sourceBefore = $db->fetchAllAssociative('SELECT * FROM opening_catalogue_state');
    $curriculumBefore = $db->fetchAllAssociative('SELECT * FROM lesson_revision ORDER BY lesson_id,revision');
    $password = 'a long practice test password';
    foreach ([1, 2] as $id) {
        $db->insert('user_account', ['id' => $id, 'email' => 'practice'.$id.'@example.test', 'password' => password_hash($password, PASSWORD_ARGON2ID, ['memory_cost' => 8192, 'time_cost' => 1]), 'created_at' => gmdate('Y-m-d H:i:s'), 'verified_at' => gmdate('Y-m-d H:i:s')]);
    }
    $newClient = static fn (): KernelBrowser => new KernelBrowser(new Kernel('test', true));
    $public = $newClient();
    $get($public, '/api/me/practice?userId=1');
    $assert(401 === $public->getResponse()->getStatusCode(), 'Anonymous practice data was exposed.');
    $courses = $get($public, '/api/courses')['courses'];
    $assert(!$public->getResponse()->headers->has('Set-Cookie'), 'Public metadata created a private session.');
    $a = $newClient();
    $b = $newClient();
    foreach ([$a, $b] as $index => $browser) {
        $post($browser, '/api/auth/login', ['email' => 'practice'.($index + 1).'@example.test', 'password' => $password]);
        $assert(200 === $browser->getResponse()->getStatusCode(), 'Fixture login failed.');
    }
    $empty = $practice($a);
    $assert(count($courses) === count($empty['courses']) && [] === $empty['reviewQueue'] && [] === $empty['history'], 'Empty account invented practice or lost current courses.');
    $reader = new LessonReader($db);
    $lessons = [];
    foreach ($courses as $index => $course) {
        $lesson = $reader->lesson($course['id']);
        $lessons[$course['id']] = $lesson;
        $actual = $empty['courses'][$index];
        $assert($course['id'] === $actual['id'] && $course['title'] === $actual['title'] && $course['revision'] === $actual['revision'] && $course['recommendedColor'] === $actual['recommendedColor'], 'Private/public current metadata mismatch.');
        $assert(count($lesson['lines']) === $actual['lineCount'] && 2 * count($lesson['lines']) === count($actual['lines']), 'Line denominator or both-colour rows are wrong.');
        foreach ($lesson['lines'] as $line) {
            foreach (['w', 'b'] as $color) {
                $fresh = $find($empty, $course['id'], $line['id'], $color);
                $assert(0 === $fresh['completions'] && null === $fresh['nextReviewAt'] && !$fresh['reviewDue'], 'Unpractised context was overdue.');
            }
        }
    }
    $italian = $lessons['italian'];
    [$main, $quiet] = $italian['lines'];
    $body = ['attemptId' => $attempt(), 'lessonId' => 'italian', 'revision' => $italian['revision'], 'lineId' => $main['id'], 'playingColor' => 'w', 'hintCount' => 0, 'path' => array_column($main['steps'], 'uci')];
    $save($a, $body, 200);
    $save($a, $body, 200);
    $assert(1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion'), 'Pre-column legacy retry double-counted.');
    $legacy = $find($practice($a), 'italian', $main['id'], 'w');
    $assert(1 === $legacy['completions'] && 0 === $legacy['assessedCompletions'] && 'unknown' === $legacy['lastOutcome'] && $legacy['reviewDue'], 'Pre-column exposure was not unknown/due.');
    $assert(0 === array_sum(array_column($legacy['positions'], 'practices')), 'Legacy data fabricated position evidence.');
    $foreign = $get($b, '/api/me/practice?userId=1');
    $assert(0 === $find($foreign, 'italian', $main['id'], 'w')['completions'], 'Another user selected the private owner.');

    $db->executeStatement(trim(preg_replace('/^--.*$/m', '', file_get_contents('/app/docs/M5B_SCHEMA_PROPOSAL.sql'))));
    $assert($migratedDdl === $db->fetchAssociative('SHOW CREATE TABLE user_line_completion')['Create Table'], 'Proposal and permanent migration differ.');
    $assert(null === $db->fetchOne('SELECT assessment_json FROM user_line_completion'), 'Migration inferred old recall.');
    $measured = [...$body, 'attemptId' => $attempt(), 'assessment' => $assessment($main, 'w')];
    $post($a, '/api/me/completions', $measured, false);
    $assert(403 === $a->getResponse()->getStatusCode(), 'Assessed completion bypassed CSRF.');
    $save($a, [...$measured, 'userId' => 2], 200);
    $stored = $db->fetchOne('SELECT assessment_json FROM user_line_completion WHERE user_id=1 AND attempt_id=?', [$measured['attemptId']]);
    $assert((new AssessmentValidator())->normalize($measured['assessment'], $main['steps'], 'w', 0) === $stored, 'Assessment was not normalized or owner-scoped.');
    $reordered = $measured;
    $reordered['assessment']['decisions'] = array_reverse($reordered['assessment']['decisions']);
    $save($a, $reordered, 200);
    $save($a, [...$measured, 'assessment' => [...$measured['assessment'], 'usedUndo' => true]], 409);
    $save($a, [...$body, 'assessment' => $measured['assessment']], 409);
    $withoutAssessment = $measured;
    unset($withoutAssessment['assessment']);
    $save($a, $withoutAssessment, 200);
    $save($a, [...$measured, 'mode' => 'variation', 'selectedLineIds' => [$main['id']]], 200);
    $assert($stored === $db->fetchOne('SELECT assessment_json FROM user_line_completion WHERE user_id=1 AND attempt_id=?', [$measured['attemptId']]), 'Legacy retry erased measured evidence.');
    foreach ([null, [], [...$measured['assessment'], 'version' => 2], [...$measured['assessment'], 'usedRetry' => true], [...$measured['assessment'], 'extra' => true], [...$measured['assessment'], 'decisions' => []], [...$measured['assessment'], 'decisions' => [['ply' => 1, 'attempts' => 1, 'hinted' => false]]]] as $invalid) {
        $save($a, [...$measured, 'assessment' => $invalid], 400);
    }
    $save($a, [...$measured, 'hintCount' => 1, 'assessment' => [...$measured['assessment'], 'usedHint' => true]], 400);
    $save($b, $measured, 200);
    $assert(2 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion WHERE attempt_id=?', [$measured['attemptId']]), 'Separate-user attempt identity was not independent.');

    $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    $insert = static function (array $lesson, array $line, string $color, DateTimeImmutable $at, ?array $summary = null) use ($db, $attempt): void {
        $hints = ($summary['usedHint'] ?? false) ? max(1, count(array_filter($summary['decisions'], static fn (array $decision): bool => $decision['hinted']))) : 0;
        $db->insert('user_line_completion', ['user_id' => 1, 'attempt_id' => $attempt(), 'lesson_id' => $lesson['id'], 'revision' => $lesson['revision'], 'line_id' => $line['id'], 'playing_color' => $color, 'hint_count' => $hints, 'completed_at' => $at->format('Y-m-d H:i:s'), 'assessment_json' => null === $summary ? null : (new AssessmentValidator())->normalize($summary, $line['steps'], $color, $hints)]);
    };
    $start = $now->modify('-2 days');
    $insert($italian, $quiet, 'b', $start, $assessment($quiet, 'b'));
    $insert($italian, $quiet, 'b', $start->modify('+1 hour'), $assessment($quiet, 'b'));
    $early = $find($practice($a), 'italian', $quiet['id'], 'b');
    $assert($start->modify('+1 day')->format(DATE_ATOM) === $early['nextReviewAt'], 'Early clean practice postponed or advanced review.');
    $insert($italian, $quiet, 'b', $start->modify('+1 day'), $assessment($quiet, 'b'));
    $due = $find($practice($a), 'italian', $quiet['id'], 'b');
    $assert($start->modify('+4 days')->format(DATE_ATOM) === $due['nextReviewAt'] && !$due['reviewDue'], 'Due clean review did not advance to three days.');
    $assisted = [...$assessment($main, 'b'), 'usedHint' => true, 'usedRetry' => true];
    $assisted['decisions'][0]['hinted'] = true;
    $assisted['decisions'][0]['attempts'] = 2;
    $insert($italian, $main, 'b', $now->modify('-11 minutes'), $assisted);
    $black = $find($practice($a), 'italian', $main['id'], 'b');
    $assert('assisted' === $black['lastOutcome'] && $black['reviewDue'], 'Assisted Black practice was not due after ten minutes.');
    $assert(1 === $black['positions'][0]['hints'] && 1 === $black['positions'][0]['retries'] && 0 === $black['positions'][0]['recalled'] && 1 === $black['positions'][1]['recalled'], 'Black evidence leaked between decision contexts.');
    $caro = $lessons['caro-kann'];
    $caroLine = $caro['lines'][0];
    $tieAt = $now->modify('-2 hours');
    foreach ([[true, $italian, $quiet], [false, $caro, $caroLine]] as [$cleanFirst, $lesson, $line]) {
        $clean = $assessment($line, 'w');
        $assisted = [...$clean, 'usedUndo' => true, 'usedHint' => true];
        $insert($lesson, $line, 'w', $tieAt, $cleanFirst ? $clean : $assisted);
        $insert($lesson, $line, 'w', $tieAt, $cleanFirst ? $assisted : $clean);
    }
    $ties = $practice($a);
    $tieOne = $find($ties, 'italian', $quiet['id'], 'w');
    $tieTwo = $find($ties, 'caro-kann', $caroLine['id'], 'w');
    $assert('assisted' === $tieOne['lastOutcome'] && $tieOne['lastOutcome'] === $tieTwo['lastOutcome'] && $tieOne['nextReviewAt'] === $tieTwo['nextReviewAt'], 'Equal-second event order changed the conservative outcome.');
    $assert(2 === $tieOne['assessedCompletions'] && 1 === $tieOne['unassistedCompletions'] && 1 === $tieOne['positions'][0]['recalled'] && 0 === $tieOne['positions'][0]['hints'], 'Undo credited abandoned hints or independent position recall.');
    $old = $reader->lesson('italian', 1);
    $oldLine = $old['lines'][0];
    $insert($old, $oldLine, 'w', $now->modify('-3 days'));
    $insert($old, $oldLine, 'w', $now->modify('-3 days'), $assessment($oldLine, 'w'));
    $history = $practice($a);
    $assert(1 === count($history['history']) && 1 === $history['history'][0]['revision'] && 2 === $history['history'][0]['completions'] && 1 === $history['history'][0]['assessedCompletions'], 'Retained revision history was lost or reinterpreted.');
    $assert(2 === $find($history, 'italian', $main['id'], 'w')['completions'], 'Old revision inflated current exposure.');
    foreach ($lessons as $lesson) {
        foreach ($lesson['lines'] as $line) {
            foreach (['w', 'b'] as $color) {
                $insert($lesson, $line, $color, $now->modify('-1 hour'));
            }
        }
    }
    $queued = $practice($a);
    $expectedQueue = [];
    $ordinal = 0;
    foreach ($queued['courses'] as $course) {
        foreach ($course['lines'] as $line) {
            if ($line['reviewDue']) {
                $expectedQueue[] = ['lessonId' => $course['id'], 'revision' => $course['revision'], 'lineId' => $line['lineId'], 'playingColor' => $line['playingColor'], 'due' => $line['nextReviewAt'], 'ordinal' => $ordinal];
            }
            ++$ordinal;
        }
    }
    usort($expectedQueue, static fn (array $a, array $b): int => [$a['due'], $a['ordinal']] <=> [$b['due'], $b['ordinal']]);
    $expectedQueue = array_map(static fn (array $row): array => array_intersect_key($row, array_flip(['lessonId', 'revision', 'lineId', 'playingColor'])), array_slice($expectedQueue, 0, 20));
    $assert($expectedQueue === $queued['reviewQueue'] && 20 === count($queued['reviewQueue']), 'Review queue cap or due/course/line/colour ordering changed.');
    $post($a, '/api/auth/logout', []);
    $assert(200 === $a->getResponse()->getStatusCode(), 'Logout failed.');
    $a = $newClient();
    $post($a, '/api/auth/login', ['email' => 'practice1@example.test', 'password' => $password]);
    $afterLogin = $practice($a);
    $assert($find($queued, 'italian', $main['id'], 'w') === $find($afterLogin, 'italian', $main['id'], 'w'), 'Practice did not survive a new authenticated browser session.');
    $assert([] === $practice($b)['history'], 'Another user read retained practice history.');
    $assert($sourceBefore === $db->fetchAllAssociative('SELECT * FROM opening_catalogue_state') && $curriculumBefore === $db->fetchAllAssociative('SELECT * FROM lesson_revision ORDER BY lesson_id,revision'), 'Practice changed source or curriculum.');
    $db->delete('user_account', ['id' => 1]);
    $assert(0 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion WHERE user_id=1') && 1 === (int) $db->fetchOne('SELECT COUNT(*) FROM user_line_completion WHERE user_id=2'), 'Account deletion failed to isolate private ledger cascade.');
    printf("Practice integration passed (%d checks): pre-column legacy API, approved schema parity, assessed atomic/idempotent writes, private current/history projections, review schedule/queue, exact decision contexts and logout/session persistence.\n", $checks);
} finally {
    $db->close();
}
