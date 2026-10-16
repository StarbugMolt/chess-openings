<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Scotch Game course: complete named pinned paths with original teaching notes. */
final class Version20261016020000 extends AbstractMigration
{
    public const CLASSICAL_GOTTSCHALL_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. d4 exd4 4. Nxd4 Bc5 5. Be3 Qf6 6. c3 Nge7 7. Qd2 d5 8. Nb5 Bxe3 9. Qxe3 O-O 10. Nxc7 Rb8 11. Nxd5 Nxd5 12. exd5 Nb4';
    public const SCOTCH_GAMBIT_MAX_LANGE_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. d4 exd4 4. Bc4 Nf6 5. O-O Bc5 6. e5 d5 7. exf6 dxc4 8. Re1+ Be6 9. Ng5 Qd5 10. Nc3 Qf5 11. Nce4';

    public const CLASSICAL_GOTTSCHALL_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/3PP3/5N2/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'e5d4', 'san' => 'exd4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/3PP3/5N2/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/8/3pP3/5N2/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f3d4', 'san' => 'Nxd4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/8/3pP3/5N2/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/8/3NP3/8/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'f8c5', 'san' => 'Bc5', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/8/3NP3/8/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b5/3NP3/8/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'c1e3', 'san' => 'Be3', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b5/3NP3/8/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b5/3NP3/4B3/PPP2PPP/RN1QKB1R b KQkq -'],
        ['uci' => 'd8f6', 'san' => 'Qf6', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b5/3NP3/4B3/PPP2PPP/RN1QKB1R b KQkq -', 'toKey' => 'r1b1k1nr/pppp1ppp/2n2q2/2b5/3NP3/4B3/PPP2PPP/RN1QKB1R w KQkq -'],
        ['uci' => 'c2c3', 'san' => 'c3', 'fromKey' => 'r1b1k1nr/pppp1ppp/2n2q2/2b5/3NP3/4B3/PPP2PPP/RN1QKB1R w KQkq -', 'toKey' => 'r1b1k1nr/pppp1ppp/2n2q2/2b5/3NP3/2P1B3/PP3PPP/RN1QKB1R b KQkq -'],
        ['uci' => 'g8e7', 'san' => 'Nge7', 'fromKey' => 'r1b1k1nr/pppp1ppp/2n2q2/2b5/3NP3/2P1B3/PP3PPP/RN1QKB1R b KQkq -', 'toKey' => 'r1b1k2r/ppppnppp/2n2q2/2b5/3NP3/2P1B3/PP3PPP/RN1QKB1R w KQkq -'],
        ['uci' => 'd1d2', 'san' => 'Qd2', 'fromKey' => 'r1b1k2r/ppppnppp/2n2q2/2b5/3NP3/2P1B3/PP3PPP/RN1QKB1R w KQkq -', 'toKey' => 'r1b1k2r/ppppnppp/2n2q2/2b5/3NP3/2P1B3/PP1Q1PPP/RN2KB1R b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'r1b1k2r/ppppnppp/2n2q2/2b5/3NP3/2P1B3/PP1Q1PPP/RN2KB1R b KQkq -', 'toKey' => 'r1b1k2r/ppp1nppp/2n2q2/2bp4/3NP3/2P1B3/PP1Q1PPP/RN2KB1R w KQkq -'],
        ['uci' => 'd4b5', 'san' => 'Nb5', 'fromKey' => 'r1b1k2r/ppp1nppp/2n2q2/2bp4/3NP3/2P1B3/PP1Q1PPP/RN2KB1R w KQkq -', 'toKey' => 'r1b1k2r/ppp1nppp/2n2q2/1Nbp4/4P3/2P1B3/PP1Q1PPP/RN2KB1R b KQkq -'],
        ['uci' => 'c5e3', 'san' => 'Bxe3', 'fromKey' => 'r1b1k2r/ppp1nppp/2n2q2/1Nbp4/4P3/2P1B3/PP1Q1PPP/RN2KB1R b KQkq -', 'toKey' => 'r1b1k2r/ppp1nppp/2n2q2/1N1p4/4P3/2P1b3/PP1Q1PPP/RN2KB1R w KQkq -'],
        ['uci' => 'd2e3', 'san' => 'Qxe3', 'fromKey' => 'r1b1k2r/ppp1nppp/2n2q2/1N1p4/4P3/2P1b3/PP1Q1PPP/RN2KB1R w KQkq -', 'toKey' => 'r1b1k2r/ppp1nppp/2n2q2/1N1p4/4P3/2P1Q3/PP3PPP/RN2KB1R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1b1k2r/ppp1nppp/2n2q2/1N1p4/4P3/2P1Q3/PP3PPP/RN2KB1R b KQkq -', 'toKey' => 'r1b2rk1/ppp1nppp/2n2q2/1N1p4/4P3/2P1Q3/PP3PPP/RN2KB1R w KQ -'],
        ['uci' => 'b5c7', 'san' => 'Nxc7', 'fromKey' => 'r1b2rk1/ppp1nppp/2n2q2/1N1p4/4P3/2P1Q3/PP3PPP/RN2KB1R w KQ -', 'toKey' => 'r1b2rk1/ppN1nppp/2n2q2/3p4/4P3/2P1Q3/PP3PPP/RN2KB1R b KQ -'],
        ['uci' => 'a8b8', 'san' => 'Rb8', 'fromKey' => 'r1b2rk1/ppN1nppp/2n2q2/3p4/4P3/2P1Q3/PP3PPP/RN2KB1R b KQ -', 'toKey' => '1rb2rk1/ppN1nppp/2n2q2/3p4/4P3/2P1Q3/PP3PPP/RN2KB1R w KQ -'],
        ['uci' => 'c7d5', 'san' => 'Nxd5', 'fromKey' => '1rb2rk1/ppN1nppp/2n2q2/3p4/4P3/2P1Q3/PP3PPP/RN2KB1R w KQ -', 'toKey' => '1rb2rk1/pp2nppp/2n2q2/3N4/4P3/2P1Q3/PP3PPP/RN2KB1R b KQ -'],
        ['uci' => 'e7d5', 'san' => 'Nxd5', 'fromKey' => '1rb2rk1/pp2nppp/2n2q2/3N4/4P3/2P1Q3/PP3PPP/RN2KB1R b KQ -', 'toKey' => '1rb2rk1/pp3ppp/2n2q2/3n4/4P3/2P1Q3/PP3PPP/RN2KB1R w KQ -'],
        ['uci' => 'e4d5', 'san' => 'exd5', 'fromKey' => '1rb2rk1/pp3ppp/2n2q2/3n4/4P3/2P1Q3/PP3PPP/RN2KB1R w KQ -', 'toKey' => '1rb2rk1/pp3ppp/2n2q2/3P4/8/2P1Q3/PP3PPP/RN2KB1R b KQ -'],
        ['uci' => 'c6b4', 'san' => 'Nb4', 'fromKey' => '1rb2rk1/pp3ppp/2n2q2/3P4/8/2P1Q3/PP3PPP/RN2KB1R b KQ -', 'toKey' => '1rb2rk1/pp3ppp/5q2/3P4/1n6/2P1Q3/PP3PPP/RN2KB1R w KQ -'],
    ];

    public const SCOTCH_GAMBIT_MAX_LANGE_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/3PP3/5N2/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'e5d4', 'san' => 'exd4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/3PP3/5N2/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/8/3pP3/5N2/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1c4', 'san' => 'Bc4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/8/3pP3/5N2/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/8/2BpP3/5N2/PPP2PPP/RNBQK2R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/8/2BpP3/5N2/PPP2PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/8/2BpP3/5N2/PPP2PPP/RNBQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/8/2BpP3/5N2/PPP2PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqkb1r/pppp1ppp/2n2n2/8/2BpP3/5N2/PPP2PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'f8c5', 'san' => 'Bc5', 'fromKey' => 'r1bqkb1r/pppp1ppp/2n2n2/8/2BpP3/5N2/PPP2PPP/RNBQ1RK1 b kq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b5/2BpP3/5N2/PPP2PPP/RNBQ1RK1 w kq -'],
        ['uci' => 'e4e5', 'san' => 'e5', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b5/2BpP3/5N2/PPP2PPP/RNBQ1RK1 w kq -', 'toKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b1P3/2Bp4/5N2/PPP2PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'r1bqk2r/pppp1ppp/2n2n2/2b1P3/2Bp4/5N2/PPP2PPP/RNBQ1RK1 b kq -', 'toKey' => 'r1bqk2r/ppp2ppp/2n2n2/2bpP3/2Bp4/5N2/PPP2PPP/RNBQ1RK1 w kq d6'],
        ['uci' => 'e5f6', 'san' => 'exf6', 'fromKey' => 'r1bqk2r/ppp2ppp/2n2n2/2bpP3/2Bp4/5N2/PPP2PPP/RNBQ1RK1 w kq d6', 'toKey' => 'r1bqk2r/ppp2ppp/2n2P2/2bp4/2Bp4/5N2/PPP2PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'd5c4', 'san' => 'dxc4', 'fromKey' => 'r1bqk2r/ppp2ppp/2n2P2/2bp4/2Bp4/5N2/PPP2PPP/RNBQ1RK1 b kq -', 'toKey' => 'r1bqk2r/ppp2ppp/2n2P2/2b5/2pp4/5N2/PPP2PPP/RNBQ1RK1 w kq -'],
        ['uci' => 'f1e1', 'san' => 'Re1+', 'fromKey' => 'r1bqk2r/ppp2ppp/2n2P2/2b5/2pp4/5N2/PPP2PPP/RNBQ1RK1 w kq -', 'toKey' => 'r1bqk2r/ppp2ppp/2n2P2/2b5/2pp4/5N2/PPP2PPP/RNBQR1K1 b kq -'],
        ['uci' => 'c8e6', 'san' => 'Be6', 'fromKey' => 'r1bqk2r/ppp2ppp/2n2P2/2b5/2pp4/5N2/PPP2PPP/RNBQR1K1 b kq -', 'toKey' => 'r2qk2r/ppp2ppp/2n1bP2/2b5/2pp4/5N2/PPP2PPP/RNBQR1K1 w kq -'],
        ['uci' => 'f3g5', 'san' => 'Ng5', 'fromKey' => 'r2qk2r/ppp2ppp/2n1bP2/2b5/2pp4/5N2/PPP2PPP/RNBQR1K1 w kq -', 'toKey' => 'r2qk2r/ppp2ppp/2n1bP2/2b3N1/2pp4/8/PPP2PPP/RNBQR1K1 b kq -'],
        ['uci' => 'd8d5', 'san' => 'Qd5', 'fromKey' => 'r2qk2r/ppp2ppp/2n1bP2/2b3N1/2pp4/8/PPP2PPP/RNBQR1K1 b kq -', 'toKey' => 'r3k2r/ppp2ppp/2n1bP2/2bq2N1/2pp4/8/PPP2PPP/RNBQR1K1 w kq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r3k2r/ppp2ppp/2n1bP2/2bq2N1/2pp4/8/PPP2PPP/RNBQR1K1 w kq -', 'toKey' => 'r3k2r/ppp2ppp/2n1bP2/2bq2N1/2pp4/2N5/PPP2PPP/R1BQR1K1 b kq -'],
        ['uci' => 'd5f5', 'san' => 'Qf5', 'fromKey' => 'r3k2r/ppp2ppp/2n1bP2/2bq2N1/2pp4/2N5/PPP2PPP/R1BQR1K1 b kq -', 'toKey' => 'r3k2r/ppp2ppp/2n1bP2/2b2qN1/2pp4/2N5/PPP2PPP/R1BQR1K1 w kq -'],
        ['uci' => 'c3e4', 'san' => 'Nce4', 'fromKey' => 'r3k2r/ppp2ppp/2n1bP2/2b2qN1/2pp4/2N5/PPP2PPP/R1BQR1K1 w kq -', 'toKey' => 'r3k2r/ppp2ppp/2n1bP2/2b2qN1/2ppN3/8/PPP2PPP/R1BQR1K1 b kq -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Scotch Game course: Gottschall Variation; Scotch Gambit, Max Lange Attack, Long Variation';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play White with 1. e4 e5 2. Nf3 Nc6 3. d4, the Scotch Game. You open the centre at once: after 3...exd4 Black\'s e5 pawn is gone, and your pieces can develop quickly. The Gottschall Variation teaches the classical recapture 4. Nxd4: you defend the knight against ...Bc5 and ...Qf6 with Be3, c3 and Qd2, then take two pawns while Black develops quickly against your uncastled king. The Max Lange Attack teaches the Scotch Gambit 4. Bc4: you leave the d4 pawn, castle, push e5 and use the open e-file and your knights against Black\'s king. Black replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['classical-gottschall', 'Gottschall: hold d4, then take two pawns', 'Twelve White moves. Defend the knight on d4 against ...Bc5 and ...Qf6, then take the pawns on c7 and d5 while Black develops quickly against your king in the centre. Source: Gottschall Variation.', self::CLASSICAL_GOTTSCHALL_SOURCE, self::CLASSICAL_GOTTSCHALL_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open diagonals for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop with a threat: the knight attacks the pawn on e5.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 by developing a knight.'],
                ['d4', 'Move your pawn from d2 to d4.', 'This is the Scotch Game. Strike in the centre at once: the pawn and your knight both attack e5, and only the knight on c6 defends it.'],
                ['exd4', 'Move your pawn from e5 to d4, capturing the white pawn.', 'Black takes on d4 rather than defend e5 a second time. You can win this pawn back, or leave it there for quicker development.'],
                ['Nxd4', 'Move your knight from f3 to d4, capturing the black pawn.', 'This is where this line chooses its own path: win the pawn back at once with the knight. It stands in the centre, attacked by the knight on c6 and defended by your queen.'],
                ['Bc5', 'Move your bishop from f8 to c5.', 'Black develops the bishop and attacks the knight on d4 a second time. Only your queen defends it, so Black threatens to win it.'],
                ['Be3', 'Move your bishop from c1 to e3.', 'Develop the bishop and defend the knight a second time. Two attackers now meet two defenders.'],
                ['Qf6', 'Move your queen from d8 to f6.', 'Black\'s queen joins the attack along the diagonal from f6. The knight on d4 is now attacked three times. The queen also eyes f2, which your bishop and king protect.'],
                ['c3', 'Move your pawn from c2 to c3.', 'Add a third defender with a pawn. The knight now has as many defenders as attackers, and a pawn is the cheapest defender if exchanges start on d4.'],
                ['Nge7', 'Move your knight from g8 to e7.', 'Black develops the kingside knight to e7, because the queen occupies f6. Black can now castle, and the knight supports a later ...d5.'],
                ['Qd2', 'Move your queen from d1 to d2.', 'Put the queen on d2. It protects your bishop on e3 and still guards the knight on d4 along the d-file. Clearing d1 also prepares queenside castling once the knight on b1 has moved.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Black strikes back in the centre. The pawn attacks your e4 pawn, which nothing defends, and opens the diagonal of the bishop on c8.'],
                ['Nb5', 'Move your knight from d4 to b5.', 'Jump to b5. The knight threatens Nxc7+, a check that would also attack the rook on a8, and nothing defends c7. Moving the knight also opens the diagonal between the bishops: your bishop on e3 now attacks the bishop on c5, which is unprotected.'],
                ['Bxe3', 'Move your bishop from c5 to e3, capturing the white bishop.', 'Black exchanges bishops before the bishop on c5 can be taken. The threat on c7 is still there.'],
                ['Qxe3', 'Move your queen from d2 to e3, capturing the black bishop.', 'Recapture with the queen. On e3 it also defends your e4 pawn, and Nxc7+ is still threatened.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles and gives up the c7 pawn. Nxc7 is no longer check, and Black brings the king to safety while yours is still on e1.'],
                ['Nxc7', 'Move your knight from b5 to c7, capturing the black pawn.', 'Take the pawn. The knight attacks the rook on a8 and the pawn on d5, which your e4 pawn also attacks.'],
                ['Rb8', 'Move your rook from a8 to b8.', 'Black moves the rook out of attack. The pawn on d5 is now attacked twice and defended only by the knight on e7.'],
                ['Nxd5', 'Move your knight from c7 to d5, capturing the black pawn.', 'Take a second pawn. Your knight on d5 attacks Black\'s queen on f6 and the knight on e7, and your e4 pawn protects it.'],
                ['Nxd5', 'Move your knight from e7 to d5, capturing the white knight.', 'Black exchanges knights on d5 and removes the piece that attacked the queen.'],
                ['exd5', 'Move your pawn from e4 to d5, capturing the black knight.', 'Recapture with the pawn. You are two pawns up, and the pawn on d5 now attacks the knight on c6.'],
                ['Nb4', 'Move your knight from c6 to b4.', 'Black\'s knight leaves c6 and attacks your pawn on d5, which nothing defends. It also threatens ...Nc2+, which would attack your king, queen and the rook on a1 at once. The knight on b4 is unprotected, and your c3 pawn attacks it. You are two pawns up, but your king is still in the centre. The named Gottschall Variation ends here: your next task is to meet the threat on c2, then develop the bishop on f1 and castle.'],
            ]],
            ['scotch-gambit-max-lange', 'Max Lange Attack: e5 and the open e-file', 'Eleven White moves. Leave the pawn on d4, castle and push e5, then use the open e-file and your knights on g5 and e4 against Black\'s king in the centre. Source: Scotch Gambit, Max Lange Attack, Long Variation.', self::SCOTCH_GAMBIT_MAX_LANGE_SOURCE, self::SCOTCH_GAMBIT_MAX_LANGE_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open diagonals for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop with a threat: the knight attacks the pawn on e5.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 by developing a knight.'],
                ['d4', 'Move your pawn from d2 to d4.', 'This is the Scotch Game. Strike in the centre at once: the pawn and your knight both attack e5, and only the knight on c6 defends it.'],
                ['exd4', 'Move your pawn from e5 to d4, capturing the white pawn.', 'Black takes on d4 rather than defend e5 a second time. You can win this pawn back, or leave it there for quicker development.'],
                ['Bc4', 'Move your bishop from f1 to c4.', 'This is where this line chooses its own path: leave the pawn on d4 and develop the bishop instead. It aims at f7, the square next to Black\'s king that only the king defends. Giving the pawn for time like this is the Scotch Gambit.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Black develops and attacks your e4 pawn, which nothing defends.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle and leave e4 as it is. Your king is safe, and the rook on f1 can quickly reach the e-file if it opens.'],
                ['Bc5', 'Move your bishop from f8 to c5.', 'Black develops the bishop and protects the extra pawn on d4.'],
                ['e5', 'Move your pawn from e4 to e5.', 'Push the pawn and attack the knight on f6. Your knight on f3 protects the pawn on e5.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Black hits back instead of moving the knight: the pawn attacks your bishop on c4 and opens the diagonal of the bishop on c8.'],
                ['exf6', 'Move your pawn from e5 to f6, capturing the black knight.', 'Take the knight. Black will take your bishop in return, but your new pawn on f6 attacks g7 and e7 and stays close to Black\'s king.'],
                ['dxc4', 'Move your pawn from d5 to c4, capturing the white bishop.', 'Black takes the bishop. Black is a pawn up, but the e-file is now open all the way to the king on e8.'],
                ['Re1+', 'Move your rook from f1 to e1, giving check.', 'Give check on the open e-file. Black must block the check or move the king, which would give up castling.'],
                ['Be6', 'Move your bishop from c8 to e6.', 'Black blocks with the bishop. The bishop is now pinned: it cannot leave e6, because your rook would then give check.'],
                ['Ng5', 'Move your knight from f3 to g5.', 'Attack the pinned bishop a second time. Only the f7 pawn defends it. The knight on g5 also eyes f7 and h7.'],
                ['Qd5', 'Move your queen from d8 to d5.', 'Black\'s queen defends the bishop on e6 and attacks your knight on g5. Your bishop on c1 protects the knight.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'Develop the knight and attack the queen. If the d4 pawn takes the knight, the d-file opens and your queen takes Black\'s queen on d5, because the pinned bishop on e6 cannot recapture.'],
                ['Qf5', 'Move your queen from d5 to f5.', 'Black\'s queen moves to f5, where it still defends the bishop on e6 and attacks your knight on g5.'],
                ['Nce4', 'Move your knight from c3 to e4.', 'Bring the other knight to e4. It protects the knight on g5 and the pawn on f6, and attacks the bishop on c5, which Black\'s queen defends. The two knights protect each other. The knight now blocks the e-file, so the bishop on e6 is no longer pinned. The named Max Lange Attack ends here: your next task is to bring the bishop on c1 and your queen into play and use the f6 pawn, which attacks g7, against Black\'s kingside.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='scotch'")) {
            throw new \RuntimeException('The Scotch Game course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Scotch Game teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::CLASSICAL_GOTTSCHALL_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Scotch Game start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('scotch',NULL,10)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('scotch',1,?,?,?,UTC_TIMESTAMP(),'w')", ['Scotch Game', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('scotch',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('scotch',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Scotch Game edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='scotch'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Scotch Game sessions and progress.');
    }
}
