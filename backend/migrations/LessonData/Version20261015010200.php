<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Owner-approved complete beginner setup. Old revisions remain immutable and playable in pinned sessions. */
final class Version20261015010200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Publish London revision 3: eight White moves through castling, with separate authored fact provenance';
    }

    public function up(Schema $schema): void
    {
        $steps = [
            ['d4', 'Move your pawn from d2 to d4.', 'Take space in the centre and open a path for the bishop on c1.'],
            ['d5', 'Move your pawn from d7 to d5.', 'Black occupies the centre too. The d-pawns now face each other.'],
            ['Nf3', 'Move your knight from g1 to f3.', 'Develop a knight towards the centre, where it helps control more squares.'],
            ['Nf6', 'Move your knight from g8 to f6.', 'Black develops a knight and keeps an eye on the central squares.'],
            ['Bf4', 'Move your bishop from c1 to f4.', 'Bring this bishop outside your pawn chain before playing e3, which would close its diagonal.'],
            ['e6', 'Move your pawn from e7 to e6.', 'Black supports the pawn on d5 and opens a path for the bishop on f8.'],
            ['e3', 'Move your pawn from e2 to e3.', 'Support your pawn on d4 and open a path for the bishop on f1.'],
            ['c5', 'Move your pawn from c7 to c5.', 'Black challenges the pawn on d4. This is pressure on the centre, not a random pawn move.'],
            ['c3', 'Move your pawn from c2 to c3.', 'Give the pawn on d4 another defender. Your c3, d4 and e3 pawns form a compact central structure.'],
            ['Nc6', 'Move your knight from b8 to c6.', 'Black develops another piece and adds pressure to d4.'],
            ['Nbd2', 'Move your knight from b1 to d2.', 'Bring your remaining knight into play. It supports f3 and leaves c3 available for your pawn.'],
            ['Be7', 'Move your bishop from f8 to e7.', 'Black clears f8 so the king can castle, without immediately challenging your bishop on f4.'],
            ['Bd3', 'Move your bishop from f1 to d3.', 'Develop your last bishop towards the centre. The squares between your king and rook are now clear for castling.'],
            ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black brings the king towards shelter and the rook towards the centre. Castling moves the king and rook together.'],
            ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Your king moves towards shelter and your rook joins the game. You have built the London setup and castled. This is an opening foundation, not the end of the game.'],
        ];
        $edges = (new LessonGraphV1($this->connection))->authored('london/3/first-setup', self::class);
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at) SELECT lesson_id,3,title,?,start_position_id,UTC_TIMESTAMP() FROM lesson_revision WHERE lesson_id='london' AND revision=1", ['Build a complete beginner London setup against a direct central response: develop both knights and bishops, support your centre with pawns, then castle. You play eight White moves; Black replies automatically along this one taught line. Other replies are possible in a real game.']);
        $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('london',3,'first-setup','Build the London and castle','Eight White moves: centre, development and king safety. One fixed teaching line against ...d5, ...Nf6 and ...e6.',0,15)");
        foreach ($steps as $ply => [$san,$hint,$explanation]) {
            $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('london',3,'first-setup',?,?,?,?)", [$ply + 1, $edges[$ply] ?? throw new \LogicException(), $hint, $explanation]);
        }
        $this->addSql("UPDATE lesson SET current_revision=3 WHERE id='london'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; do not erase earlier practice or provenance.');
    }
}
