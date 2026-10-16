<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Catalan Opening course: complete named pinned paths with original teaching notes. */
final class Version20261015011100 extends AbstractMigration
{
    public const CLOSED_SOURCE = '1. d4 Nf6 2. c4 e6 3. Nf3 d5 4. g3 Be7 5. Bg2 O-O 6. O-O Nbd7 7. Qc2 c6 8. Bf4 b6 9. Nbd2 Bb7 10. e4 dxe4 11. Nxe4 Nxe4 12. Qxe4';
    public const SOKOLSKY_SOURCE = '1. d4 Nf6 2. c4 e6 3. g3 d5 4. Bg2 Be7 5. Nf3 O-O 6. O-O Nbd7 7. Qc2 c6 8. Nbd2 b6 9. b3 a5 10. Bb2 Ba6';

    public const CLOSED_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/5N2/PP2PPPP/RNBQKB1R b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/5N2/PP2PPPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/5N2/PP2PPPP/RNBQKB1R w KQkq -'],
        ['uci' => 'g2g3', 'san' => 'g3', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/5N2/PP2PPPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/5NP1/PP2PP1P/RNBQKB1R b KQkq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/5NP1/PP2PP1P/RNBQKB1R b KQkq -', 'toKey' => 'rnbqk2r/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PP1P/RNBQKB1R w KQkq -'],
        ['uci' => 'f1g2', 'san' => 'Bg2', 'fromKey' => 'rnbqk2r/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PP1P/RNBQKB1R w KQkq -', 'toKey' => 'rnbqk2r/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQK2R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQK2R b KQkq -', 'toKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQK2R w KQ -', 'toKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQ1RK1 b - -'],
        ['uci' => 'b8d7', 'san' => 'Nbd7', 'fromKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQ1RK1 b - -', 'toKey' => 'r1bq1rk1/pppnbppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQ1RK1 w - -'],
        ['uci' => 'd1c2', 'san' => 'Qc2', 'fromKey' => 'r1bq1rk1/pppnbppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQ1RK1 w - -', 'toKey' => 'r1bq1rk1/pppnbppp/4pn2/3p4/2PP4/5NP1/PPQ1PPBP/RNB2RK1 b - -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'r1bq1rk1/pppnbppp/4pn2/3p4/2PP4/5NP1/PPQ1PPBP/RNB2RK1 b - -', 'toKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p4/2PP4/5NP1/PPQ1PPBP/RNB2RK1 w - -'],
        ['uci' => 'c1f4', 'san' => 'Bf4', 'fromKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p4/2PP4/5NP1/PPQ1PPBP/RNB2RK1 w - -', 'toKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p4/2PP1B2/5NP1/PPQ1PPBP/RN3RK1 b - -'],
        ['uci' => 'b7b6', 'san' => 'b6', 'fromKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p4/2PP1B2/5NP1/PPQ1PPBP/RN3RK1 b - -', 'toKey' => 'r1bq1rk1/p2nbppp/1pp1pn2/3p4/2PP1B2/5NP1/PPQ1PPBP/RN3RK1 w - -'],
        ['uci' => 'b1d2', 'san' => 'Nbd2', 'fromKey' => 'r1bq1rk1/p2nbppp/1pp1pn2/3p4/2PP1B2/5NP1/PPQ1PPBP/RN3RK1 w - -', 'toKey' => 'r1bq1rk1/p2nbppp/1pp1pn2/3p4/2PP1B2/5NP1/PPQNPPBP/R4RK1 b - -'],
        ['uci' => 'c8b7', 'san' => 'Bb7', 'fromKey' => 'r1bq1rk1/p2nbppp/1pp1pn2/3p4/2PP1B2/5NP1/PPQNPPBP/R4RK1 b - -', 'toKey' => 'r2q1rk1/pb1nbppp/1pp1pn2/3p4/2PP1B2/5NP1/PPQNPPBP/R4RK1 w - -'],
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'r2q1rk1/pb1nbppp/1pp1pn2/3p4/2PP1B2/5NP1/PPQNPPBP/R4RK1 w - -', 'toKey' => 'r2q1rk1/pb1nbppp/1pp1pn2/3p4/2PPPB2/5NP1/PPQN1PBP/R4RK1 b - -'],
        ['uci' => 'd5e4', 'san' => 'dxe4', 'fromKey' => 'r2q1rk1/pb1nbppp/1pp1pn2/3p4/2PPPB2/5NP1/PPQN1PBP/R4RK1 b - -', 'toKey' => 'r2q1rk1/pb1nbppp/1pp1pn2/8/2PPpB2/5NP1/PPQN1PBP/R4RK1 w - -'],
        ['uci' => 'd2e4', 'san' => 'Nxe4', 'fromKey' => 'r2q1rk1/pb1nbppp/1pp1pn2/8/2PPpB2/5NP1/PPQN1PBP/R4RK1 w - -', 'toKey' => 'r2q1rk1/pb1nbppp/1pp1pn2/8/2PPNB2/5NP1/PPQ2PBP/R4RK1 b - -'],
        ['uci' => 'f6e4', 'san' => 'Nxe4', 'fromKey' => 'r2q1rk1/pb1nbppp/1pp1pn2/8/2PPNB2/5NP1/PPQ2PBP/R4RK1 b - -', 'toKey' => 'r2q1rk1/pb1nbppp/1pp1p3/8/2PPnB2/5NP1/PPQ2PBP/R4RK1 w - -'],
        ['uci' => 'c2e4', 'san' => 'Qxe4', 'fromKey' => 'r2q1rk1/pb1nbppp/1pp1p3/8/2PPnB2/5NP1/PPQ2PBP/R4RK1 w - -', 'toKey' => 'r2q1rk1/pb1nbppp/1pp1p3/8/2PPQB2/5NP1/PP3PBP/R4RK1 b - -'],
    ];

    public const SOKOLSKY_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g2g3', 'san' => 'g3', 'fromKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/6P1/PP2PP1P/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/6P1/PP2PP1P/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/6P1/PP2PP1P/RNBQKBNR w KQkq -'],
        ['uci' => 'f1g2', 'san' => 'Bg2', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/6P1/PP2PP1P/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/6P1/PP2PPBP/RNBQK1NR b KQkq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/6P1/PP2PPBP/RNBQK1NR b KQkq -', 'toKey' => 'rnbqk2r/ppp1bppp/4pn2/3p4/2PP4/6P1/PP2PPBP/RNBQK1NR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqk2r/ppp1bppp/4pn2/3p4/2PP4/6P1/PP2PPBP/RNBQK1NR w KQkq -', 'toKey' => 'rnbqk2r/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQK2R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQK2R b KQkq -', 'toKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQK2R w KQ -', 'toKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQ1RK1 b - -'],
        ['uci' => 'b8d7', 'san' => 'Nbd7', 'fromKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQ1RK1 b - -', 'toKey' => 'r1bq1rk1/pppnbppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQ1RK1 w - -'],
        ['uci' => 'd1c2', 'san' => 'Qc2', 'fromKey' => 'r1bq1rk1/pppnbppp/4pn2/3p4/2PP4/5NP1/PP2PPBP/RNBQ1RK1 w - -', 'toKey' => 'r1bq1rk1/pppnbppp/4pn2/3p4/2PP4/5NP1/PPQ1PPBP/RNB2RK1 b - -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'r1bq1rk1/pppnbppp/4pn2/3p4/2PP4/5NP1/PPQ1PPBP/RNB2RK1 b - -', 'toKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p4/2PP4/5NP1/PPQ1PPBP/RNB2RK1 w - -'],
        ['uci' => 'b1d2', 'san' => 'Nbd2', 'fromKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p4/2PP4/5NP1/PPQ1PPBP/RNB2RK1 w - -', 'toKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p4/2PP4/5NP1/PPQNPPBP/R1B2RK1 b - -'],
        ['uci' => 'b7b6', 'san' => 'b6', 'fromKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p4/2PP4/5NP1/PPQNPPBP/R1B2RK1 b - -', 'toKey' => 'r1bq1rk1/p2nbppp/1pp1pn2/3p4/2PP4/5NP1/PPQNPPBP/R1B2RK1 w - -'],
        ['uci' => 'b2b3', 'san' => 'b3', 'fromKey' => 'r1bq1rk1/p2nbppp/1pp1pn2/3p4/2PP4/5NP1/PPQNPPBP/R1B2RK1 w - -', 'toKey' => 'r1bq1rk1/p2nbppp/1pp1pn2/3p4/2PP4/1P3NP1/P1QNPPBP/R1B2RK1 b - -'],
        ['uci' => 'a7a5', 'san' => 'a5', 'fromKey' => 'r1bq1rk1/p2nbppp/1pp1pn2/3p4/2PP4/1P3NP1/P1QNPPBP/R1B2RK1 b - -', 'toKey' => 'r1bq1rk1/3nbppp/1pp1pn2/p2p4/2PP4/1P3NP1/P1QNPPBP/R1B2RK1 w - -'],
        ['uci' => 'c1b2', 'san' => 'Bb2', 'fromKey' => 'r1bq1rk1/3nbppp/1pp1pn2/p2p4/2PP4/1P3NP1/P1QNPPBP/R1B2RK1 w - -', 'toKey' => 'r1bq1rk1/3nbppp/1pp1pn2/p2p4/2PP4/1P3NP1/PBQNPPBP/R4RK1 b - -'],
        ['uci' => 'c8a6', 'san' => 'Ba6', 'fromKey' => 'r1bq1rk1/3nbppp/1pp1pn2/p2p4/2PP4/1P3NP1/PBQNPPBP/R4RK1 b - -', 'toKey' => 'r2q1rk1/3nbppp/bpp1pn2/p2p4/2PP4/1P3NP1/PBQNPPBP/R4RK1 w - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Catalan Opening course: Closed main line, Closed Sokolsky';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = "Learn the Catalan as White: a Queen's Gambit centre with pawns on c4 and d4, combined with a kingside fianchetto that puts your bishop on the long diagonal towards d5, b7 and a8. Both lines are complete named Closed Catalan records, in which Black keeps the pawn on d5 and builds a solid setup with ...c6 and ...b6. In the Main Line you protect c4 with Qc2, develop with Bf4 and break with e4. In the Sokolsky Variation you support c4 with b3 and fianchetto your second bishop while Black expands with ...a5 and aims at c4 with ...Ba6. Black replies automatically along the selected line; real opponents can choose other continuations.";
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['closed-main-line', 'Closed main line: Qc2, Bf4 and the e4 break', "Twelve White moves. Fianchetto your king's bishop, protect c4 with Qc2, develop with Bf4 and Nbd2, then break with e4 and centralise your queen. Source: Closed, Main Line.", self::CLOSED_SOURCE, self::CLOSED_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'Take space in the centre and open a path for the bishop on c1.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops a knight and controls e4, keeping the choice of pawn structure open for another move.'],
                ['c4', 'Move your pawn from c2 to c4.', "Gain more central space. The pawn controls d5, and together with d4 it gives you the Queen's Gambit centre that the Catalan is built on."],
                ['e6', 'Move your pawn from e7 to e6.', 'Black opens a path for the bishop on f8 and prepares ...d5, aiming for a solid centre.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop a knight, guard d4, control e5 and clear the way for castling kingside.'],
                ['d5', 'Move your pawn from d7 to d5.', "Black takes central space. The pawn on d5 attacks your c4 pawn, and the structure is now the same as in the Queen's Gambit Declined."],
                ['g3', 'Move your pawn from g2 to g3.', 'This is the Catalan move. You prepare Bg2, placing the bishop on the long diagonal that runs through d5 towards b7 and a8. Your c4 pawn is not protected for the moment; in this line Black leaves it alone.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Black develops the bishop and prepares to castle, keeping the pawn on d5 instead of capturing on c4. Lines in which Black holds d5 like this are called the Closed Catalan.'],
                ['Bg2', 'Move your bishop from f1 to g2.', "Complete the fianchetto. The bishop's diagonal runs through f3, e4 and d5 to b7 and a8. Your knight on f3 stands in front of it for now, but whenever the knight moves or the centre opens, the bishop presses along the whole diagonal."],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black puts the king in safety before deciding how to develop the queenside pieces.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle. Your king is sheltered by the pawns and the bishop on g2, and the rook comes to f1.'],
                ['Nbd7', 'Move your knight from b8 to d7.', 'Black develops this knight to d7 rather than c6, so the c-pawn stays free to support d5 with ...c6 or to strike with ...c5. From d7 the knight also watches e5 and c5.'],
                ['Qc2', 'Move your queen from d1 to c2.', 'This is a typical Catalan square for the queen. From c2 it protects your c4 pawn, so ...dxc4 can be answered by recapturing with the queen, and it supports the future e2-e4 break along the c2-e4 diagonal.'],
                ['c6', 'Move your pawn from c7 to c6.', 'Black reinforces d5 with a pawn, forming a solid c6-d5-e6 structure. The aim is to keep the centre closed in front of your g2 bishop while finishing development.'],
                ['Bf4', 'Move your bishop from c1 to f4.', "Develop your last bishop outside the pawn chain. From f4 it controls e5 together with your d4 pawn and f3 knight, which makes Black's freeing advance ...e5 harder to achieve."],
                ['b6', 'Move your pawn from b7 to b6.', 'The bishop on c8 is shut in by the pawn on e6, so Black prepares to develop it on b7 instead, on the same long diagonal as your g2 bishop.'],
                ['Nbd2', 'Move your knight from b1 to d2.', 'Develop the knight to d2 rather than c3. From d2 it supports e4 together with your queen and also guards c4. Because your bishop has already left c1, the knight does not block it.'],
                ['Bb7', 'Move your bishop from c8 to b7.', 'Black completes the fianchetto. For now the bishop sits behind the pawns on c6 and d5, but if the centre opens or Black plays ...c5, it will contest the long diagonal against your g2 bishop.'],
                ['e4', 'Move your pawn from e2 to e4.', 'This is the central break your setup was preparing. Your queen and knight support e4, and the pawn attacks d5, so Black must decide what to do about the centre.'],
                ['dxe4', 'Move your pawn from d5 to e4, capturing the white pawn.', "Black releases the tension by capturing. The centre opens, but the exchanges that follow also give Black's pieces more room."],
                ['Nxe4', 'Move your knight from d2 to e4, capturing the black pawn.', 'Recapture with the knight. It lands on a central square and attacks the knight on f6. Material is level.'],
                ['Nxe4', 'Move your knight from f6 to e4, capturing the white knight.', 'Black exchanges knights. With fewer pieces on the board, the remaining black pieces have more space.'],
                ['Qxe4', 'Move your queen from c2 to e4, capturing the black knight.', "Recapture with the queen, now centralised on e4, where it eyes the pawns on c6 and h7; both are defended for now. The named Closed Main Line ends here. Your next tasks are to bring your rooks to the central files and watch Black's freeing ideas: ...Nf6 would attack your queen, and ...c5 would open the b7 bishop's diagonal towards it."],
            ]],
            ['closed-sokolsky', 'Sokolsky Variation: b3, Bb2 and the fight for c4', 'Ten White moves. Fianchetto early, support c4 with Qc2 and b3, and develop your second bishop on b2 while Black expands with ...a5 and aims at c4 with ...Ba6. Source: Closed, Sokolsky Variation.', self::SOKOLSKY_SOURCE, self::SOKOLSKY_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'Take space in the centre and open a path for the bishop on c1.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops a knight and controls e4, keeping the choice of pawn structure open for another move.'],
                ['c4', 'Move your pawn from c2 to c4.', "Gain more central space. The pawn controls d5, and together with d4 it gives you the Queen's Gambit centre that the Catalan is built on."],
                ['e6', 'Move your pawn from e7 to e6.', 'Black opens a path for the bishop on f8 and prepares ...d5, aiming for a solid centre.'],
                ['g3', 'Move your pawn from g2 to g3.', 'Here you play the Catalan move before Nf3. The pawn prepares Bg2, which puts the bishop on the long diagonal towards d5, b7 and a8.'],
                ['d5', 'Move your pawn from d7 to d5.', "Black takes central space with a Queen's Gambit Declined structure. The pawn attacks your c4 pawn and stands on the long diagonal your bishop is about to use."],
                ['Bg2', 'Move your bishop from f1 to g2.', 'Complete the fianchetto. Because your knight is not on f3 yet, the bishop already presses directly on d5 along the long diagonal, together with your c4 pawn.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Black develops the bishop and prepares to castle, keeping the pawn on d5 instead of capturing on c4. Lines in which Black holds d5 like this are called the Closed Catalan.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop the knight, guard d4, control e5 and clear the way for castling. The knight now stands in front of your bishop, but it can step away later to reopen the diagonal.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black puts the king in safety before deciding how to develop the queenside pieces.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle. Your king is sheltered by the pawns and the bishop on g2, and the rook comes to f1.'],
                ['Nbd7', 'Move your knight from b8 to d7.', 'Black develops this knight to d7 rather than c6, so the c-pawn stays free to support d5 with ...c6 or to strike with ...c5. From d7 the knight also watches e5 and c5.'],
                ['Qc2', 'Move your queen from d1 to c2.', 'This is a typical Catalan square for the queen. From c2 it protects your c4 pawn, so ...dxc4 can be answered by recapturing with the queen, and it supports a future e2-e4 break along the c2-e4 diagonal.'],
                ['c6', 'Move your pawn from c7 to c6.', 'Black reinforces d5 with a pawn, forming a solid c6-d5-e6 structure. The aim is to keep the centre closed in front of your g2 bishop while finishing development.'],
                ['Nbd2', 'Move your knight from b1 to d2.', 'Develop the knight to d2, where it supports e4 and guards c4. The knight now blocks the c1 bishop\'s route towards f4, so in this line that bishop will develop on b2 instead.'],
                ['b6', 'Move your pawn from b7 to b6.', 'The bishop on c8 is shut in by the pawn on e6, so Black prepares to develop it on the queenside, on b7 or a6.'],
                ['b3', 'Move your pawn from b2 to b3.', 'Support c4 with a pawn and prepare Bb2. If Black ever captures on c4, you can recapture with the b-pawn and keep a pawn in the centre.'],
                ['a5', 'Move your pawn from a7 to a5.', 'Black gains space on the queenside. The pawn can advance to a4 to challenge your b3 pawn and open the a-file for the rook on a8.'],
                ['Bb2', 'Move your bishop from c1 to b2.', 'Fianchetto your second bishop. From b2 it defends d4, and with c1 cleared your rooks are now connected along the back rank.'],
                ['Ba6', 'Move your bishop from c8 to a6.', "Instead of contesting the long diagonal from b7, Black's bishop attacks your c4 pawn together with the pawn on d5. Your b3 pawn, queen and knight defend it. The named Sokolsky Variation ends here. Your next tasks are to bring your rooks to the central files and prepare the e4 break, while keeping c4 secure and watching for ...a4."],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='catalan'")) {
            throw new \RuntimeException('The Catalan Opening course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Catalan Opening teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::CLOSED_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Catalan Opening start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('catalan',NULL,6)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('catalan',1,?,?,?,UTC_TIMESTAMP(),'w')", ['Catalan Opening', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('catalan',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('catalan',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Catalan Opening edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='catalan'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Catalan Opening sessions and progress.');
    }
}
