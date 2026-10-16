<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New King's Indian Defense course: complete named pinned paths with original teaching notes. */
final class Version20261015011300 extends AbstractMigration
{
    public const CLASSICAL_SOURCE = '1. d4 Nf6 2. c4 g6 3. Nc3 Bg7 4. e4 d6 5. Nf3 O-O 6. Be2 e5 7. O-O Nc6 8. d5 Ne7 9. Ne1 Nd7 10. Be3 f5 11. f3 f4 12. Bf2 g5 13. Rc1 Ng6 14. c5';
    public const PANNO_SOURCE = '1. d4 Nf6 2. c4 g6 3. Nf3 Bg7 4. g3 O-O 5. Bg2 d6 6. O-O Nc6 7. Nc3 a6 8. d5 Na5 9. Nd2 c5 10. Qc2 Rb8 11. b3 b5 12. Bb2 bxc4 13. bxc4 Bh6';

    public const CLASSICAL_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g7g6', 'san' => 'g6', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'f8g7', 'san' => 'Bg7', 'fromKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk2r/ppppppbp/5np1/8/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqk2r/ppppppbp/5np1/8/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk2r/ppppppbp/5np1/8/2PPP3/2N5/PP3PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'rnbqk2r/ppppppbp/5np1/8/2PPP3/2N5/PP3PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk2r/ppp1ppbp/3p1np1/8/2PPP3/2N5/PP3PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqk2r/ppp1ppbp/3p1np1/8/2PPP3/2N5/PP3PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk2r/ppp1ppbp/3p1np1/8/2PPP3/2N2N2/PP3PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/ppp1ppbp/3p1np1/8/2PPP3/2N2N2/PP3PPP/R1BQKB1R b KQkq -', 'toKey' => 'rnbq1rk1/ppp1ppbp/3p1np1/8/2PPP3/2N2N2/PP3PPP/R1BQKB1R w KQ -'],
        ['uci' => 'f1e2', 'san' => 'Be2', 'fromKey' => 'rnbq1rk1/ppp1ppbp/3p1np1/8/2PPP3/2N2N2/PP3PPP/R1BQKB1R w KQ -', 'toKey' => 'rnbq1rk1/ppp1ppbp/3p1np1/8/2PPP3/2N2N2/PP2BPPP/R1BQK2R b KQ -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbq1rk1/ppp1ppbp/3p1np1/8/2PPP3/2N2N2/PP2BPPP/R1BQK2R b KQ -', 'toKey' => 'rnbq1rk1/ppp2pbp/3p1np1/4p3/2PPP3/2N2N2/PP2BPPP/R1BQK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'rnbq1rk1/ppp2pbp/3p1np1/4p3/2PPP3/2N2N2/PP2BPPP/R1BQK2R w KQ -', 'toKey' => 'rnbq1rk1/ppp2pbp/3p1np1/4p3/2PPP3/2N2N2/PP2BPPP/R1BQ1RK1 b - -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbq1rk1/ppp2pbp/3p1np1/4p3/2PPP3/2N2N2/PP2BPPP/R1BQ1RK1 b - -', 'toKey' => 'r1bq1rk1/ppp2pbp/2np1np1/4p3/2PPP3/2N2N2/PP2BPPP/R1BQ1RK1 w - -'],
        ['uci' => 'd4d5', 'san' => 'd5', 'fromKey' => 'r1bq1rk1/ppp2pbp/2np1np1/4p3/2PPP3/2N2N2/PP2BPPP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/ppp2pbp/2np1np1/3Pp3/2P1P3/2N2N2/PP2BPPP/R1BQ1RK1 b - -'],
        ['uci' => 'c6e7', 'san' => 'Ne7', 'fromKey' => 'r1bq1rk1/ppp2pbp/2np1np1/3Pp3/2P1P3/2N2N2/PP2BPPP/R1BQ1RK1 b - -', 'toKey' => 'r1bq1rk1/ppp1npbp/3p1np1/3Pp3/2P1P3/2N2N2/PP2BPPP/R1BQ1RK1 w - -'],
        ['uci' => 'f3e1', 'san' => 'Ne1', 'fromKey' => 'r1bq1rk1/ppp1npbp/3p1np1/3Pp3/2P1P3/2N2N2/PP2BPPP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/ppp1npbp/3p1np1/3Pp3/2P1P3/2N5/PP2BPPP/R1BQNRK1 b - -'],
        ['uci' => 'f6d7', 'san' => 'Nd7', 'fromKey' => 'r1bq1rk1/ppp1npbp/3p1np1/3Pp3/2P1P3/2N5/PP2BPPP/R1BQNRK1 b - -', 'toKey' => 'r1bq1rk1/pppnnpbp/3p2p1/3Pp3/2P1P3/2N5/PP2BPPP/R1BQNRK1 w - -'],
        ['uci' => 'c1e3', 'san' => 'Be3', 'fromKey' => 'r1bq1rk1/pppnnpbp/3p2p1/3Pp3/2P1P3/2N5/PP2BPPP/R1BQNRK1 w - -', 'toKey' => 'r1bq1rk1/pppnnpbp/3p2p1/3Pp3/2P1P3/2N1B3/PP2BPPP/R2QNRK1 b - -'],
        ['uci' => 'f7f5', 'san' => 'f5', 'fromKey' => 'r1bq1rk1/pppnnpbp/3p2p1/3Pp3/2P1P3/2N1B3/PP2BPPP/R2QNRK1 b - -', 'toKey' => 'r1bq1rk1/pppnn1bp/3p2p1/3Ppp2/2P1P3/2N1B3/PP2BPPP/R2QNRK1 w - -'],
        ['uci' => 'f2f3', 'san' => 'f3', 'fromKey' => 'r1bq1rk1/pppnn1bp/3p2p1/3Ppp2/2P1P3/2N1B3/PP2BPPP/R2QNRK1 w - -', 'toKey' => 'r1bq1rk1/pppnn1bp/3p2p1/3Ppp2/2P1P3/2N1BP2/PP2B1PP/R2QNRK1 b - -'],
        ['uci' => 'f5f4', 'san' => 'f4', 'fromKey' => 'r1bq1rk1/pppnn1bp/3p2p1/3Ppp2/2P1P3/2N1BP2/PP2B1PP/R2QNRK1 b - -', 'toKey' => 'r1bq1rk1/pppnn1bp/3p2p1/3Pp3/2P1Pp2/2N1BP2/PP2B1PP/R2QNRK1 w - -'],
        ['uci' => 'e3f2', 'san' => 'Bf2', 'fromKey' => 'r1bq1rk1/pppnn1bp/3p2p1/3Pp3/2P1Pp2/2N1BP2/PP2B1PP/R2QNRK1 w - -', 'toKey' => 'r1bq1rk1/pppnn1bp/3p2p1/3Pp3/2P1Pp2/2N2P2/PP2BBPP/R2QNRK1 b - -'],
        ['uci' => 'g6g5', 'san' => 'g5', 'fromKey' => 'r1bq1rk1/pppnn1bp/3p2p1/3Pp3/2P1Pp2/2N2P2/PP2BBPP/R2QNRK1 b - -', 'toKey' => 'r1bq1rk1/pppnn1bp/3p4/3Pp1p1/2P1Pp2/2N2P2/PP2BBPP/R2QNRK1 w - -'],
        ['uci' => 'a1c1', 'san' => 'Rc1', 'fromKey' => 'r1bq1rk1/pppnn1bp/3p4/3Pp1p1/2P1Pp2/2N2P2/PP2BBPP/R2QNRK1 w - -', 'toKey' => 'r1bq1rk1/pppnn1bp/3p4/3Pp1p1/2P1Pp2/2N2P2/PP2BBPP/2RQNRK1 b - -'],
        ['uci' => 'e7g6', 'san' => 'Ng6', 'fromKey' => 'r1bq1rk1/pppnn1bp/3p4/3Pp1p1/2P1Pp2/2N2P2/PP2BBPP/2RQNRK1 b - -', 'toKey' => 'r1bq1rk1/pppn2bp/3p2n1/3Pp1p1/2P1Pp2/2N2P2/PP2BBPP/2RQNRK1 w - -'],
        ['uci' => 'c4c5', 'san' => 'c5', 'fromKey' => 'r1bq1rk1/pppn2bp/3p2n1/3Pp1p1/2P1Pp2/2N2P2/PP2BBPP/2RQNRK1 w - -', 'toKey' => 'r1bq1rk1/pppn2bp/3p2n1/2PPp1p1/4Pp2/2N2P2/PP2BBPP/2RQNRK1 b - -'],
    ];

    public const PANNO_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g7g6', 'san' => 'g6', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/5N2/PP2PPPP/RNBQKB1R b KQkq -'],
        ['uci' => 'f8g7', 'san' => 'Bg7', 'fromKey' => 'rnbqkb1r/pppppp1p/5np1/8/2PP4/5N2/PP2PPPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqk2r/ppppppbp/5np1/8/2PP4/5N2/PP2PPPP/RNBQKB1R w KQkq -'],
        ['uci' => 'g2g3', 'san' => 'g3', 'fromKey' => 'rnbqk2r/ppppppbp/5np1/8/2PP4/5N2/PP2PPPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqk2r/ppppppbp/5np1/8/2PP4/5NP1/PP2PP1P/RNBQKB1R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/ppppppbp/5np1/8/2PP4/5NP1/PP2PP1P/RNBQKB1R b KQkq -', 'toKey' => 'rnbq1rk1/ppppppbp/5np1/8/2PP4/5NP1/PP2PP1P/RNBQKB1R w KQ -'],
        ['uci' => 'f1g2', 'san' => 'Bg2', 'fromKey' => 'rnbq1rk1/ppppppbp/5np1/8/2PP4/5NP1/PP2PP1P/RNBQKB1R w KQ -', 'toKey' => 'rnbq1rk1/ppppppbp/5np1/8/2PP4/5NP1/PP2PPBP/RNBQK2R b KQ -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'rnbq1rk1/ppppppbp/5np1/8/2PP4/5NP1/PP2PPBP/RNBQK2R b KQ -', 'toKey' => 'rnbq1rk1/ppp1ppbp/3p1np1/8/2PP4/5NP1/PP2PPBP/RNBQK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'rnbq1rk1/ppp1ppbp/3p1np1/8/2PP4/5NP1/PP2PPBP/RNBQK2R w KQ -', 'toKey' => 'rnbq1rk1/ppp1ppbp/3p1np1/8/2PP4/5NP1/PP2PPBP/RNBQ1RK1 b - -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbq1rk1/ppp1ppbp/3p1np1/8/2PP4/5NP1/PP2PPBP/RNBQ1RK1 b - -', 'toKey' => 'r1bq1rk1/ppp1ppbp/2np1np1/8/2PP4/5NP1/PP2PPBP/RNBQ1RK1 w - -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r1bq1rk1/ppp1ppbp/2np1np1/8/2PP4/5NP1/PP2PPBP/RNBQ1RK1 w - -', 'toKey' => 'r1bq1rk1/ppp1ppbp/2np1np1/8/2PP4/2N2NP1/PP2PPBP/R1BQ1RK1 b - -'],
        ['uci' => 'a7a6', 'san' => 'a6', 'fromKey' => 'r1bq1rk1/ppp1ppbp/2np1np1/8/2PP4/2N2NP1/PP2PPBP/R1BQ1RK1 b - -', 'toKey' => 'r1bq1rk1/1pp1ppbp/p1np1np1/8/2PP4/2N2NP1/PP2PPBP/R1BQ1RK1 w - -'],
        ['uci' => 'd4d5', 'san' => 'd5', 'fromKey' => 'r1bq1rk1/1pp1ppbp/p1np1np1/8/2PP4/2N2NP1/PP2PPBP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/1pp1ppbp/p1np1np1/3P4/2P5/2N2NP1/PP2PPBP/R1BQ1RK1 b - -'],
        ['uci' => 'c6a5', 'san' => 'Na5', 'fromKey' => 'r1bq1rk1/1pp1ppbp/p1np1np1/3P4/2P5/2N2NP1/PP2PPBP/R1BQ1RK1 b - -', 'toKey' => 'r1bq1rk1/1pp1ppbp/p2p1np1/n2P4/2P5/2N2NP1/PP2PPBP/R1BQ1RK1 w - -'],
        ['uci' => 'f3d2', 'san' => 'Nd2', 'fromKey' => 'r1bq1rk1/1pp1ppbp/p2p1np1/n2P4/2P5/2N2NP1/PP2PPBP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/1pp1ppbp/p2p1np1/n2P4/2P5/2N3P1/PP1NPPBP/R1BQ1RK1 b - -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'r1bq1rk1/1pp1ppbp/p2p1np1/n2P4/2P5/2N3P1/PP1NPPBP/R1BQ1RK1 b - -', 'toKey' => 'r1bq1rk1/1p2ppbp/p2p1np1/n1pP4/2P5/2N3P1/PP1NPPBP/R1BQ1RK1 w - c6'],
        ['uci' => 'd1c2', 'san' => 'Qc2', 'fromKey' => 'r1bq1rk1/1p2ppbp/p2p1np1/n1pP4/2P5/2N3P1/PP1NPPBP/R1BQ1RK1 w - c6', 'toKey' => 'r1bq1rk1/1p2ppbp/p2p1np1/n1pP4/2P5/2N3P1/PPQNPPBP/R1B2RK1 b - -'],
        ['uci' => 'a8b8', 'san' => 'Rb8', 'fromKey' => 'r1bq1rk1/1p2ppbp/p2p1np1/n1pP4/2P5/2N3P1/PPQNPPBP/R1B2RK1 b - -', 'toKey' => '1rbq1rk1/1p2ppbp/p2p1np1/n1pP4/2P5/2N3P1/PPQNPPBP/R1B2RK1 w - -'],
        ['uci' => 'b2b3', 'san' => 'b3', 'fromKey' => '1rbq1rk1/1p2ppbp/p2p1np1/n1pP4/2P5/2N3P1/PPQNPPBP/R1B2RK1 w - -', 'toKey' => '1rbq1rk1/1p2ppbp/p2p1np1/n1pP4/2P5/1PN3P1/P1QNPPBP/R1B2RK1 b - -'],
        ['uci' => 'b7b5', 'san' => 'b5', 'fromKey' => '1rbq1rk1/1p2ppbp/p2p1np1/n1pP4/2P5/1PN3P1/P1QNPPBP/R1B2RK1 b - -', 'toKey' => '1rbq1rk1/4ppbp/p2p1np1/nppP4/2P5/1PN3P1/P1QNPPBP/R1B2RK1 w - -'],
        ['uci' => 'c1b2', 'san' => 'Bb2', 'fromKey' => '1rbq1rk1/4ppbp/p2p1np1/nppP4/2P5/1PN3P1/P1QNPPBP/R1B2RK1 w - -', 'toKey' => '1rbq1rk1/4ppbp/p2p1np1/nppP4/2P5/1PN3P1/PBQNPPBP/R4RK1 b - -'],
        ['uci' => 'b5c4', 'san' => 'bxc4', 'fromKey' => '1rbq1rk1/4ppbp/p2p1np1/nppP4/2P5/1PN3P1/PBQNPPBP/R4RK1 b - -', 'toKey' => '1rbq1rk1/4ppbp/p2p1np1/n1pP4/2p5/1PN3P1/PBQNPPBP/R4RK1 w - -'],
        ['uci' => 'b3c4', 'san' => 'bxc4', 'fromKey' => '1rbq1rk1/4ppbp/p2p1np1/n1pP4/2p5/1PN3P1/PBQNPPBP/R4RK1 w - -', 'toKey' => '1rbq1rk1/4ppbp/p2p1np1/n1pP4/2P5/2N3P1/PBQNPPBP/R4RK1 b - -'],
        ['uci' => 'g7h6', 'san' => 'Bh6', 'fromKey' => '1rbq1rk1/4ppbp/p2p1np1/n1pP4/2P5/2N3P1/PBQNPPBP/R4RK1 b - -', 'toKey' => '1rbq1rk1/4pp1p/p2p1npb/n1pP4/2P5/2N3P1/PBQNPPBP/R4RK1 w - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the King\'s Indian Defense course: Classical main line, Fianchetto Panno';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = "Play Black against 1. d4 and 2. c4. In the King's Indian Defense you let White build a broad pawn centre, fianchetto your bishop on g7 and castle, and only then challenge the centre. When White closes it with d5, the pawn chains point at opposite wings: you attack on the kingside, White on the queenside. Learn two complete named lines: the Classical main line, where you storm the kingside with ...f5, ...f4 and ...g5, and the Fianchetto Variation with the Panno setup, where you fight on the queenside with ...Nc6, ...a6, ...Rb8 and ...b5. White replies automatically along the selected line; real opponents can choose other continuations.";
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['classical-main-line', 'Classical main line: kingside storm against queenside break', 'Thirteen Black moves. Fianchetto, castle and strike with ...e5, reroute your knights after White closes the centre, then push ...f5, ...f4 and ...g5 while White breaks with c4-c5. Source: Orthodox Variation, Classical System, Kozul Gambit.', self::CLASSICAL_SOURCE, self::CLASSICAL_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a diagonal for the bishop on c1.'],
                ['Nf6', 'Move your knight from g8 to f6.', "Develop the knight to a square where it controls e4 and d5. You do not occupy the centre with a pawn yet: this flexible, hypermodern approach is the basis of the King's Indian."],
                ['c4', 'Move your pawn from c2 to c4.', 'White gains space on the queenside and adds control of d5.'],
                ['g6', 'Move your pawn from g7 to g6.', "Prepare to fianchetto your bishop on g7. From there it will work along the long diagonal towards White's centre and queenside."],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the knight, controls d5 and e4, and prepares to play e2-e4.'],
                ['Bg7', 'Move your bishop from f8 to g7.', 'Complete the fianchetto. Once the knight on f6 moves, the bishop will look straight down the long diagonal towards d4. Your king can now castle quickly.'],
                ['e4', 'Move your pawn from e2 to e4.', "White builds a big pawn centre on c4, d4 and e4. In the King's Indian you allow this, planning to challenge the centre once your king is safe."],
                ['d6', 'Move your pawn from d7 to d6.', 'Control e5, so White cannot simply push e4-e5 to chase your knight. The pawn also prepares ...e5 and opens a path for the bishop on c8.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the kingside knight, supports d4 and controls e5.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle into the safety of the fianchetto. Your king sits behind the pawns and the bishop on g7, and the rook comes to f8, behind the f-pawn you will push later.'],
                ['Be2', 'Move your bishop from f1 to e2.', 'White develops the bishop modestly and prepares to castle.'],
                ['e5', 'Move your pawn from e7 to e5.', "Challenge White's centre with a pawn of your own. Your pawn on d6 supports it, and White must choose between exchanging on e5, advancing with d5 or keeping the tension."],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles and keeps the central tension for now.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and add a second attacker to d4, alongside your pawn on e5. White has to settle the tension in the centre.'],
                ['d5', 'Move your pawn from d4 to d5.', "White closes the centre and attacks your knight. The pawn chains now point at opposite wings: White's pawns on e4 and d5 point towards the queenside, where White will play c4-c5; your pawns on e5 and d6 point towards the kingside, where you will attack."],
                ['Ne7', 'Move your knight from c6 to e7.', 'Retreat the knight towards the kingside, where your play will be. From e7 it supports a later ...f5 and can continue to g6.'],
                ['Ne1', 'Move your knight from f3 to e1.', 'White reroutes the knight. It can go to d3 to support c4-c5, and leaving f3 makes room for f2-f3, which will reinforce e4.'],
                ['Nd7', 'Move your knight from f6 to d7.', 'Move the knight off f6 so your f-pawn can advance to f5. On d7 it also watches c5, the square White needs for the queenside break.'],
                ['Be3', 'Move your bishop from c1 to e3.', 'White develops the bishop to a square where it also controls c5, supporting the c4-c5 advance.'],
                ['f5', 'Move your pawn from f7 to f5.', 'This is your main pawn break. The pawn attacks e4 and begins your kingside play, with the rook on f8 standing behind it.'],
                ['f3', 'Move your pawn from f2 to f3.', 'White supports e4 with a pawn, so the centre stays closed.'],
                ['f4', 'Move your pawn from f5 to f4.', 'Advance with gain of time: the pawn attacks the bishop on e3. You release the pressure on e4, but gain kingside space and a pawn wedge for ...g5 and ...g4.'],
                ['Bf2', 'Move your bishop from e3 to f2.', "White retreats the bishop. From f2 it still controls c5 and stays close to White's king."],
                ['g5', 'Move your pawn from g6 to g5.', "Start the pawn storm. The g-pawn supports f4 and prepares ...g4 to attack f3 and open lines towards White's king."],
                ['Rc1', 'Move your rook from a1 to c1.', 'White puts the rook on the c-file, ready for the moment the file opens after c4-c5.'],
                ['Ng6', 'Move your knight from e7 to g6.', "Bring the knight into the kingside attack. On g6 it supports the pawn on f4 and controls h4, joining your pawns in the storm against White's king."],
                ['c5', 'Move your pawn from c4 to c5.', "White makes the queenside break. The pawn attacks d6 and aims to open the c-file for the rook on c1; White is ready to give up this pawn to open lines quickly. The named Kozul Gambit ends here. The middlegame is a race: your next task is to keep the kingside storm moving with ...h5 and ...g4 while making sure White's queenside play does not arrive first."],
            ]],
            ['fianchetto-panno', 'Fianchetto Panno: queenside play with ...b5', "Thirteen Black moves. Meet White's fianchetto with ...Nc6 and ...a6, send the knight to a5 against c4, then open the b-file with ...Rb8 and ...b5. Source: Fianchetto Variation, Panno Variation, Donner Line.", self::PANNO_SOURCE, self::PANNO_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a diagonal for the bishop on c1.'],
                ['Nf6', 'Move your knight from g8 to f6.', "Develop the knight to a square where it controls e4 and d5. You do not occupy the centre with a pawn yet: this flexible, hypermodern approach is the basis of the King's Indian."],
                ['c4', 'Move your pawn from c2 to c4.', 'White gains space on the queenside and adds control of d5.'],
                ['g6', 'Move your pawn from g7 to g6.', "Prepare to fianchetto your bishop on g7. From there it will work along the long diagonal towards White's centre and queenside."],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the knight, supports d4 and controls e5.'],
                ['Bg7', 'Move your bishop from f8 to g7.', 'Complete the fianchetto. The bishop will work along the long diagonal once the knight on f6 moves, and your king can castle next.'],
                ['g3', 'Move your pawn from g2 to g3.', "White chooses the Fianchetto Variation. The bishop will go to g2, where it protects White's king and looks along the long light-square diagonal."],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', "Castle early. Your king is safe behind the fianchetto, and you can decide later how to challenge White's centre."],
                ['Bg2', 'Move your bishop from f1 to g2.', 'White completes the fianchetto. The bishop is behind the knight on f3 for now, but will reach the centre once the knight moves.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Control e5 and open a path for the bishop on c8. Your setup stays flexible while you see where White puts the pieces.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles, completing a kingside setup that mirrors yours.'],
                ['Nc6', 'Move your knight from b8 to c6.', "This starts the Panno setup. The knight attacks d4 straight away, and you prepare queenside play with ...a6, ...Rb8 and ...b5 against White's pawn on c4."],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the other knight, which controls d5 and e4.'],
                ['a6', 'Move your pawn from a7 to a6.', "Prepare ...b5. The pawn on a6 will support the b-pawn when it challenges c4, and it keeps White's knight out of b5."],
                ['d5', 'Move your pawn from d4 to d5.', "White gains space and attacks your knight on c6. With the centre closed on d5, the fight turns to White's pawn on c4."],
                ['Na5', 'Move your knight from c6 to a5.', 'Move the knight with an attack of its own. From a5 it hits the pawn on c4, which nothing defends at this moment.'],
                ['Nd2', 'Move your knight from f3 to d2.', 'White defends c4 with the knight. Moving it from f3 also opens the long diagonal for the bishop on g2, which now supports d5.'],
                ['c5', 'Move your pawn from c7 to c5.', "Gain queenside space. The pawn controls b4, so White cannot easily chase your knight from a5 with b2-b4, and it keeps White's c-pawn on c4, where ...b5 can attack it."],
                ['Qc2', 'Move your queen from d1 to c2.', 'White brings the queen to c2, where it protects the knight on c3 and watches e4. White is preparing b2-b3 to give c4 a pawn defender.'],
                ['Rb8', 'Move your rook from a8 to b8.', 'Put the rook behind your b-pawn. When ...b5 opens the b-file, the rook will already be in place.'],
                ['b3', 'Move your pawn from b2 to b3.', 'White supports c4 with a pawn and prepares to develop the bishop on b2.'],
                ['b5', 'Move your pawn from b7 to b5.', 'This is your queenside lever. The pawn attacks c4, and your pawn on a6 and rook on b8 support it, so you are ready to open the b-file.'],
                ['Bb2', 'Move your bishop from c1 to b2.', 'White develops the last minor piece on the long diagonal and connects the rooks.'],
                ['bxc4', 'Move your pawn from b5 to c4, capturing the white pawn.', 'Capture on c4 to open the b-file. After White recaptures, your rook on b8 will face the bishop on b2.'],
                ['bxc4', 'Move your pawn from b3 to c4, capturing the black pawn.', 'White recaptures. The b-file is now open, and your rook on b8 attacks the bishop on b2, which the queen defends.'],
                ['Bh6', 'Move your bishop from g7 to h6.', 'Use the bishop on another diagonal. On g7 it was blocked by your own knight on f6; on h6 it attacks the knight on d2, the only defender of c4, so that knight cannot easily move. The named Donner Line ends here. Your next task is to bring more pieces to the queenside and increase the pressure on c4 and along the b-file.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='kings-indian'")) {
            throw new \RuntimeException("The King's Indian Defense course already exists.");
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('King\'s Indian Defense teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::CLASSICAL_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported King\'s Indian Defense start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('kings-indian',NULL,8)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('kings-indian',1,?,?,?,UTC_TIMESTAMP(),'b')", ['King\'s Indian Defense', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('kings-indian',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('kings-indian',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved King\'s Indian Defense edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='kings-indian'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve King\'s Indian Defense sessions and progress.');
    }
}
