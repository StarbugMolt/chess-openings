<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Petrov's Defense course: complete named pinned paths with original teaching notes. */
final class Version20261016020300 extends AbstractMigration
{
    public const CLASSICAL_MAROCZY_SOURCE = '1. e4 e5 2. Nf3 Nf6 3. Nxe5 d6 4. Nf3 Nxe4 5. d4 d5 6. Bd3 Be7 7. O-O Nc6 8. Re1 Bg4 9. c3 f5 10. c4 Bh4';
    public const CLASSICAL_MARSHALL_SOURCE = '1. e4 e5 2. Nf3 Nf6 3. Nxe5 d6 4. Nf3 Nxe4 5. d4 d5 6. Bd3 Bd6 7. O-O O-O 8. c4 c6 9. Re1 Bg4';

    public const CLASSICAL_MAROCZY_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/5n2/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f3e5', 'san' => 'Nxe5', 'fromKey' => 'rnbqkb1r/pppp1ppp/5n2/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/5n2/4N3/4P3/8/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'rnbqkb1r/pppp1ppp/5n2/4N3/4P3/8/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/3p1n2/4N3/4P3/8/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'e5f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkb1r/ppp2ppp/3p1n2/4N3/4P3/8/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/3p1n2/8/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'f6e4', 'san' => 'Nxe4', 'fromKey' => 'rnbqkb1r/ppp2ppp/3p1n2/8/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/3p4/8/4n3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkb1r/ppp2ppp/3p4/8/4n3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/3p4/8/3Pn3/5N2/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'd6d5', 'san' => 'd5', 'fromKey' => 'rnbqkb1r/ppp2ppp/3p4/8/3Pn3/5N2/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/8/3p4/3Pn3/5N2/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'rnbqkb1r/ppp2ppp/8/3p4/3Pn3/5N2/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/8/3p4/3Pn3/3B1N2/PPP2PPP/RNBQK2R b KQkq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'rnbqkb1r/ppp2ppp/8/3p4/3Pn3/3B1N2/PPP2PPP/RNBQK2R b KQkq -', 'toKey' => 'rnbqk2r/ppp1bppp/8/3p4/3Pn3/3B1N2/PPP2PPP/RNBQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/ppp1bppp/8/3p4/3Pn3/3B1N2/PPP2PPP/RNBQK2R w KQkq -', 'toKey' => 'rnbqk2r/ppp1bppp/8/3p4/3Pn3/3B1N2/PPP2PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqk2r/ppp1bppp/8/3p4/3Pn3/3B1N2/PPP2PPP/RNBQ1RK1 b kq -', 'toKey' => 'r1bqk2r/ppp1bppp/2n5/3p4/3Pn3/3B1N2/PPP2PPP/RNBQ1RK1 w kq -'],
        ['uci' => 'f1e1', 'san' => 'Re1', 'fromKey' => 'r1bqk2r/ppp1bppp/2n5/3p4/3Pn3/3B1N2/PPP2PPP/RNBQ1RK1 w kq -', 'toKey' => 'r1bqk2r/ppp1bppp/2n5/3p4/3Pn3/3B1N2/PPP2PPP/RNBQR1K1 b kq -'],
        ['uci' => 'c8g4', 'san' => 'Bg4', 'fromKey' => 'r1bqk2r/ppp1bppp/2n5/3p4/3Pn3/3B1N2/PPP2PPP/RNBQR1K1 b kq -', 'toKey' => 'r2qk2r/ppp1bppp/2n5/3p4/3Pn1b1/3B1N2/PPP2PPP/RNBQR1K1 w kq -'],
        ['uci' => 'c2c3', 'san' => 'c3', 'fromKey' => 'r2qk2r/ppp1bppp/2n5/3p4/3Pn1b1/3B1N2/PPP2PPP/RNBQR1K1 w kq -', 'toKey' => 'r2qk2r/ppp1bppp/2n5/3p4/3Pn1b1/2PB1N2/PP3PPP/RNBQR1K1 b kq -'],
        ['uci' => 'f7f5', 'san' => 'f5', 'fromKey' => 'r2qk2r/ppp1bppp/2n5/3p4/3Pn1b1/2PB1N2/PP3PPP/RNBQR1K1 b kq -', 'toKey' => 'r2qk2r/ppp1b1pp/2n5/3p1p2/3Pn1b1/2PB1N2/PP3PPP/RNBQR1K1 w kq -'],
        ['uci' => 'c3c4', 'san' => 'c4', 'fromKey' => 'r2qk2r/ppp1b1pp/2n5/3p1p2/3Pn1b1/2PB1N2/PP3PPP/RNBQR1K1 w kq -', 'toKey' => 'r2qk2r/ppp1b1pp/2n5/3p1p2/2PPn1b1/3B1N2/PP3PPP/RNBQR1K1 b kq -'],
        ['uci' => 'e7h4', 'san' => 'Bh4', 'fromKey' => 'r2qk2r/ppp1b1pp/2n5/3p1p2/2PPn1b1/3B1N2/PP3PPP/RNBQR1K1 b kq -', 'toKey' => 'r2qk2r/ppp3pp/2n5/3p1p2/2PPn1bb/3B1N2/PP3PPP/RNBQR1K1 w kq -'],
    ];

    public const CLASSICAL_MARSHALL_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/5n2/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f3e5', 'san' => 'Nxe5', 'fromKey' => 'rnbqkb1r/pppp1ppp/5n2/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/5n2/4N3/4P3/8/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'rnbqkb1r/pppp1ppp/5n2/4N3/4P3/8/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/3p1n2/4N3/4P3/8/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'e5f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkb1r/ppp2ppp/3p1n2/4N3/4P3/8/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/3p1n2/8/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'f6e4', 'san' => 'Nxe4', 'fromKey' => 'rnbqkb1r/ppp2ppp/3p1n2/8/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/3p4/8/4n3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkb1r/ppp2ppp/3p4/8/4n3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/3p4/8/3Pn3/5N2/PPP2PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'd6d5', 'san' => 'd5', 'fromKey' => 'rnbqkb1r/ppp2ppp/3p4/8/3Pn3/5N2/PPP2PPP/RNBQKB1R b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/8/3p4/3Pn3/5N2/PPP2PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'rnbqkb1r/ppp2ppp/8/3p4/3Pn3/5N2/PPP2PPP/RNBQKB1R w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/8/3p4/3Pn3/3B1N2/PPP2PPP/RNBQK2R b KQkq -'],
        ['uci' => 'f8d6', 'san' => 'Bd6', 'fromKey' => 'rnbqkb1r/ppp2ppp/8/3p4/3Pn3/3B1N2/PPP2PPP/RNBQK2R b KQkq -', 'toKey' => 'rnbqk2r/ppp2ppp/3b4/3p4/3Pn3/3B1N2/PPP2PPP/RNBQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/ppp2ppp/3b4/3p4/3Pn3/3B1N2/PPP2PPP/RNBQK2R w KQkq -', 'toKey' => 'rnbqk2r/ppp2ppp/3b4/3p4/3Pn3/3B1N2/PPP2PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/ppp2ppp/3b4/3p4/3Pn3/3B1N2/PPP2PPP/RNBQ1RK1 b kq -', 'toKey' => 'rnbq1rk1/ppp2ppp/3b4/3p4/3Pn3/3B1N2/PPP2PPP/RNBQ1RK1 w - -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbq1rk1/ppp2ppp/3b4/3p4/3Pn3/3B1N2/PPP2PPP/RNBQ1RK1 w - -', 'toKey' => 'rnbq1rk1/ppp2ppp/3b4/3p4/2PPn3/3B1N2/PP3PPP/RNBQ1RK1 b - -'],
        ['uci' => 'c7c6', 'san' => 'c6', 'fromKey' => 'rnbq1rk1/ppp2ppp/3b4/3p4/2PPn3/3B1N2/PP3PPP/RNBQ1RK1 b - -', 'toKey' => 'rnbq1rk1/pp3ppp/2pb4/3p4/2PPn3/3B1N2/PP3PPP/RNBQ1RK1 w - -'],
        ['uci' => 'f1e1', 'san' => 'Re1', 'fromKey' => 'rnbq1rk1/pp3ppp/2pb4/3p4/2PPn3/3B1N2/PP3PPP/RNBQ1RK1 w - -', 'toKey' => 'rnbq1rk1/pp3ppp/2pb4/3p4/2PPn3/3B1N2/PP3PPP/RNBQR1K1 b - -'],
        ['uci' => 'c8g4', 'san' => 'Bg4', 'fromKey' => 'rnbq1rk1/pp3ppp/2pb4/3p4/2PPn3/3B1N2/PP3PPP/RNBQR1K1 b - -', 'toKey' => 'rn1q1rk1/pp3ppp/2pb4/3p4/2PPn1b1/3B1N2/PP3PPP/RNBQR1K1 w - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Petrov\'s Defense course: Classical Attack, Maróczy Variation; Classical Attack, Marshall Variation, Chinese Gambit';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. e4 with 1...e5 2. Nf3 Nf6, Petrov\'s Defense. Instead of defending your e5 pawn, you counterattack White\'s e4 pawn. After 3. Nxe5 you first drive the knight back with ...d6 and only then take on e4, so material is level and your knight stands in the centre. Both lines meet the Classical Attack with d4 and Bd3, where you support the knight with ...d5. In the Maróczy Variation, you develop with ...Be7, ...Nc6 and ...Bg4, then support the knight with ...f5. In the Marshall Variation, you put the bishop on d6, castle and hold the d5 pawn with ...c6. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['classical-maroczy', 'Maróczy: ...Nc6, ...Bg4 and ...f5 in the Classical Attack', 'Ten Black moves. Win back the e4 pawn, support your central knight with ...d5 and ...f5, pin White\'s knight with ...Bg4 and counterattack f2 with ...Bh4. Source: Classical Attack, Maróczy Variation.', self::CLASSICAL_MAROCZY_SOURCE, self::CLASSICAL_MAROCZY_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens a diagonal for the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Occupy e5 with your own pawn and claim an equal share of the centre. It also stops White\'s e-pawn from advancing.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the knight and attacks your pawn on e5.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Counterattack instead of defending. Your knight attacks e4, which is no better defended than your pawn on e5. This is Petrov\'s Defense.'],
                ['Nxe5', 'Move your knight from f3 to e5, capturing the black pawn.', 'White takes your pawn on e5. From there the knight eyes f7 and d7.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Drive the knight away before you win back the pawn. Your e-pawn has gone, so the e-file leads to your king; taking on e4 at once would allow Qe2, attacking your knight on that file.'],
                ['Nf3', 'Move your knight from e5 to f3.', 'White\'s knight retreats to f3. Nothing defends the pawn on e4 now.'],
                ['Nxe4', 'Move your knight from f6 to e4, capturing the white pawn.', 'Take the pawn back. Material is level again, and your knight stands in the centre.'],
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre, frees the bishop on c1 and prepares Bd3 to challenge your knight.'],
                ['d5', 'Move your pawn from d6 to d5.', 'Support the knight on e4 with a pawn and claim your share of the centre.'],
                ['Bd3', 'Move your bishop from f1 to d3.', 'White develops the bishop, attacks your knight and prepares to castle. Your pawn on d5 defends the knight.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Develop the bishop to e7 and prepare to castle. Here this line takes its own path: on e7 the bishop does not block your queen, which keeps defending d5.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles and brings the king to safety.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and attack d4. With the bishop on d3 blocking White\'s queen, only the knight on f3 defends that pawn.'],
                ['Re1', 'Move your rook from f1 to e1.', 'White puts the rook on the e-file. Your knight on e4 is now attacked by the bishop and the rook, and behind it on the file stand your bishop on e7 and your king. Your pawn on d5 defends the knight.'],
                ['Bg4', 'Move your bishop from c8 to g4.', 'Develop the bishop and pin the knight on f3 against White\'s queen. That knight is the only defender of d4, which your knight on c6 attacks.'],
                ['c3', 'Move your pawn from c2 to c3.', 'White adds a pawn to the defence of d4.'],
                ['f5', 'Move your pawn from f7 to f5.', 'Support the knight on e4 with a second pawn, so it is now defended twice. The cost is that no pawn can defend e6 any more, so that square becomes weaker.'],
                ['c4', 'Move your pawn from c3 to c4.', 'White attacks d5, one of the two pawns that support your knight. Only your queen defends d5.'],
                ['Bh4', 'Move your bishop from e7 to h4.', 'Counterattack f2, which only White\'s king defends. Your knight on e4 also covers f2, so if White does nothing about it, ...Bxf2+ is check, the king cannot take back, and your bishop then attacks the rook on e1. Your bishop has left e7, so the rook on e1 now pins your knight against your king. The named Maróczy Variation ends here: your next task is to castle, which takes your king off the e-file and frees the knight from the pin.'],
            ]],
            ['classical-marshall', 'Marshall: ...Bd6, ...c6 and ...Bg4 in the Classical Attack', 'Nine Black moves. Win back the e4 pawn, develop the bishop on d6, castle, hold d5 with ...c6 and pin White\'s knight with ...Bg4. Source: Classical Attack, Marshall Variation, Chinese Gambit.', self::CLASSICAL_MARSHALL_SOURCE, self::CLASSICAL_MARSHALL_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens a diagonal for the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Occupy e5 with your own pawn and claim an equal share of the centre. It also stops White\'s e-pawn from advancing.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the knight and attacks your pawn on e5.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Counterattack instead of defending. Your knight attacks e4, which is no better defended than your pawn on e5. This is Petrov\'s Defense.'],
                ['Nxe5', 'Move your knight from f3 to e5, capturing the black pawn.', 'White takes your pawn on e5. From there the knight eyes f7 and d7.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Drive the knight away before you win back the pawn. Your e-pawn has gone, so the e-file leads to your king; taking on e4 at once would allow Qe2, attacking your knight on that file.'],
                ['Nf3', 'Move your knight from e5 to f3.', 'White\'s knight retreats to f3. Nothing defends the pawn on e4 now.'],
                ['Nxe4', 'Move your knight from f6 to e4, capturing the white pawn.', 'Take the pawn back. Material is level again, and your knight stands in the centre.'],
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre, frees the bishop on c1 and prepares Bd3 to challenge your knight.'],
                ['d5', 'Move your pawn from d6 to d5.', 'Support the knight on e4 with a pawn and claim your share of the centre.'],
                ['Bd3', 'Move your bishop from f1 to d3.', 'White develops the bishop, attacks your knight and prepares to castle. Your pawn on d5 defends the knight.'],
                ['Bd6', 'Move your bishop from f8 to d6.', 'Develop the bishop to d6, where it aims along the diagonal towards h2. Here this line takes its own path. On d6 the bishop blocks your queen\'s defence of d5.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles and brings the king to safety.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle and take your king off the e-file before a white rook arrives there.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White attacks d5, the pawn that defends your knight on e4. Your bishop on d6 blocks the queen, so nothing protects d5 at the moment.'],
                ['c6', 'Move your pawn from c7 to c6.', 'Defend d5 with the c-pawn. The pawns on c6 and d5 now form a chain that supports the knight on e4.'],
                ['Re1', 'Move your rook from f1 to e1.', 'White puts the rook on the e-file. Your knight on e4 is now attacked by the bishop and the rook and defended only by the pawn on d5, which the c4 pawn also attacks.'],
                ['Bg4', 'Move your bishop from c8 to g4.', 'Develop the bishop and pin the knight on f3 against White\'s queen. That knight defends d4 and also h2, the square your bishop on d6 aims at. The named Chinese Gambit ends here: your next task is to develop your last knight with ...Nd7 and keep the knight on e4 supported, for example with ...f5.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='petrov'")) {
            throw new \RuntimeException('The Petrov\'s Defense course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Petrov\'s Defense teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::CLASSICAL_MAROCZY_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Petrov\'s Defense start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('petrov',NULL,13)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('petrov',1,?,?,?,UTC_TIMESTAMP(),'b')", ['Petrov\'s Defense', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('petrov',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('petrov',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Petrov\'s Defense edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='petrov'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Petrov\'s Defense sessions and progress.');
    }
}
