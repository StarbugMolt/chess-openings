<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Caro-Kann revision 2: keeps both revision 1 lines and adds two complete named pinned paths against 3. Nd2. */
final class Version20261015010800 extends AbstractMigration
{
    public const CLASSICAL_SOURCE = '1. e4 c6 2. d4 d5 3. Nd2 dxe4 4. Nxe4 Bf5 5. Ng3 Bg6 6. h4 h6 7. Nf3 Nd7 8. h5 Bh7 9. Bd3 Bxd3 10. Qxd3 e6 11. Bd2 Ngf6 12. O-O-O Be7';
    public const KARPOV_SOURCE = '1. e4 c6 2. d4 d5 3. Nd2 dxe4 4. Nxe4 Nd7 5. Ng5 Ngf6 6. Bd3 e6 7. N1f3 Bd6 8. Qe2 h6 9. Ne4 Nxe4 10. Qxe4';
    private const KEPT_LINES = ['exchange-setup' => 10, 'panov-development' => 18];

    public const CLASSICAL_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/2p5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pp1ppppp/2p5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/2p5/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pp1ppppp/2p5/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1d2', 'san' => 'Nd2', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPPN1PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'd5e4', 'san' => 'dxe4', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPPN1PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/8/3Pp3/8/PPPN1PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'd2e4', 'san' => 'Nxe4', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/8/3Pp3/8/PPPN1PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/8/3PN3/8/PPP2PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'c8f5', 'san' => 'Bf5', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/8/3PN3/8/PPP2PPP/R1BQKBNR b KQkq -', 'toKey' => 'rn1qkbnr/pp2pppp/2p5/5b2/3PN3/8/PPP2PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'e4g3', 'san' => 'Ng3', 'fromKey' => 'rn1qkbnr/pp2pppp/2p5/5b2/3PN3/8/PPP2PPP/R1BQKBNR w KQkq -', 'toKey' => 'rn1qkbnr/pp2pppp/2p5/5b2/3P4/6N1/PPP2PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'f5g6', 'san' => 'Bg6', 'fromKey' => 'rn1qkbnr/pp2pppp/2p5/5b2/3P4/6N1/PPP2PPP/R1BQKBNR b KQkq -', 'toKey' => 'rn1qkbnr/pp2pppp/2p3b1/8/3P4/6N1/PPP2PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'h2h4', 'san' => 'h4', 'fromKey' => 'rn1qkbnr/pp2pppp/2p3b1/8/3P4/6N1/PPP2PPP/R1BQKBNR w KQkq -', 'toKey' => 'rn1qkbnr/pp2pppp/2p3b1/8/3P3P/6N1/PPP2PP1/R1BQKBNR b KQkq -'],
        ['uci' => 'h7h6', 'san' => 'h6', 'fromKey' => 'rn1qkbnr/pp2pppp/2p3b1/8/3P3P/6N1/PPP2PP1/R1BQKBNR b KQkq -', 'toKey' => 'rn1qkbnr/pp2ppp1/2p3bp/8/3P3P/6N1/PPP2PP1/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rn1qkbnr/pp2ppp1/2p3bp/8/3P3P/6N1/PPP2PP1/R1BQKBNR w KQkq -', 'toKey' => 'rn1qkbnr/pp2ppp1/2p3bp/8/3P3P/5NN1/PPP2PP1/R1BQKB1R b KQkq -'],
        ['uci' => 'b8d7', 'san' => 'Nd7', 'fromKey' => 'rn1qkbnr/pp2ppp1/2p3bp/8/3P3P/5NN1/PPP2PP1/R1BQKB1R b KQkq -', 'toKey' => 'r2qkbnr/pp1nppp1/2p3bp/8/3P3P/5NN1/PPP2PP1/R1BQKB1R w KQkq -'],
        ['uci' => 'h4h5', 'san' => 'h5', 'fromKey' => 'r2qkbnr/pp1nppp1/2p3bp/8/3P3P/5NN1/PPP2PP1/R1BQKB1R w KQkq -', 'toKey' => 'r2qkbnr/pp1nppp1/2p3bp/7P/3P4/5NN1/PPP2PP1/R1BQKB1R b KQkq -'],
        ['uci' => 'g6h7', 'san' => 'Bh7', 'fromKey' => 'r2qkbnr/pp1nppp1/2p3bp/7P/3P4/5NN1/PPP2PP1/R1BQKB1R b KQkq -', 'toKey' => 'r2qkbnr/pp1npppb/2p4p/7P/3P4/5NN1/PPP2PP1/R1BQKB1R w KQkq -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'r2qkbnr/pp1npppb/2p4p/7P/3P4/5NN1/PPP2PP1/R1BQKB1R w KQkq -', 'toKey' => 'r2qkbnr/pp1npppb/2p4p/7P/3P4/3B1NN1/PPP2PP1/R1BQK2R b KQkq -'],
        ['uci' => 'h7d3', 'san' => 'Bxd3', 'fromKey' => 'r2qkbnr/pp1npppb/2p4p/7P/3P4/3B1NN1/PPP2PP1/R1BQK2R b KQkq -', 'toKey' => 'r2qkbnr/pp1nppp1/2p4p/7P/3P4/3b1NN1/PPP2PP1/R1BQK2R w KQkq -'],
        ['uci' => 'd1d3', 'san' => 'Qxd3', 'fromKey' => 'r2qkbnr/pp1nppp1/2p4p/7P/3P4/3b1NN1/PPP2PP1/R1BQK2R w KQkq -', 'toKey' => 'r2qkbnr/pp1nppp1/2p4p/7P/3P4/3Q1NN1/PPP2PP1/R1B1K2R b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'r2qkbnr/pp1nppp1/2p4p/7P/3P4/3Q1NN1/PPP2PP1/R1B1K2R b KQkq -', 'toKey' => 'r2qkbnr/pp1n1pp1/2p1p2p/7P/3P4/3Q1NN1/PPP2PP1/R1B1K2R w KQkq -'],
        ['uci' => 'c1d2', 'san' => 'Bd2', 'fromKey' => 'r2qkbnr/pp1n1pp1/2p1p2p/7P/3P4/3Q1NN1/PPP2PP1/R1B1K2R w KQkq -', 'toKey' => 'r2qkbnr/pp1n1pp1/2p1p2p/7P/3P4/3Q1NN1/PPPB1PP1/R3K2R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Ngf6', 'fromKey' => 'r2qkbnr/pp1n1pp1/2p1p2p/7P/3P4/3Q1NN1/PPPB1PP1/R3K2R b KQkq -', 'toKey' => 'r2qkb1r/pp1n1pp1/2p1pn1p/7P/3P4/3Q1NN1/PPPB1PP1/R3K2R w KQkq -'],
        ['uci' => 'e1c1', 'san' => 'O-O-O', 'fromKey' => 'r2qkb1r/pp1n1pp1/2p1pn1p/7P/3P4/3Q1NN1/PPPB1PP1/R3K2R w KQkq -', 'toKey' => 'r2qkb1r/pp1n1pp1/2p1pn1p/7P/3P4/3Q1NN1/PPPB1PP1/2KR3R b kq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'r2qkb1r/pp1n1pp1/2p1pn1p/7P/3P4/3Q1NN1/PPPB1PP1/2KR3R b kq -', 'toKey' => 'r2qk2r/pp1nbpp1/2p1pn1p/7P/3P4/3Q1NN1/PPPB1PP1/2KR3R w kq -'],
    ];

    public const KARPOV_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/2p5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pp1ppppp/2p5/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/2p5/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pp1ppppp/2p5/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1d2', 'san' => 'Nd2', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPPN1PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'd5e4', 'san' => 'dxe4', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/3PP3/8/PPPN1PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/8/3Pp3/8/PPPN1PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'd2e4', 'san' => 'Nxe4', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/8/3Pp3/8/PPPN1PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/8/3PN3/8/PPP2PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'b8d7', 'san' => 'Nd7', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/8/3PN3/8/PPP2PPP/R1BQKBNR b KQkq -', 'toKey' => 'r1bqkbnr/pp1npppp/2p5/8/3PN3/8/PPP2PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'e4g5', 'san' => 'Ng5', 'fromKey' => 'r1bqkbnr/pp1npppp/2p5/8/3PN3/8/PPP2PPP/R1BQKBNR w KQkq -', 'toKey' => 'r1bqkbnr/pp1npppp/2p5/6N1/3P4/8/PPP2PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Ngf6', 'fromKey' => 'r1bqkbnr/pp1npppp/2p5/6N1/3P4/8/PPP2PPP/R1BQKBNR b KQkq -', 'toKey' => 'r1bqkb1r/pp1npppp/2p2n2/6N1/3P4/8/PPP2PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'r1bqkb1r/pp1npppp/2p2n2/6N1/3P4/8/PPP2PPP/R1BQKBNR w KQkq -', 'toKey' => 'r1bqkb1r/pp1npppp/2p2n2/6N1/3P4/3B4/PPP2PPP/R1BQK1NR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'r1bqkb1r/pp1npppp/2p2n2/6N1/3P4/3B4/PPP2PPP/R1BQK1NR b KQkq -', 'toKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/6N1/3P4/3B4/PPP2PPP/R1BQK1NR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'N1f3', 'fromKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/6N1/3P4/3B4/PPP2PPP/R1BQK1NR w KQkq -', 'toKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/6N1/3P4/3B1N2/PPP2PPP/R1BQK2R b KQkq -'],
        ['uci' => 'f8d6', 'san' => 'Bd6', 'fromKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/6N1/3P4/3B1N2/PPP2PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqk2r/pp1n1ppp/2pbpn2/6N1/3P4/3B1N2/PPP2PPP/R1BQK2R w KQkq -'],
        ['uci' => 'd1e2', 'san' => 'Qe2', 'fromKey' => 'r1bqk2r/pp1n1ppp/2pbpn2/6N1/3P4/3B1N2/PPP2PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqk2r/pp1n1ppp/2pbpn2/6N1/3P4/3B1N2/PPP1QPPP/R1B1K2R b KQkq -'],
        ['uci' => 'h7h6', 'san' => 'h6', 'fromKey' => 'r1bqk2r/pp1n1ppp/2pbpn2/6N1/3P4/3B1N2/PPP1QPPP/R1B1K2R b KQkq -', 'toKey' => 'r1bqk2r/pp1n1pp1/2pbpn1p/6N1/3P4/3B1N2/PPP1QPPP/R1B1K2R w KQkq -'],
        ['uci' => 'g5e4', 'san' => 'Ne4', 'fromKey' => 'r1bqk2r/pp1n1pp1/2pbpn1p/6N1/3P4/3B1N2/PPP1QPPP/R1B1K2R w KQkq -', 'toKey' => 'r1bqk2r/pp1n1pp1/2pbpn1p/8/3PN3/3B1N2/PPP1QPPP/R1B1K2R b KQkq -'],
        ['uci' => 'f6e4', 'san' => 'Nxe4', 'fromKey' => 'r1bqk2r/pp1n1pp1/2pbpn1p/8/3PN3/3B1N2/PPP1QPPP/R1B1K2R b KQkq -', 'toKey' => 'r1bqk2r/pp1n1pp1/2pbp2p/8/3Pn3/3B1N2/PPP1QPPP/R1B1K2R w KQkq -'],
        ['uci' => 'e2e4', 'san' => 'Qxe4', 'fromKey' => 'r1bqk2r/pp1n1pp1/2pbp2p/8/3Pn3/3B1N2/PPP1QPPP/R1B1K2R w KQkq -', 'toKey' => 'r1bqk2r/pp1n1pp1/2pbp2p/8/3PQ3/3B1N2/PPP2PPP/R1B1K2R b KQkq -'],
    ];

    public function getDescription(): string
    {
        return 'Publish Caro-Kann revision 2: add the complete Classical Lobron and Karpov Modern main lines';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $opening = [
            ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens a path for the bishop on f1. The Caro-Kann is a reply to this first move.'],
            ['c6', 'Move your pawn from c7 to c6.', 'Prepare to move your d-pawn to d5. From c6, this pawn will support that central advance.'],
            ['d4', 'Move your pawn from d2 to d4.', 'White places a second pawn in the centre. Black will challenge that centre rather than leave it unopposed.'],
            ['d5', 'Move your pawn from d7 to d5.', 'Challenge the white pawn on e4. Your pawn on c6 supports the pawn on d5: this is the central idea behind your first move.'],
            ['Nd2', 'Move your knight from b1 to d2.', 'White defends e4 with a knight instead of exchanging or advancing the pawn.'],
            ['dxe4', 'Move your pawn from d5 to e4, capturing the white pawn.', 'Exchange in the centre. You give up your central pawn, but your pieces get free squares to develop.'],
            ['Nxe4', 'Move your knight from d2 to e4, capturing the black pawn.', 'White recaptures and places the knight in the centre.'],
        ];
        $introduction = 'Play Black against 1. e4. Prepare ...d5 with ...c6 and develop your pieces harmoniously. Start with the short Exchange setup and the Panov line, then play two complete named main lines against 3. Nd2: the Classical Variation, where your light-squared bishop comes out before ...e6, and the Karpov Variation with ...Nd7. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['classical-main-line', 'Classical main line: the bishop comes out first', 'Twelve Black moves. Bring the bishop to f5 before ...e6, meet the h-pawn advance, trade bishops and finish development. Source: Classical Variation, Lobron System.', self::CLASSICAL_SOURCE, self::CLASSICAL_FACTS, [...$opening,
                ['Bf5', 'Move your bishop from c8 to f5.', 'This is the key idea of the Caro-Kann: your light-squared bishop develops outside the pawn chain before ...e6 would shut it in. It also attacks the knight on e4.'],
                ['Ng3', 'Move your knight from e4 to g3.', 'White moves the attacked knight and attacks your bishop in return.'],
                ['Bg6', 'Move your bishop from f5 to g6.', 'Keep the bishop on the same useful diagonal, out of reach of the knight.'],
                ['h4', 'Move your pawn from h2 to h4.', 'White threatens h4-h5, attacking the bishop at a moment when your own pawn on h7 blocks its retreat.'],
                ['h6', 'Move your pawn from h7 to h6.', 'Make room on h7, so the bishop has a safe retreat when the h-pawn arrives.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the other knight.'],
                ['Nd7', 'Move your knight from b8 to d7.', 'Develop the knight. From d7 it controls e5, so White\'s knight cannot settle there comfortably.'],
                ['h5', 'Move your pawn from h4 to h5.', 'White gains space and makes the bishop move again.'],
                ['Bh7', 'Move your bishop from g6 to h7.', 'Retreat to the square you prepared. The bishop still looks along the diagonal towards White\'s position.'],
                ['Bd3', 'Move your bishop from f1 to d3.', 'White offers to exchange light-squared bishops.'],
                ['Bxd3', 'Move your bishop from h7 to d3, capturing the white bishop.', 'Exchange bishops. White\'s active attacking bishop disappears, and your bishop never got locked behind your pawns.'],
                ['Qxd3', 'Move your queen from d1 to d3, capturing the black bishop.', 'White recaptures with the queen.'],
                ['e6', 'Move your pawn from e7 to e6.', 'With the light-squared bishop already gone, ...e6 costs you nothing: it opens a path for the bishop on f8 and supports d5 and f5.'],
                ['Bd2', 'Move your bishop from c1 to d2.', 'White develops the last minor piece and prepares to castle on the queenside.'],
                ['Ngf6', 'Move your knight from g8 to f6.', 'Develop your last knight. It protects your kingside and controls d5 and e4.'],
                ['O-O-O', 'Castle queenside: move your king from e1 to c1. The rook moves from a1 to d1 automatically.', 'White castles on the other side of the board, away from the advanced h-pawn. The rook lands behind the d4 pawn.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Develop your last minor piece and prepare to castle. Your position is compact and has no weak points. The named Lobron System ends here; your next decision is where to put your king.'],
            ]],
            ['karpov-main-line', 'Karpov Variation: ...Nd7 before ...Nf6', 'Nine Black moves. Prepare ...Ngf6 with ...Nd7, stop the knight jump to e6, and exchange knights. Source: Karpov Variation, Modern Main Line.', self::KARPOV_SOURCE, self::KARPOV_FACTS, [...$opening,
                ['Nd7', 'Move your knight from b8 to d7.', 'This is the Karpov Variation. You prepare ...Ngf6, so that if White exchanges knights on f6 you can recapture with a knight and keep your pawns intact.'],
                ['Ng5', 'Move your knight from e4 to g5.', 'White avoids the knight exchange and aims at the squares f7 and e6.'],
                ['Ngf6', 'Move your knight from g8 to f6.', 'Develop the second knight. It controls e4 and helps defend your kingside.'],
                ['Bd3', 'Move your bishop from f1 to d3.', 'White develops the bishop and points it towards your kingside.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Take the e6 square away from White\'s knight and open a path for the bishop on f8. In this line the bishop on c8 waits for later.'],
                ['N1f3', 'Move your knight from g1 to f3.', 'White develops the second knight. Both white knights are now aimed at your kingside.'],
                ['Bd6', 'Move your bishop from f8 to d6.', 'Develop the bishop to an active square and prepare to castle.'],
                ['Qe2', 'Move your queen from d1 to e2.', 'White brings the queen into play behind the knights and keeps both castling options open.'],
                ['h6', 'Move your pawn from h7 to h6.', 'Now that e6 is covered by your pawn, it is safe to ask the knight on g5 to leave.'],
                ['Ne4', 'Move your knight from g5 to e4.', 'White returns the knight to the centre and offers an exchange.'],
                ['Nxe4', 'Move your knight from f6 to e4, capturing the white knight.', 'Exchange knights. Trading pieces gives your slightly cramped position more room.'],
                ['Qxe4', 'Move your queen from e2 to e4, capturing the black knight.', 'White recaptures with an active queen in the centre. The named Modern Main Line ends here: your next tasks are to castle, challenge the centre with ...c5 or ...e5, and find a role for the bishop on c8.'],
            ]],
        ];

        if (1 !== (int) $this->connection->fetchOne("SELECT current_revision FROM lesson WHERE id='caro-kann'")) {
            throw new \RuntimeException('Caro-Kann revision 2 must be published directly after revision 1.');
        }
        foreach (self::KEPT_LINES as $lineId => $plies) {
            if ($plies !== (int) $this->connection->fetchOne("SELECT ply_count FROM lesson_line WHERE lesson_id='caro-kann' AND revision=1 AND id=?", [$lineId])) {
                throw new \RuntimeException('The retained Caro-Kann revision 1 line is missing or changed: '.$lineId);
            }
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Caro-Kann teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }

        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) SELECT lesson_id,2,title,?,start_position_id,UTC_TIMESTAMP(),recommended_color FROM lesson_revision WHERE lesson_id='caro-kann' AND revision=1", [$introduction]);
        foreach (array_keys(self::KEPT_LINES) as $lineId) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) SELECT lesson_id,2,id,title,description,sort_order,ply_count FROM lesson_line WHERE lesson_id='caro-kann' AND revision=1 AND id=?", [$lineId]);
            $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) SELECT lesson_id,2,line_id,ply,chess_move_id,hint,explanation FROM lesson_line_step WHERE lesson_id='caro-kann' AND revision=1 AND line_id=?", [$lineId]);
        }
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('caro-kann',2,?,?,?,?,?)", [$lineId, $title, $description, count(self::KEPT_LINES) + $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('caro-kann',2,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Caro-Kann edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=2 WHERE id='caro-kann'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve previous Caro-Kann sessions and progress.');
    }
}
