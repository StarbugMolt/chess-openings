<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Italian revision 3: every line is a complete named pinned path with at least nine learner moves. */
final class Version20261015011500 extends AbstractMigration
{
    public const MOLLER_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. c3 Nf6 5. d4 exd4 6. cxd4 Bb4+ 7. Nc3 Nxe4 8. O-O Bxc3 9. d5 Bf6 10. Re1 Ne7 11. Rxe4 d6 12. Bg5 Bxg5 13. Nxg5 O-O 14. Nxh7';
    /** @var array<string, array{int, int}> retained line => [previous ply count, new sort order] */
    private const KEPT_LINES = ['pianissimo-main-line' => [17, 0], 'two-knights-knorre' => [23, 2]];

    public const MOLLER_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1c4', 'san' => 'Bc4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'f8c5', 'san' => 'Bc5', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'c2c3', 'san' => 'c3', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/2P2N2/PP1P1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/2P2N2/PP1P1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b1p3/2B1P3/2P2N2/PP1P1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b1p3/2B1P3/2P2N2/PP1P1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b1p3/2BPP3/2P2N2/PP3PPP/RNBQK2R b KQkq -'],
        ['uci' => 'e5d4', 'san' => 'exd4', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b1p3/2BPP3/2P2N2/PP3PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b5/2BpP3/2P2N2/PP3PPP/RNBQK2R w KQkq -'],
        ['uci' => 'c3d4', 'san' => 'cxd4', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b5/2BpP3/2P2N2/PP3PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b5/2BPP3/5N2/PP3PPP/RNBQK2R b KQkq -'],
        ['uci' => 'c5b4', 'san' => 'Bb4+', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b5/2BPP3/5N2/PP3PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/8/1bBPP3/5N2/PP3PPP/RNBQK2R w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/8/1bBPP3/5N2/PP3PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/8/1bBPP3/2N2N2/PP3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'f6e4', 'san' => 'Nxe4', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/8/1bBPP3/2N2N2/PP3PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n5/8/1bBPn3/2N2N2/PP3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pppp1ppp/2n5/8/1bBPn3/2N2N2/PP3PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n5/8/1bBPn3/2N2N2/PP3PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'b4c3', 'san' => 'Bxc3', 'fromKey' => 'r1bqk2r/pppp1ppp/2n5/8/1bBPn3/2N2N2/PP3PPP/R1BQ1RK1 b kq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n5/8/2BPn3/2b2N2/PP3PPP/R1BQ1RK1 w kq -'],
        ['uci' => 'd4d5', 'san' => 'd5', 'fromKey' => 'r1bqk2r/pppp1ppp/2n5/8/2BPn3/2b2N2/PP3PPP/R1BQ1RK1 w kq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n5/3P4/2B1n3/2b2N2/PP3PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'c3f6', 'san' => 'Bf6', 'fromKey' => 'r1bqk2r/pppp1ppp/2n5/3P4/2B1n3/2b2N2/PP3PPP/R1BQ1RK1 b kq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2b2/3P4/2B1n3/5N2/PP3PPP/R1BQ1RK1 w kq -'],
        ['uci' => 'f1e1', 'san' => 'Re1', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2b2/3P4/2B1n3/5N2/PP3PPP/R1BQ1RK1 w kq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2b2/3P4/2B1n3/5N2/PP3PPP/R1BQR1K1 b kq -'],
        ['uci' => 'c6e7', 'san' => 'Ne7', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2b2/3P4/2B1n3/5N2/PP3PPP/R1BQR1K1 b kq -', 'toKey' => 'r1bqk2r/ppppnppp/5b2/3P4/2B1n3/5N2/PP3PPP/R1BQR1K1 w kq -'],
        ['uci' => 'e1e4', 'san' => 'Rxe4', 'fromKey' => 'r1bqk2r/ppppnppp/5b2/3P4/2B1n3/5N2/PP3PPP/R1BQR1K1 w kq -', 'toKey' => 'r1bqk2r/ppppnppp/5b2/3P4/2B1R3/5N2/PP3PPP/R1BQ2K1 b kq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'r1bqk2r/ppppnppp/5b2/3P4/2B1R3/5N2/PP3PPP/R1BQ2K1 b kq -', 'toKey' => 'r1bqk2r/ppp1nppp/3p1b2/3P4/2B1R3/5N2/PP3PPP/R1BQ2K1 w kq -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'r1bqk2r/ppp1nppp/3p1b2/3P4/2B1R3/5N2/PP3PPP/R1BQ2K1 w kq -', 'toKey' => 'r1bqk2r/ppp1nppp/3p1b2/3P2B1/2B1R3/5N2/PP3PPP/R2Q2K1 b kq -'],
        ['uci' => 'f6g5', 'san' => 'Bxg5', 'fromKey' => 'r1bqk2r/ppp1nppp/3p1b2/3P2B1/2B1R3/5N2/PP3PPP/R2Q2K1 b kq -', 'toKey' => 'r1bqk2r/ppp1nppp/3p4/3P2b1/2B1R3/5N2/PP3PPP/R2Q2K1 w kq -'],
        ['uci' => 'f3g5', 'san' => 'Nxg5', 'fromKey' => 'r1bqk2r/ppp1nppp/3p4/3P2b1/2B1R3/5N2/PP3PPP/R2Q2K1 w kq -', 'toKey' => 'r1bqk2r/ppp1nppp/3p4/3P2N1/2B1R3/8/PP3PPP/R2Q2K1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/ppp1nppp/3p4/3P2N1/2B1R3/8/PP3PPP/R2Q2K1 b kq -', 'toKey' => 'r1bq1rk1/ppp1nppp/3p4/3P2N1/2B1R3/8/PP3PPP/R2Q2K1 w - -'],
        ['uci' => 'g5h7', 'san' => 'Nxh7', 'fromKey' => 'r1bq1rk1/ppp1nppp/3p4/3P2N1/2B1R3/8/PP3PPP/R2Q2K1 w - -', 'toKey' => 'r1bq1rk1/ppp1nppN/3p4/3P4/2B1R3/8/PP3PPP/R2Q2K1 b - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish Italian revision 3: complete named lines only (giuoco-piano-moller)';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play White with 1. e4 e5 2. Nf3 Nc6 3. Bc4. Learn three complete named lines from the opening reference: the Giuoco Pianissimo main line, where you defend e4 with d3, castle and build slowly; the Giuoco Piano with c3 and d4, where you give up a pawn for fast development and an attack; and the Two Knights Defense, where Black answers 3...Nf6 and gives up a pawn for active pieces. Black replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, int, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['giuoco-piano-moller', 'Giuoco Piano: the c3 and d4 centre', 'Fourteen White moves. Build the c3 and d4 centre, give up a pawn for fast development, then attack with d5, Re1 and the knight on g5. Source: Giuoco Piano, Therkatz-Herzog Variation.', 1, self::MOLLER_SOURCE, self::MOLLER_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open diagonals for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop with a threat: the knight attacks the pawn on e5.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 by developing a knight.'],
                ['Bc4', 'Move your bishop from f1 to c4.', 'The bishop aims at f7, the square next to Black\'s king that only the king defends. Moving it also clears the way for castling.'],
                ['Bc5', 'Move your bishop from f8 to c5.', 'Black mirrors your idea: this bishop aims at f2, the sensitive square next to your king.'],
                ['c3', 'Move your pawn from c2 to c3.', 'Prepare d4. The pawn on c3 will support it, so you can build a broad centre with pawns on d4 and e4.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops and attacks your pawn on e4.'],
                ['d4', 'Move your pawn from d2 to d4.', 'Strike in the centre straight away. The pawn attacks the bishop on c5 and the pawn on e5. This is the classical Giuoco Piano plan that c3 prepared.'],
                ['exd4', 'Move your pawn from e5 to d4, capturing the white pawn.', 'Black takes on d4 instead of letting your pawns gain more space.'],
                ['cxd4', 'Move your pawn from c3 to d4, capturing the black pawn.', 'Recapture with the c-pawn. You now have pawns on d4 and e4, the broad centre your c3 move prepared, and the bishop on c5 is attacked again.'],
                ['Bb4+', 'Move your bishop from c5 to b4, giving check.', 'Black moves the bishop with check instead of retreating, gaining time before you can castle.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'Block the check by developing your knight. It defends e4, but it is now pinned to your king, so it cannot recapture on e4. In this line you accept giving up that pawn for quick development.'],
                ['Nxe4', 'Move your knight from f6 to e4, capturing the white pawn.', 'Black takes your e4 pawn. Your knight on c3 cannot recapture, because it is pinned to your king.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle instead of defending material. With your king gone from e1 the knight on c3 is no longer pinned, so the black knight on e4 is now attacked.'],
                ['Bxc3', 'Move your bishop from b4 to c3, capturing the white knight.', 'Black removes the attacker by taking your knight on c3.'],
                ['d5', 'Move your pawn from d4 to d5.', 'This is the Möller Attack. Instead of recapturing on c3, push the pawn and attack the knight on c6. Your b2 pawn still attacks the bishop on c3, so Black has several pieces to look after at once.'],
                ['Bf6', 'Move your bishop from c3 to f6.', 'Black saves the bishop by bringing it back to f6, where it also guards the kingside.'],
                ['Re1', 'Move your rook from f1 to e1.', 'The rook attacks the knight on e4. The e-file behind that knight is empty all the way to Black\'s king on e8, so the knight is pinned and cannot move away.'],
                ['Ne7', 'Move your knight from c6 to e7.', 'Black moves the knight away from your d5 pawn and places it on e7, between your rook and the king. This breaks the pin on the knight on e4.'],
                ['Rxe4', 'Move your rook from e1 to e4, capturing the black knight.', 'Win back the piece. You are now a pawn down, but your pieces are active and Black\'s king is still in the centre.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Black stops your d-pawn from advancing further and opens the diagonal of the bishop on c8, preparing to castle.'],
                ['Bg5', 'Move your bishop from c1 to g5.', 'Develop your last bishop with a challenge: it attacks the bishop on f6, which guards Black\'s kingside.'],
                ['Bxg5', 'Move your bishop from f6 to g5, capturing the white bishop.', 'Black exchanges bishops.'],
                ['Nxg5', 'Move your knight from f3 to g5, capturing the black bishop.', 'Recapture with the knight. It now aims at h7 and f7, close to Black\'s king.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles and takes the king out of the centre.'],
                ['Nxh7', 'Move your knight from g5 to h7, capturing the black pawn.', 'Take the pawn on h7. The knight now attacks the rook on f8 and material is level again. If Black\'s king takes the knight, your queen can come to h5 with check and your rook on e4 can swing to h4 against the king. The named Therkatz-Herzog Variation ends here, at the start of a sharp attacking middlegame.'],
            ]],
        ];

        if (2 !== (int) $this->connection->fetchOne("SELECT current_revision FROM lesson WHERE id='italian'")) {
            throw new \RuntimeException('Italian revision 3 must be published directly after revision 2.');
        }
        foreach (self::KEPT_LINES as $lineId => [$plies]) {
            if ($plies !== (int) $this->connection->fetchOne("SELECT ply_count FROM lesson_line WHERE lesson_id='italian' AND revision=2 AND id=?", [$lineId])) {
                throw new \RuntimeException('A retained Italian revision 2 line is missing or changed: '.$lineId);
            }
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Italian teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }

        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) SELECT lesson_id,3,title,?,start_position_id,UTC_TIMESTAMP(),recommended_color FROM lesson_revision WHERE lesson_id='italian' AND revision=2", [$introduction]);
        foreach (self::KEPT_LINES as $lineId => [, $order]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) SELECT lesson_id,3,id,title,description,?,ply_count FROM lesson_line WHERE lesson_id='italian' AND revision=2 AND id=?", [$order, $lineId]);
            $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) SELECT lesson_id,3,line_id,ply,chess_move_id,hint,explanation FROM lesson_line_step WHERE lesson_id='italian' AND revision=2 AND line_id=?", [$lineId]);
        }
        foreach ($lines as [$lineId, $title, $description, $order, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('italian',3,?,?,?,?,?)", [$lineId, $title, $description, $order, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('italian',3,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Italian edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=3 WHERE id='italian'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve previous Italian sessions and progress.');
    }
}
