<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Slav Defense course: complete named pinned paths with original teaching notes. */
final class Version20261015011400 extends AbstractMigration
{
    public const CZECH_SOURCE = '1. d4 d5 2. c4 c6 3. Nf3 Nf6 4. Nc3 dxc4 5. a4 Bf5 6. e3 e6 7. Bxc4 Bb4 8. O-O O-O 9. Qe2';
    public const MERAN_SOURCE = '1. d4 d5 2. c4 c6 3. Nc3 Nf6 4. Nf3 e6 5. e3 Nbd7 6. Bd3 dxc4 7. Bxc4 b5 8. Bd3 a6 9. e4 c5 10. e5 cxd4 11. Nxb5 Nxe5 12. Nxe5 axb5 13. O-O Qd5 14. Qe2 Ba6 15. Bg5';

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
    ];

    public const MERAN_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pp2pppp/2p5/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/2p2n2/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkb1r/pp2pppp/2p2n2/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/2p2n2/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkb1r/pp2pppp/2p2n2/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/pp3ppp/2p1pn2/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -'],
        ['uci' => 'e2e3', 'san' => 'e3', 'fromKey' => 'rnbqkb1r/pp3ppp/2p1pn2/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/pp3ppp/2p1pn2/3p4/2PP4/2N1PN2/PP3PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'b8d7', 'san' => 'Nbd7', 'fromKey' => 'rnbqkb1r/pp3ppp/2p1pn2/3p4/2PP4/2N1PN2/PP3PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/3p4/2PP4/2N1PN2/PP3PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/3p4/2PP4/2N1PN2/PP3PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/3p4/2PP4/2NBPN2/PP3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'd5c4', 'san' => 'dxc4', 'fromKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/3p4/2PP4/2NBPN2/PP3PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/8/2pP4/2NBPN2/PP3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'd3c4', 'san' => 'Bxc4', 'fromKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/8/2pP4/2NBPN2/PP3PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/8/2BP4/2N1PN2/PP3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'b7b5', 'san' => 'b5', 'fromKey' => 'r1bqkb1r/pp1n1ppp/2p1pn2/8/2BP4/2N1PN2/PP3PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqkb1r/p2n1ppp/2p1pn2/1p6/2BP4/2N1PN2/PP3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'c4d3', 'san' => 'Bd3', 'fromKey' => 'r1bqkb1r/p2n1ppp/2p1pn2/1p6/2BP4/2N1PN2/PP3PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqkb1r/p2n1ppp/2p1pn2/1p6/3P4/2NBPN2/PP3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'a7a6', 'san' => 'a6', 'fromKey' => 'r1bqkb1r/p2n1ppp/2p1pn2/1p6/3P4/2NBPN2/PP3PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqkb1r/3n1ppp/p1p1pn2/1p6/3P4/2NBPN2/PP3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'e3e4', 'san' => 'e4', 'fromKey' => 'r1bqkb1r/3n1ppp/p1p1pn2/1p6/3P4/2NBPN2/PP3PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqkb1r/3n1ppp/p1p1pn2/1p6/3PP3/2NB1N2/PP3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'c6c5', 'san' => 'c5', 'fromKey' => 'r1bqkb1r/3n1ppp/p1p1pn2/1p6/3PP3/2NB1N2/PP3PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqkb1r/3n1ppp/p3pn2/1pp5/3PP3/2NB1N2/PP3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'e4e5', 'san' => 'e5', 'fromKey' => 'r1bqkb1r/3n1ppp/p3pn2/1pp5/3PP3/2NB1N2/PP3PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqkb1r/3n1ppp/p3pn2/1pp1P3/3P4/2NB1N2/PP3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'c5d4', 'san' => 'cxd4', 'fromKey' => 'r1bqkb1r/3n1ppp/p3pn2/1pp1P3/3P4/2NB1N2/PP3PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqkb1r/3n1ppp/p3pn2/1p2P3/3p4/2NB1N2/PP3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'c3b5', 'san' => 'Nxb5', 'fromKey' => 'r1bqkb1r/3n1ppp/p3pn2/1p2P3/3p4/2NB1N2/PP3PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqkb1r/3n1ppp/p3pn2/1N2P3/3p4/3B1N2/PP3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'd7e5', 'san' => 'Nxe5', 'fromKey' => 'r1bqkb1r/3n1ppp/p3pn2/1N2P3/3p4/3B1N2/PP3PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqkb1r/5ppp/p3pn2/1N2n3/3p4/3B1N2/PP3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'f3e5', 'san' => 'Nxe5', 'fromKey' => 'r1bqkb1r/5ppp/p3pn2/1N2n3/3p4/3B1N2/PP3PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqkb1r/5ppp/p3pn2/1N2N3/3p4/3B4/PP3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'a6b5', 'san' => 'axb5', 'fromKey' => 'r1bqkb1r/5ppp/p3pn2/1N2N3/3p4/3B4/PP3PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqkb1r/5ppp/4pn2/1p2N3/3p4/3B4/PP3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqkb1r/5ppp/4pn2/1p2N3/3p4/3B4/PP3PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqkb1r/5ppp/4pn2/1p2N3/3p4/3B4/PP3PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'd8d5', 'san' => 'Qd5', 'fromKey' => 'r1bqkb1r/5ppp/4pn2/1p2N3/3p4/3B4/PP3PPP/R1BQ1RK1 b kq -', 'toKey' => 'r1b1kb1r/5ppp/4pn2/1p1qN3/3p4/3B4/PP3PPP/R1BQ1RK1 w kq -'],
        ['uci' => 'd1e2', 'san' => 'Qe2', 'fromKey' => 'r1b1kb1r/5ppp/4pn2/1p1qN3/3p4/3B4/PP3PPP/R1BQ1RK1 w kq -', 'toKey' => 'r1b1kb1r/5ppp/4pn2/1p1qN3/3p4/3B4/PP2QPPP/R1B2RK1 b kq -'],
        ['uci' => 'c8a6', 'san' => 'Ba6', 'fromKey' => 'r1b1kb1r/5ppp/4pn2/1p1qN3/3p4/3B4/PP2QPPP/R1B2RK1 b kq -', 'toKey' => 'r3kb1r/5ppp/b3pn2/1p1qN3/3p4/3B4/PP2QPPP/R1B2RK1 w kq -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'r3kb1r/5ppp/b3pn2/1p1qN3/3p4/3B4/PP2QPPP/R1B2RK1 w kq -', 'toKey' => 'r3kb1r/5ppp/b3pn2/1p1qN1B1/3p4/3B4/PP2QPPP/R4RK1 b kq -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Slav Defense course: Czech main line, Semi-Slav Meran';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. d4 and 2. c4. In the Slav Defense you support d5 with ...c6, which, unlike ...e6, keeps the diagonal of your light-squared bishop open. The first line is the Czech Variation main line: you take on c4, develop the bishop to f5 before playing ...e6, pin with ...Bb4 and castle. The second line is the Semi-Slav Meran, where you combine ...c6 with ...e6: the bishop on c8 is shut in at first, so you take on c4, gain time with ...b5 and free your queenside with ...a6 and ...c5, which leads to a sharp central fight. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['czech-main-line', 'Czech Variation: the bishop comes out before ...e6', 'Eight Black moves. Take on c4, develop the bishop to f5 before ...e6, pin the knight with ...Bb4 and castle. Source: Czech Variation, Classical System, Main Line.', self::CZECH_SOURCE, self::CZECH_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a path for the bishop on c1.'],
                ['d5', 'Move your pawn from d7 to d5.', "Take central space and stop White's d-pawn from advancing further. Your pawn on d5 also controls c4 and e4."],
                ['c4', 'Move your pawn from c2 to c4.', "White offers the c-pawn to pull your d-pawn away from the centre. This is the Queen's Gambit."],
                ['c6', 'Move your pawn from c7 to c6.', 'This is the Slav Defense. The pawn on c6 supports d5, so your centre holds even if White captures there. Unlike ...e6, it does not block your bishop on c8, which can still come out to f5 or g4.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops a knight, defends d4 and controls e5.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop a knight towards the centre. It defends d5 and controls e4.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the second knight and adds another attacker to d5.'],
                ['dxc4', 'Move your pawn from d5 to c4, capturing the white pawn.', 'Now you take on c4. With your pawn on c6, a later ...b5 could defend the captured pawn, so White has to spend time getting it back. Giving up d5 for a moment also frees squares for your pieces.'],
                ['a4', 'Move your pawn from a2 to a4.', 'White stops ...b5: the pawn on a4 controls b5. The price is that no white pawn can ever attack the b4 square again, which gives your pieces a safe post there.'],
                ['Bf5', 'Move your bishop from c8 to f5.', "Develop your light-squared bishop outside the pawn chain before ...e6 would shut it in. From f5 it also watches e4, which makes White's e2-e4 advance harder."],
                ['e3', 'Move your pawn from e2 to e3.', 'White opens the diagonal of the bishop on f1, which now attacks your pawn on c4. White plans to win that pawn back with a developing move.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Support d5 and open a path for the bishop on f8. Your other bishop is already outside the chain, so ...e6 no longer shuts it in. Holding on to the c4 pawn is not the aim of this line.'],
                ['Bxc4', 'Move your bishop from f1 to c4, capturing the black pawn.', 'White wins the pawn back. Material is level and the bishop stands on an active diagonal.'],
                ['Bb4', 'Move your bishop from f8 to b4.', "Develop the bishop to the square that a4 gave up. It pins the knight on c3 to the king on e1, so the knight cannot move for now. A pinned knight cannot take on e4 either, which weakens White's control of that square."],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles. The king has left e1, so the knight on c3 is no longer pinned, although your bishop still attacks it; the pawn on b2 defends it.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Put your king in safety. Of your minor pieces, only the knight on b8 still waits to be developed.'],
                ['Qe2', 'Move your queen from d1 to e2.', 'White prepares the central advance e3-e4: the queen will support that pawn from behind, it defends the bishop on c4 and it leaves d1 free for a rook. The named Main Line ends here: your next tasks are to develop the knight on b8 and to be ready for e4, which would attack your bishop on f5.'],
            ]],
            ['semi-slav-meran', 'Semi-Slav Meran: ...b5, ...c5 and the Rellstab Attack', 'Fourteen Black moves. Build the Semi-Slav triangle, gain time with ...dxc4 and ...b5, strike back with ...c5, then come through a sharp exchange sequence in the centre with active pieces and an extra pawn. Source: Semi-Slav Defense: Meran Variation, Rellstab Attack.', self::MERAN_SOURCE, self::MERAN_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a path for the bishop on c1.'],
                ['d5', 'Move your pawn from d7 to d5.', "Take central space and stop White's d-pawn from advancing further."],
                ['c4', 'Move your pawn from c2 to c4.', 'White offers the c-pawn to pull your d-pawn away from the centre.'],
                ['c6', 'Move your pawn from c7 to c6.', 'Support d5 with a pawn. As in every Slav, the pawn on c6 holds the centre without blocking your bishop on c8 for now.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops a knight and adds another attacker to d5.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop a knight, defend d5 a second time and control e4.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the second knight, defends d4 and controls e5.'],
                ['e6', 'Move your pawn from e7 to e6.', 'This is the Semi-Slav: your pawns on c6, d5 and e6 form a triangle that holds d5 firmly, and the bishop on f8 can now come out. The cost is that e6 shuts in your bishop on c8. In the Meran you will open a new diagonal for it by advancing your queenside pawns.'],
                ['e3', 'Move your pawn from e2 to e3.', 'White supports d4 and opens the diagonal for the bishop on f1. The bishop on c1 stays behind the pawn on e3 for now.'],
                ['Nbd7', 'Move your knight from b8 to d7.', 'Develop the knight to d7 rather than c6, so your c-pawn stays free to advance later. From d7 the knight also watches c5 and e5.'],
                ['Bd3', 'Move your bishop from f1 to d3.', 'White develops the bishop towards your kingside. It aims at h7 and supports a later e3-e4.'],
                ['dxc4', 'Move your pawn from d5 to c4, capturing the white pawn.', "Take on c4 now that White's bishop has moved to d3: recapturing costs that bishop a second move. This capture starts the Meran plan."],
                ['Bxc4', 'Move your bishop from d3 to c4, capturing the black pawn.', 'White wins the pawn back with the bishop. Material is level.'],
                ['b5', 'Move your pawn from b7 to b5.', 'Gain time by attacking the bishop, with the pawn on c6 supporting b5. The pawn also takes queenside space and frees b7 for your bishop on c8.'],
                ['Bd3', 'Move your bishop from c4 to d3.', 'White retreats the bishop to d3, where it points at your kingside again and supports e4.'],
                ['a6', 'Move your pawn from a7 to a6.', 'Prepare ...c5. Once your c-pawn advances it will no longer defend b5, so the pawn on a6 takes over that job.'],
                ['e4', 'Move your pawn from e3 to e4.', 'White occupies the centre with the e-pawn, supported by the knight on c3 and the bishop on d3. It threatens to advance again to e5 and drive your knight away from f6.'],
                ['c5', 'Move your pawn from c6 to c5.', "Strike at White's centre: your pawn attacks d4. With b5 covered by your a6 pawn, your queenside pawns are no longer passive, and the c6 square is free for your pieces."],
                ['e5', 'Move your pawn from e4 to e5.', 'White advances and attacks your knight on f6. The pawn on e5 is defended by the pawn on d4 and the knight on f3.'],
                ['cxd4', 'Move your pawn from c5 to d4, capturing the white pawn.', 'Instead of moving the knight, capture in the centre. You remove one of the defenders of e5, win a pawn, and your pawn on d4 now attacks the knight on c3. Each side has a knight under attack.'],
                ['Nxb5', 'Move your knight from c3 to b5, capturing the black pawn.', "White's knight, attacked by your pawn on d4, captures on b5 instead of retreating. Material is level again. Your a6 pawn can take the knight, but your own knight on f6 is still attacked by the pawn on e5."],
                ['Nxe5', 'Move your knight from d7 to e5, capturing the white pawn.', "Take the pawn on e5. This removes the pawn that attacked your knight on f6 and puts you a pawn ahead. Your knight now attacks White's knight on f3 and bishop on d3, and the knight on b5 is still attacked by your a6 pawn."],
                ['Nxe5', 'Move your knight from f3 to e5, capturing the black knight.', 'White captures your knight on e5, so for a moment White is ahead in material. Your a6 pawn can still capture the knight on b5.'],
                ['axb5', 'Move your pawn from a6 to b5, capturing the white knight.', 'Take the knight. Count the material: each side has given up one knight, and you have won one more pawn than White, so you are a pawn up. Your pawn on d4 is passed, because White has no c- or e-pawn left to stop it, and your rook on a8 now looks down the half-open a-file at a2.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles. Your king is still in the centre, so finishing your development quickly matters.'],
                ['Qd5', 'Move your queen from d8 to d5.', "Centralise the queen. From d5 it attacks the knight on e5, which nothing defends at the moment, and protects your pawns on b5 and d4. It also eyes the pawns on a2 and g2, which White's rook and king defend."],
                ['Qe2', 'Move your queen from d1 to e2.', 'White defends the knight on e5 with the queen. The queen also stands behind the knight on the e-file, in line with your pawn on e6 and your king on e8.'],
                ['Ba6', 'Move your bishop from c8 to a6.', "Develop your last queenside minor piece. The bishop adds a second defender to b5, which White's bishop on d3 attacks, and lines up on the same diagonal as White's bishop, queen and rook on d3, e2 and f1, with your b5 pawn in between. It also clears the back rank between your king and the rook on a8."],
                ['Bg5', 'Move your bishop from c1 to g5.', 'White develops the last minor piece and attacks your knight on f6, which guards your queen and the squares d7 and h7 near your king. If White takes on f6, only your g-pawn can recapture, which would split your kingside pawns. The named Rellstab Attack ends here: your next tasks are to complete your development, find a safe place for your king and make use of your extra passed pawn on d4.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='slav'")) {
            throw new \RuntimeException('The Slav Defense course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Slav Defense teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::CZECH_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Slav Defense start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('slav',NULL,9)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('slav',1,?,?,?,UTC_TIMESTAMP(),'b')", ['Slav Defense', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('slav',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('slav',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Slav Defense edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='slav'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Slav Defense sessions and progress.');
    }
}
