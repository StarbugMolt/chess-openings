<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Italian revision 2: keeps both short revision 1 lines and adds two complete named pinned paths. */
final class Version20261015010600 extends AbstractMigration
{
    public const PIANISSIMO_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. c3 Nf6 5. d3 d6 6. O-O O-O 7. Re1 a6 8. Bb3 Ba7 9. h3';
    public const KNORRE_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Bc4 Nf6 4. Ng5 d5 5. exd5 Na5 6. Bb5+ c6 7. dxc6 bxc6 8. Be2 h6 9. Nf3 e4 10. Ne5 Bd6 11. d4 Qc7 12. Bd2';
    private const KEPT_LINES = ['centre-plan' => 8, 'quiet-plan' => 8];

    public const PIANISSIMO_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1c4', 'san' => 'Bc4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'f8c5', 'san' => 'Bc5', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'c2c3', 'san' => 'c3', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/2P2N2/PP1P1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/2P2N2/PP1P1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b1p3/2B1P3/2P2N2/PP1P1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'd2d3', 'san' => 'd3', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b1p3/2B1P3/2P2N2/PP1P1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQK2R b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk2r/ppp2ppp/2np1n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/ppp2ppp/2np1n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk2r/ppp2ppp/2np1n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/ppp2ppp/2np1n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQ1RK1 b kq -', 'toKey' => 'r1bq1rk1/ppp2ppp/2np1n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQ1RK1 w - -'],
        ['uci' => 'f1e1', 'san' => 'Re1', 'fromKey' => 'r1bq1rk1/ppp2ppp/2np1n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQ1RK1 w - -', 'toKey' => 'r1bq1rk1/ppp2ppp/2np1n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQR1K1 b - -'],
        ['uci' => 'a7a6', 'san' => 'a6', 'fromKey' => 'r1bq1rk1/ppp2ppp/2np1n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQR1K1 b - -', 'toKey' => 'r1bq1rk1/1pp2ppp/p1np1n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQR1K1 w - -'],
        ['uci' => 'c4b3', 'san' => 'Bb3', 'fromKey' => 'r1bq1rk1/1pp2ppp/p1np1n2/2b1p3/2B1P3/2PP1N2/PP3PPP/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/1pp2ppp/p1np1n2/2b1p3/4P3/1BPP1N2/PP3PPP/RNBQR1K1 b - -'],
        ['uci' => 'c5a7', 'san' => 'Ba7', 'fromKey' => 'r1bq1rk1/1pp2ppp/p1np1n2/2b1p3/4P3/1BPP1N2/PP3PPP/RNBQR1K1 b - -', 'toKey' => 'r1bq1rk1/bpp2ppp/p1np1n2/4p3/4P3/1BPP1N2/PP3PPP/RNBQR1K1 w - -'],
        ['uci' => 'h2h3', 'san' => 'h3', 'fromKey' => 'r1bq1rk1/bpp2ppp/p1np1n2/4p3/4P3/1BPP1N2/PP3PPP/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/bpp2ppp/p1np1n2/4p3/4P3/1BPP1N1P/PP3PP1/RNBQR1K1 b - -'],
    ];

    public const KNORRE_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1c4', 'san' => 'Bc4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'f3g5', 'san' => 'Ng5', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p1N1/2B1P3/8/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p1N1/2B1P3/8/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/ppp2ppp/2n2n2/3pp1N1/2B1P3/8/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'e4d5', 'san' => 'exd5', 'fromKey' => 'r1bqkb1r/ppp2ppp/2n2n2/3pp1N1/2B1P3/8/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/ppp2ppp/2n2n2/3Pp1N1/2B5/8/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'c6a5', 'san' => 'Na5', 'fromKey' => 'r1bqkb1r/ppp2ppp/2n2n2/3Pp1N1/2B5/8/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/ppp2ppp/5n2/n2Pp1N1/2B5/8/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'c4b5', 'san' => 'Bb5+', 'fromKey' => 'r1bqkb1r/ppp2ppp/5n2/n2Pp1N1/2B5/8/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/ppp2ppp/5n2/nB1Pp1N1/8/8/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'r1bqkb1r/ppp2ppp/5n2/nB1Pp1N1/8/8/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2p2n2/nB1Pp1N1/8/8/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'd5c6', 'san' => 'dxc6', 'fromKey' => 'r1bqkb1r/pp3ppp/2p2n2/nB1Pp1N1/8/8/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2P2n2/nB2p1N1/8/8/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'b7c6', 'san' => 'bxc6', 'fromKey' => 'r1bqkb1r/pp3ppp/2P2n2/nB2p1N1/8/8/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/p4ppp/2p2n2/nB2p1N1/8/8/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'b5e2', 'san' => 'Be2', 'fromKey' => 'r1bqkb1r/p4ppp/2p2n2/nB2p1N1/8/8/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/p4ppp/2p2n2/n3p1N1/8/8/PPPPBPPP/RNBQK2R b KQkq -'],
        ['uci' => 'h7h6', 'san' => 'h6', 'fromKey' => 'r1bqkb1r/p4ppp/2p2n2/n3p1N1/8/8/PPPPBPPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/p4pp1/2p2n1p/n3p1N1/8/8/PPPPBPPP/RNBQK2R w KQkq -'],
        ['uci' => 'g5f3', 'san' => 'Nf3', 'fromKey' => 'r1bqkb1r/p4pp1/2p2n1p/n3p1N1/8/8/PPPPBPPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/p4pp1/2p2n1p/n3p3/8/5N2/PPPPBPPP/RNBQK2R b KQkq -'],
        ['uci' => 'e5e4', 'san' => 'e4', 'fromKey' => 'r1bqkb1r/p4pp1/2p2n1p/n3p3/8/5N2/PPPPBPPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/p4pp1/2p2n1p/n7/4p3/5N2/PPPPBPPP/RNBQK2R w KQkq -'],
        ['uci' => 'f3e5', 'san' => 'Ne5', 'fromKey' => 'r1bqkb1r/p4pp1/2p2n1p/n7/4p3/5N2/PPPPBPPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/p4pp1/2p2n1p/n3N3/4p3/8/PPPPBPPP/RNBQK2R b KQkq -'],
        ['uci' => 'f8d6', 'san' => 'Bd6', 'fromKey' => 'r1bqkb1r/p4pp1/2p2n1p/n3N3/4p3/8/PPPPBPPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk2r/p4pp1/2pb1n1p/n3N3/4p3/8/PPPPBPPP/RNBQK2R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'r1bqk2r/p4pp1/2pb1n1p/n3N3/4p3/8/PPPPBPPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk2r/p4pp1/2pb1n1p/n3N3/3Pp3/8/PPP1BPPP/RNBQK2R b KQkq d3'],
        ['uci' => 'd8c7', 'san' => 'Qc7', 'fromKey' => 'r1bqk2r/p4pp1/2pb1n1p/n3N3/3Pp3/8/PPP1BPPP/RNBQK2R b KQkq d3', 'toKey' => 'r1b1k2r/p1q2pp1/2pb1n1p/n3N3/3Pp3/8/PPP1BPPP/RNBQK2R w KQkq -'],
        ['uci' => 'c1d2', 'san' => 'Bd2', 'fromKey' => 'r1b1k2r/p1q2pp1/2pb1n1p/n3N3/3Pp3/8/PPP1BPPP/RNBQK2R w KQkq -', 'toKey' => 'r1b1k2r/p1q2pp1/2pb1n1p/n3N3/3Pp3/8/PPPBBPPP/RN1QK2R b KQkq -'],
    ];

    public function getDescription(): string
    {
        return 'Publish Italian revision 2: add the complete Giuoco Pianissimo main line and Two Knights Knorre line';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $opening = [
            ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open diagonals for your queen and the bishop on f1.'],
            ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing.'],
            ['Nf3', 'Move your knight from g1 to f3.', 'Develop with a threat: the knight attacks the pawn on e5.'],
            ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 by developing a knight.'],
            ['Bc4', 'Move your bishop from f1 to c4.', 'The bishop aims at f7, the square next to Black\'s king that only the king defends. Moving it also clears the way for castling.'],
        ];
        $introduction = 'Play White with 1. e4 e5 2. Nf3 Nc6 3. Bc4. Start with two short lines, then play two complete named lines from the opening reference: the Giuoco Pianissimo main line, where both sides castle and build slowly, and the Two Knights Defense, where you win a pawn and Black gets active pieces in return. Black replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['pianissimo-main-line', 'Main line: the Giuoco Pianissimo', 'Nine White moves, to the end of the named main line. Defend e4 with d3, castle, and prepare a later d4 slowly and safely.', self::PIANISSIMO_SOURCE, self::PIANISSIMO_FACTS, [...$opening,
                ['Bc5', 'Move your bishop from f8 to c5.', 'Black mirrors your idea: this bishop aims at f2, the sensitive square next to your king.'],
                ['c3', 'Move your pawn from c2 to c3.', 'Prepare to support a later d4 advance and take the d4 square away from the black knight.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops and attacks your pawn on e4.'],
                ['d3', 'Move your pawn from d2 to d3.', 'Defend e4 calmly. Instead of opening the centre with d4 straight away, you finish development first. This slow plan is the Giuoco Pianissimo, the "very quiet game".'],
                ['d6', 'Move your pawn from d7 to d6.', 'Black supports e5 and opens the diagonal of the bishop on c8. Both sides now have a solid pawn centre.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Put your king in safety before the centre opens. Your rook can now join the game.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles too. Both kings are safe; the fight is now about plans rather than quick attacks.'],
                ['Re1', 'Move your rook from f1 to e1.', 'The rook supports your e4 pawn and frees f1, so the knight on b1 can later travel via d2 and f1 towards the kingside.'],
                ['a6', 'Move your pawn from a7 to a6.', 'Black makes room for the bishop to drop back to a7 if you push d4 or b4, and prepares ...b5 to chase your bishop.'],
                ['Bb3', 'Move your bishop from c4 to b3.', 'Step back before ...b5 can attack the bishop and gain time. On b3 it still aims at f7.'],
                ['Ba7', 'Move your bishop from c5 to a7.', 'Black tucks the bishop away from a future d4 advance. From a7 it still points at f2.'],
                ['h3', 'Move your pawn from h2 to h3.', 'Stop ...Bg4 and ...Ng4, which could pin or harass your knight on f3. The named main line ends here: both kings are safe, and your plans of Nbd2-f1 and a well-prepared d4 are ready.'],
            ]],
            ['two-knights-knorre', 'If Black plays 3...Nf6: the Two Knights Defense', 'Twelve White moves. Attack f7 with Ng5, win a pawn, and learn why Black gets fast development in return. Source: Two Knights Defense, Knorre Variation.', self::KNORRE_SOURCE, self::KNORRE_FACTS, [...$opening,
                ['Nf6', 'Move your knight from g8 to f6.', 'Instead of ...Bc5, Black develops the other knight and attacks your pawn on e4. This is the Two Knights Defense.'],
                ['Ng5', 'Move your knight from f3 to g5.', 'Attack f7 a second time, together with the bishop on c4. Only the king defends that square, so Black must react at once.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Black blocks the bishop\'s diagonal towards f7 by putting a pawn in its way.'],
                ['exd5', 'Move your pawn from e4 to d5, capturing the black pawn.', 'Win a pawn. Recapturing with ...Nxd5 would let your knight and bishop attack f7 again, so Black chooses another plan.'],
                ['Na5', 'Move your knight from c6 to a5.', 'Black attacks your bishop on c4 instead of recapturing. Black accepts being a pawn down in return for time and quick development.'],
                ['Bb5+', 'Move your bishop from c4 to b5, giving check.', 'Move the attacked bishop with check, so Black must deal with the check first.'],
                ['c6', 'Move your pawn from c7 to c6.', 'Black blocks the check and attacks both your bishop and your pawn on d5.'],
                ['dxc6', 'Move your pawn from d5 to c6, capturing the black pawn.', 'Take another pawn rather than let Black win yours back. You stay a pawn ahead.'],
                ['bxc6', 'Move your pawn from b7 to c6, capturing the white pawn.', 'Black recaptures and attacks your bishop on b5 again.'],
                ['Be2', 'Move your bishop from b5 to e2.', 'Retreat to a safe square close to your king.'],
                ['h6', 'Move your pawn from h7 to h6.', 'Black attacks your knight on g5. It has already moved twice and must move again: this gained time is Black\'s compensation for the pawn.'],
                ['Nf3', 'Move your knight from g5 to f3.', 'Retreat towards the centre and your king.'],
                ['e4', 'Move your pawn from e5 to e4.', 'Black chases the knight once more and gains space in the centre.'],
                ['Ne5', 'Move your knight from f3 to e5.', 'Jump into the centre rather than going back to g1. On e5 the knight is well placed and Black\'s pawns cannot easily attack it.'],
                ['Bd6', 'Move your bishop from f8 to d6.', 'Black develops with an attack on your knight on e5 and prepares to castle.'],
                ['d4', 'Move your pawn from d2 to d4.', 'Support the knight with a pawn, claim central space and open a path for your bishop on c1.'],
                ['Qc7', 'Move your queen from d8 to c7.', 'Black adds a second attacker to your knight on e5 and also protects the knight on a5.'],
                ['Bd2', 'Move your bishop from c1 to d2.', 'Develop your last minor piece with a purpose: it already eyes the knight on a5, and if Black later wins a pawn back with ...Bxe5 dxe5 Qxe5, the bishop can jump to c3 and attack the queen. The named Knorre Variation ends here: you are a pawn ahead, while Black has active pieces in return.'],
            ]],
        ];

        if (1 !== (int) $this->connection->fetchOne("SELECT current_revision FROM lesson WHERE id='italian'")) {
            throw new \RuntimeException('Italian revision 2 must be published directly after revision 1.');
        }
        foreach (self::KEPT_LINES as $lineId => $plies) {
            if ($plies !== (int) $this->connection->fetchOne("SELECT ply_count FROM lesson_line WHERE lesson_id='italian' AND revision=1 AND id=?", [$lineId])) {
                throw new \RuntimeException('The retained Italian revision 1 line is missing or changed: '.$lineId);
            }
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Italian teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }

        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) SELECT lesson_id,2,title,?,start_position_id,UTC_TIMESTAMP(),recommended_color FROM lesson_revision WHERE lesson_id='italian' AND revision=1", [$introduction]);
        foreach (array_keys(self::KEPT_LINES) as $lineId) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) SELECT lesson_id,2,id,title,description,sort_order,ply_count FROM lesson_line WHERE lesson_id='italian' AND revision=1 AND id=?", [$lineId]);
            $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) SELECT lesson_id,2,line_id,ply,chess_move_id,hint,explanation FROM lesson_line_step WHERE lesson_id='italian' AND revision=1 AND line_id=?", [$lineId]);
        }
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('italian',2,?,?,?,?,?)", [$lineId, $title, $description, count(self::KEPT_LINES) + $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('italian',2,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Italian edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=2 WHERE id='italian'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve previous Italian sessions and progress.');
    }
}
