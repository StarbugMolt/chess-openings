<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Owner feedback: start the London against a direct central setup. Retain revision 1. */
final class Version20261015010100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Publish London revision 2 against d5/e6/Nf6, preserving pinned earlier sessions';
    }

    public function up(Schema $schema): void
    {
        $steps = [
            ['d4', 'Move your pawn from d2 to d4.', 'Your pawn takes space in the centre and opens a path for the bishop on c1.'],
            ['d5', 'Move your pawn from d7 to d5.', 'Black places a pawn in the centre too. Watch how both sides develop around these central pawns.'],
            ['Nf3', 'Move your knight from g1 to f3.', 'Bring a knight towards the centre. On f3 it helps control the important central squares.'],
            ['e6', 'Move your pawn from e7 to e6.', 'Black supports the pawn on d5 and opens a path for the bishop on f8.'],
            ['Bf4', 'Move your bishop from c1 to f4.', 'Bring this bishop out before playing e3, which would close its diagonal. The pawn on d4, knight on f3 and bishop on f4 form your first London setup.'],
            ['Nf6', 'Move your knight from g8 to f6.', 'Black develops a knight towards the centre. This short introduction ends here; the next ideas for White include e3 and developing more pieces.'],
        ];
        $edges = (new LessonGraphV1($this->connection))->imported('london/2/first-setup');
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at) SELECT lesson_id,2,title,?,start_position_id,UTC_TIMESTAMP() FROM lesson_revision WHERE lesson_id='london' AND revision=1", ['Start with the London against Black putting pawns on d5 and e6, then developing a knight to f6. You play three White moves to build the first shape: a central pawn, a knight and an active bishop. This is one short starting line, not a claim that Black always replies this way.']);
        $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('london',2,'first-setup','Your first London setup','Three moves for each side, with a direct central response from Black.',0,6)");
        foreach ($steps as $ply => [$san,$hint,$explanation]) {
            $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('london',2,'first-setup',?,?,?,?)", [$ply + 1, $edges[$ply] ?? throw new \LogicException(), $hint, $explanation]);
        }
        $this->addSql("UPDATE lesson SET current_revision=2 WHERE id='london'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Preserve authored revisions and completions.');
    }
}
