<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Queen's Gambit revision 2: three complete named pinned paths with original teaching notes. */
final class Version20261015010700 extends AbstractMigration
{
    public const LEVENFISH_SOURCE = '1. d4 d5 2. c4 e6 3. Nc3 Nf6 4. Nf3 c5 5. e3 Nc6 6. Bd3 Bd6 7. O-O O-O 8. Qe2 Qe7 9. dxc5 Bxc5 10. e4';
    public const ORTHODOX_SOURCE = '1. d4 d5 2. c4 e6 3. Nc3 Nf6 4. Bg5 Be7 5. e3 O-O 6. Nf3 Nbd7 7. Rc1 c6 8. Bd3 dxc4 9. Bxc4 Nd5 10. Bxe7 Qxe7 11. O-O Nxc3 12. Rxc3 e5 13. dxe5 Nxe5 14. Nxe5 Qxe5';
    public const ACCEPTED_SOURCE = '1. d4 d5 2. c4 dxc4 3. Nf3 Nf6 4. e3 e6 5. Bxc4 c5 6. O-O a6 7. Qe2 b5 8. Bb3 Nc6 9. Rd1 c4 10. Bc2 Nb4 11. Nc3 Nxc2 12. Qxc2 Bb7 13. d5 Qc7';

    public const LEVENFISH_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/pp3ppp/4pn2/2pp4/2PP4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -'],
        ['uci' => 'e2e3', 'san' => 'e3', 'fromKey' => 'rnbqkb1r/pp3ppp/4pn2/2pp4/2PP4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/pp3ppp/4pn2/2pp4/2PP4/2N1PN2/PP3PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkb1r/pp3ppp/4pn2/2pp4/2PP4/2N1PN2/PP3PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2n1pn2/2pp4/2PP4/2N1PN2/PP3PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'r1bqkb1r/pp3ppp/2n1pn2/2pp4/2PP4/2N1PN2/PP3PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2n1pn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQK2R b KQkq -'],
        ['uci' => 'f8d6', 'san' => 'Bd6', 'fromKey' => 'r1bqkb1r/pp3ppp/2n1pn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQK2R b KQkq -', 'toKey' => 'r1bqk2r/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQK2R w KQkq -', 'toKey' => 'r1bqk2r/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQ1RK1 b kq -', 'toKey' => 'r1bq1rk1/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQ1RK1 w - -'],
        ['uci' => 'd1e2', 'san' => 'Qe2', 'fromKey' => 'r1bq1rk1/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP2QPPP/R1B2RK1 b - -'],
        ['uci' => 'd8e7', 'san' => 'Qe7', 'fromKey' => 'r1bq1rk1/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP2QPPP/R1B2RK1 b - -', 'toKey' => 'r1b2rk1/pp2qppp/2nbpn2/2pp4/2PP4/2NBPN2/PP2QPPP/R1B2RK1 w - -'],
        ['uci' => 'd4c5', 'san' => 'dxc5', 'fromKey' => 'r1b2rk1/pp2qppp/2nbpn2/2pp4/2PP4/2NBPN2/PP2QPPP/R1B2RK1 w - -', 'toKey' => 'r1b2rk1/pp2qppp/2nbpn2/2Pp4/2P5/2NBPN2/PP2QPPP/R1B2RK1 b - -'],
        ['uci' => 'd6c5', 'san' => 'Bxc5', 'fromKey' => 'r1b2rk1/pp2qppp/2nbpn2/2Pp4/2P5/2NBPN2/PP2QPPP/R1B2RK1 b - -', 'toKey' => 'r1b2rk1/pp2qppp/2n1pn2/2bp4/2P5/2NBPN2/PP2QPPP/R1B2RK1 w - -'],
        ['uci' => 'e3e4', 'san' => 'e4', 'fromKey' => 'r1b2rk1/pp2qppp/2n1pn2/2bp4/2P5/2NBPN2/PP2QPPP/R1B2RK1 w - -', 'toKey' => 'r1b2rk1/pp2qppp/2n1pn2/2bp4/2P1P3/2NB1N2/PP2QPPP/R1B2RK1 b - -'],
    ];

    public const ORTHODOX_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p2B1/2PP4/2N5/PP2PPPP/R2QKBNR b KQkq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p2B1/2PP4/2N5/PP2PPPP/R2QKBNR b KQkq -', 'toKey' => 'rnbqk2r/ppp1bppp/4pn2/3p2B1/2PP4/2N5/PP2PPPP/R2QKBNR w KQkq -'],
        ['uci' => 'e2e3', 'san' => 'e3', 'fromKey' => 'rnbqk2r/ppp1bppp/4pn2/3p2B1/2PP4/2N5/PP2PPPP/R2QKBNR w KQkq -', 'toKey' => 'rnbqk2r/ppp1bppp/4pn2/3p2B1/2PP4/2N1P3/PP3PPP/R2QKBNR b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/ppp1bppp/4pn2/3p2B1/2PP4/2N1P3/PP3PPP/R2QKBNR b KQkq -', 'toKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p2B1/2PP4/2N1P3/PP3PPP/R2QKBNR w KQ -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p2B1/2PP4/2N1P3/PP3PPP/R2QKBNR w KQ -', 'toKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p2B1/2PP4/2N1PN2/PP3PPP/R2QKB1R b KQ -'],
        ['uci' => 'b8d7', 'san' => 'Nbd7', 'fromKey' => 'rnbq1rk1/ppp1bppp/4pn2/3p2B1/2PP4/2N1PN2/PP3PPP/R2QKB1R b KQ -', 'toKey' => 'r1bq1rk1/pppnbppp/4pn2/3p2B1/2PP4/2N1PN2/PP3PPP/R2QKB1R w KQ -'],
        ['uci' => 'a1c1', 'san' => 'Rc1', 'fromKey' => 'r1bq1rk1/pppnbppp/4pn2/3p2B1/2PP4/2N1PN2/PP3PPP/R2QKB1R w KQ -', 'toKey' => 'r1bq1rk1/pppnbppp/4pn2/3p2B1/2PP4/2N1PN2/PP3PPP/2RQKB1R b K -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'r1bq1rk1/pppnbppp/4pn2/3p2B1/2PP4/2N1PN2/PP3PPP/2RQKB1R b K -', 'toKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p2B1/2PP4/2N1PN2/PP3PPP/2RQKB1R w K -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p2B1/2PP4/2N1PN2/PP3PPP/2RQKB1R w K -', 'toKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p2B1/2PP4/2NBPN2/PP3PPP/2RQK2R b K -'],
        ['uci' => 'd5c4', 'san' => 'dxc4', 'fromKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/3p2B1/2PP4/2NBPN2/PP3PPP/2RQK2R b K -', 'toKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/6B1/2pP4/2NBPN2/PP3PPP/2RQK2R w K -'],
        ['uci' => 'd3c4', 'san' => 'Bxc4', 'fromKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/6B1/2pP4/2NBPN2/PP3PPP/2RQK2R w K -', 'toKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/6B1/2BP4/2N1PN2/PP3PPP/2RQK2R b K -'],
        ['uci' => 'f6d5', 'san' => 'Nd5', 'fromKey' => 'r1bq1rk1/pp1nbppp/2p1pn2/6B1/2BP4/2N1PN2/PP3PPP/2RQK2R b K -', 'toKey' => 'r1bq1rk1/pp1nbppp/2p1p3/3n2B1/2BP4/2N1PN2/PP3PPP/2RQK2R w K -'],
        ['uci' => 'g5e7', 'san' => 'Bxe7', 'fromKey' => 'r1bq1rk1/pp1nbppp/2p1p3/3n2B1/2BP4/2N1PN2/PP3PPP/2RQK2R w K -', 'toKey' => 'r1bq1rk1/pp1nBppp/2p1p3/3n4/2BP4/2N1PN2/PP3PPP/2RQK2R b K -'],
        ['uci' => 'd8e7', 'san' => 'Qxe7', 'fromKey' => 'r1bq1rk1/pp1nBppp/2p1p3/3n4/2BP4/2N1PN2/PP3PPP/2RQK2R b K -', 'toKey' => 'r1b2rk1/pp1nqppp/2p1p3/3n4/2BP4/2N1PN2/PP3PPP/2RQK2R w K -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1b2rk1/pp1nqppp/2p1p3/3n4/2BP4/2N1PN2/PP3PPP/2RQK2R w K -', 'toKey' => 'r1b2rk1/pp1nqppp/2p1p3/3n4/2BP4/2N1PN2/PP3PPP/2RQ1RK1 b - -'],
        ['uci' => 'd5c3', 'san' => 'Nxc3', 'fromKey' => 'r1b2rk1/pp1nqppp/2p1p3/3n4/2BP4/2N1PN2/PP3PPP/2RQ1RK1 b - -', 'toKey' => 'r1b2rk1/pp1nqppp/2p1p3/8/2BP4/2n1PN2/PP3PPP/2RQ1RK1 w - -'],
        ['uci' => 'c1c3', 'san' => 'Rxc3', 'fromKey' => 'r1b2rk1/pp1nqppp/2p1p3/8/2BP4/2n1PN2/PP3PPP/2RQ1RK1 w - -', 'toKey' => 'r1b2rk1/pp1nqppp/2p1p3/8/2BP4/2R1PN2/PP3PPP/3Q1RK1 b - -'],
        ['uci' => 'e6e5', 'san' => 'e5', 'fromKey' => 'r1b2rk1/pp1nqppp/2p1p3/8/2BP4/2R1PN2/PP3PPP/3Q1RK1 b - -', 'toKey' => 'r1b2rk1/pp1nqppp/2p5/4p3/2BP4/2R1PN2/PP3PPP/3Q1RK1 w - -'],
        ['uci' => 'd4e5', 'san' => 'dxe5', 'fromKey' => 'r1b2rk1/pp1nqppp/2p5/4p3/2BP4/2R1PN2/PP3PPP/3Q1RK1 w - -', 'toKey' => 'r1b2rk1/pp1nqppp/2p5/4P3/2B5/2R1PN2/PP3PPP/3Q1RK1 b - -'],
        ['uci' => 'd7e5', 'san' => 'Nxe5', 'fromKey' => 'r1b2rk1/pp1nqppp/2p5/4P3/2B5/2R1PN2/PP3PPP/3Q1RK1 b - -', 'toKey' => 'r1b2rk1/pp2qppp/2p5/4n3/2B5/2R1PN2/PP3PPP/3Q1RK1 w - -'],
        ['uci' => 'f3e5', 'san' => 'Nxe5', 'fromKey' => 'r1b2rk1/pp2qppp/2p5/4n3/2B5/2R1PN2/PP3PPP/3Q1RK1 w - -', 'toKey' => 'r1b2rk1/pp2qppp/2p5/4N3/2B5/2R1P3/PP3PPP/3Q1RK1 b - -'],
        ['uci' => 'e7e5', 'san' => 'Qxe5', 'fromKey' => 'r1b2rk1/pp2qppp/2p5/4N3/2B5/2R1P3/PP3PPP/3Q1RK1 b - -', 'toKey' => 'r1b2rk1/pp3ppp/2p5/4q3/2B5/2R1P3/PP3PPP/3Q1RK1 w - -'],
    ];

    public const ACCEPTED_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd5c4', 'san' => 'dxc4', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/8/2pP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/8/2pP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/8/2pP4/5N2/PP2PPPP/RNBQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/8/2pP4/5N2/PP2PPPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/5n2/8/2pP4/5N2/PP2PPPP/RNBQKB1R w KQkq -'],
        ['uci' => 'e2e3', 'san' => 'e3', 'fromKey' => 'rnbqkb1r/ppp1pppp/5n2/8/2pP4/5N2/PP2PPPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/5n2/8/2pP4/4PN2/PP3PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkb1r/ppp1pppp/5n2/8/2pP4/4PN2/PP3PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/8/2pP4/4PN2/PP3PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1c4', 'san' => 'Bxc4', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/8/2pP4/4PN2/PP3PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/8/2BP4/4PN2/PP3PPP/RNBQK2R b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/8/2BP4/4PN2/PP3PPP/RNBQK2R b KQkq -', 'toKey' => 'rnbqkb1r/pp3ppp/4pn2/2p5/2BP4/4PN2/PP3PPP/RNBQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'rnbqkb1r/pp3ppp/4pn2/2p5/2BP4/4PN2/PP3PPP/RNBQK2R w KQkq -', 'toKey' => 'rnbqkb1r/pp3ppp/4pn2/2p5/2BP4/4PN2/PP3PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'a7a6', 'san' => 'a6', 'fromKey' => 'rnbqkb1r/pp3ppp/4pn2/2p5/2BP4/4PN2/PP3PPP/RNBQ1RK1 b kq -', 'toKey' => 'rnbqkb1r/1p3ppp/p3pn2/2p5/2BP4/4PN2/PP3PPP/RNBQ1RK1 w kq -'],
        ['uci' => 'd1e2', 'san' => 'Qe2', 'fromKey' => 'rnbqkb1r/1p3ppp/p3pn2/2p5/2BP4/4PN2/PP3PPP/RNBQ1RK1 w kq -', 'toKey' => 'rnbqkb1r/1p3ppp/p3pn2/2p5/2BP4/4PN2/PP2QPPP/RNB2RK1 b kq -'],
        ['uci' => 'b7b5', 'san' => 'b5', 'fromKey' => 'rnbqkb1r/1p3ppp/p3pn2/2p5/2BP4/4PN2/PP2QPPP/RNB2RK1 b kq -', 'toKey' => 'rnbqkb1r/5ppp/p3pn2/1pp5/2BP4/4PN2/PP2QPPP/RNB2RK1 w kq -'],
        ['uci' => 'c4b3', 'san' => 'Bb3', 'fromKey' => 'rnbqkb1r/5ppp/p3pn2/1pp5/2BP4/4PN2/PP2QPPP/RNB2RK1 w kq -', 'toKey' => 'rnbqkb1r/5ppp/p3pn2/1pp5/3P4/1B2PN2/PP2QPPP/RNB2RK1 b kq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkb1r/5ppp/p3pn2/1pp5/3P4/1B2PN2/PP2QPPP/RNB2RK1 b kq -', 'toKey' => 'r1bqkb1r/5ppp/p1n1pn2/1pp5/3P4/1B2PN2/PP2QPPP/RNB2RK1 w kq -'],
        ['uci' => 'f1d1', 'san' => 'Rd1', 'fromKey' => 'r1bqkb1r/5ppp/p1n1pn2/1pp5/3P4/1B2PN2/PP2QPPP/RNB2RK1 w kq -', 'toKey' => 'r1bqkb1r/5ppp/p1n1pn2/1pp5/3P4/1B2PN2/PP2QPPP/RNBR2K1 b kq -'],
        ['uci' => 'c5c4', 'san' => 'c4', 'fromKey' => 'r1bqkb1r/5ppp/p1n1pn2/1pp5/3P4/1B2PN2/PP2QPPP/RNBR2K1 b kq -', 'toKey' => 'r1bqkb1r/5ppp/p1n1pn2/1p6/2pP4/1B2PN2/PP2QPPP/RNBR2K1 w kq -'],
        ['uci' => 'b3c2', 'san' => 'Bc2', 'fromKey' => 'r1bqkb1r/5ppp/p1n1pn2/1p6/2pP4/1B2PN2/PP2QPPP/RNBR2K1 w kq -', 'toKey' => 'r1bqkb1r/5ppp/p1n1pn2/1p6/2pP4/4PN2/PPB1QPPP/RNBR2K1 b kq -'],
        ['uci' => 'c6b4', 'san' => 'Nb4', 'fromKey' => 'r1bqkb1r/5ppp/p1n1pn2/1p6/2pP4/4PN2/PPB1QPPP/RNBR2K1 b kq -', 'toKey' => 'r1bqkb1r/5ppp/p3pn2/1p6/1npP4/4PN2/PPB1QPPP/RNBR2K1 w kq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r1bqkb1r/5ppp/p3pn2/1p6/1npP4/4PN2/PPB1QPPP/RNBR2K1 w kq -', 'toKey' => 'r1bqkb1r/5ppp/p3pn2/1p6/1npP4/2N1PN2/PPB1QPPP/R1BR2K1 b kq -'],
        ['uci' => 'b4c2', 'san' => 'Nxc2', 'fromKey' => 'r1bqkb1r/5ppp/p3pn2/1p6/1npP4/2N1PN2/PPB1QPPP/R1BR2K1 b kq -', 'toKey' => 'r1bqkb1r/5ppp/p3pn2/1p6/2pP4/2N1PN2/PPn1QPPP/R1BR2K1 w kq -'],
        ['uci' => 'e2c2', 'san' => 'Qxc2', 'fromKey' => 'r1bqkb1r/5ppp/p3pn2/1p6/2pP4/2N1PN2/PPn1QPPP/R1BR2K1 w kq -', 'toKey' => 'r1bqkb1r/5ppp/p3pn2/1p6/2pP4/2N1PN2/PPQ2PPP/R1BR2K1 b kq -'],
        ['uci' => 'c8b7', 'san' => 'Bb7', 'fromKey' => 'r1bqkb1r/5ppp/p3pn2/1p6/2pP4/2N1PN2/PPQ2PPP/R1BR2K1 b kq -', 'toKey' => 'r2qkb1r/1b3ppp/p3pn2/1p6/2pP4/2N1PN2/PPQ2PPP/R1BR2K1 w kq -'],
        ['uci' => 'd4d5', 'san' => 'd5', 'fromKey' => 'r2qkb1r/1b3ppp/p3pn2/1p6/2pP4/2N1PN2/PPQ2PPP/R1BR2K1 w kq -', 'toKey' => 'r2qkb1r/1b3ppp/p3pn2/1p1P4/2p5/2N1PN2/PPQ2PPP/R1BR2K1 b kq -'],
        ['uci' => 'd8c7', 'san' => 'Qc7', 'fromKey' => 'r2qkb1r/1b3ppp/p3pn2/1p1P4/2p5/2N1PN2/PPQ2PPP/R1BR2K1 b kq -', 'toKey' => 'r3kb1r/1bq2ppp/p3pn2/1p1P4/2p5/2N1PN2/PPQ2PPP/R1BR2K1 w kq -'],
    ];

    public function getDescription(): string
    {
        return "Publish Queen's Gambit revision 2: complete Semi-Tarrasch Levenfish, Orthodox Classical and Accepted Classical lines";
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $opening = [
            ['d4', 'Move your pawn from d2 to d4.', 'Take space in the centre and open a path for the bishop on c1.'],
            ['d5', 'Move your pawn from d7 to d5.', 'Black takes central space too and stops your d-pawn from moving straight ahead.'],
            ['c4', 'Move your pawn from c2 to c4.', "Challenge Black's central pawn from the side. This is the Queen's Gambit: Black could capture this pawn, but does not have to."],
        ];
        $declined = [...$opening,
            ['e6', 'Move your pawn from e7 to e6.', 'Black declines the offered pawn for now, supports d5 and opens a path for the bishop on f8.'],
            ['Nc3', 'Move your knight from b1 to c3.', "Develop a knight towards the centre and add pressure to Black's pawn on d5."],
            ['Nf6', 'Move your knight from g8 to f6.', 'Black develops a knight, supports the pawn on d5 and controls e4.'],
        ];
        $introduction = "Learn the Queen's Gambit as White against three of Black's main replies, each a complete named line from the opening reference. In the Semi-Tarrasch you castle and then break with e4. In the Orthodox Defense, Black frees a cramped position with exchanges and ...e5. In the Queen's Gambit Accepted, Black takes on c4 and you win the pawn back. Black replies automatically along the selected line; real opponents can choose other continuations.";
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['declined-development', 'Semi-Tarrasch: develop, castle and play e4', 'Ten White moves. Keep the central tension, castle, then prepare and play the e4 break. Source: Semi-Tarrasch Defense, Levenfish Variation.', self::LEVENFISH_SOURCE, self::LEVENFISH_FACTS, [...$declined,
                ['Nf3', 'Move your knight from g1 to f3.', 'Bring your other knight into play. It defends d4 and clears g1 for castling.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Black challenges your pawn on d4. Both sides now have pawns that could capture each other: this is central tension. Neither side must capture immediately.'],
                ['e3', 'Move your pawn from e2 to e3.', 'Support d4 and open the diagonal for your bishop on f1. In this line you keep the central tension while developing.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black develops the second knight and adds another attacker to d4. The pawn on e3 and knight on f3 help you defend it.'],
                ['Bd3', 'Move your bishop from f1 to d3.', 'Put the bishop on an open diagonal. With f1 and g1 clear, your king can prepare to castle.'],
                ['Bd6', 'Move your bishop from f8 to d6.', 'Black develops the bishop towards the centre and clears f8 for castling.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Move your king towards the shelter of its pawns and bring the rook closer to the centre. Castling moves the king and rook together.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles too. Both kings are safe, and the four central pawns on c4, d4, c5 and d5 still face each other.'],
                ['Qe2', 'Move your queen from d1 to e2.', 'Prepare the central advance e3-e4. From e2 the queen supports e4 and steps off the d-file before it opens.'],
                ['Qe7', 'Move your queen from d8 to e7.', 'Black copies the idea and brings the queen off the back rank towards the centre.'],
                ['dxc5', 'Move your pawn from d4 to c5, capturing the black pawn.', "Release the tension at a moment of your choosing: the capture pulls Black's bishop away from d6 and clears the way for e4."],
                ['Bxc5', 'Move your bishop from d6 to c5, capturing the white pawn.', 'Black recaptures and material is level again.'],
                ['e4', 'Move your pawn from e3 to e4.', 'This is the move your setup was preparing: the pawn attacks d5 and opens lines for your pieces, supported by the knight on c3, the bishop on d3 and the queen on e2. The named Levenfish Variation ends here.'],
            ]],
            ['orthodox-classical', 'Orthodox Defense: the classical main line', 'Fourteen White moves, the longest named line in this course. Pin with Bg5, use the c-file, and watch how Black frees the position with ...Nd5 and ...e5. Source: Orthodox Defense, Classical Variation.', self::ORTHODOX_SOURCE, self::ORTHODOX_FACTS, [...$opening,
                ['e6', 'Move your pawn from e7 to e6.', "Black declines the pawn and supports d5. This is the Queen's Gambit Declined. The pawn on e6 also shuts in Black's bishop on c8: freeing that bishop is Black's main task in this line."],
                ['Nc3', 'Move your knight from b1 to c3.', "Develop a knight towards the centre and add pressure to Black's pawn on d5."],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black defends d5 a second time with a developing move.'],
                ['Bg5', 'Move your bishop from c1 to g5.', 'Pin the knight on f6 against the queen on d8. That knight defends d5, so pressure on f6 is also pressure on the centre.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Black places the bishop between the knight and queen, which ends the pin, and prepares to castle.'],
                ['e3', 'Move your pawn from e2 to e3.', 'Support d4 and open a path for the bishop on f1.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black puts the king in safety early.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop your last knight. It supports d4, watches e5 and clears the way for castling.'],
                ['Nbd7', 'Move your knight from b8 to d7.', 'Black develops this knight to d7 rather than c6, so the c-pawn stays free to support d5 with ...c6.'],
                ['Rc1', 'Move your rook from a1 to c1.', "Put the rook on the c-file. If the c-pawns are exchanged, the file opens towards Black's queenside."],
                ['c6', 'Move your pawn from c7 to c6.', 'Black reinforces d5 and prepares the freeing plan that follows.'],
                ['Bd3', 'Move your bishop from f1 to d3.', 'Develop your last minor piece. Only castling remains.'],
                ['dxc4', 'Move your pawn from d5 to c4, capturing the white pawn.', 'Black gives up the centre on purpose, but only now that your bishop has moved to d3: recapturing costs that bishop a second move.'],
                ['Bxc4', 'Move your bishop from d3 to c4, capturing the black pawn.', 'Win the pawn back. Material is level and your bishop is on an active diagonal again.'],
                ['Nd5', 'Move your knight from f6 to d5.', "This is Capablanca's freeing idea. The knight uncovers the bishop on e7, which now attacks your bishop on g5, and offers exchanges: a cramped position is easier to handle with fewer pieces on the board."],
                ['Bxe7', 'Move your bishop from g5 to e7, capturing the black bishop.', 'Exchange bishops rather than retreat.'],
                ['Qxe7', 'Move your queen from d8 to e7, capturing the white bishop.', 'Black recaptures with the queen, which now watches the e5 square.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Put your king in safety. Your rook from h1 now stands on f1, ready for the centre.'],
                ['Nxc3', 'Move your knight from d5 to c3, capturing the white knight.', 'Black exchanges a second pair of pieces.'],
                ['Rxc3', 'Move your rook from c1 to c3, capturing the black knight.', 'Recapture with the rook. Your pawns stay healthy, and the rook you placed on c1 is now active on the third rank.'],
                ['e5', 'Move your pawn from e6 to e5.', 'This is the freeing move Black was preparing. The pawn challenges d4 and opens the diagonal of the bishop on c8, which finally has a future.'],
                ['dxe5', 'Move your pawn from d4 to e5, capturing the black pawn.', 'Resolve the central tension. Black will win the pawn back, and more pieces will be exchanged.'],
                ['Nxe5', 'Move your knight from d7 to e5, capturing the white pawn.', 'Black regains the pawn. The knight now attacks your bishop on c4 and your knight on f3.'],
                ['Nxe5', 'Move your knight from f3 to e5, capturing the black knight.', 'Exchange knights. Material is level.'],
                ['Qxe5', 'Move your queen from e7 to e5, capturing the white knight.', "Black recaptures with a centralised queen. Black's freeing plan has worked: the bishop on c8 can now come out to f5 or g4. The named Classical Variation ends here, with a balanced position."],
            ]],
            ['accepted-classical', "Queen's Gambit Accepted: the classical plan", 'Thirteen White moves. Win back the c4 pawn, prepare the centre with Qe2 and Rd1, and break with d5. Source: Classical, Flohr Variation.', self::ACCEPTED_SOURCE, self::ACCEPTED_FACTS, [...$opening,
                ['dxc4', 'Move your pawn from d5 to c4, capturing the white pawn.', "Black accepts the gambit. Black does not try to keep the pawn: giving up the centre for a moment lets Black develop freely and attack your d4 pawn later. This is the Queen's Gambit Accepted."],
                ['Nf3', 'Move your knight from g1 to f3.', "Develop a knight and stop ...e5, which would free Black's game."],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops and controls e4, so you cannot easily build a big pawn centre with e4.'],
                ['e3', 'Move your pawn from e2 to e3.', 'Open the diagonal for your bishop on f1 so it can recapture on c4.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Black opens a path for the bishop on f8 and prepares to castle.'],
                ['Bxc4', 'Move your bishop from f1 to c4, capturing the black pawn.', 'Win back the pawn with a developing move. Material is level and you have more pawns in the centre.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Black attacks your pawn on d4. This counterattack is what Black gave up the centre for.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle before the centre opens.'],
                ['a6', 'Move your pawn from a7 to a6.', 'Black prepares ...b5 to gain space and chase your bishop.'],
                ['Qe2', 'Move your queen from d1 to e2.', 'Prepare Rd1 and a later central advance. The queen leaves the d-file so your rook can stand behind the d-pawn.'],
                ['b5', 'Move your pawn from b7 to b5.', 'Black gains queenside space and attacks your bishop.'],
                ['Bb3', 'Move your bishop from c4 to b3.', "Retreat to a diagonal where the bishop still points at Black's centre and the pawn on e6."],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black develops and adds pressure to d4.'],
                ['Rd1', 'Move your rook from f1 to d1.', 'Put the rook behind your d-pawn. When the centre opens, the rook is already on the right file.'],
                ['c4', 'Move your pawn from c5 to c4.', 'Black gains space and attacks your bishop again.'],
                ['Bc2', 'Move your bishop from b3 to c2.', "Keep the bishop. On c2 it aims towards Black's kingside."],
                ['Nb4', 'Move your knight from c6 to b4.', 'Black attacks the bishop on c2, planning to exchange the knight for it.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'Finish your development instead of moving the bishop again. If Black takes it, your queen recaptures.'],
                ['Nxc2', 'Move your knight from b4 to c2, capturing the white bishop.', 'Black wins the bishop pair: two bishops against your bishop and knight.'],
                ['Qxc2', 'Move your queen from e2 to c2, capturing the black knight.', 'Recapture. Material is level, and your queen now also watches e4.'],
                ['Bb7', 'Move your bishop from c8 to b7.', 'Black puts the bishop on the long diagonal, aiming at d5 and e4.'],
                ['d5', 'Move your pawn from d4 to d5.', 'This is the central break your rook and queen prepared. The pawn attacks e6 and blocks the bishop on b7.'],
                ['Qc7', 'Move your queen from d8 to c7.', 'Black takes the queen off the d-file, where it would face your rook as soon as the d5 pawn moves on or is exchanged. The named Flohr Variation ends here, with the centre opening just as your pieces prepared.'],
            ]],
        ];

        if (1 !== (int) $this->connection->fetchOne("SELECT current_revision FROM lesson WHERE id='queens-gambit'")) {
            throw new \RuntimeException("Queen's Gambit revision 2 must be published directly after revision 1.");
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException("Queen's Gambit teaching text and frozen path disagree: ".$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }

        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) SELECT lesson_id,2,title,?,start_position_id,UTC_TIMESTAMP(),recommended_color FROM lesson_revision WHERE lesson_id='queens-gambit' AND revision=1", [$introduction]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('queens-gambit',2,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('queens-gambit',2,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException("Missing resolved Queen's Gambit edge."), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=2 WHERE id='queens-gambit'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException("Publish a new revision; preserve previous Queen's Gambit sessions and progress.");
    }
}
