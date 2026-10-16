<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Slav revision 2: every line is a complete named pinned path with at least nine learner moves. */
final class Version20261015011800 extends AbstractMigration
{
    public const CZECH_SOURCE = '1. d4 d5 2. c4 c6 3. Nf3 Nf6 4. Nc3 dxc4 5. a4 Bf5 6. e3 e6 7. Bxc4 Bb4 8. O-O O-O 9. Qe2 Ne4 10. g4';
    /** @var array<string, array{int, int}> retained line => [previous ply count, new sort order] */
    private const KEPT_LINES = ['semi-slav-meran' => [29, 1]];

    public const CZECH_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/2PP4/5N2/PP2PPPP/RNBQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/2PP4/5N2/PP2PPPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/2p2n2/3p4/2PP4/5N2/PP2PPPP/RNBQKB1R w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pp2pppp/2p2n2/3p4/2PP4/5N2/PP2PPPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/2p2n2/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -'],
        ['uci' => 'd5c4', 'san' => 'dxc4', 'fromKey' => 'rnbqkb1r/pp2pppp/2p2n2/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/2p2n2/8/2pP4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -'],
        ['uci' => 'a2a4', 'san' => 'a4', 'fromKey' => 'rnbqkb1r/pp2pppp/2p2n2/8/2pP4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/2p2n2/8/P1pP4/2N2N2/1P2PPPP/R1BQKB1R b KQkq -'],
        ['uci' => 'c8f5', 'san' => 'Bf5', 'fromKey' => 'rnbqkb1r/pp2pppp/2p2n2/8/P1pP4/2N2N2/1P2PPPP/R1BQKB1R b KQkq -', 'toKey' => 'rn1qkb1r/pp2pppp/2p2n2/5b2/P1pP4/2N2N2/1P2PPPP/R1BQKB1R w KQkq -'],
        ['uci' => 'e2e3', 'san' => 'e3', 'fromKey' => 'rn1qkb1r/pp2pppp/2p2n2/5b2/P1pP4/2N2N2/1P2PPPP/R1BQKB1R w KQkq -', 'toKey' => 'rn1qkb1r/pp2pppp/2p2n2/5b2/P1pP4/2N1PN2/1P3PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rn1qkb1r/pp2pppp/2p2n2/5b2/P1pP4/2N1PN2/1P3PPP/R1BQKB1R b KQkq -', 'toKey' => 'rn1qkb1r/pp3ppp/2p1pn2/5b2/P1pP4/2N1PN2/1P3PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'f1c4', 'san' => 'Bxc4', 'fromKey' => 'rn1qkb1r/pp3ppp/2p1pn2/5b2/P1pP4/2N1PN2/1P3PPP/R1BQKB1R w KQkq -', 'toKey' => 'rn1qkb1r/pp3ppp/2p1pn2/5b2/P1BP4/2N1PN2/1P3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'f8b4', 'san' => 'Bb4', 'fromKey' => 'rn1qkb1r/pp3ppp/2p1pn2/5b2/P1BP4/2N1PN2/1P3PPP/R1BQK2R b KQkq -', 'toKey' => 'rn1qk2r/pp3ppp/2p1pn2/5b2/PbBP4/2N1PN2/1P3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'rn1qk2r/pp3ppp/2p1pn2/5b2/PbBP4/2N1PN2/1P3PPP/R1BQK2R w KQkq -', 'toKey' => 'rn1qk2r/pp3ppp/2p1pn2/5b2/PbBP4/2N1PN2/1P3PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rn1qk2r/pp3ppp/2p1pn2/5b2/PbBP4/2N1PN2/1P3PPP/R1BQ1RK1 b kq -', 'toKey' => 'rn1q1rk1/pp3ppp/2p1pn2/5b2/PbBP4/2N1PN2/1P3PPP/R1BQ1RK1 w - -'],
        ['uci' => 'd1e2', 'san' => 'Qe2', 'fromKey' => 'rn1q1rk1/pp3ppp/2p1pn2/5b2/PbBP4/2N1PN2/1P3PPP/R1BQ1RK1 w - -', 'toKey' => 'rn1q1rk1/pp3ppp/2p1pn2/5b2/PbBP4/2N1PN2/1P2QPPP/R1B2RK1 b - -'],
        ['uci' => 'f6e4', 'san' => 'Ne4', 'fromKey' => 'rn1q1rk1/pp3ppp/2p1pn2/5b2/PbBP4/2N1PN2/1P2QPPP/R1B2RK1 b - -', 'toKey' => 'rn1q1rk1/pp3ppp/2p1p3/5b2/PbBPn3/2N1PN2/1P2QPPP/R1B2RK1 w - -'],
        ['uci' => 'g2g4', 'san' => 'g4', 'fromKey' => 'rn1q1rk1/pp3ppp/2p1p3/5b2/PbBPn3/2N1PN2/1P2QPPP/R1B2RK1 w - -', 'toKey' => 'rn1q1rk1/pp3ppp/2p1p3/5b2/PbBPn1P1/2N1PN2/1P2QP1P/R1B2RK1 b - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish Slav revision 2: complete named lines only (czech-main-line)';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. d4 and 2. c4. In the Slav Defense you support d5 with ...c6, which, unlike ...e6, keeps the diagonal of your light-squared bishop open. The first line is the Czech Variation: you take on c4, develop the bishop to f5 before playing ...e6, pin with ...Bb4, castle and put a knight on e4. The second line is the Semi-Slav Meran, where you combine ...c6 with ...e6: the bishop on c8 is shut in at first, so you take on c4, gain time with ...b5 and free your queenside with ...a6 and ...c5, which leads to a sharp central fight. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, int, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['czech-main-line', 'Czech Variation: ...Bf5, ...Bb4 and a knight on e4', 'Nine Black moves. Take on c4, develop the bishop to f5 before ...e6, pin with ...Bb4, castle and put a knight on e4. Source: Czech Variation, Dutch Variation, Sämisch Variation.', 0, self::CZECH_SOURCE, self::CZECH_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a path for the bishop on c1.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Take central space and stop White\'s d-pawn from advancing further. Your pawn on d5 also controls c4 and e4.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White offers the c-pawn to pull your d-pawn away from the centre. This is the Queen\'s Gambit.'],
                ['c6', 'Move your pawn from c7 to c6.', 'This is the Slav Defense. The pawn on c6 supports d5, so your centre holds even if White captures there. Unlike ...e6, it does not block your bishop on c8, which can still come out to f5 or g4.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops a knight, defends d4 and controls e5.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop a knight towards the centre. It defends d5 and controls e4.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the second knight and adds another attacker to d5.'],
                ['dxc4', 'Move your pawn from d5 to c4, capturing the white pawn.', 'Now you take on c4. With your pawn on c6, a later ...b5 could defend the captured pawn, so White has to spend time getting it back. Giving up d5 for a moment also frees squares for your pieces.'],
                ['a4', 'Move your pawn from a2 to a4.', 'White stops ...b5: the pawn on a4 controls b5. The price is that no white pawn can ever attack the b4 square again, which gives your pieces a safe post there.'],
                ['Bf5', 'Move your bishop from c8 to f5.', 'Develop your light-squared bishop outside the pawn chain before ...e6 would shut it in. From f5 it also watches e4, which makes White\'s e2-e4 advance harder.'],
                ['e3', 'Move your pawn from e2 to e3.', 'White opens the diagonal of the bishop on f1, which now attacks your pawn on c4. White plans to win that pawn back with a developing move.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Support d5 and open a path for the bishop on f8. Your other bishop is already outside the chain, so ...e6 no longer shuts it in. Holding on to the c4 pawn is not the aim of this line.'],
                ['Bxc4', 'Move your bishop from f1 to c4, capturing the black pawn.', 'White wins the pawn back. Material is level and the bishop stands on an active diagonal.'],
                ['Bb4', 'Move your bishop from f8 to b4.', 'Develop the bishop to the square that a4 gave up. It pins the knight on c3 to the king on e1, so the knight cannot move for now. A pinned knight cannot take on e4 either, which weakens White\'s control of that square.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles. The king has left e1, so the knight on c3 is no longer pinned, although your bishop still attacks it; the pawn on b2 defends it.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Put your king in safety. Of your minor pieces, only the knight on b8 still waits to be developed.'],
                ['Qe2', 'Move your queen from d1 to e2.', 'White prepares the central advance e3-e4: the queen will support that pawn from behind. It also defends the bishop on c4 and leaves d1 free for a rook.'],
                ['Ne4', 'Move your knight from f6 to e4.', 'Occupy e4 yourself before White can push a pawn there. The knight stands on a strong central square, supported by your bishop on f5, and attacks the knight on c3.'],
                ['g4', 'Move your pawn from g2 to g4.', 'White attacks your bishop on f5 with a pawn, so the bishop must move and will stop supporting the knight on e4. This is the Sämisch Variation: White loosens the kingside to drive your pieces back. The named Sämisch Variation ends here: your next task is to find safe squares for your bishop and knight while watching White\'s e4 advance.'],
            ]],
        ];

        if (1 !== (int) $this->connection->fetchOne("SELECT current_revision FROM lesson WHERE id='slav'")) {
            throw new \RuntimeException('Slav revision 2 must be published directly after revision 1.');
        }
        foreach (self::KEPT_LINES as $lineId => [$plies]) {
            if ($plies !== (int) $this->connection->fetchOne("SELECT ply_count FROM lesson_line WHERE lesson_id='slav' AND revision=1 AND id=?", [$lineId])) {
                throw new \RuntimeException('A retained Slav revision 1 line is missing or changed: '.$lineId);
            }
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Slav teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }

        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) SELECT lesson_id,2,title,?,start_position_id,UTC_TIMESTAMP(),recommended_color FROM lesson_revision WHERE lesson_id='slav' AND revision=1", [$introduction]);
        foreach (self::KEPT_LINES as $lineId => [, $order]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) SELECT lesson_id,2,id,title,description,?,ply_count FROM lesson_line WHERE lesson_id='slav' AND revision=1 AND id=?", [$order, $lineId]);
            $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) SELECT lesson_id,2,line_id,ply,chess_move_id,hint,explanation FROM lesson_line_step WHERE lesson_id='slav' AND revision=1 AND line_id=?", [$lineId]);
        }
        foreach ($lines as [$lineId, $title, $description, $order, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('slav',2,?,?,?,?,?)", [$lineId, $title, $description, $order, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('slav',2,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Slav edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=2 WHERE id='slav'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve previous Slav sessions and progress.');
    }
}
