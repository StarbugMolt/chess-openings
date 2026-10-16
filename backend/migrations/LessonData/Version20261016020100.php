<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Four Knights Game course: complete named pinned paths with original teaching notes. */
final class Version20261016020100 extends AbstractMigration
{
    public const SCOTCH_FOUR_KNIGHTS_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Nc3 Nf6 4. d4 exd4 5. Nxd4 Bb4 6. Nxc6 bxc6 7. Bd3 d5 8. exd5 O-O 9. O-O';
    public const SPANISH_SYMMETRICAL_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Nc3 Nf6 4. Bb5 Bb4 5. O-O O-O 6. d3 d6 7. Bg5 Bxc3 8. bxc3 Qe7 9. Re1 Nd8 10. d4 Bg4';
    public const RUBINSTEIN_MAROCZY_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Nc3 Nf6 4. Bb5 Nd4 5. Be2 Nxf3+ 6. Bxf3 Bc5 7. O-O O-O 8. d3 d6 9. Na4 Bb6';

    public const SCOTCH_FOUR_KNIGHTS_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p3/3PP3/2N2N2/PPP2PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'e5d4', 'san' => 'exd4', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p3/3PP3/2N2N2/PPP2PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/8/3pP3/2N2N2/PPP2PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'f3d4', 'san' => 'Nxd4', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/8/3pP3/2N2N2/PPP2PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/8/3NP3/2N5/PPP2PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'f8b4', 'san' => 'Bb4', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/8/3NP3/2N5/PPP2PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/8/1b1NP3/2N5/PPP2PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'd4c6', 'san' => 'Nxc6', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/8/1b1NP3/2N5/PPP2PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2N2n2/8/1b2P3/2N5/PPP2PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'b7c6', 'san' => 'bxc6', 'fromKey' => 'r1bqk2r/pppp1ppp/2N2n2/8/1b2P3/2N5/PPP2PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqk2r/p1pp1ppp/2p2n2/8/1b2P3/2N5/PPP2PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'r1bqk2r/p1pp1ppp/2p2n2/8/1b2P3/2N5/PPP2PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqk2r/p1pp1ppp/2p2n2/8/1b2P3/2NB4/PPP2PPP/R1BQK2R b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'r1bqk2r/p1pp1ppp/2p2n2/8/1b2P3/2NB4/PPP2PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqk2r/p1p2ppp/2p2n2/3p4/1b2P3/2NB4/PPP2PPP/R1BQK2R w KQkq -'],
        ['uci' => 'e4d5', 'san' => 'exd5', 'fromKey' => 'r1bqk2r/p1p2ppp/2p2n2/3p4/1b2P3/2NB4/PPP2PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqk2r/p1p2ppp/2p2n2/3P4/1b6/2NB4/PPP2PPP/R1BQK2R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/p1p2ppp/2p2n2/3P4/1b6/2NB4/PPP2PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bq1rk1/p1p2ppp/2p2n2/3P4/1b6/2NB4/PPP2PPP/R1BQK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bq1rk1/p1p2ppp/2p2n2/3P4/1b6/2NB4/PPP2PPP/R1BQK2R w KQ -', 'toKey' => 'r1bq1rk1/p1p2ppp/2p2n2/3P4/1b6/2NB4/PPP2PPP/R1BQ1RK1 b - -'],
    ];

    public const SPANISH_SYMMETRICAL_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'f1b5', 'san' => 'Bb5', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/1B2p3/4P3/2N2N2/PPPP1PPP/R1BQK2R b KQkq -'],
        ['uci' => 'f8b4', 'san' => 'Bb4', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/1B2p3/4P3/2N2N2/PPPP1PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/1B2p3/1b2P3/2N2N2/PPPP1PPP/R1BQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/1B2p3/1b2P3/2N2N2/PPPP1PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/1B2p3/1b2P3/2N2N2/PPPP1PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/1B2p3/1b2P3/2N2N2/PPPP1PPP/R1BQ1RK1 b kq -', 'toKey' => 'r1bq1rk1/pppp1ppp/2n2n2/1B2p3/1b2P3/2N2N2/PPPP1PPP/R1BQ1RK1 w - -'],
        ['uci' => 'd2d3', 'san' => 'd3', 'fromKey' => 'r1bq1rk1/pppp1ppp/2n2n2/1B2p3/1b2P3/2N2N2/PPPP1PPP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/pppp1ppp/2n2n2/1B2p3/1b2P3/2NP1N2/PPP2PPP/R1BQ1RK1 b - -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'r1bq1rk1/pppp1ppp/2n2n2/1B2p3/1b2P3/2NP1N2/PPP2PPP/R1BQ1RK1 b - -', 'toKey' => 'r1bq1rk1/ppp2ppp/2np1n2/1B2p3/1b2P3/2NP1N2/PPP2PPP/R1BQ1RK1 w - -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'r1bq1rk1/ppp2ppp/2np1n2/1B2p3/1b2P3/2NP1N2/PPP2PPP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/ppp2ppp/2np1n2/1B2p1B1/1b2P3/2NP1N2/PPP2PPP/R2Q1RK1 b - -'],
        ['uci' => 'b4c3', 'san' => 'Bxc3', 'fromKey' => 'r1bq1rk1/ppp2ppp/2np1n2/1B2p1B1/1b2P3/2NP1N2/PPP2PPP/R2Q1RK1 b - -', 'toKey' => 'r1bq1rk1/ppp2ppp/2np1n2/1B2p1B1/4P3/2bP1N2/PPP2PPP/R2Q1RK1 w - -'],
        ['uci' => 'b2c3', 'san' => 'bxc3', 'fromKey' => 'r1bq1rk1/ppp2ppp/2np1n2/1B2p1B1/4P3/2bP1N2/PPP2PPP/R2Q1RK1 w - -', 'toKey' => 'r1bq1rk1/ppp2ppp/2np1n2/1B2p1B1/4P3/2PP1N2/P1P2PPP/R2Q1RK1 b - -'],
        ['uci' => 'd8e7', 'san' => 'Qe7', 'fromKey' => 'r1bq1rk1/ppp2ppp/2np1n2/1B2p1B1/4P3/2PP1N2/P1P2PPP/R2Q1RK1 b - -', 'toKey' => 'r1b2rk1/ppp1qppp/2np1n2/1B2p1B1/4P3/2PP1N2/P1P2PPP/R2Q1RK1 w - -'],
        ['uci' => 'f1e1', 'san' => 'Re1', 'fromKey' => 'r1b2rk1/ppp1qppp/2np1n2/1B2p1B1/4P3/2PP1N2/P1P2PPP/R2Q1RK1 w - -', 'toKey' => 'r1b2rk1/ppp1qppp/2np1n2/1B2p1B1/4P3/2PP1N2/P1P2PPP/R2QR1K1 b - -'],
        ['uci' => 'c6d8', 'san' => 'Nd8', 'fromKey' => 'r1b2rk1/ppp1qppp/2np1n2/1B2p1B1/4P3/2PP1N2/P1P2PPP/R2QR1K1 b - -', 'toKey' => 'r1bn1rk1/ppp1qppp/3p1n2/1B2p1B1/4P3/2PP1N2/P1P2PPP/R2QR1K1 w - -'],
        ['uci' => 'd3d4', 'san' => 'd4', 'fromKey' => 'r1bn1rk1/ppp1qppp/3p1n2/1B2p1B1/4P3/2PP1N2/P1P2PPP/R2QR1K1 w - -', 'toKey' => 'r1bn1rk1/ppp1qppp/3p1n2/1B2p1B1/3PP3/2P2N2/P1P2PPP/R2QR1K1 b - -'],
        ['uci' => 'c8g4', 'san' => 'Bg4', 'fromKey' => 'r1bn1rk1/ppp1qppp/3p1n2/1B2p1B1/3PP3/2P2N2/P1P2PPP/R2QR1K1 b - -', 'toKey' => 'r2n1rk1/ppp1qppp/3p1n2/1B2p1B1/3PP1b1/2P2N2/P1P2PPP/R2QR1K1 w - -'],
    ];

    public const RUBINSTEIN_MAROCZY_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'f1b5', 'san' => 'Bb5', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/4p3/4P3/2N2N2/PPPP1PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/1B2p3/4P3/2N2N2/PPPP1PPP/R1BQK2R b KQkq -'],
        ['uci' => 'c6d4', 'san' => 'Nd4', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/1B2p3/4P3/2N2N2/PPPP1PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/5n2/1B2p3/3nP3/2N2N2/PPPP1PPP/R1BQK2R w KQkq -'],
        ['uci' => 'b5e2', 'san' => 'Be2', 'fromKey' => 'r1bqkb1r/pppp1ppp/5n2/1B2p3/3nP3/2N2N2/PPPP1PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/5n2/4p3/3nP3/2N2N2/PPPPBPPP/R1BQK2R b KQkq -'],
        ['uci' => 'd4f3', 'san' => 'Nxf3+', 'fromKey' => 'r1bqkb1r/pppp1ppp/5n2/4p3/3nP3/2N2N2/PPPPBPPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/5n2/4p3/4P3/2N2n2/PPPPBPPP/R1BQK2R w KQkq -'],
        ['uci' => 'e2f3', 'san' => 'Bxf3', 'fromKey' => 'r1bqkb1r/pppp1ppp/5n2/4p3/4P3/2N2n2/PPPPBPPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/5n2/4p3/4P3/2N2B2/PPPP1PPP/R1BQK2R b KQkq -'],
        ['uci' => 'f8c5', 'san' => 'Bc5', 'fromKey' => 'r1bqkb1r/pppp1ppp/5n2/4p3/4P3/2N2B2/PPPP1PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/5n2/2b1p3/4P3/2N2B2/PPPP1PPP/R1BQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pppp1ppp/5n2/2b1p3/4P3/2N2B2/PPPP1PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqk2r/pppp1ppp/5n2/2b1p3/4P3/2N2B2/PPPP1PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pppp1ppp/5n2/2b1p3/4P3/2N2B2/PPPP1PPP/R1BQ1RK1 b kq -', 'toKey' => 'r1bq1rk1/pppp1ppp/5n2/2b1p3/4P3/2N2B2/PPPP1PPP/R1BQ1RK1 w - -'],
        ['uci' => 'd2d3', 'san' => 'd3', 'fromKey' => 'r1bq1rk1/pppp1ppp/5n2/2b1p3/4P3/2N2B2/PPPP1PPP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/pppp1ppp/5n2/2b1p3/4P3/2NP1B2/PPP2PPP/R1BQ1RK1 b - -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'r1bq1rk1/pppp1ppp/5n2/2b1p3/4P3/2NP1B2/PPP2PPP/R1BQ1RK1 b - -', 'toKey' => 'r1bq1rk1/ppp2ppp/3p1n2/2b1p3/4P3/2NP1B2/PPP2PPP/R1BQ1RK1 w - -'],
        ['uci' => 'c3a4', 'san' => 'Na4', 'fromKey' => 'r1bq1rk1/ppp2ppp/3p1n2/2b1p3/4P3/2NP1B2/PPP2PPP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/ppp2ppp/3p1n2/2b1p3/N3P3/3P1B2/PPP2PPP/R1BQ1RK1 b - -'],
        ['uci' => 'c5b6', 'san' => 'Bb6', 'fromKey' => 'r1bq1rk1/ppp2ppp/3p1n2/2b1p3/N3P3/3P1B2/PPP2PPP/R1BQ1RK1 b - -', 'toKey' => 'r1bq1rk1/ppp2ppp/1b1p1n2/4p3/N3P3/3P1B2/PPP2PPP/R1BQ1RK1 w - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Four Knights Game course: Scotch Variation Accepted, Main Line; Spanish Variation, Symmetrical Variation; Spanish Variation, Rubinstein Variation, Maróczy Variation';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Learn the Four Knights Game as White. After 1. e4 e5 2. Nf3 Nc6 3. Nc3 Nf6 all four knights are developed towards the centre, and your plan is simple: bring out the bishops, castle quickly and choose the right moment for a central break. In the Scotch Four Knights, you open the centre with 4. d4 and answer Black\'s pin with ...Bb4 by exchanging knights on c6. In the Symmetrical Variation, both sides play Bb5 and ...Bb4, and you break the symmetry with Bg5, Re1 and d4. In the Rubinstein Variation, Black answers 4. Bb5 with ...Nd4 and you keep your pieces together with Be2 and Bxf3. Black replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['scotch-four-knights', 'Scotch Four Knights: d4 and the exchange on c6', 'Nine White moves. Open the centre with d4, exchange knights on c6, defend e4 with Bd3 and castle while Black challenges your centre with ...d5. Source: Scotch Variation Accepted, Main Line.', self::SCOTCH_FOUR_KNIGHTS_SOURCE, self::SCOTCH_FOUR_KNIGHTS_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open paths for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing further.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop a knight with a threat: it attacks the pawn on e5. It also clears the way for castling.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 by developing a knight.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'Develop the second knight. It defends e4 and controls d5.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops the last knight and attacks e4. All four knights are now in play, which gives the opening its name.'],
                ['d4', 'Move your pawn from d2 to d4.', 'Open the centre at once with the d-pawn, which attacks e5. This is the Scotch Four Knights: here this line takes its own path instead of developing the bishop to b5.'],
                ['exd4', 'Move your pawn from e5 to d4, capturing the white pawn.', 'Black takes the pawn on d4 and opens the centre.'],
                ['Nxd4', 'Move your knight from f3 to d4, capturing the black pawn.', 'Recapture with the knight, which now stands in the centre. Black\'s knight on c6 attacks it, but your queen defends it.'],
                ['Bb4', 'Move your bishop from f8 to b4.', 'Black develops the bishop and pins your knight on c3 against your king, because your d-pawn has left d2. The pinned knight cannot defend e4, so the knight on f6 now threatens to capture that pawn.'],
                ['Nxc6', 'Move your knight from d4 to c6, capturing the black knight.', 'Exchange knights on c6. From there your knight attacks the queen on d8 and the bishop on b4, so Black has to recapture at once.'],
                ['bxc6', 'Move your pawn from b7 to c6, capturing the white knight.', 'Black recaptures with the b-pawn, towards the centre. The new pawn on c6 controls d5, and the b-file is now half-open for a black rook.'],
                ['Bd3', 'Move your bishop from f1 to d3.', 'Defend e4 with the bishop and clear the way to castle kingside.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Black strikes at e4 with the d-pawn. Your pawn there is now attacked by this pawn and by the knight on f6.'],
                ['exd5', 'Move your pawn from e4 to d5, capturing the black pawn.', 'Take on d5. Your pawn now attacks c6, and the e-file is open while Black\'s king still stands on it.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles and takes the king off the open e-file. Black does not recapture yet: your pawn on d5 is attacked by the pawn on c6, the knight on f6 and the queen on d8, and only your knight on c3 defends it.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle as well. Your king leaves e1, so the knight on c3 is no longer pinned. The named Main Line ends here: your next task is to develop the bishop on c1, for example to g5, where it pins the knight on f6 against the queen on d8.'],
            ]],
            ['spanish-symmetrical', 'Symmetrical Spanish: Bg5 and the d4 break', 'Ten White moves. Develop as Black does with Bb5, castling and d3, break the symmetry with Bg5, accept doubled c-pawns for the bishop pair and prepare d4 with Re1. Source: Spanish Variation, Symmetrical Variation.', self::SPANISH_SYMMETRICAL_SOURCE, self::SPANISH_SYMMETRICAL_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open paths for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing further.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop a knight with a threat: it attacks the pawn on e5. It also clears the way for castling.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 by developing a knight.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'Develop the second knight. It defends e4 and controls d5.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops the last knight and attacks e4. All four knights are now in play, which gives the opening its name.'],
                ['Bb5', 'Move your bishop from f1 to b5.', 'Develop the bishop to b5 and attack the knight on c6, the piece that defends e5. This is the Spanish Four Knights: here the line takes a different path from the Scotch Four Knights, which plays d4 instead.'],
                ['Bb4', 'Move your bishop from f8 to b4.', 'Black copies your idea and develops the bishop to b4, attacking your knight on c3. Here this line takes its own path, with a symmetrical position. Your d-pawn still stands on d2, so the knight is not pinned.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle. Your king is safe, and the rook on f1 can later come to e1.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles. Both kings are safe, and the middlegame plans begin.'],
                ['d3', 'Move your pawn from d2 to d3.', 'Defend e4 with the pawn and open the diagonal of the bishop on c1.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Black plays the same move: the pawn defends e5 and opens the diagonal of the bishop on c8.'],
                ['Bg5', 'Move your bishop from c1 to g5.', 'Develop the bishop and pin the knight on f6 against the queen on d8. This move breaks the symmetry.'],
                ['Bxc3', 'Move your bishop from b4 to c3, capturing the white knight.', 'Black gives up the bishop for your knight. Your pawns will be doubled on the c-file, but you gain the bishop pair.'],
                ['bxc3', 'Move your pawn from b2 to c3, capturing the black bishop.', 'Recapture with the b-pawn, towards the centre. The pawn on c3 will support d4, and your rook can use the half-open b-file.'],
                ['Qe7', 'Move your queen from d8 to e7.', 'Black moves the queen to e7, where it defends e5 and frees d8 for the knight. The knight on f6 is still pinned, now against the queen on e7.'],
                ['Re1', 'Move your rook from f1 to e1.', 'Put the rook on e1. It protects e4, which will need a new defender once your d-pawn advances to d4.'],
                ['Nd8', 'Move your knight from c6 to d8.', 'Black\'s knight heads for e6, where it would attack your bishop on g5. Leaving c6 also lets the c-pawn advance to c6 and attack your bishop on b5.'],
                ['d4', 'Move your pawn from d3 to d4.', 'Strike in the centre with d4, supported by the pawn on c3, the knight on f3 and the queen. Your pawn attacks e5, which Black defends with the pawn on d6 and the queen.'],
                ['Bg4', 'Move your bishop from c8 to g4.', 'Black develops the last bishop and pins your knight on f3 against the queen on d1. That knight defends d4 and your bishop on g5. The named Symmetrical Variation ends here: your next task is to question the bishop on g4 with h3 and keep your centre on d4 and e4 well supported.'],
            ]],
            ['rubinstein-maroczy', 'Rubinstein: Be2 and Na4 against ...Nd4', 'Nine White moves. Answer ...Nd4 by retreating to e2, recapture on f3 with the bishop, castle and chase Black\'s bishop with Na4. Source: Spanish Variation, Rubinstein Variation, Maróczy Variation.', self::RUBINSTEIN_MAROCZY_SOURCE, self::RUBINSTEIN_MAROCZY_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open paths for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing further.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop a knight with a threat: it attacks the pawn on e5. It also clears the way for castling.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 by developing a knight.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'Develop the second knight. It defends e4 and controls d5.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops the last knight and attacks e4. All four knights are now in play, which gives the opening its name.'],
                ['Bb5', 'Move your bishop from f1 to b5.', 'Develop the bishop to b5 and attack the knight on c6, the piece that defends e5. This is the Spanish Four Knights: here the line takes a different path from the Scotch Four Knights, which plays d4 instead.'],
                ['Nd4', 'Move your knight from c6 to d4.', 'Black moves the knight again, to d4, where it attacks your bishop on b5 and your knight on f3. Here this line takes its own path. To do this, Black leaves the pawn on e5 without a defender.'],
                ['Be2', 'Move your bishop from b5 to e2.', 'Retreat the bishop to e2, where it defends the knight on f3. This line keeps your pieces together rather than taking the pawn on e5.'],
                ['Nxf3+', 'Move your knight from d4 to f3, capturing the white knight with check.', 'Black exchanges knights on f3 with check.'],
                ['Bxf3', 'Move your bishop from e2 to f3, capturing the black knight.', 'Recapture with the bishop. On f3 it supports your e4 pawn together with the knight on c3.'],
                ['Bc5', 'Move your bishop from f8 to c5.', 'Black develops the bishop to c5, where it aims at f2 and controls d4.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle. Your king leaves the centre, and the rook on f1 becomes a second defender of f2.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles. Both kings are safe, and the middlegame plans begin.'],
                ['d3', 'Move your pawn from d2 to d3.', 'Defend e4 with the pawn and open the diagonal of the bishop on c1.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Black defends e5 with the pawn and opens the diagonal of the bishop on c8. The pawn also protects the bishop on c5.'],
                ['Na4', 'Move your knight from c3 to a4.', 'Attack the bishop on c5 with the knight. If the bishop stays, you can exchange your knight for it and keep two bishops against Black\'s bishop and knight.'],
                ['Bb6', 'Move your bishop from c5 to b6.', 'Black keeps the bishop on the same diagonal, where it still aims at f2. Your knight on a4 attacks it again, and the pawns on a7 and c7 defend it. The named Maróczy Variation ends here: your next task is to decide whether to take on b6 for the bishop pair, and to develop your bishop on c1, for example to g5 to pin the knight on f6.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='four-knights'")) {
            throw new \RuntimeException('The Four Knights Game course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Four Knights Game teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::SCOTCH_FOUR_KNIGHTS_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Four Knights Game start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('four-knights',NULL,11)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('four-knights',1,?,?,?,UTC_TIMESTAMP(),'w')", ['Four Knights Game', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('four-knights',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('four-knights',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Four Knights Game edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='four-knights'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Four Knights Game sessions and progress.');
    }
}
