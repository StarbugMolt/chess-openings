<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Hand-authored finite introductions. Later corrections publish a NEW revision/migration. */
final class Version20261015010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Publish beginner Italian and London introductions with validated imported paths';
    }

    public function up(Schema $schema): void
    {
        $italian = [
            ['e4', 'Move the pawn in front of your king from e2 to e4.', 'The pawn takes space in the centre and opens a path for your bishop.'],
            ['e5', 'Move the pawn in front of your king from e7 to e5.', 'Black also takes space in the centre.'],
            ['Nf3', 'Move your knight from g1 to f3.', 'A knight comes towards the centre and attacks the pawn on e5.'],
            ['Nc6', 'Move your knight from b8 to c6.', 'The knight develops and protects the pawn on e5.'],
            ['Bc4', 'Move your bishop from f1 to c4.', 'The bishop develops to an active diagonal. This is the characteristic Italian setup.'],
            ['Bc5', 'Move your bishop from f8 to c5.', 'Black develops a bishop too. Both sides are bringing pieces into play.'],
        ];
        $courses = [
            ['italian', 'Italian Game', 'Take space in the centre, bring out a knight and bishop, then prepare your next central move. You will play one short, fixed line. The other side replies automatically.', [
                ['centre-plan', 'Prepare the centre', 'Your first line: build towards a later d-pawn advance.', [...$italian,
                    ['c3', 'Move your pawn from c2 to c3.', 'The pawn on c3 supports a later d2–d4 advance. You have reached the end of this short introduction.'],
                    ['Nf6', 'Move your knight from g8 to f6.', 'Black develops another knight and attacks the pawn on e4. This is the stopping point for this lesson.'],
                ]],
                ['quiet-plan', 'A quieter setup', 'An alternative: support the centre with your d-pawn.', [...$italian,
                    ['d3', 'Move your pawn from d2 to d3.', 'The d-pawn supports e4 and opens a path for the other bishop. This is a different taught line, not a chess mistake.'],
                    ['Nf6', 'Move your knight from g8 to f6.', 'Black develops another knight towards the centre. This is the stopping point for this lesson.'],
                ]],
            ]],
            ['london', 'London System', 'Learn a small London setup: a central pawn, a knight, then your bishop outside the pawn chain. This introduction shows one response with Black preparing a bishop on g7; opponents can choose other moves.', [
                ['first-setup', 'Your first London setup', 'Four moves for each side, one step at a time.', [
                    ['d4', 'Move your pawn from d2 to d4.', 'The pawn takes space in the centre and opens a path for your dark-squared bishop.'],
                    ['Nf6', 'Move your knight from g8 to f6.', 'Black brings a knight towards the centre.'],
                    ['Nf3', 'Move your knight from g1 to f3.', 'Your knight develops and helps control the centre.'],
                    ['g6', 'Move your pawn from g7 to g6.', 'Black prepares a home on g7 for the bishop.'],
                    ['Bf4', 'Move your bishop from c1 to f4.', 'Bring the bishop out before playing e3, which would close its diagonal. This is a familiar London idea.'],
                    ['Bg7', 'Move your bishop from f8 to g7.', 'Black develops the bishop along the long diagonal.'],
                    ['e3', 'Move your pawn from e2 to e3.', 'The pawn supports d4 and opens a path for your other bishop.'],
                    ['d6', 'Move your pawn from d7 to d6.', 'Black supports the centre. You have reached the end of this short London introduction.'],
                ]],
            ]],
        ];
        $graph = new LessonGraphV1($this->connection);
        foreach ($courses as $order => [$id, $title, $intro, $lines]) {
            $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [LessonGraphV1::paths()[$id.'/1/'.$lines[0][0]][0]['fromKey']]);
            $resolved = [];
            foreach ($lines as [$lineId, $lineTitle, $description, $steps]) {
                $path = $id.'/1/'.$lineId;
                if (array_column($steps, 0) !== array_column(LessonGraphV1::paths()[$path], 'san')) {
                    throw new \LogicException('Teaching text and frozen path disagree.');
                }
                $resolved[$lineId] = $graph->imported($path);
            }
            $this->addSql('INSERT INTO lesson (id,current_revision,sort_order) VALUES (?,NULL,?)', [$id, $order]);
            $this->addSql('INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at) VALUES (?,1,?,?,?,UTC_TIMESTAMP())', [$id, $title, $intro, $start]);
            foreach ($lines as $lineOrder => [$lineId, $lineTitle, $description, $steps]) {
                $this->addSql('INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES (?,1,?,?,?,?,?)', [$id, $lineId, $lineTitle, $description, $lineOrder, count($steps)]);
                foreach ($steps as $index => [$san, $hint, $explanation]) {
                    $this->addSql('INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES (?,1,?,?,?,?,?)', [$id, $lineId, $index + 1, $resolved[$lineId][$index] ?? throw new \LogicException('Missing resolved lesson edge.'), $hint, $explanation]);
                }
            }
            $this->addSql('UPDATE lesson SET current_revision=1 WHERE id=?', [$id]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish another immutable lesson revision instead.');
    }
}
