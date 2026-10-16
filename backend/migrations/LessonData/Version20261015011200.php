<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Sicilian Defense course: complete named pinned paths with original teaching notes. */
final class Version20261015011200 extends AbstractMigration
{
    public const NAJDORF_SOURCE = '1. e4 c5 2. Nf3 d6 3. d4 cxd4 4. Nxd4 Nf6 5. Nc3 a6 6. Bg5 e6 7. f4 Be7 8. Qf3 Qc7 9. O-O-O Nbd7';
    public const DRAGON_SOURCE = '1. e4 c5 2. Nf3 d6 3. d4 cxd4 4. Nxd4 Nf6 5. Nc3 g6 6. Be3 Bg7 7. f3 O-O 8. Qd2 Nc6 9. Bc4 Bd7 10. O-O-O Qa5 11. h4 Rfc8 12. Bb3 h5';
    public const SVESHNIKOV_SOURCE = '1. e4 c5 2. Nf3 Nc6 3. d4 cxd4 4. Nxd4 Nf6 5. Nc3 e5 6. Ndb5 d6 7. Bg5 a6 8. Na3 b5 9. Bxf6 gxf6 10. Nd5 f5';

    public const NAJDORF_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/3p4/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pp2pppp/3p4/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/3p4/2p5/3PP3/5N2/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'c5d4', 'san' => 'cxd4', 'fromKey' => 'rnbqkbnr/pp2pppp/3p4/2p5/3PP3/5N2/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/3p4/8/3pP3/5N2/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f3d4', 'san' => 'Nxd4', 'fromKey' => 'rnbqkbnr/pp2pppp/3p4/8/3pP3/5N2/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/3p4/8/3NP3/8/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pp2pppp/3p4/8/3NP3/8/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/3p1n2/8/3NP3/8/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pp2pppp/3p1n2/8/3NP3/8/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/3p1n2/8/3NP3/2N5/PPP2PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'a7a6', 'san' => 'a6', 'fromKey' => 'rnbqkb1r/pp2pppp/3p1n2/8/3NP3/2N5/PPP2PPP/R1BQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/1p2pppp/p2p1n2/8/3NP3/2N5/PPP2PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'rnbqkb1r/1p2pppp/p2p1n2/8/3NP3/2N5/PPP2PPP/R1BQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/1p2pppp/p2p1n2/6B1/3NP3/2N5/PPP2PPP/R2QKB1R b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkb1r/1p2pppp/p2p1n2/6B1/3NP3/2N5/PPP2PPP/R2QKB1R b KQkq -', 'toKey' => 'rnbqkb1r/1p3ppp/p2ppn2/6B1/3NP3/2N5/PPP2PPP/R2QKB1R w KQkq -'],
        ['uci' => 'f2f4', 'san' => 'f4', 'fromKey' => 'rnbqkb1r/1p3ppp/p2ppn2/6B1/3NP3/2N5/PPP2PPP/R2QKB1R w KQkq -', 'toKey' => 'rnbqkb1r/1p3ppp/p2ppn2/6B1/3NPP2/2N5/PPP3PP/R2QKB1R b KQkq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'rnbqkb1r/1p3ppp/p2ppn2/6B1/3NPP2/2N5/PPP3PP/R2QKB1R b KQkq -', 'toKey' => 'rnbqk2r/1p2bppp/p2ppn2/6B1/3NPP2/2N5/PPP3PP/R2QKB1R w KQkq -'],
        ['uci' => 'd1f3', 'san' => 'Qf3', 'fromKey' => 'rnbqk2r/1p2bppp/p2ppn2/6B1/3NPP2/2N5/PPP3PP/R2QKB1R w KQkq -', 'toKey' => 'rnbqk2r/1p2bppp/p2ppn2/6B1/3NPP2/2N2Q2/PPP3PP/R3KB1R b KQkq -'],
        ['uci' => 'd8c7', 'san' => 'Qc7', 'fromKey' => 'rnbqk2r/1p2bppp/p2ppn2/6B1/3NPP2/2N2Q2/PPP3PP/R3KB1R b KQkq -', 'toKey' => 'rnb1k2r/1pq1bppp/p2ppn2/6B1/3NPP2/2N2Q2/PPP3PP/R3KB1R w KQkq -'],
        ['uci' => 'e1c1', 'san' => 'O-O-O', 'fromKey' => 'rnb1k2r/1pq1bppp/p2ppn2/6B1/3NPP2/2N2Q2/PPP3PP/R3KB1R w KQkq -', 'toKey' => 'rnb1k2r/1pq1bppp/p2ppn2/6B1/3NPP2/2N2Q2/PPP3PP/2KR1B1R b kq -'],
        ['uci' => 'b8d7', 'san' => 'Nbd7', 'fromKey' => 'rnb1k2r/1pq1bppp/p2ppn2/6B1/3NPP2/2N2Q2/PPP3PP/2KR1B1R b kq -', 'toKey' => 'r1b1k2r/1pqnbppp/p2ppn2/6B1/3NPP2/2N2Q2/PPP3PP/2KR1B1R w kq -'],
    ];

    public const DRAGON_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/3p4/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pp2pppp/3p4/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/3p4/2p5/3PP3/5N2/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'c5d4', 'san' => 'cxd4', 'fromKey' => 'rnbqkbnr/pp2pppp/3p4/2p5/3PP3/5N2/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/3p4/8/3pP3/5N2/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f3d4', 'san' => 'Nxd4', 'fromKey' => 'rnbqkbnr/pp2pppp/3p4/8/3pP3/5N2/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkbnr/pp2pppp/3p4/8/3NP3/8/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pp2pppp/3p4/8/3NP3/8/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/3p1n2/8/3NP3/8/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pp2pppp/3p1n2/8/3NP3/8/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/3p1n2/8/3NP3/2N5/PPP2PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'g7g6', 'san' => 'g6', 'fromKey' => 'rnbqkb1r/pp2pppp/3p1n2/8/3NP3/2N5/PPP2PPP/R1BQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/pp2pp1p/3p1np1/8/3NP3/2N5/PPP2PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'c1e3', 'san' => 'Be3', 'fromKey' => 'rnbqkb1r/pp2pp1p/3p1np1/8/3NP3/2N5/PPP2PPP/R1BQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/pp2pp1p/3p1np1/8/3NP3/2N1B3/PPP2PPP/R2QKB1R b KQkq -'],
        ['uci' => 'f8g7', 'san' => 'Bg7', 'fromKey' => 'rnbqkb1r/pp2pp1p/3p1np1/8/3NP3/2N1B3/PPP2PPP/R2QKB1R b KQkq -', 'toKey' => 'rnbqk2r/pp2ppbp/3p1np1/8/3NP3/2N1B3/PPP2PPP/R2QKB1R w KQkq -'],
        ['uci' => 'f2f3', 'san' => 'f3', 'fromKey' => 'rnbqk2r/pp2ppbp/3p1np1/8/3NP3/2N1B3/PPP2PPP/R2QKB1R w KQkq -', 'toKey' => 'rnbqk2r/pp2ppbp/3p1np1/8/3NP3/2N1BP2/PPP3PP/R2QKB1R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/pp2ppbp/3p1np1/8/3NP3/2N1BP2/PPP3PP/R2QKB1R b KQkq -', 'toKey' => 'rnbq1rk1/pp2ppbp/3p1np1/8/3NP3/2N1BP2/PPP3PP/R2QKB1R w KQ -'],
        ['uci' => 'd1d2', 'san' => 'Qd2', 'fromKey' => 'rnbq1rk1/pp2ppbp/3p1np1/8/3NP3/2N1BP2/PPP3PP/R2QKB1R w KQ -', 'toKey' => 'rnbq1rk1/pp2ppbp/3p1np1/8/3NP3/2N1BP2/PPPQ2PP/R3KB1R b KQ -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbq1rk1/pp2ppbp/3p1np1/8/3NP3/2N1BP2/PPPQ2PP/R3KB1R b KQ -', 'toKey' => 'r1bq1rk1/pp2ppbp/2np1np1/8/3NP3/2N1BP2/PPPQ2PP/R3KB1R w KQ -'],
        ['uci' => 'f1c4', 'san' => 'Bc4', 'fromKey' => 'r1bq1rk1/pp2ppbp/2np1np1/8/3NP3/2N1BP2/PPPQ2PP/R3KB1R w KQ -', 'toKey' => 'r1bq1rk1/pp2ppbp/2np1np1/8/2BNP3/2N1BP2/PPPQ2PP/R3K2R b KQ -'],
        ['uci' => 'c8d7', 'san' => 'Bd7', 'fromKey' => 'r1bq1rk1/pp2ppbp/2np1np1/8/2BNP3/2N1BP2/PPPQ2PP/R3K2R b KQ -', 'toKey' => 'r2q1rk1/pp1bppbp/2np1np1/8/2BNP3/2N1BP2/PPPQ2PP/R3K2R w KQ -'],
        ['uci' => 'e1c1', 'san' => 'O-O-O', 'fromKey' => 'r2q1rk1/pp1bppbp/2np1np1/8/2BNP3/2N1BP2/PPPQ2PP/R3K2R w KQ -', 'toKey' => 'r2q1rk1/pp1bppbp/2np1np1/8/2BNP3/2N1BP2/PPPQ2PP/2KR3R b - -'],
        ['uci' => 'd8a5', 'san' => 'Qa5', 'fromKey' => 'r2q1rk1/pp1bppbp/2np1np1/8/2BNP3/2N1BP2/PPPQ2PP/2KR3R b - -', 'toKey' => 'r4rk1/pp1bppbp/2np1np1/q7/2BNP3/2N1BP2/PPPQ2PP/2KR3R w - -'],
        ['uci' => 'h2h4', 'san' => 'h4', 'fromKey' => 'r4rk1/pp1bppbp/2np1np1/q7/2BNP3/2N1BP2/PPPQ2PP/2KR3R w - -', 'toKey' => 'r4rk1/pp1bppbp/2np1np1/q7/2BNP2P/2N1BP2/PPPQ2P1/2KR3R b - -'],
        ['uci' => 'f8c8', 'san' => 'Rfc8', 'fromKey' => 'r4rk1/pp1bppbp/2np1np1/q7/2BNP2P/2N1BP2/PPPQ2P1/2KR3R b - -', 'toKey' => 'r1r3k1/pp1bppbp/2np1np1/q7/2BNP2P/2N1BP2/PPPQ2P1/2KR3R w - -'],
        ['uci' => 'c4b3', 'san' => 'Bb3', 'fromKey' => 'r1r3k1/pp1bppbp/2np1np1/q7/2BNP2P/2N1BP2/PPPQ2P1/2KR3R w - -', 'toKey' => 'r1r3k1/pp1bppbp/2np1np1/q7/3NP2P/1BN1BP2/PPPQ2P1/2KR3R b - -'],
        ['uci' => 'h7h5', 'san' => 'h5', 'fromKey' => 'r1r3k1/pp1bppbp/2np1np1/q7/3NP2P/1BN1BP2/PPPQ2P1/2KR3R b - -', 'toKey' => 'r1r3k1/pp1bppb1/2np1np1/q6p/3NP2P/1BN1BP2/PPPQ2P1/2KR3R w - -'],
    ];

    public const SVESHNIKOV_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pp1ppppp/8/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pp1ppppp/2n5/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'r1bqkbnr/pp1ppppp/2n5/2p5/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pp1ppppp/2n5/2p5/3PP3/5N2/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'c5d4', 'san' => 'cxd4', 'fromKey' => 'r1bqkbnr/pp1ppppp/2n5/2p5/3PP3/5N2/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pp1ppppp/2n5/8/3pP3/5N2/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f3d4', 'san' => 'Nxd4', 'fromKey' => 'r1bqkbnr/pp1ppppp/2n5/8/3pP3/5N2/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pp1ppppp/2n5/8/3NP3/8/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/pp1ppppp/2n5/8/3NP3/8/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pp1ppppp/2n2n2/8/3NP3/8/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r1bqkb1r/pp1ppppp/2n2n2/8/3NP3/8/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pp1ppppp/2n2n2/8/3NP3/2N5/PPP2PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'r1bqkb1r/pp1ppppp/2n2n2/8/3NP3/2N5/PPP2PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pp1p1ppp/2n2n2/4p3/3NP3/2N5/PPP2PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'd4b5', 'san' => 'Ndb5', 'fromKey' => 'r1bqkb1r/pp1p1ppp/2n2n2/4p3/3NP3/2N5/PPP2PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pp1p1ppp/2n2n2/1N2p3/4P3/2N5/PPP2PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'r1bqkb1r/pp1p1ppp/2n2n2/1N2p3/4P3/2N5/PPP2PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2np1n2/1N2p3/4P3/2N5/PPP2PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'r1bqkb1r/pp3ppp/2np1n2/1N2p3/4P3/2N5/PPP2PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2np1n2/1N2p1B1/4P3/2N5/PPP2PPP/R2QKB1R b KQkq -'],
        ['uci' => 'a7a6', 'san' => 'a6', 'fromKey' => 'r1bqkb1r/pp3ppp/2np1n2/1N2p1B1/4P3/2N5/PPP2PPP/R2QKB1R b KQkq -', 'toKey' => 'r1bqkb1r/1p3ppp/p1np1n2/1N2p1B1/4P3/2N5/PPP2PPP/R2QKB1R w KQkq -'],
        ['uci' => 'b5a3', 'san' => 'Na3', 'fromKey' => 'r1bqkb1r/1p3ppp/p1np1n2/1N2p1B1/4P3/2N5/PPP2PPP/R2QKB1R w KQkq -', 'toKey' => 'r1bqkb1r/1p3ppp/p1np1n2/4p1B1/4P3/N1N5/PPP2PPP/R2QKB1R b KQkq -'],
        ['uci' => 'b7b5', 'san' => 'b5', 'fromKey' => 'r1bqkb1r/1p3ppp/p1np1n2/4p1B1/4P3/N1N5/PPP2PPP/R2QKB1R b KQkq -', 'toKey' => 'r1bqkb1r/5ppp/p1np1n2/1p2p1B1/4P3/N1N5/PPP2PPP/R2QKB1R w KQkq -'],
        ['uci' => 'g5f6', 'san' => 'Bxf6', 'fromKey' => 'r1bqkb1r/5ppp/p1np1n2/1p2p1B1/4P3/N1N5/PPP2PPP/R2QKB1R w KQkq -', 'toKey' => 'r1bqkb1r/5ppp/p1np1B2/1p2p3/4P3/N1N5/PPP2PPP/R2QKB1R b KQkq -'],
        ['uci' => 'g7f6', 'san' => 'gxf6', 'fromKey' => 'r1bqkb1r/5ppp/p1np1B2/1p2p3/4P3/N1N5/PPP2PPP/R2QKB1R b KQkq -', 'toKey' => 'r1bqkb1r/5p1p/p1np1p2/1p2p3/4P3/N1N5/PPP2PPP/R2QKB1R w KQkq -'],
        ['uci' => 'c3d5', 'san' => 'Nd5', 'fromKey' => 'r1bqkb1r/5p1p/p1np1p2/1p2p3/4P3/N1N5/PPP2PPP/R2QKB1R w KQkq -', 'toKey' => 'r1bqkb1r/5p1p/p1np1p2/1p1Np3/4P3/N7/PPP2PPP/R2QKB1R b KQkq -'],
        ['uci' => 'f6f5', 'san' => 'f5', 'fromKey' => 'r1bqkb1r/5p1p/p1np1p2/1p1Np3/4P3/N7/PPP2PPP/R2QKB1R b KQkq -', 'toKey' => 'r1bqkb1r/5p1p/p1np4/1p1Npp2/4P3/N7/PPP2PPP/R2QKB1R w KQkq -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Sicilian Defense course: Najdorf, Dragon Yugoslav Attack, Sveshnikov';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. e4 with ...c5. Instead of copying White\'s central pawn, you fight for d4 from the side and usually trade your c-pawn for White\'s d-pawn. You get an extra central pawn and a half-open c-file; White gets quicker development and attacking chances. Play three complete named main lines: the Najdorf, where ...a6 meets White\'s Bg5, f4 and Qf3 attack; the Dragon against the Yugoslav Attack, with kings on opposite wings; and the Sveshnikov, where ...e5 gains time at the cost of a hole on d5. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['najdorf-main-line', 'Najdorf: ...a6 against the Bg5 and f4 attack', 'Nine Black moves. Trade your c-pawn for White\'s d-pawn, control b5 with ...a6, meet Bg5 and f4 with ...e6 and ...Be7, then bring your queen and knight into play. Source: Najdorf Variation, Main Line.', self::NAJDORF_SOURCE, self::NAJDORF_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens lines for the queen and the bishop on f1. The Sicilian Defense is a reply to this first move.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Fight for d4 from the side instead of mirroring White with ...e5. Your c-pawn controls d4 and b4 and makes the position unbalanced from the first move.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the knight, adds control of d4 and prepares to open the centre with d4.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Control e5, so White\'s e-pawn cannot easily advance and push away a knight on f6 later. The move also opens a diagonal for your bishop on c8.'],
                ['d4', 'Move your pawn from d2 to d4.', 'White opens the centre and offers to exchange the d-pawn for your c-pawn.'],
                ['cxd4', 'Move your pawn from c5 to d4, capturing the white pawn.', 'Exchange your c-pawn for White\'s d-pawn. This is the central Sicilian trade: you keep two central pawns against White\'s one, and the c-file is now half-open for your rooks and queen.'],
                ['Nxd4', 'Move your knight from f3 to d4, capturing the black pawn.', 'White recaptures with a centralised knight. White leads in development, which is the price you pay for your central pawn majority.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight and attack the pawn on e4. Nothing defends that pawn yet, so White has to spend a move on it.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White defends e4 and develops another piece.'],
                ['a6', 'Move your pawn from a7 to a6.', 'This is the Najdorf. The quiet pawn move takes b5 away from White\'s knights and bishop, and keeps open plans such as ...e5 in the centre or ...b5 on the queenside.'],
                ['Bg5', 'Move your bishop from c1 to g5.', 'White develops the bishop and aims at your knight on f6, which guards d5 and e4. Your pawn on e7 still stands between the bishop and your queen.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Strengthen the centre: the pawn on e6 controls d5 and f5 and opens the diagonal for the bishop on f8. Your knight is now pinned against your queen, so you will deal with that next.'],
                ['f4', 'Move your pawn from f2 to f4.', 'White adds the f-pawn to the attack. It prepares e5, which would hit the pinned knight on f6, and gives White more space on the kingside.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Put the bishop between the knight and the queen. This ends the pin and prepares to castle on the kingside.'],
                ['Qf3', 'Move your queen from d1 to f3.', 'White brings the queen next to the kingside pawns, where it supports a later g4 advance, and clears d1 so White can castle queenside.'],
                ['Qc7', 'Move your queen from d8 to c7.', 'Put your queen on the half-open c-file, where it looks at the knight on c3 and the queenside where White\'s king is heading. It also leaves d8 free.'],
                ['O-O-O', 'Castle queenside: move your king from e1 to c1. The rook moves from a1 to d1 automatically.', 'White castles queenside and brings the rook to the d-file behind the knight on d4. With pieces aimed at the centre and kingside, White is ready for a fast attack with e5 or g4.'],
                ['Nbd7', 'Move your knight from b8 to d7.', 'Develop your last knight. From d7 it controls e5 and c5, and if White captures on f6 you can recapture with a piece and keep your pawns intact. The named Main Line ends here: your next task is to start queenside play with ...b5 and decide when your king is safe enough to castle.'],
            ]],
            ['dragon-yugoslav', 'Dragon: the fianchetto against the Yugoslav Attack', 'Twelve Black moves. Fianchetto your bishop on the long diagonal, castle, use the c-file against White\'s queenside king and stop the h-pawn with ...h5. Source: Dragon Variation, Yugoslav Attack, Soltis Variation.', self::DRAGON_SOURCE, self::DRAGON_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens lines for the queen and the bishop on f1. The Sicilian Defense is a reply to this first move.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Fight for d4 from the side instead of mirroring White with ...e5. Your c-pawn controls d4 and b4 and makes the position unbalanced from the first move.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the knight, adds control of d4 and prepares to open the centre with d4.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Control e5, so White\'s e-pawn cannot easily advance and push away a knight on f6 later. The move also opens a diagonal for your bishop on c8.'],
                ['d4', 'Move your pawn from d2 to d4.', 'White opens the centre and offers to exchange the d-pawn for your c-pawn.'],
                ['cxd4', 'Move your pawn from c5 to d4, capturing the white pawn.', 'Exchange your c-pawn for White\'s d-pawn. This is the central Sicilian trade: you keep two central pawns against White\'s one, and the c-file is now half-open for your rooks and queen.'],
                ['Nxd4', 'Move your knight from f3 to d4, capturing the black pawn.', 'White recaptures with a centralised knight. White leads in development, which is the price you pay for your central pawn majority.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight and attack the pawn on e4. Nothing defends that pawn yet, so White has to spend a move on it.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White defends e4 and develops another piece.'],
                ['g6', 'Move your pawn from g7 to g6.', 'This is the Dragon. Prepare to fianchetto your bishop on g7, on the long diagonal that runs through d4 towards White\'s queenside.'],
                ['Be3', 'Move your bishop from c1 to e3.', 'White develops the bishop to e3, where it protects the knight on d4 and prepares Qd2.'],
                ['Bg7', 'Move your bishop from f8 to g7.', 'Fianchetto the bishop. It guards your king after castling, and once your knight on f6 moves it will look straight down the long diagonal towards d4 and b2.'],
                ['f3', 'Move your pawn from f2 to f3.', 'This begins the Yugoslav Attack. The pawn on f3 secures e4 and keeps your knight off g4, where it would attack the bishop on e3. It also prepares a kingside pawn storm with g4 and h4.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle your king to safety behind the fianchetto. Your rook also comes to f8.'],
                ['Qd2', 'Move your queen from d1 to d2.', 'White lines up queen and bishop on the c1-h6 diagonal, preparing Bh6 to exchange your fianchettoed bishop, and clears d1 for queenside castling.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and attack the knight on d4. White keeps it defended with the bishop and queen, but any exchange on d4 would open your bishop\'s diagonal.'],
                ['Bc4', 'Move your bishop from f1 to c4.', 'White puts the bishop on the a2-g8 diagonal. It controls d5, so ...d5 is harder to play, and it aims through e6 at f7 near your king.'],
                ['Bd7', 'Move your bishop from c8 to d7.', 'Develop your last minor piece. The bishop defends the knight on c6 and clears c8, so a rook can come to the c-file.'],
                ['O-O-O', 'Castle queenside: move your king from e1 to c1. The rook moves from a1 to d1 automatically.', 'White castles queenside, so the kings are on opposite wings. White will attack with the kingside pawns, while you look for play on the c-file and the queenside.'],
                ['Qa5', 'Move your queen from d8 to a5.', 'Bring the queen towards White\'s king. From a5 it attacks the pawn on a2 and the knight on c3, and adds weight to your queenside play.'],
                ['h4', 'Move your pawn from h2 to h4.', 'White starts the pawn storm. The h-pawn heads for h5 to open the h-file against your king.'],
                ['Rfc8', 'Move your rook from f8 to c8.', 'Put the rook on the half-open c-file. Once your knight on c6 moves, the rook will bear down on c4 and c3, the squares in front of White\'s king.'],
                ['Bb3', 'Move your bishop from c4 to b3.', 'White moves the bishop from c4 to b3 before you can attack it with ...Ne5 and your rook behind it. On b3 it stays on the same diagonal and also defends a2.'],
                ['h5', 'Move your pawn from h7 to h5.', 'This is the Soltis Variation. Your pawn blocks White\'s h-pawn, so the h-file stays closed and White\'s attack slows down until White prepares g4. The named Soltis Variation ends here: your next task is to build queenside play with ...Ne5, ...Nc4 and the c-file, while watching for White\'s g4 break.'],
            ]],
            ['sveshnikov', 'Sveshnikov: ...e5, ...b5 and the f5 break', 'Ten Black moves. Chase the knight with ...e5, accept the hole on d5, gain queenside space with ...b5 and break with ...f5, keeping the bishop pair. Source: Lasker-Pelikan Variation, Sveshnikov Variation.', self::SVESHNIKOV_SOURCE, self::SVESHNIKOV_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens lines for the queen and the bishop on f1. The Sicilian Defense is a reply to this first move.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Fight for d4 from the side instead of mirroring White with ...e5. Your c-pawn controls d4 and b4 and makes the position unbalanced from the first move.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the knight, adds control of d4 and prepares to open the centre with d4.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight. It controls d4 and e5 and keeps several Sicilian setups available.'],
                ['d4', 'Move your pawn from d2 to d4.', 'White opens the centre and offers to exchange the d-pawn for your c-pawn.'],
                ['cxd4', 'Move your pawn from c5 to d4, capturing the white pawn.', 'Exchange your c-pawn for White\'s d-pawn. You gain an extra central pawn and a half-open c-file for your rooks and queen.'],
                ['Nxd4', 'Move your knight from f3 to d4, capturing the black pawn.', 'White recaptures with a centralised knight. Your knight on c6 now attacks it, but the queen on d1 defends it.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight and attack the pawn on e4. Nothing defends that pawn yet, so White has to spend a move on it.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White defends e4 and develops another piece.'],
                ['e5', 'Move your pawn from e7 to e5.', 'This is the Sveshnikov. Chase the knight from d4 and gain time. In return you leave a hole on d5, because no black pawn can control that square any more, and your d-pawn will be backward.'],
                ['Ndb5', 'Move your knight from d4 to b5.', 'White\'s knight jumps to b5 and eyes d6, a square your ...e5 weakened. From d6 it would give check and attack f7 and b7.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Take d6 with your pawn, so the knight cannot land there. You accept a backward pawn on d6 in exchange for active piece play.'],
                ['Bg5', 'Move your bishop from c1 to g5.', 'White pins your knight against the queen. That knight is the piece that controls d5, so White is trying to take it away.'],
                ['a6', 'Move your pawn from a7 to a6.', 'Ask the knight on b5 to leave. It has no comfortable square: d4 is covered by your e5 pawn and c3 is occupied by the other knight.'],
                ['Na3', 'Move your knight from b5 to a3.', 'White retreats the knight to the edge of the board. It will try to return to play later via c4 or c2.'],
                ['b5', 'Move your pawn from b7 to b5.', 'Gain space on the queenside and take c4 away from the knight on a3, so it stays out of play for longer.'],
                ['Bxf6', 'Move your bishop from g5 to f6, capturing the black knight.', 'White gives up the bishop for your knight, removing the only black piece that controlled d5.'],
                ['gxf6', 'Move your pawn from g7 to f6, capturing the white bishop.', 'Recapture with the g-pawn. Your pawns are doubled, but you keep the bishop pair, gain the half-open g-file and can later push this pawn to f5 to strike at e4.'],
                ['Nd5', 'Move your knight from c3 to d5.', 'White plants the knight on d5, the hole created by your ...e5. No black pawn can ever attack it there.'],
                ['f5', 'Move your pawn from f6 to f5.', 'Strike at e4 with your f-pawn. This is the key Sveshnikov break: it challenges White\'s centre and clears f6, opening the d8-h4 diagonal for your queen. The named Sveshnikov Variation ends here: your next tasks are to develop the bishop to g7, castle, and use your bishop pair, while preparing to challenge the d5 knight with ...Be6 or ...Ne7.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='sicilian'")) {
            throw new \RuntimeException('The Sicilian Defense course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Sicilian Defense teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::NAJDORF_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Sicilian Defense start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('sicilian',NULL,7)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('sicilian',1,?,?,?,UTC_TIMESTAMP(),'b')", ['Sicilian Defense', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('sicilian',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('sicilian',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Sicilian Defense edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='sicilian'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Sicilian Defense sessions and progress.');
    }
}
