<?php

declare(strict_types=1);

use App\Kernel;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Dotenv\Dotenv;
use AppMigrations\Support\LessonGraphV1;

require '/app/backend/vendor/autoload.php';
require '/app/backend/migrations/Support/LessonGraphV1.php';
$database = getenv('MIGRATION_TEST_DATABASE');
if (!is_string($database) || !preg_match('/\Achess_migration_test_[0-9_]+\z/', $database)) { throw new RuntimeException('Disposable database required.'); }
$url = preg_replace('~/[^/?]*(?=\?|$)~', '/'.$database, (string) getenv('DATABASE_URL'));
putenv('DATABASE_URL='.$url); $_SERVER['DATABASE_URL'] = $_ENV['DATABASE_URL'] = $url;
(new Dotenv())->bootEnv('/app/backend/.env');
$db = DriverManager::getConnection((new DsnParser(['mysql' => 'pdo_mysql']))->parse($url));
$environment = array_merge(getenv(), ['DATABASE_URL' => $url, 'APP_ENV' => 'test', 'APP_DEBUG' => '1']);
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void { if (!$ok) throw new RuntimeException($message); ++$checks; };
$cli = static function (array $args) use ($environment,$assert): void {
    $process = proc_open([PHP_BINARY,'bin/console',...$args,'--no-interaction'],[1=>['pipe','w'],2=>['pipe','w']],$pipes,'/app/backend',$environment);
    $out = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    $assert(proc_close($process) === 0,'Console failed: '.$out);
};
$client = static fn (): KernelBrowser => new KernelBrowser(new Kernel('test',true));
$get = static function (KernelBrowser $client, string $path): array { $client->request('GET',$path); return json_decode($client->getResponse()->getContent(),true,flags:JSON_THROW_ON_ERROR); };
$post = static function (KernelBrowser $client, string $path, array $body, bool $csrf = true) use ($get): array {
    $token = $csrf ? $get($client,'/api/auth/csrf')['csrfToken'] : '';
    $client->request('POST',$path,server:['CONTENT_TYPE'=>'application/json','HTTP_X_CSRF_TOKEN'=>$token],content:json_encode((object)$body,JSON_THROW_ON_ERROR));
    return json_decode($client->getResponse()->getContent(),true,flags:JSON_THROW_ON_ERROR);
};
try {
    $full = '1' === getenv('LESSON_FULL_IMPORT');
    $cli(['app:database:prepare',...($full ? [] : ['--source-directory=/app/backend/tests/Fixtures/Lessons/source'])]);
    if ($full) { $assert(3865 === (int)$db->fetchOne('SELECT COUNT(*) FROM opening_entry'),'Full pinned bootstrap source was not imported.'); }
    $assert(10 === (int)$db->fetchOne('SELECT COUNT(*) FROM lesson'),'Fresh bootstrap did not publish courses.');
    $assert(46 === (int)$db->fetchOne('SELECT COUNT(*) FROM lesson_line') && 880 === (int)$db->fetchOne('SELECT COUNT(*) FROM lesson_line_step'),'Finite curriculum path count wrong.');
    $assert(19 === (int)$db->fetchOne('SELECT COUNT(*) FROM doctrine_lesson_versions'),'Lesson history missing.');
    $cli(['app:database:prepare','--source-directory=/missing/retained-catalogue']);
    $assert(1 === (int)$db->fetchOne('SELECT COUNT(*) FROM opening_import_batch') && 880 === (int)$db->fetchOne('SELECT COUNT(*) FROM lesson_line_step'),'Bootstrap rerun changed source or lessons.');
    $graphWriter = new LessonGraphV1($db);
    $counts = $db->fetchAllKeyValue('SELECT \'positions\',COUNT(*) FROM authored_chess_position UNION ALL SELECT \'moves\',COUNT(*) FROM authored_chess_move');
    $assert((int)$counts['positions'] > 0 && (int)$counts['moves'] > 0,'London continuation has no separate authored provenance.');
    $assert((int)$db->fetchOne('SELECT COUNT(DISTINCT authored_in) FROM authored_chess_move') === 1,'Imported lessons invented authored provenance.');
    $edges = $graphWriter->authored('london/3/first-setup','AppMigrations\\LessonData\\Version20261015010200');
    $assert(15 === count($edges) && $counts === $db->fetchAllKeyValue('SELECT \'positions\',COUNT(*) FROM authored_chess_position UNION ALL SELECT \'moves\',COUNT(*) FROM authored_chess_move'),'Authored graph rerun duplicated/relabelled facts.');
    $db->beginTransaction();
    try {
        // Remove one authored edge so the writer inserts it, then encounter a later conflict.
        // Its own transaction must undo that insertion and its provenance together.
        $authoredIds = array_map('intval',$db->fetchFirstColumn('SELECT move_id FROM authored_chess_move ORDER BY move_id'));
        $db->executeStatement("DELETE FROM lesson_line_step WHERE lesson_id='london' AND revision=3");
        $db->delete('authored_chess_move',['move_id'=>$authoredIds[0]]);
        $db->delete('chess_move',['id'=>$authoredIds[0]]);
        $db->update('chess_move',['san'=>'conflict'],['id'=>$authoredIds[array_key_last($authoredIds)]]);
        $before = $db->fetchAllKeyValue('SELECT \'facts\',COUNT(*) FROM chess_move UNION ALL SELECT \'provenance\',COUNT(*) FROM authored_chess_move');
        try { $graphWriter->authored('london/3/first-setup','AppMigrations\\LessonData\\Version20261015010200'); throw new LogicException('Conflicting authored facts were accepted.'); }
        catch (RuntimeException $e) { $assert(str_contains($e->getMessage(),'conflicts with'),'Wrong authored conflict error.'); }
        $assert($before === $db->fetchAllKeyValue('SELECT \'facts\',COUNT(*) FROM chess_move UNION ALL SELECT \'provenance\',COUNT(*) FROM authored_chess_move'),'A conflict left partial authored facts/provenance.');
    } finally { $db->rollBack(); }
    $db->beginTransaction();
    try {
        $db->executeStatement('UPDATE lesson SET current_revision=999 WHERE id=\'london\'');
        throw new RuntimeException('Dangling publication pointer accepted.');
    } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException) {
        $assert(true,'Publication FK rejects unknown revision.');
    } finally { $db->rollBack(); }
    // The global graph still contains the imported edges, but no longer suffices for authoring
    // if the corresponding pinned named-path membership is absent.
    $db->beginTransaction();
    try {
        $db->executeStatement('DELETE s FROM opening_entry_step s JOIN opening_entry e ON e.id=s.opening_entry_id WHERE BINARY e.original_pgn=?',["1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. c3 Nf6"]);
        try { $graphWriter->imported('italian/1/centre-plan'); throw new LogicException('Cumulative graph was accepted without pinned source membership.'); }
        catch (RuntimeException $e) { $assert(str_contains($e->getMessage(),'Incomplete pinned source'),'Wrong missing-membership failure.'); }
    } finally { $db->rollBack(); }
    $public = $client(); $courses = $get($public,'/api/courses');
    $assert(10 === count($courses['courses']) && $courses['courses'][0]['title'] === 'Italian Game','Public course entry point wrong.');
    $blackCourses = array_column(array_filter($courses['courses'], static fn(array $course): bool => $course['recommendedColor']==='b'),'id');
    $assert(['caro-kann','french','sicilian','kings-indian','slav'] === array_values($blackCourses),'Black defences were not discoverable.');
    $assert(!$public->getResponse()->headers->has('Set-Cookie'),'Public courses created a session.');
    $get($public,'/api/lessons/italian'); $assert(401 === $public->getResponse()->getStatusCode(),'Anonymous training allowed.');
    $get($public,'/api/me/progress'); $assert(401 === $public->getResponse()->getStatusCode(),'Anonymous progress allowed.');
    $post($public,'/api/me/completions',[],false); $assert(401 === $public->getResponse()->getStatusCode(),'Anonymous completion allowed.');
    $london = $get($public,'/api/openings?search=London');
    $assert(($full ? $london['total'] >= 2 : 2 === $london['total']) && count($london['items']) === $london['total'],'Identical London names were not grouped independently of the classical named path.');
    $londonName = array_values(array_filter($london['items'],static fn(array $row):bool=>$row['name']==='London System'))[0];
    $assert(3 === $londonName['sequenceCount'] && 3 === count($londonName['sequences']),'Source sequences were lost by grouping.');
    $assert($londonName['sequences'][0]['pgn'] !== $londonName['sequences'][2]['pgn'],'Different stopping depths not preserved.');
    $password = 'a long lesson test password';
    foreach ([1,2] as $id) { $db->insert('user_account',['id'=>$id,'email'=>'lesson'.$id.'@example.test','password'=>password_hash($password,PASSWORD_ARGON2ID,['memory_cost'=>8192,'time_cost'=>1]),'created_at'=>gmdate('Y-m-d H:i:s'),'verified_at'=>gmdate('Y-m-d H:i:s')]); }
    $a=$client(); $b=$client();
    foreach ([$a,$b] as $i=>$browser) { $post($browser,'/api/auth/login',['email'=>'lesson'.($i+1).'@example.test','password'=>$password]); $assert(200 === $browser->getResponse()->getStatusCode(),'Test account login failed.'); }
    $graph = $get($a,'/api/lessons/italian');
    $assert(3 === $graph['lesson']['revision'] && 1 === $graph['catalogueRevision'] && ['pianissimo-main-line','giuoco-piano-moller','two-knights-knorre'] === array_column($graph['lesson']['lines'],'id'),'Lesson identities or lines wrong.');
    $assert([17,27,23] === array_map(static fn(array $l): int => count($l['steps']),$graph['lesson']['lines']),'Complete named Italian lines were not published.');
    // Owner requirement: every current line teaches at least nine of the learner's own moves. London is the documented exception: the pinned catalogue has no deeper London record.
    $short = $db->fetchFirstColumn("SELECT CONCAT(l.id,'/',n.id) FROM lesson l JOIN lesson_revision r ON r.lesson_id=l.id AND r.revision=l.current_revision JOIN lesson_line n ON n.lesson_id=l.id AND n.revision=l.current_revision WHERE l.id<>'london' AND IF(r.recommended_color='w',CEIL(n.ply_count/2),FLOOR(n.ply_count/2))<9");
    $assert([] === $short,'Current lines are too short: '.implode(', ',$short));
    $assert($a->getResponse()->headers->hasCacheControlDirective('private') && $a->getResponse()->headers->hasCacheControlDirective('no-store'),'User-specific lesson response could be shared.');
    $assert([] === $graph['progress'],'Fresh user had invented progress.');
    $londonLesson = $get($a,'/api/lessons/london')['lesson'];
    $assert(3 === $londonLesson['revision'] && 15 === count($londonLesson['lines'][0]['steps']) && 'O-O' === $londonLesson['lines'][0]['steps'][14]['san'],'Full London through White castling was not published.');
    $caro = $get($a,'/api/lessons/caro-kann')['lesson'];
    $assert('b' === $caro['recommendedColor'] && 3 === $caro['revision'] && [24,19,19] === array_map(static fn(array $l): int => count($l['steps']),$caro['lines']) && 'O-O' === $caro['lines'][2]['steps'][18]['san'],'Black Caro-Kann lines unavailable.');
    $queensGambit = $get($a,'/api/lessons/queens-gambit')['lesson'];
    $assert(2 === $queensGambit['revision'] && [19,28,26] === array_map(static fn(array $l): int => count($l['steps']),$queensGambit['lines']) && 'Qxe5' === $queensGambit['lines'][1]['steps'][27]['san'],'Complete named Queen\'s Gambit lines were not published.');
    $newCourses = ['ruy-lopez'=>['w',[28,24,36],1],'catalan'=>['w',[23,20],1],'sicilian'=>['b',[18,24,20],1],'kings-indian'=>['b',[27,26],1],'slav'=>['b',[19,29],2]];
    foreach ($newCourses as $courseId => [$color,$lengths,$revision]) {
        $course = $get($a,'/api/lessons/'.$courseId)['lesson'];
        $assert($revision === $course['revision'] && $color === $course['recommendedColor'] && $lengths === array_map(static fn(array $l): int => count($l['steps']),$course['lines']),'Complete named lines missing for '.$courseId.'.');
    }
    $french = $get($a,'/api/lessons/french')['lesson'];
    $assert(3 === $french['revision'] && [20,18,20] === array_map(static fn(array $l): int => count($l['steps']),$french['lines']),'Complete named French lines were not published.');
    $line = $graph['lesson']['lines'][0];
    $body = ['attemptId'=>'01234567-89ab-4cde-8fab-0123456789ab','lessonId'=>'italian','revision'=>$graph['lesson']['revision'],'lineId'=>$line['id'],'playingColor'=>'w','hintCount'=>1,'path'=>array_column($line['steps'],'uci')];
    $post($a,'/api/me/completions',$body,false); $assert(403 === $a->getResponse()->getStatusCode(),'Completion lacked CSRF.');
    $bad=$body; $bad['path'][6]='d2d3'; $post($a,'/api/me/completions',$bad); $assert(400 === $a->getResponse()->getStatusCode(),'Alternative line credited as strict target.');
    $bad=$body; array_pop($bad['path']); $post($a,'/api/me/completions',$bad); $assert(400 === $a->getResponse()->getStatusCode(),'Partial path credited.');
    $bad=$body; $bad['revision']=99; $post($a,'/api/me/completions',$bad); $assert(404 === $a->getResponse()->getStatusCode(),'Unknown revision silently reinterpreted.');
    $bad=$body; $bad['lineId']='unknown'; $post($a,'/api/me/completions',$bad); $assert(400 === $a->getResponse()->getStatusCode(),'Unknown line credited.');
    $post($a,'/api/me/completions',[...$body,'userId'=>2]); $assert(200 === $a->getResponse()->getStatusCode(),'Valid completion failed.');
    $assert(1 === (int)$db->fetchOne('SELECT user_id FROM user_line_completion'),'Client chose progress owner.');
    $post($a,'/api/me/completions',$body); $assert(200 === $a->getResponse()->getStatusCode() && 1 === (int)$db->fetchOne('SELECT COUNT(*) FROM user_line_completion'),'Retry double-counted.');
    $post($a,'/api/me/completions',[...$body,'playingColor'=>'b']); $assert(409 === $a->getResponse()->getStatusCode(),'Attempt identity reused for another configuration.');
    $assert(1 === $get($a,'/api/me/progress')['progress'][0]['completions'],'Completion not durably aggregated.');
    $assert([] === $get($b,'/api/me/progress?userId=1')['progress'],'Second user could access first user progress.');
    $post($b,'/api/me/completions',$body); $assert(200 === $b->getResponse()->getStatusCode() && 2 === (int)$db->fetchOne('SELECT COUNT(*) FROM user_line_completion'),'Idempotency keys incorrectly shared between accounts.');
    // A new authored revision does not overwrite or reinterpret an attempt pinned to the previous revision.
    $db->executeStatement('INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at) SELECT lesson_id,4,title,introduction,start_position_id,published_at FROM lesson_revision WHERE lesson_id=\'italian\' AND revision=3');
    $db->executeStatement('INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) SELECT lesson_id,4,id,title,description,sort_order,ply_count FROM lesson_line WHERE lesson_id=\'italian\' AND revision=3');
    $db->executeStatement('INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) SELECT lesson_id,4,line_id,ply,chess_move_id,hint,explanation FROM lesson_line_step WHERE lesson_id=\'italian\' AND revision=3');
    $db->executeStatement("UPDATE lesson SET current_revision=4 WHERE id='italian'");
    $post($a,'/api/me/completions',[...$body,'attemptId'=>'11234567-89ab-4cde-8fab-0123456789ab','playingColor'=>'b']);
    $assert(200 === $a->getResponse()->getStatusCode(),'Retained old revision rejected during new publication.');
    $assert(4 === $get($a,'/api/lessons/italian')['lesson']['revision'],'New lesson navigation did not see new revision.');
    $assert(3 === (int)$db->fetchOne('SELECT revision FROM user_line_completion WHERE attempt_id=? AND user_id=1',[$body['attemptId']]),'Old progress changed revision.');
    $db->executeStatement('UPDATE opening_catalogue_state SET catalogue_revision=2');
    $post($a,'/api/me/completions',$body); $assert(200 === $a->getResponse()->getStatusCode(),'Catalogue update invalidated pinned authored path.');
    $assert(372 === (int)$db->fetchOne('SELECT COUNT(*) FROM lesson_line_step WHERE revision=1'),'Source change mutated authored steps.');
    $blackBody = [...$body,'attemptId'=>'21234567-89ab-4cde-8fab-0123456789ab','lessonId'=>'caro-kann','lineId'=>$caro['lines'][1]['id'],'playingColor'=>'b','path'=>array_column($caro['lines'][1]['steps'],'uci')];
    $post($a,'/api/me/completions',$blackBody);
    $assert(200 === $a->getResponse()->getStatusCode() && 'b' === $db->fetchOne('SELECT playing_color FROM user_line_completion WHERE attempt_id=?',[$blackBody['attemptId']]),'Black practice was not saved.');
    $db->delete('user_account',['id'=>2]);
    $assert(0 === (int)$db->fetchOne('SELECT COUNT(*) FROM user_line_completion WHERE user_id=2'),'Account removal did not cascade private completion.');
    // Simulate a future source adopting the approved continuation. This synthetic record is
    // confined to the disposable DB and must reuse graph IDs without erasing their authorship.
    $temporary = sys_get_temp_dir().'/chess_lesson_future_'.bin2hex(random_bytes(8));
    mkdir($temporary,0700);
    try {
        foreach (['a','b','c','d','e'] as $file) copy('/app/backend/tests/Fixtures/Lessons/source/'.$file.'.tsv',$temporary.'/'.$file.'.tsv');
        file_put_contents($temporary.'/d.tsv',"D02\tSynthetic future-source adoption fixture\t1. d4 d5 2. Nf3 Nf6 3. Bf4 e6 4. e3 c5 5. c3 Nc6 6. Nbd2 Be7 7. Bd3 O-O 8. O-O\n",FILE_APPEND);
        $beforeSteps = $db->fetchAllAssociative('SELECT * FROM lesson_line_step ORDER BY lesson_id,revision,line_id,ply');
        $beforeProgress = $db->fetchAllAssociative('SELECT * FROM user_line_completion ORDER BY user_id,attempt_id');
        $beforeProvenance = $db->fetchAllAssociative('SELECT * FROM authored_chess_move ORDER BY move_id');
        $cli(['app:openings:sync','--source-ref='.str_repeat('b',40),'--source-directory='.$temporary,'--force']);
        $assert($beforeSteps === $db->fetchAllAssociative('SELECT * FROM lesson_line_step ORDER BY lesson_id,revision,line_id,ply'),'A later source changed curated content.');
        $assert($beforeProgress === $db->fetchAllAssociative('SELECT * FROM user_line_completion ORDER BY user_id,attempt_id'),'A later source changed private practice.');
        $assert($beforeProvenance === $db->fetchAllAssociative('SELECT * FROM authored_chess_move ORDER BY move_id'),'Source adoption erased authored provenance.');
        $assert(count($beforeProvenance) === (int)$db->fetchOne('SELECT COUNT(DISTINCT a.move_id) FROM authored_chess_move a JOIN opening_entry_step s ON s.chess_move_id=a.move_id JOIN opening_entry e ON e.id=s.opening_entry_id JOIN opening_catalogue_state c ON c.active_batch_id=e.batch_id WHERE e.full_name=?',['Synthetic future-source adoption fixture']),'Future source did not reuse approved authored facts.');
    } finally { foreach (glob($temporary.'/*.tsv') ?: [] as $file) unlink($file); rmdir($temporary); }
    printf("Lesson integration passed (%d checks): fresh schema/import/authored bootstrap, source-name grouping, authenticated finite paths, CSRF/ownership/idempotency, pinned revisions and private progress.\n",$checks);
} finally { $db->close(); }
