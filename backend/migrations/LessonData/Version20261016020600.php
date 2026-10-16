<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Grünfeld Defense course: complete named pinned paths with original teaching notes. */
final class Version20261016020600 extends AbstractMigration
{
    public const EXCHANGE_SPASSKY_SOURCE = '1. d4 Nf6 2. c4 g6 3. Nc3 d5 4. cxd5 Nxd5 5. e4 Nxc3 6. bxc3 Bg7 7. Bc4 c5 8. Ne2 Nc6 9. Be3 O-O 10. O-O Bg4 11. f3 Na5 12. Bd3 cxd4 13. cxd4 Be6';
    public const RUSSIAN_KERES_SOURCE = '1. d4 Nf6 2. c4 g6 3. Nc3 d5 4. Nf3 Bg7 5. Qb3 dxc4 6. Qxc4 O-O 7. e4 Bg4 8. Be3 Nfd7 9. Be2 Nb6 10. Qd3 Nc6 11. O-O-O';

    public const EXCHANGE_SPASSKY_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g7g6', 'san' => 'g6', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp1pp1p/5np1/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'c4d5', 'san' => 'cxd5', 'fromKey' => 'rnbqkb1r/ppp1pp1p/5np1/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp1pp1p/5np1/3P4/3P4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'f6d5', 'san' => 'Nxd5', 'fromKey' => 'rnbqkb1r/ppp1pp1p/5np1/3P4/3P4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp1pp1p/6p1/3n4/3P4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkb1r/ppp1pp1p/6p1/3n4/3P4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp1pp1p/6p1/3n4/3PP3/2N5/PP3PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'd5c3', 'san' => 'Nxc3', 'fromKey' => 'rnbqkb1r/ppp1pp1p/6p1/3n4/3PP3/2N5/PP3PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp1pp1p/6p1/8/3PP3/2n5/PP3PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'b2c3', 'san' => 'bxc3', 'fromKey' => 'rnbqkb1r/ppp1pp1p/6p1/8/3PP3/2n5/PP3PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp1pp1p/6p1/8/3PP3/2P5/P4PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'f8g7', 'san' => 'Bg7', 'fromKey' => 'rnbqkb1r/ppp1pp1p/6p1/8/3PP3/2P5/P4PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk2r/ppp1ppbp/6p1/8/3PP3/2P5/P4PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'f1c4', 'san' => 'Bc4', 'fromKey' => 'rnbqk2r/ppp1ppbp/6p1/8/3PP3/2P5/P4PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk2r/ppp1ppbp/6p1/8/2BPP3/2P5/P4PPP/R1BQK1NR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqk2r/ppp1ppbp/6p1/8/2BPP3/2P5/P4PPP/R1BQK1NR b KQkq -', 'toKey' => 'rnbqk2r/pp2ppbp/6p1/2p5/2BPP3/2P5/P4PPP/R1BQK1NR w KQkq -'],
        ['uci' => 'g1e2', 'san' => 'Ne2', 'fromKey' => 'rnbqk2r/pp2ppbp/6p1/2p5/2BPP3/2P5/P4PPP/R1BQK1NR w KQkq -', 'toKey' => 'rnbqk2r/pp2ppbp/6p1/2p5/2BPP3/2P5/P3NPPP/R1BQK2R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqk2r/pp2ppbp/6p1/2p5/2BPP3/2P5/P3NPPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqk2r/pp2ppbp/2n3p1/2p5/2BPP3/2P5/P3NPPP/R1BQK2R w KQkq -'],
        ['uci' => 'c1e3', 'san' => 'Be3', 'fromKey' => 'r1bqk2r/pp2ppbp/2n3p1/2p5/2BPP3/2P5/P3NPPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqk2r/pp2ppbp/2n3p1/2p5/2BPP3/2P1B3/P3NPPP/R2QK2R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pp2ppbp/2n3p1/2p5/2BPP3/2P1B3/P3NPPP/R2QK2R b KQkq -', 'toKey' => 'r1bq1rk1/pp2ppbp/2n3p1/2p5/2BPP3/2P1B3/P3NPPP/R2QK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bq1rk1/pp2ppbp/2n3p1/2p5/2BPP3/2P1B3/P3NPPP/R2QK2R w KQ -', 'toKey' => 'r1bq1rk1/pp2ppbp/2n3p1/2p5/2BPP3/2P1B3/P3NPPP/R2Q1RK1 b - -'],
        ['uci' => 'c8g4', 'san' => 'Bg4', 'fromKey' => 'r1bq1rk1/pp2ppbp/2n3p1/2p5/2BPP3/2P1B3/P3NPPP/R2Q1RK1 b - -', 'toKey' => 'r2q1rk1/pp2ppbp/2n3p1/2p5/2BPP1b1/2P1B3/P3NPPP/R2Q1RK1 w - -'],
        ['uci' => 'f2f3', 'san' => 'f3', 'fromKey' => 'r2q1rk1/pp2ppbp/2n3p1/2p5/2BPP1b1/2P1B3/P3NPPP/R2Q1RK1 w - -', 'toKey' => 'r2q1rk1/pp2ppbp/2n3p1/2p5/2BPP1b1/2P1BP2/P3N1PP/R2Q1RK1 b - -'],
        ['uci' => 'c6a5', 'san' => 'Na5', 'fromKey' => 'r2q1rk1/pp2ppbp/2n3p1/2p5/2BPP1b1/2P1BP2/P3N1PP/R2Q1RK1 b - -', 'toKey' => 'r2q1rk1/pp2ppbp/6p1/n1p5/2BPP1b1/2P1BP2/P3N1PP/R2Q1RK1 w - -'],
        ['uci' => 'c4d3', 'san' => 'Bd3', 'fromKey' => 'r2q1rk1/pp2ppbp/6p1/n1p5/2BPP1b1/2P1BP2/P3N1PP/R2Q1RK1 w - -', 'toKey' => 'r2q1rk1/pp2ppbp/6p1/n1p5/3PP1b1/2PBBP2/P3N1PP/R2Q1RK1 b - -'],
        ['uci' => 'c5d4', 'san' => 'cxd4', 'fromKey' => 'r2q1rk1/pp2ppbp/6p1/n1p5/3PP1b1/2PBBP2/P3N1PP/R2Q1RK1 b - -', 'toKey' => 'r2q1rk1/pp2ppbp/6p1/n7/3pP1b1/2PBBP2/P3N1PP/R2Q1RK1 w - -'],
        ['uci' => 'c3d4', 'san' => 'cxd4', 'fromKey' => 'r2q1rk1/pp2ppbp/6p1/n7/3pP1b1/2PBBP2/P3N1PP/R2Q1RK1 w - -', 'toKey' => 'r2q1rk1/pp2ppbp/6p1/n7/3PP1b1/3BBP2/P3N1PP/R2Q1RK1 b - -'],
        ['uci' => 'g4e6', 'san' => 'Be6', 'fromKey' => 'r2q1rk1/pp2ppbp/6p1/n7/3PP1b1/3BBP2/P3N1PP/R2Q1RK1 b - -', 'toKey' => 'r2q1rk1/pp2ppbp/4b1p1/n7/3PP3/3BBP2/P3N1PP/R2Q1RK1 w - -'],
    ];

    public const RUSSIAN_KERES_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g7g6', 'san' => 'g6', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp1pp1p/5np1/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkb1r/ppp1pp1p/5np1/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp1pp1p/5np1/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -'],
        ['uci' => 'f8g7', 'san' => 'Bg7', 'fromKey' => 'rnbqkb1r/ppp1pp1p/5np1/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -', 'toKey' => 'rnbqk2r/ppp1ppbp/5np1/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -'],
        ['uci' => 'd1b3', 'san' => 'Qb3', 'fromKey' => 'rnbqk2r/ppp1ppbp/5np1/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -', 'toKey' => 'rnbqk2r/ppp1ppbp/5np1/3p4/2PP4/1QN2N2/PP2PPPP/R1B1KB1R b KQkq -'],
        ['uci' => 'd5c4', 'san' => 'dxc4', 'fromKey' => 'rnbqk2r/ppp1ppbp/5np1/3p4/2PP4/1QN2N2/PP2PPPP/R1B1KB1R b KQkq -', 'toKey' => 'rnbqk2r/ppp1ppbp/5np1/8/2pP4/1QN2N2/PP2PPPP/R1B1KB1R w KQkq -'],
        ['uci' => 'b3c4', 'san' => 'Qxc4', 'fromKey' => 'rnbqk2r/ppp1ppbp/5np1/8/2pP4/1QN2N2/PP2PPPP/R1B1KB1R w KQkq -', 'toKey' => 'rnbqk2r/ppp1ppbp/5np1/8/2QP4/2N2N2/PP2PPPP/R1B1KB1R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/ppp1ppbp/5np1/8/2QP4/2N2N2/PP2PPPP/R1B1KB1R b KQkq -', 'toKey' => 'rnbq1rk1/ppp1ppbp/5np1/8/2QP4/2N2N2/PP2PPPP/R1B1KB1R w KQ -'],
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbq1rk1/ppp1ppbp/5np1/8/2QP4/2N2N2/PP2PPPP/R1B1KB1R w KQ -', 'toKey' => 'rnbq1rk1/ppp1ppbp/5np1/8/2QPP3/2N2N2/PP3PPP/R1B1KB1R b KQ -'],
        ['uci' => 'c8g4', 'san' => 'Bg4', 'fromKey' => 'rnbq1rk1/ppp1ppbp/5np1/8/2QPP3/2N2N2/PP3PPP/R1B1KB1R b KQ -', 'toKey' => 'rn1q1rk1/ppp1ppbp/5np1/8/2QPP1b1/2N2N2/PP3PPP/R1B1KB1R w KQ -'],
        ['uci' => 'c1e3', 'san' => 'Be3', 'fromKey' => 'rn1q1rk1/ppp1ppbp/5np1/8/2QPP1b1/2N2N2/PP3PPP/R1B1KB1R w KQ -', 'toKey' => 'rn1q1rk1/ppp1ppbp/5np1/8/2QPP1b1/2N1BN2/PP3PPP/R3KB1R b KQ -'],
        ['uci' => 'f6d7', 'san' => 'Nfd7', 'fromKey' => 'rn1q1rk1/ppp1ppbp/5np1/8/2QPP1b1/2N1BN2/PP3PPP/R3KB1R b KQ -', 'toKey' => 'rn1q1rk1/pppnppbp/6p1/8/2QPP1b1/2N1BN2/PP3PPP/R3KB1R w KQ -'],
        ['uci' => 'f1e2', 'san' => 'Be2', 'fromKey' => 'rn1q1rk1/pppnppbp/6p1/8/2QPP1b1/2N1BN2/PP3PPP/R3KB1R w KQ -', 'toKey' => 'rn1q1rk1/pppnppbp/6p1/8/2QPP1b1/2N1BN2/PP2BPPP/R3K2R b KQ -'],
        ['uci' => 'd7b6', 'san' => 'Nb6', 'fromKey' => 'rn1q1rk1/pppnppbp/6p1/8/2QPP1b1/2N1BN2/PP2BPPP/R3K2R b KQ -', 'toKey' => 'rn1q1rk1/ppp1ppbp/1n4p1/8/2QPP1b1/2N1BN2/PP2BPPP/R3K2R w KQ -'],
        ['uci' => 'c4d3', 'san' => 'Qd3', 'fromKey' => 'rn1q1rk1/ppp1ppbp/1n4p1/8/2QPP1b1/2N1BN2/PP2BPPP/R3K2R w KQ -', 'toKey' => 'rn1q1rk1/ppp1ppbp/1n4p1/8/3PP1b1/2NQBN2/PP2BPPP/R3K2R b KQ -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rn1q1rk1/ppp1ppbp/1n4p1/8/3PP1b1/2NQBN2/PP2BPPP/R3K2R b KQ -', 'toKey' => 'r2q1rk1/ppp1ppbp/1nn3p1/8/3PP1b1/2NQBN2/PP2BPPP/R3K2R w KQ -'],
        ['uci' => 'e1c1', 'san' => 'O-O-O', 'fromKey' => 'r2q1rk1/ppp1ppbp/1nn3p1/8/3PP1b1/2NQBN2/PP2BPPP/R3K2R w KQ -', 'toKey' => 'r2q1rk1/ppp1ppbp/1nn3p1/8/3PP1b1/2NQBN2/PP2BPPP/2KR3R b - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Grünfeld Defense course: Exchange Variation, Spassky Variation; Russian Variation, Keres Variation';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. d4 and 2. c4. In the Grünfeld Defense you develop with ...Nf6 and ...g6 and then strike with ...d5. You let White build a big pawn centre and attack it with pieces: the bishop on g7, the knights and the ...c5 break all aim at d4. Learn two complete named lines. In the Exchange Variation, Spassky Variation, White takes on d5 and builds a centre with c3, d4 and e4, and you press d4 with ...c5, ...Nc6 and ...Bg4. In the Russian Variation, Keres Variation, White\'s queen goes to b3 and c4, and you take on c4, castle and chase the queen with ...Nfd7-b6 while lining up on d4. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['exchange-spassky', 'Exchange Variation: ...c5 and ...Nc6 against the big centre', 'Thirteen Black moves. Exchange knights on c3, press White\'s pawn on d4 with the g7 bishop, ...c5 and ...Nc6, castle, then use ...Bg4 and ...Na5 before opening the c-file with ...cxd4. Source: Exchange Variation, Spassky Variation.', self::EXCHANGE_SPASSKY_SOURCE, self::EXCHANGE_SPASSKY_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a diagonal for the bishop on c1.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight towards the centre, where it controls e4 and d5. You keep your d-pawn back for now and let White show a setup first.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White gains space on the queenside and adds control of d5.'],
                ['g6', 'Move your pawn from g7 to g6.', 'Prepare to fianchetto your bishop on g7. Once your knight on f6 moves, the bishop will work along the long diagonal towards d4.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the knight, controls d5 and e4, and prepares to play e2-e4.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Strike at the centre with the d-pawn: this is the Grünfeld Defense. Your pawn attacks c4 and controls e4. If White exchanges on d5 and builds a big pawn centre, you will attack that centre with your pieces and the ...c5 break.'],
                ['cxd5', 'Move your pawn from c4 to d5, capturing the black pawn.', 'White captures on d5: this is where the Exchange Variation chooses its own path. White removes your central pawn and gets ready to gain time against your knight with e4.'],
                ['Nxd5', 'Move your knight from f6 to d5, capturing the white pawn.', 'Recapture with the knight and restore the material balance. Your knight stands in the centre for now, but White\'s e-pawn can attack it.'],
                ['e4', 'Move your pawn from e2 to e4.', 'White attacks your knight with the e-pawn and occupies the centre with pawns on d4 and e4.'],
                ['Nxc3', 'Move your knight from d5 to c3, capturing the white knight.', 'Exchange knights instead of retreating. Your knight on c3 also attacks the queen on d1, so White must recapture at once, and only the b-pawn can do it.'],
                ['bxc3', 'Move your pawn from b2 to c3, capturing the black knight.', 'White recaptures with the b-pawn. The pawns on c3, d4 and e4 form a broad centre, but the pawn on a2 is now isolated and the centre has to be defended against your pieces.'],
                ['Bg7', 'Move your bishop from f8 to g7.', 'Develop the bishop on the long diagonal. With your knight gone from f6, it already attacks d4, and behind that pawn lie c3 and the rook on a1. Your king is also ready to castle.'],
                ['Bc4', 'Move your bishop from f1 to c4.', 'White develops the bishop to c4, where it controls d5 and aims through e6 at the pawn on f7.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Strike at d4 with the c-pawn. Three of your units now attack it: the pawn on c5, the bishop on g7 and the queen on d8, whose path down the d-file is clear. White has two defenders, the pawn on c3 and the queen on d1, so White has to keep adding protection.'],
                ['Ne2', 'Move your knight from g1 to e2.', 'White develops the knight to e2 rather than f3 and adds a third defender to d4. The f-pawn stays free to advance later.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and attack d4 a fourth time. Your knight, pawn, bishop and queen now all aim at the pawn, against three white defenders.'],
                ['Be3', 'Move your bishop from c1 to e3.', 'White develops the bishop and defends d4 a fourth time, so the pawn now has as many defenders as attackers.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle your king to safety behind the fianchetto. Your rook also comes to f8.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles as well. Both kings are safe, and the fight around d4 continues.'],
                ['Bg4', 'Move your bishop from c8 to g4.', 'Develop your last minor piece with a threat. The bishop attacks the knight on e2, one of the defenders of d4, and the knight is pinned to the queen on d1: if it moves, your bishop can take the queen.'],
                ['f3', 'Move your pawn from f2 to f3.', 'White attacks the bishop with the f-pawn. The pawn on f3 also defends e4 and blocks the diagonal between your bishop and the queen.'],
                ['Na5', 'Move your knight from c6 to a5.', 'Leave your bishop where it is and attack the bishop on c4 instead. Both bishops are now attacked and neither is defended: if White takes yours with fxg4, you take back a piece with ...Nxc4, and the material stays level.'],
                ['Bd3', 'Move your bishop from c4 to d3.', 'White saves the bishop by moving it to d3, where it defends e4. The bishop now blocks the d-file, so the queen on d1 no longer defends d4, and your bishop on g4 is still attacked by the f3-pawn.'],
                ['cxd4', 'Move your pawn from c5 to d4, capturing the white pawn.', 'Exchange pawns on d4 before saving your bishop. If White takes the bishop with fxg4 instead of recapturing, ...dxe3 wins a piece back.'],
                ['cxd4', 'Move your pawn from c3 to d4, capturing the black pawn.', 'White recaptures with the c-pawn and keeps two pawns in the centre. The c-file is now open, and no pawn can defend d4 any more: only the bishop on e3 and the knight on e2 protect it.'],
                ['Be6', 'Move your bishop from g4 to e6.', 'Now save your bishop by retreating it to e6. From there it controls d5 and c4 and attacks the pawn on a2, which the rook on a1 defends. The named Spassky Variation ends here: your next task is to put your rook on the open c-file with ...Rc8 and keep pressing d4 with your bishop on g7 and your queen.'],
            ]],
            ['russian-keres', 'Russian Variation: ...dxc4 and ...Nb6 against Qb3', 'Ten Black moves. Take on c4 to gain time against the queen, castle and develop with ...Bg4, then reroute the f6 knight to b6 and bring the other knight to c6, aiming at d4. Source: Russian Variation, Keres Variation.', self::RUSSIAN_KERES_SOURCE, self::RUSSIAN_KERES_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a diagonal for the bishop on c1.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight towards the centre, where it controls e4 and d5. You keep your d-pawn back for now and let White show a setup first.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White gains space on the queenside and adds control of d5.'],
                ['g6', 'Move your pawn from g7 to g6.', 'Prepare to fianchetto your bishop on g7. Once your knight on f6 moves, the bishop will work along the long diagonal towards d4.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the knight, controls d5 and e4, and prepares to play e2-e4.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Strike at the centre with the d-pawn: this is the Grünfeld Defense. Your pawn attacks c4 and controls e4. If White exchanges on d5 and builds a big pawn centre, you will attack that centre with your pieces and the ...c5 break.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the kingside knight instead of capturing on d5: this is where the Russian Variation chooses its own path. The knight defends d4 and controls e5.'],
                ['Bg7', 'Move your bishop from f8 to g7.', 'Develop the bishop. For now it stands behind your knight on f6, but it is ready to work along the long diagonal towards d4, and your king can castle next.'],
                ['Qb3', 'Move your queen from d1 to b3.', 'White brings the queen to b3. It lines up behind the c4-pawn against d5 and attacks the pawn on b7, which your bishop on c8 defends.'],
                ['dxc4', 'Move your pawn from d5 to c4, capturing the white pawn.', 'Take on c4. Your pawn on c4 now attacks the queen on b3, so White\'s queen has to move again to win the pawn back.'],
                ['Qxc4', 'Move your queen from b3 to c4, capturing the black pawn.', 'White takes the pawn back with the queen. The queen on c4 attacks the pawns on c7 and f7, and White can now play e4 to build a full pawn centre.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle your king to safety. Your king on g8 and rook on f8 now both defend f7, the pawn the queen on c4 is eyeing.'],
                ['e4', 'Move your pawn from e2 to e4.', 'White builds a broad pawn centre on d4 and e4, backed by the knights and the queen. Your plan in the Russian Variation is to attack this centre with your pieces.'],
                ['Bg4', 'Move your bishop from c8 to g4.', 'Develop the bishop and attack the knight on f3. That knight defends d4 and e5, and only the pawn on g2 protects it.'],
                ['Be3', 'Move your bishop from c1 to e3.', 'White develops the bishop and adds a defender to d4.'],
                ['Nfd7', 'Move your knight from f6 to d7.', 'Move the knight from f6 to d7. This opens the long diagonal, so your bishop on g7 now attacks d4, and the knight can continue to b6 to attack the queen.'],
                ['Be2', 'Move your bishop from f1 to e2.', 'White develops the bishop, which defends the knight on f3 and clears the way for White to castle on either side.'],
                ['Nb6', 'Move your knight from d7 to b6.', 'Attack the queen on c4 with the knight. The knight also controls d5, and by leaving d7 it reopens the d-file, so your queen on d8 attacks d4 again.'],
                ['Qd3', 'Move your queen from c4 to d3.', 'White retreats the queen to d3, where it defends d4 and e4.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop your last knight and add a third attacker on d4, next to the bishop on g7 and the queen on d8. Your bishop on g4 also attacks the knight on f3, one of the three defenders of d4.'],
                ['O-O-O', 'Castle queenside: move your king from e1 to c1. The rook moves from a1 to d1 automatically.', 'White castles queenside, so the kings are on opposite wings, and the rook on d1 now stands behind the queen on the d-file. The named Keres Variation ends here: your next task is to keep the pressure on d4 and prepare a central break with ...e5 or ...f5.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='grunfeld'")) {
            throw new \RuntimeException('The Grünfeld Defense course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Grünfeld Defense teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::EXCHANGE_SPASSKY_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Grünfeld Defense start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('grunfeld',NULL,16)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('grunfeld',1,?,?,?,UTC_TIMESTAMP(),'b')", ['Grünfeld Defense', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('grunfeld',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('grunfeld',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Grünfeld Defense edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='grunfeld'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Grünfeld Defense sessions and progress.');
    }
}
