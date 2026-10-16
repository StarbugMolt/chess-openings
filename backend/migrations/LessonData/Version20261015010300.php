<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Original teaching notes over frozen prefixes of two pinned imported source paths. */
final class Version20261015010300 extends AbstractMigration
{
    private const EXCHANGE_SOURCE = '1. e4 c6 2. d4 d5 3. exd5 cxd5 4. Bd3 Nc6 5. c3 Nf6 6. Bf4';
    private const PANOV_SOURCE = '1. e4 c6 2. d4 d5 3. exd5 cxd5 4. c4 Nc6 5. Nc3 Nf6 6. Nf3 e6 7. cxd5 exd5 8. Bb5 Bd6 9. Bg5 O-O 10. O-O';

    private const EXCHANGE_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/2p5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pp1ppppp/2p5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/2p5/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pp1ppppp/2p5/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'e4d5', 'san' => 'exd5', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3P4/3P4/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c6d5', 'san' => 'cxd5', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3P4/3P4/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/8/3p4/3P4/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'rnbqkbnr/pp2pppp/8/3p4/3P4/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/8/3p4/3P4/3B4/PPP2PPP/RNBQK1NR b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pp2pppp/8/3p4/3P4/3B4/PPP2PPP/RNBQK1NR b KQkq -', 'toKey' => 'r1bqkbnr/pp2pppp/2n5/3p4/3P4/3B4/PPP2PPP/RNBQK1NR w KQkq -'],
        ['uci' => 'c2c3', 'san' => 'c3', 'fromKey' => 'r1bqkbnr/pp2pppp/2n5/3p4/3P4/3B4/PPP2PPP/RNBQK1NR w KQkq -', 'toKey' => 'r1bqkbnr/pp2pppp/2n5/3p4/3P4/2PB4/PP3PPP/RNBQK1NR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/pp2pppp/2n5/3p4/3P4/2PB4/PP3PPP/RNBQK1NR b KQkq -', 'toKey' => 'r1bqkb1r/pp2pppp/2n2n2/3p4/3P4/2PB4/PP3PPP/RNBQK1NR w KQkq -'],
    ];

    private const PANOV_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/2p5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pp1ppppp/2p5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/2p5/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pp1ppppp/2p5/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'e4d5', 'san' => 'exd5', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3P4/3P4/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c6d5', 'san' => 'cxd5', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3P4/3P4/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/8/3p4/3P4/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkbnr/pp2pppp/8/3p4/3P4/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/8/3p4/2PP4/8/PP3PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pp2pppp/8/3p4/2PP4/8/PP3PPP/RNBQKBNR b KQkq -', 'toKey' => 'r1bqkbnr/pp2pppp/2n5/3p4/2PP4/8/PP3PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r1bqkbnr/pp2pppp/2n5/3p4/2PP4/8/PP3PPP/RNBQKBNR w KQkq -', 'toKey' => 'r1bqkbnr/pp2pppp/2n5/3p4/2PP4/2N5/PP3PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/pp2pppp/2n5/3p4/2PP4/2N5/PP3PPP/R1BQKBNR b KQkq -', 'toKey' => 'r1bqkb1r/pp2pppp/2n2n2/3p4/2PP4/2N5/PP3PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'r1bqkb1r/pp2pppp/2n2n2/3p4/2PP4/2N5/PP3PPP/R1BQKBNR w KQkq -', 'toKey' => 'r1bqkb1r/pp2pppp/2n2n2/3p4/2PP4/2N2N2/PP3PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'r1bqkb1r/pp2pppp/2n2n2/3p4/2PP4/2N2N2/PP3PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2n1pn2/3p4/2PP4/2N2N2/PP3PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'c4d5', 'san' => 'cxd5', 'fromKey' => 'r1bqkb1r/pp3ppp/2n1pn2/3p4/2PP4/2N2N2/PP3PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2n1pn2/3P4/3P4/2N2N2/PP3PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'e6d5', 'san' => 'exd5', 'fromKey' => 'r1bqkb1r/pp3ppp/2n1pn2/3P4/3P4/2N2N2/PP3PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2n2n2/3p4/3P4/2N2N2/PP3PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'f1b5', 'san' => 'Bb5', 'fromKey' => 'r1bqkb1r/pp3ppp/2n2n2/3p4/3P4/2N2N2/PP3PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2n2n2/1B1p4/3P4/2N2N2/PP3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'f8d6', 'san' => 'Bd6', 'fromKey' => 'r1bqkb1r/pp3ppp/2n2n2/1B1p4/3P4/2N2N2/PP3PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqk2r/pp3ppp/2nb1n2/1B1p4/3P4/2N2N2/PP3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'r1bqk2r/pp3ppp/2nb1n2/1B1p4/3P4/2N2N2/PP3PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqk2r/pp3ppp/2nb1n2/1B1p2B1/3P4/2N2N2/PP3PPP/R2QK2R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pp3ppp/2nb1n2/1B1p2B1/3P4/2N2N2/PP3PPP/R2QK2R b KQkq -', 'toKey' => 'r1bq1rk1/pp3ppp/2nb1n2/1B1p2B1/3P4/2N2N2/PP3PPP/R2QK2R w KQ -'],
    ];

    public function getDescription(): string
    {
        return 'Publish beginner Caro-Kann lessons for Black: Exchange development and the Panov line through castling';
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. e4. Prepare ...d5 with ...c6, answer a central exchange, and bring your pieces into play. Start with a five-move Exchange setup, then try a longer Panov line ending in castling. White replies automatically along the selected line; real opponents can choose other continuations.';
        $common = [
            ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens a path for the bishop on f1. The Caro-Kann is a reply to this first move.'],
            ['c6', 'Move your pawn from c7 to c6.', 'Prepare to move your d-pawn to d5. From c6, this pawn will support that central advance.'],
            ['d4', 'Move your pawn from d2 to d4.', 'White places a second pawn in the centre. Black will challenge that centre rather than leave it unopposed.'],
            ['d5', 'Move your pawn from d7 to d5.', 'Challenge the white pawn on e4. Your pawn on c6 supports the pawn on d5: this is the central idea behind your first move.'],
            ['exd5', 'Move your pawn from e4 to d5, capturing the black pawn.', 'White exchanges the e-pawn for the black d-pawn. Pawns capture one square diagonally, rather than straight ahead.'],
            ['cxd5', 'Move your pawn from c6 to d5, capturing the white pawn.', 'Recapture with the pawn you prepared on c6. The exchange is complete, and Black still has a pawn in the centre.'],
        ];
        $lines = [
            ['exchange-setup', 'First steps: the Exchange setup', 'Five Black moves. Meet a central exchange, then develop both knights. This short foundation stops before castling.', self::EXCHANGE_SOURCE, self::EXCHANGE_FACTS, [...$common,
                ['Bd3', 'Move your bishop from f1 to d3.', 'White develops a bishop towards the centre. Both sides now need to bring more pieces off their starting squares.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop a knight towards the centre. It attacks the white pawn on d4 and controls the central square e5.'],
                ['c3', 'Move your pawn from c2 to c3.', 'White gives the pawn on d4 another defender. This is a solid reply to the pressure from your knight.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop your other knight towards the centre. You have learned the Caro-Kann pawn idea and brought both knights into play. This line ends here; your bishops and king still need attention in a real game.'],
            ]],
            ['panov-development', 'Next step: meet the Panov and castle', 'Nine Black moves. White challenges d5 with the c-pawn; develop your pieces, recapture in the centre, then castle.', self::PANOV_SOURCE, self::PANOV_FACTS, [...$common,
                ['c4', 'Move your pawn from c2 to c4.', 'White attacks the black pawn on d5 with another pawn. This central challenge is called the Panov Attack.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop a knight and put pressure on the pawn on d4. You can bring pieces into play while White decides how to handle the central pawns.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops a knight and adds another attack on the black pawn on d5.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Bring your second knight towards the centre. Both black knights are now active instead of sitting on the back rank.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the other knight and adds another defender to d4. Moving it also clears g1 for later castling.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Support the pawn on d5 and open a diagonal for the bishop on f8. This prepares the next stage of your development.'],
                ['cxd5', 'Move your pawn from c4 to d5, capturing the black pawn.', 'White exchanges the c-pawn for the black pawn on d5. Black now needs to recapture to restore the material balance.'],
                ['exd5', 'Move your pawn from e6 to d5, capturing the white pawn.', 'Recapture with your e-pawn and keep a pawn in the centre. Both sides now have a d-pawn without a friendly pawn on either neighbouring file, so pieces must help defend those pawns.'],
                ['Bb5', 'Move your bishop from f1 to b5.', 'White develops a bishop and pins the knight on c6 to the king on e8: moving that knight would expose the king.'],
                ['Bd6', 'Move your bishop from f8 to d6.', 'Develop your bishop on an open diagonal. With the bishop and knight out of the way, the squares between your king and h8 rook are clear for castling.'],
                ['Bg5', 'Move your bishop from c1 to g5.', 'White develops another bishop and lines it up with the knight on f6 and queen on d8. This is pressure on a piece, rather than a check on the king.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Bring your king towards shelter and move a rook closer to the centre. Castling also takes your king away from the bishop pin on c6. This lesson ends with both knights and one bishop developed; the bishop on c8 still needs a plan.'],
            ]],
        ];
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, $title, $description, $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Caro-Kann teaching text and frozen path disagree.');
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::EXCHANGE_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Caro-Kann start position is missing.');
        }
        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('caro-kann',NULL,2)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('caro-kann',1,'Caro-Kann Defense',?,?,UTC_TIMESTAMP(),'b')", [$introduction, $start]);
        foreach ($lines as $order => [$lineId, $title, $description, $source, $facts, $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('caro-kann',1,?,?,?,?,?)", [$lineId, $title, $description, $order, count($steps)]);
            foreach ($steps as $index => [$san, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('caro-kann',1,?,?,?,?,?)", [$lineId, $index + 1, $resolved[$lineId][$index] ?? throw new \LogicException('Missing resolved Caro-Kann edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='caro-kann'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve previous Caro-Kann sessions and progress.');
    }
}
