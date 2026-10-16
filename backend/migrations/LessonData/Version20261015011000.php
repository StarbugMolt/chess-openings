<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Ruy Lopez course: complete named pinned paths with original teaching notes. */
final class Version20261015011000 extends AbstractMigration
{
    public const CHIGORIN_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7 6. Re1 b5 7. Bb3 d6 8. c3 O-O 9. h3 Na5 10. Bc2 c5 11. d4 Qc7 12. Nbd2 Bd7 13. Nf1 Rfe8 14. Ne3 g6';
    public const BREYER_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7 6. Re1 b5 7. Bb3 d6 8. c3 O-O 9. h3 Nb8 10. d4 Nbd7 11. Nbd2 Bb7 12. Bc2 c5';
    public const MARSHALL_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7 6. Re1 b5 7. Bb3 O-O 8. c3 d5 9. exd5 Nxd5 10. Nxe5 Nxe5 11. Rxe5 c6 12. d4 Bd6 13. Re1 Qh4 14. g3 Qh3 15. Be3 Bg4 16. Qd3 Rae8 17. Nd2 Re6 18. a4 Qh5';

    public const CHIGORIN_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1b5', 'san' => 'Bb5', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'a7a6', 'san' => 'a6', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkbnr/1ppp1ppp/p1n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'b5a4', 'san' => 'Ba4', 'fromKey' => 'r1bqkbnr/1ppp1ppp/p1n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkbnr/1ppp1ppp/p1n5/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/1ppp1ppp/p1n5/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 b kq -', 'toKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 w kq -'],
        ['uci' => 'f1e1', 'san' => 'Re1', 'fromKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 w kq -', 'toKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 b kq -'],
        ['uci' => 'b7b5', 'san' => 'b5', 'fromKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 b kq -', 'toKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 w kq -'],
        ['uci' => 'a4b3', 'san' => 'Bb3', 'fromKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 w kq -', 'toKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 b kq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 b kq -', 'toKey' => 'r1bqk2r/2p1bppp/p1np1n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 w kq -'],
        ['uci' => 'c2c3', 'san' => 'c3', 'fromKey' => 'r1bqk2r/2p1bppp/p1np1n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 w kq -', 'toKey' => 'r1bqk2r/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 b kq -', 'toKey' => 'r1bq1rk1/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 w - -'],
        ['uci' => 'h2h3', 'san' => 'h3', 'fromKey' => 'r1bq1rk1/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N1P/PP1P1PP1/RNBQR1K1 b - -'],
        ['uci' => 'c6a5', 'san' => 'Na5', 'fromKey' => 'r1bq1rk1/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N1P/PP1P1PP1/RNBQR1K1 b - -', 'toKey' => 'r1bq1rk1/2p1bppp/p2p1n2/np2p3/4P3/1BP2N1P/PP1P1PP1/RNBQR1K1 w - -'],
        ['uci' => 'b3c2', 'san' => 'Bc2', 'fromKey' => 'r1bq1rk1/2p1bppp/p2p1n2/np2p3/4P3/1BP2N1P/PP1P1PP1/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/2p1bppp/p2p1n2/np2p3/4P3/2P2N1P/PPBP1PP1/RNBQR1K1 b - -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'r1bq1rk1/2p1bppp/p2p1n2/np2p3/4P3/2P2N1P/PPBP1PP1/RNBQR1K1 b - -', 'toKey' => 'r1bq1rk1/4bppp/p2p1n2/npp1p3/4P3/2P2N1P/PPBP1PP1/RNBQR1K1 w - -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'r1bq1rk1/4bppp/p2p1n2/npp1p3/4P3/2P2N1P/PPBP1PP1/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/4bppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPB2PP1/RNBQR1K1 b - -'],
        ['uci' => 'd8c7', 'san' => 'Qc7', 'fromKey' => 'r1bq1rk1/4bppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPB2PP1/RNBQR1K1 b - -', 'toKey' => 'r1b2rk1/2q1bppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPB2PP1/RNBQR1K1 w - -'],
        ['uci' => 'b1d2', 'san' => 'Nbd2', 'fromKey' => 'r1b2rk1/2q1bppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPB2PP1/RNBQR1K1 w - -', 'toKey' => 'r1b2rk1/2q1bppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPBN1PP1/R1BQR1K1 b - -'],
        ['uci' => 'c8d7', 'san' => 'Bd7', 'fromKey' => 'r1b2rk1/2q1bppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPBN1PP1/R1BQR1K1 b - -', 'toKey' => 'r4rk1/2qbbppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPBN1PP1/R1BQR1K1 w - -'],
        ['uci' => 'd2f1', 'san' => 'Nf1', 'fromKey' => 'r4rk1/2qbbppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPBN1PP1/R1BQR1K1 w - -', 'toKey' => 'r4rk1/2qbbppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPB2PP1/R1BQRNK1 b - -'],
        ['uci' => 'f8e8', 'san' => 'Rfe8', 'fromKey' => 'r4rk1/2qbbppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPB2PP1/R1BQRNK1 b - -', 'toKey' => 'r3r1k1/2qbbppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPB2PP1/R1BQRNK1 w - -'],
        ['uci' => 'f1e3', 'san' => 'Ne3', 'fromKey' => 'r3r1k1/2qbbppp/p2p1n2/npp1p3/3PP3/2P2N1P/PPB2PP1/R1BQRNK1 w - -', 'toKey' => 'r3r1k1/2qbbppp/p2p1n2/npp1p3/3PP3/2P1NN1P/PPB2PP1/R1BQR1K1 b - -'],
        ['uci' => 'g7g6', 'san' => 'g6', 'fromKey' => 'r3r1k1/2qbbppp/p2p1n2/npp1p3/3PP3/2P1NN1P/PPB2PP1/R1BQR1K1 b - -', 'toKey' => 'r3r1k1/2qbbp1p/p2p1np1/npp1p3/3PP3/2P1NN1P/PPB2PP1/R1BQR1K1 w - -'],
    ];

    public const BREYER_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1b5', 'san' => 'Bb5', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'a7a6', 'san' => 'a6', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkbnr/1ppp1ppp/p1n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'b5a4', 'san' => 'Ba4', 'fromKey' => 'r1bqkbnr/1ppp1ppp/p1n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkbnr/1ppp1ppp/p1n5/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/1ppp1ppp/p1n5/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 b kq -', 'toKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 w kq -'],
        ['uci' => 'f1e1', 'san' => 'Re1', 'fromKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 w kq -', 'toKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 b kq -'],
        ['uci' => 'b7b5', 'san' => 'b5', 'fromKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 b kq -', 'toKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 w kq -'],
        ['uci' => 'a4b3', 'san' => 'Bb3', 'fromKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 w kq -', 'toKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 b kq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 b kq -', 'toKey' => 'r1bqk2r/2p1bppp/p1np1n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 w kq -'],
        ['uci' => 'c2c3', 'san' => 'c3', 'fromKey' => 'r1bqk2r/2p1bppp/p1np1n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 w kq -', 'toKey' => 'r1bqk2r/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 b kq -', 'toKey' => 'r1bq1rk1/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 w - -'],
        ['uci' => 'h2h3', 'san' => 'h3', 'fromKey' => 'r1bq1rk1/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N1P/PP1P1PP1/RNBQR1K1 b - -'],
        ['uci' => 'c6b8', 'san' => 'Nb8', 'fromKey' => 'r1bq1rk1/2p1bppp/p1np1n2/1p2p3/4P3/1BP2N1P/PP1P1PP1/RNBQR1K1 b - -', 'toKey' => 'rnbq1rk1/2p1bppp/p2p1n2/1p2p3/4P3/1BP2N1P/PP1P1PP1/RNBQR1K1 w - -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbq1rk1/2p1bppp/p2p1n2/1p2p3/4P3/1BP2N1P/PP1P1PP1/RNBQR1K1 w - -', 'toKey' => 'rnbq1rk1/2p1bppp/p2p1n2/1p2p3/3PP3/1BP2N1P/PP3PP1/RNBQR1K1 b - -'],
        ['uci' => 'b8d7', 'san' => 'Nbd7', 'fromKey' => 'rnbq1rk1/2p1bppp/p2p1n2/1p2p3/3PP3/1BP2N1P/PP3PP1/RNBQR1K1 b - -', 'toKey' => 'r1bq1rk1/2pnbppp/p2p1n2/1p2p3/3PP3/1BP2N1P/PP3PP1/RNBQR1K1 w - -'],
        ['uci' => 'b1d2', 'san' => 'Nbd2', 'fromKey' => 'r1bq1rk1/2pnbppp/p2p1n2/1p2p3/3PP3/1BP2N1P/PP3PP1/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/2pnbppp/p2p1n2/1p2p3/3PP3/1BP2N1P/PP1N1PP1/R1BQR1K1 b - -'],
        ['uci' => 'c8b7', 'san' => 'Bb7', 'fromKey' => 'r1bq1rk1/2pnbppp/p2p1n2/1p2p3/3PP3/1BP2N1P/PP1N1PP1/R1BQR1K1 b - -', 'toKey' => 'r2q1rk1/1bpnbppp/p2p1n2/1p2p3/3PP3/1BP2N1P/PP1N1PP1/R1BQR1K1 w - -'],
        ['uci' => 'b3c2', 'san' => 'Bc2', 'fromKey' => 'r2q1rk1/1bpnbppp/p2p1n2/1p2p3/3PP3/1BP2N1P/PP1N1PP1/R1BQR1K1 w - -', 'toKey' => 'r2q1rk1/1bpnbppp/p2p1n2/1p2p3/3PP3/2P2N1P/PPBN1PP1/R1BQR1K1 b - -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'r2q1rk1/1bpnbppp/p2p1n2/1p2p3/3PP3/2P2N1P/PPBN1PP1/R1BQR1K1 b - -', 'toKey' => 'r2q1rk1/1b1nbppp/p2p1n2/1pp1p3/3PP3/2P2N1P/PPBN1PP1/R1BQR1K1 w - -'],
    ];

    public const MARSHALL_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1b5', 'san' => 'Bb5', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'a7a6', 'san' => 'a6', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkbnr/1ppp1ppp/p1n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'b5a4', 'san' => 'Ba4', 'fromKey' => 'r1bqkbnr/1ppp1ppp/p1n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkbnr/1ppp1ppp/p1n5/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/1ppp1ppp/p1n5/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'r1bqkb1r/1ppp1ppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 b kq -', 'toKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 w kq -'],
        ['uci' => 'f1e1', 'san' => 'Re1', 'fromKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQ1RK1 w kq -', 'toKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 b kq -'],
        ['uci' => 'b7b5', 'san' => 'b5', 'fromKey' => 'r1bqk2r/1pppbppp/p1n2n2/4p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 b kq -', 'toKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 w kq -'],
        ['uci' => 'a4b3', 'san' => 'Bb3', 'fromKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/B3P3/5N2/PPPP1PPP/RNBQR1K1 w kq -', 'toKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/2ppbppp/p1n2n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 b kq -', 'toKey' => 'r1bq1rk1/2ppbppp/p1n2n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 w - -'],
        ['uci' => 'c2c3', 'san' => 'c3', 'fromKey' => 'r1bq1rk1/2ppbppp/p1n2n2/1p2p3/4P3/1B3N2/PPPP1PPP/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/2ppbppp/p1n2n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 b - -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'r1bq1rk1/2ppbppp/p1n2n2/1p2p3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 b - -', 'toKey' => 'r1bq1rk1/2p1bppp/p1n2n2/1p1pp3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 w - -'],
        ['uci' => 'e4d5', 'san' => 'exd5', 'fromKey' => 'r1bq1rk1/2p1bppp/p1n2n2/1p1pp3/4P3/1BP2N2/PP1P1PPP/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/2p1bppp/p1n2n2/1p1Pp3/8/1BP2N2/PP1P1PPP/RNBQR1K1 b - -'],
        ['uci' => 'f6d5', 'san' => 'Nxd5', 'fromKey' => 'r1bq1rk1/2p1bppp/p1n2n2/1p1Pp3/8/1BP2N2/PP1P1PPP/RNBQR1K1 b - -', 'toKey' => 'r1bq1rk1/2p1bppp/p1n5/1p1np3/8/1BP2N2/PP1P1PPP/RNBQR1K1 w - -'],
        ['uci' => 'f3e5', 'san' => 'Nxe5', 'fromKey' => 'r1bq1rk1/2p1bppp/p1n5/1p1np3/8/1BP2N2/PP1P1PPP/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/2p1bppp/p1n5/1p1nN3/8/1BP5/PP1P1PPP/RNBQR1K1 b - -'],
        ['uci' => 'c6e5', 'san' => 'Nxe5', 'fromKey' => 'r1bq1rk1/2p1bppp/p1n5/1p1nN3/8/1BP5/PP1P1PPP/RNBQR1K1 b - -', 'toKey' => 'r1bq1rk1/2p1bppp/p7/1p1nn3/8/1BP5/PP1P1PPP/RNBQR1K1 w - -'],
        ['uci' => 'e1e5', 'san' => 'Rxe5', 'fromKey' => 'r1bq1rk1/2p1bppp/p7/1p1nn3/8/1BP5/PP1P1PPP/RNBQR1K1 w - -', 'toKey' => 'r1bq1rk1/2p1bppp/p7/1p1nR3/8/1BP5/PP1P1PPP/RNBQ2K1 b - -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'r1bq1rk1/2p1bppp/p7/1p1nR3/8/1BP5/PP1P1PPP/RNBQ2K1 b - -', 'toKey' => 'r1bq1rk1/4bppp/p1p5/1p1nR3/8/1BP5/PP1P1PPP/RNBQ2K1 w - -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'r1bq1rk1/4bppp/p1p5/1p1nR3/8/1BP5/PP1P1PPP/RNBQ2K1 w - -', 'toKey' => 'r1bq1rk1/4bppp/p1p5/1p1nR3/3P4/1BP5/PP3PPP/RNBQ2K1 b - -'],
        ['uci' => 'e7d6', 'san' => 'Bd6', 'fromKey' => 'r1bq1rk1/4bppp/p1p5/1p1nR3/3P4/1BP5/PP3PPP/RNBQ2K1 b - -', 'toKey' => 'r1bq1rk1/5ppp/p1pb4/1p1nR3/3P4/1BP5/PP3PPP/RNBQ2K1 w - -'],
        ['uci' => 'e5e1', 'san' => 'Re1', 'fromKey' => 'r1bq1rk1/5ppp/p1pb4/1p1nR3/3P4/1BP5/PP3PPP/RNBQ2K1 w - -', 'toKey' => 'r1bq1rk1/5ppp/p1pb4/1p1n4/3P4/1BP5/PP3PPP/RNBQR1K1 b - -'],
        ['uci' => 'd8h4', 'san' => 'Qh4', 'fromKey' => 'r1bq1rk1/5ppp/p1pb4/1p1n4/3P4/1BP5/PP3PPP/RNBQR1K1 b - -', 'toKey' => 'r1b2rk1/5ppp/p1pb4/1p1n4/3P3q/1BP5/PP3PPP/RNBQR1K1 w - -'],
        ['uci' => 'g2g3', 'san' => 'g3', 'fromKey' => 'r1b2rk1/5ppp/p1pb4/1p1n4/3P3q/1BP5/PP3PPP/RNBQR1K1 w - -', 'toKey' => 'r1b2rk1/5ppp/p1pb4/1p1n4/3P3q/1BP3P1/PP3P1P/RNBQR1K1 b - -'],
        ['uci' => 'h4h3', 'san' => 'Qh3', 'fromKey' => 'r1b2rk1/5ppp/p1pb4/1p1n4/3P3q/1BP3P1/PP3P1P/RNBQR1K1 b - -', 'toKey' => 'r1b2rk1/5ppp/p1pb4/1p1n4/3P4/1BP3Pq/PP3P1P/RNBQR1K1 w - -'],
        ['uci' => 'c1e3', 'san' => 'Be3', 'fromKey' => 'r1b2rk1/5ppp/p1pb4/1p1n4/3P4/1BP3Pq/PP3P1P/RNBQR1K1 w - -', 'toKey' => 'r1b2rk1/5ppp/p1pb4/1p1n4/3P4/1BP1B1Pq/PP3P1P/RN1QR1K1 b - -'],
        ['uci' => 'c8g4', 'san' => 'Bg4', 'fromKey' => 'r1b2rk1/5ppp/p1pb4/1p1n4/3P4/1BP1B1Pq/PP3P1P/RN1QR1K1 b - -', 'toKey' => 'r4rk1/5ppp/p1pb4/1p1n4/3P2b1/1BP1B1Pq/PP3P1P/RN1QR1K1 w - -'],
        ['uci' => 'd1d3', 'san' => 'Qd3', 'fromKey' => 'r4rk1/5ppp/p1pb4/1p1n4/3P2b1/1BP1B1Pq/PP3P1P/RN1QR1K1 w - -', 'toKey' => 'r4rk1/5ppp/p1pb4/1p1n4/3P2b1/1BPQB1Pq/PP3P1P/RN2R1K1 b - -'],
        ['uci' => 'a8e8', 'san' => 'Rae8', 'fromKey' => 'r4rk1/5ppp/p1pb4/1p1n4/3P2b1/1BPQB1Pq/PP3P1P/RN2R1K1 b - -', 'toKey' => '4rrk1/5ppp/p1pb4/1p1n4/3P2b1/1BPQB1Pq/PP3P1P/RN2R1K1 w - -'],
        ['uci' => 'b1d2', 'san' => 'Nd2', 'fromKey' => '4rrk1/5ppp/p1pb4/1p1n4/3P2b1/1BPQB1Pq/PP3P1P/RN2R1K1 w - -', 'toKey' => '4rrk1/5ppp/p1pb4/1p1n4/3P2b1/1BPQB1Pq/PP1N1P1P/R3R1K1 b - -'],
        ['uci' => 'e8e6', 'san' => 'Re6', 'fromKey' => '4rrk1/5ppp/p1pb4/1p1n4/3P2b1/1BPQB1Pq/PP1N1P1P/R3R1K1 b - -', 'toKey' => '5rk1/5ppp/p1pbr3/1p1n4/3P2b1/1BPQB1Pq/PP1N1P1P/R3R1K1 w - -'],
        ['uci' => 'a2a4', 'san' => 'a4', 'fromKey' => '5rk1/5ppp/p1pbr3/1p1n4/3P2b1/1BPQB1Pq/PP1N1P1P/R3R1K1 w - -', 'toKey' => '5rk1/5ppp/p1pbr3/1p1n4/P2P2b1/1BPQB1Pq/1P1N1P1P/R3R1K1 b - -'],
        ['uci' => 'h3h5', 'san' => 'Qh5', 'fromKey' => '5rk1/5ppp/p1pbr3/1p1n4/P2P2b1/1BPQB1Pq/1P1N1P1P/R3R1K1 b - -', 'toKey' => '5rk1/5ppp/p1pbr3/1p1n3q/P2P2b1/1BPQB1P1/1P1N1P1P/R3R1K1 w - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Ruy Lopez course: Closed Chigorin, Breyer Defense, Marshall Attack';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = "Learn the Ruy Lopez as White. After 1. e4 e5 2. Nf3 Nc6 3. Bb5 your bishop attacks the knight that defends e5, and that pressure on Black's centre shapes the whole game. All three lines pass through ...a6, ...Nf6, ...Be7 and ...b5, with your bishop retreating from b5 to a4 and then b3. In the Closed Chigorin, you build a c3 and d4 centre and reroute a knight while Black expands with ...Na5 and ...c5. In the Breyer Defense, Black regroups the knight from c6 to b8 and d7. In the Marshall Attack, Black gives up a pawn for a fast attack and you learn to defend it. Black replies automatically along the selected line; real opponents can choose other continuations.";
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['chigorin-main-line', 'Closed Chigorin: queenside space against the c3/d4 centre', "Fourteen White moves. Build the c3 and d4 centre after h3, meet Black's ...Na5 and ...c5 queenside play, and reroute your queen's knight from d2 via f1 to e3. Source: Closed, Chigorin, Yugoslav System.", self::CHIGORIN_SOURCE, self::CHIGORIN_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open paths for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing further.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop a knight with a threat: it attacks the pawn on e5. It also clears the way for castling.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 with a knight. This knight is the target of your next move.'],
                ['Bb5', 'Move your bishop from f1 to b5.', "This is the Ruy Lopez. The bishop attacks the knight that defends e5. There is no immediate threat to win the pawn, but this pressure on Black's centre lasts for many moves."],
                ['a6', 'Move your pawn from a7 to a6.', 'Black asks your bishop to decide at once: take the knight, or retreat and keep the pressure.'],
                ['Ba4', 'Move your bishop from b5 to a4.', 'Keep the bishop and its pressure on the knight on c6. The bishop also keeps the option of retreating to b3 later.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops and attacks your pawn on e4, which nothing defends at the moment.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle rather than defend e4 directly. Your king is safe and the rook can come to e1. Black could take on e4 here, which leads to the Open Variation; this line follows the quieter ...Be7.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Black develops the bishop and prepares to castle. This is the Closed Ruy Lopez: Black keeps the centre solid instead of taking on e4.'],
                ['Re1', 'Move your rook from f1 to e1.', 'Defend e4 with the rook. With e4 safe, capturing on c6 and then taking the e5 pawn with your knight becomes a real threat.'],
                ['b5', 'Move your pawn from b7 to b5.', 'Black meets that threat by driving your bishop away from c6. The pawn also gains space on the queenside.'],
                ['Bb3', 'Move your bishop from a4 to b3.', 'Retreat to the a2-g8 diagonal. From b3 the bishop aims at d5 and f7 and stays active well into the middlegame.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Black supports e5 with a pawn, so the threat against it is gone. The move also opens a diagonal for the bishop on c8.'],
                ['c3', 'Move your pawn from c2 to c3.', 'Prepare d4. When the d-pawn arrives, it will be supported by this pawn and you keep a broad pawn centre. The move also gives your bishop a retreat square on c2.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles. Both kings are safe, and the middlegame plans begin.'],
                ['h3', 'Move your pawn from h2 to h3.', 'Before playing d4, stop ...Bg4. That move would pin your knight on f3 against the queen, and the knight is an important defender of d4. This quiet preparation is typical of the Closed Ruy Lopez.'],
                ['Na5', 'Move your knight from c6 to a5.', 'This is the Chigorin Variation. The knight attacks your bishop on b3 and clears the way for the c-pawn to advance to c5, gaining space on the queenside. The price is a knight on the edge of the board.'],
                ['Bc2', 'Move your bishop from b3 to c2.', 'Keep the bishop. On c2, the square your c3 move prepared, it defends e4 and stays on the long diagonal towards h7, ready for the day the centre opens.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Black gains more space on the queenside. The pawn on c5 also attacks d4, so your central advance will meet resistance.'],
                ['d4', 'Move your pawn from d2 to d4.', 'Build the classical centre. The pawn on d4 is attacked by the pawns on c5 and e5, but your c3 pawn, the knight on f3 and the queen defend it.'],
                ['Qc7', 'Move your queen from d8 to c7.', 'Black protects the c5 pawn with the queen and keeps the central tension. If pawns are later exchanged on d4, the c-file opens and the queen is already on it.'],
                ['Nbd2', 'Move your knight from b1 to d2.', 'Develop your last knight. A pawn already stands on c3, so the knight goes to d2 and will continue to f1 and then e3 or g3. This knight tour is the classic Closed Ruy Lopez manoeuvre.'],
                ['Bd7', 'Move your bishop from c8 to d7.', 'Black develops the last minor piece and connects the rooks.'],
                ['Nf1', 'Move your knight from d2 to f1.', "Continue the manoeuvre. On d2 the knight blocked your bishop on c1 and cut the queen's defence of d4; from f1 it frees both, guards h2 and heads for e3 or g3."],
                ['Rfe8', 'Move your rook from f8 to e8.', 'Black puts a rook on the e-file. It backs up the bishop on e7 and the centre, and frees f8 so the bishop can later retreat there.'],
                ['Ne3', 'Move your knight from f1 to e3.', "The knight arrives on e3. From here it eyes d5 and f5, two important squares in front of Black's position. A knight on f5 would attack the bishop on e7 and the pawn on d6."],
                ['g6', 'Move your pawn from g7 to g6.', "Black takes the f5 square away from your knight, and the bishop can later come round via f8 to g7. The named Yugoslav System ends here, with the centre still under tension. Your next tasks are to develop the bishop on c1, decide when to resolve the centre with d5 or an exchange, and watch Black's queenside space."],
            ]],
            ['breyer-defense', "Breyer Defense: Black's knight returns to b8", 'Twelve White moves. Follow the Closed Ruy Lopez to h3, then build your centre while Black regroups the knight from c6 to b8 and d7 and develops the bishop to b7. Source: Closed, Breyer Defense.', self::BREYER_SOURCE, self::BREYER_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open paths for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing further.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop a knight with a threat: it attacks the pawn on e5. It also clears the way for castling.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 with a knight. This knight is the target of your next move.'],
                ['Bb5', 'Move your bishop from f1 to b5.', "This is the Ruy Lopez. The bishop attacks the knight that defends e5. There is no immediate threat to win the pawn, but this pressure on Black's centre lasts for many moves."],
                ['a6', 'Move your pawn from a7 to a6.', 'Black asks your bishop to decide at once: take the knight, or retreat and keep the pressure.'],
                ['Ba4', 'Move your bishop from b5 to a4.', 'Keep the bishop and its pressure on the knight on c6. The bishop also keeps the option of retreating to b3 later.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops and attacks your pawn on e4, which nothing defends at the moment.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle rather than defend e4 directly. Your king is safe and the rook can come to e1. Black could take on e4 here, which leads to the Open Variation; this line follows the quieter ...Be7.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Black develops the bishop and prepares to castle. This is the Closed Ruy Lopez: Black keeps the centre solid instead of taking on e4.'],
                ['Re1', 'Move your rook from f1 to e1.', 'Defend e4 with the rook. With e4 safe, capturing on c6 and then taking the e5 pawn with your knight becomes a real threat.'],
                ['b5', 'Move your pawn from b7 to b5.', 'Black meets that threat by driving your bishop away from c6. The pawn also gains space on the queenside.'],
                ['Bb3', 'Move your bishop from a4 to b3.', 'Retreat to the a2-g8 diagonal. From b3 the bishop aims at d5 and f7 and stays active well into the middlegame.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Black supports e5 with a pawn, so the threat against it is gone. The move also opens a diagonal for the bishop on c8.'],
                ['c3', 'Move your pawn from c2 to c3.', 'Prepare d4. When the d-pawn arrives, it will be supported by this pawn and you keep a broad pawn centre. The move also gives your bishop a retreat square on c2.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles. Both kings are safe, and the middlegame plans begin.'],
                ['h3', 'Move your pawn from h2 to h3.', 'Before playing d4, stop ...Bg4. That move would pin your knight on f3 against the queen, and the knight is an important defender of d4. This quiet preparation is typical of the Closed Ruy Lopez.'],
                ['Nb8', 'Move your knight from c6 to b8.', 'This is the Breyer Defense. The knight goes back to b8 to come out again on d7. On c6 it blocked the c-pawn and the diagonal from b7; on d7 it will support e5 and leave room for ...c5 and ...Bb7.'],
                ['d4', 'Move your pawn from d2 to d4.', 'Use the time to build your centre. With the knight gone from c6, only the e5 pawn attacks d4, and your c3 pawn, knight and queen defend it.'],
                ['Nbd7', 'Move your knight from b8 to d7.', 'The knight arrives on d7 and defends e5 a second time. The c-pawn and the bishop on c8 are now free to come into play.'],
                ['Nbd2', 'Move your knight from b1 to d2.', 'Develop your last knight. It adds a defender to e4 and starts the usual route via f1 to e3 or g3, since c3 is occupied by a pawn.'],
                ['Bb7', 'Move your bishop from c8 to b7.', 'Black puts the bishop on the long diagonal, where it attacks your pawn on e4 together with the knight on f6. Your rook and the knight on d2 defend it.'],
                ['Bc2', 'Move your bishop from b3 to c2.', "Add a third defender to e4. The bishop also leaves b3 before Black's queenside pawns can chase it."],
                ['c5', 'Move your pawn from c7 to c5.', "Black gains queenside space and puts a second pawn against d4. The named Breyer Defense ends here, with Black's regrouping complete. Your next tasks are to continue the knight manoeuvre via f1, develop the bishop on c1, and decide whether to keep the central tension or close the centre with d5."],
            ]],
            ['marshall-attack', 'Marshall Attack: accept the pawn and defend', "Eighteen White moves. Accept Black's pawn sacrifice with ...d5, keep the extra pawn, and finish your development while Black brings queen, bishops and rooks against your king. Source: Marshall Attack, Main Line, Spassky Variation.", self::MARSHALL_SOURCE, self::MARSHALL_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open paths for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing further.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop a knight with a threat: it attacks the pawn on e5. It also clears the way for castling.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 with a knight. This knight is the target of your next move.'],
                ['Bb5', 'Move your bishop from f1 to b5.', "This is the Ruy Lopez. The bishop attacks the knight that defends e5. There is no immediate threat to win the pawn, but this pressure on Black's centre lasts for many moves."],
                ['a6', 'Move your pawn from a7 to a6.', 'Black asks your bishop to decide at once: take the knight, or retreat and keep the pressure.'],
                ['Ba4', 'Move your bishop from b5 to a4.', 'Keep the bishop and its pressure on the knight on c6. The bishop also keeps the option of retreating to b3 later.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops and attacks your pawn on e4, which nothing defends at the moment.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle rather than defend e4 directly. Your king is safe and the rook can come to e1. Black could take on e4 here, which leads to the Open Variation; this line follows the quieter ...Be7.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Black develops the bishop and prepares to castle. This is the Closed Ruy Lopez: Black keeps the centre solid instead of taking on e4.'],
                ['Re1', 'Move your rook from f1 to e1.', 'Defend e4 with the rook. With e4 safe, capturing on c6 and then taking the e5 pawn with your knight becomes a real threat.'],
                ['b5', 'Move your pawn from b7 to b5.', 'Black meets that threat by driving your bishop away from c6. The pawn also gains space on the queenside.'],
                ['Bb3', 'Move your bishop from a4 to b3.', 'Retreat to the a2-g8 diagonal. From b3 the bishop aims at d5 and f7 and stays active well into the middlegame.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles before choosing a central plan. Delaying ...d6 keeps open the option of striking with ...d5.'],
                ['c3', 'Move your pawn from c2 to c3.', 'Prepare d4 and give your bishop a retreat square on c2. Black uses this moment to strike in the centre first.'],
                ['d5', 'Move your pawn from d7 to d5.', 'This is the Marshall Attack. Black attacks e4 with a pawn and is ready to give up a pawn in return for quick development and an attack against your king.'],
                ['exd5', 'Move your pawn from e4 to d5, capturing the black pawn.', 'Accept the gambit. Your pawn on d5 now attacks the knight on c6, so Black must react at once.'],
                ['Nxd5', 'Move your knight from f6 to d5, capturing the white pawn.', 'Black recaptures with the knight, which takes a central post. The pawn on e5 is now attacked by your knight and rook but defended only by the knight on c6.'],
                ['Nxe5', 'Move your knight from f3 to e5, capturing the black pawn.', 'Take the e5 pawn. Your knight now attacks the knight on c6 and the pawn on f7.'],
                ['Nxe5', 'Move your knight from c6 to e5, capturing the white knight.', 'Black exchanges knights. This removes your knight from f3, an important defender of your kingside.'],
                ['Rxe5', 'Move your rook from e1 to e5, capturing the black knight.', 'Recapture with the rook. You are a pawn up. The rook and your bishop on b3 both attack the knight on d5, which has only the queen to defend it.'],
                ['c6', 'Move your pawn from c7 to c6.', 'Black defends the knight with a pawn rather than retreat it. Black spends no time trying to win the pawn back and concentrates on development and the attack.'],
                ['d4', 'Move your pawn from d2 to d4.', 'Strengthen the centre and open the diagonal of your bishop on c1. Your queenside pieces are still at home, so freeing them matters.'],
                ['Bd6', 'Move your bishop from e7 to d6.', 'Black develops with tempo: the bishop attacks your rook on e5 and, once the rook moves, points straight at h2 next to your king.'],
                ['Re1', 'Move your rook from e5 to e1.', 'Bring the rook back to e1, where it guards your back rank and still controls the open e-file.'],
                ['Qh4', 'Move your queen from d8 to h4.', "Black's queen joins the bishop on d6 in attacking h2. Black threatens to take the pawn with check."],
                ['g3', 'Move your pawn from g2 to g3.', "Block the bishop's diagonal and attack the queen. The pawn move leaves f3 and h3 without pawn cover, and Black will try to use those light squares."],
                ['Qh3', 'Move your queen from h4 to h3.', 'The queen goes to h3, right next to your king, where it controls g2 and f1. Black now wants to bring more pieces into the attack.'],
                ['Be3', 'Move your bishop from c1 to e3.', 'Develop the bishop and protect d4. When you are a pawn up and under attack, finishing your development is the main defensive task.'],
                ['Bg4', 'Move your bishop from c8 to g4.', 'Black develops the last minor piece with tempo: the bishop attacks your queen on d1. It also eyes f3, a key square next to your king.'],
                ['Qd3', 'Move your queen from d1 to d3.', "Move the queen to an active square. From d3 it guards e3 and the pawn on d4, and it can return to f1 to challenge Black's queen. It also eyes the pawn on b5."],
                ['Rae8', 'Move your rook from a8 to e8.', 'Black brings the last rook into play on the open e-file. It adds a second attacker to your bishop on e3, alongside the knight on d5.'],
                ['Nd2', 'Move your knight from b1 to d2.', 'Develop your last piece. The knight guards f3, a key square near your king, and your rooks are now connected.'],
                ['Re6', 'Move your rook from e8 to e6.', 'Black prepares to swing the rook along the sixth rank towards your kingside, for example to f6 or h6.'],
                ['a4', 'Move your pawn from a2 to a4.', 'Counterattack on the queenside. The pawn attacks b5 together with your queen, and if the a-file opens, your rook on a1 joins the game.'],
                ['Qh5', 'Move your queen from h3 to h5.', "Black moves the queen to h5, where it still eyes h2 and also defends the knight on d5 along the fifth rank. The named Spassky Variation ends here: you are a pawn up, but Black's pieces are aimed at your king. Your next tasks are to keep your king safe and make your extra pawn and queenside play count."],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='ruy-lopez'")) {
            throw new \RuntimeException('The Ruy Lopez course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Ruy Lopez teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::CHIGORIN_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Ruy Lopez start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('ruy-lopez',NULL,5)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('ruy-lopez',1,?,?,?,UTC_TIMESTAMP(),'w')", ['Ruy Lopez', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('ruy-lopez',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('ruy-lopez',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Ruy Lopez edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='ruy-lopez'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Ruy Lopez sessions and progress.');
    }
}
