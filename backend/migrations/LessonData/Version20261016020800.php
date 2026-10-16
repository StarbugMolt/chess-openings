<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Tarrasch Defense course: complete named pinned paths with original teaching notes. */
final class Version20261016020800 extends AbstractMigration
{
    public const CLASSICAL_MAIN_LINE_SOURCE = '1. d4 d5 2. c4 e6 3. Nc3 c5 4. cxd5 exd5 5. Nf3 Nc6 6. g3 Nf6 7. Bg2 Be7 8. O-O O-O 9. Bg5 cxd4 10. Nxd4 h6 11. Be3 Re8';
    public const CLASSICAL_ADVANCE_SOURCE = '1. d4 d5 2. c4 e6 3. Nc3 c5 4. cxd5 exd5 5. Nf3 Nc6 6. g3 Nf6 7. Bg2 Be7 8. O-O O-O 9. Bg5 c4';

    public const CLASSICAL_MAIN_LINE_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp3ppp/4p3/2pp4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'c4d5', 'san' => 'cxd5', 'fromKey' => 'rnbqkbnr/pp3ppp/4p3/2pp4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp3ppp/4p3/2pP4/3P4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'e6d5', 'san' => 'exd5', 'fromKey' => 'rnbqkbnr/pp3ppp/4p3/2pP4/3P4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp3ppp/8/2pp4/3P4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pp3ppp/8/2pp4/3P4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp3ppp/8/2pp4/3P4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pp3ppp/8/2pp4/3P4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pp3ppp/2n5/2pp4/3P4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -'],
        ['uci' => 'g2g3', 'san' => 'g3', 'fromKey' => 'r1bqkbnr/pp3ppp/2n5/2pp4/3P4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pp3ppp/2n5/2pp4/3P4/2N2NP1/PP2PP1P/R1BQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/pp3ppp/2n5/2pp4/3P4/2N2NP1/PP2PP1P/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2n2n2/2pp4/3P4/2N2NP1/PP2PP1P/R1BQKB1R w KQkq -'],
        ['uci' => 'f1g2', 'san' => 'Bg2', 'fromKey' => 'r1bqkb1r/pp3ppp/2n2n2/2pp4/3P4/2N2NP1/PP2PP1P/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQK2R b KQkq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'r1bqkb1r/pp3ppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQK2R b KQkq -', 'toKey' => 'r1bqk2r/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQK2R w KQkq -', 'toKey' => 'r1bqk2r/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQ1RK1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQ1RK1 b kq -', 'toKey' => 'r1bq1rk1/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQ1RK1 w - -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'r1bq1rk1/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/pp2bppp/2n2n2/2pp2B1/3P4/2N2NP1/PP2PPBP/R2Q1RK1 b - -'],
        ['uci' => 'c5d4', 'san' => 'cxd4', 'fromKey' => 'r1bq1rk1/pp2bppp/2n2n2/2pp2B1/3P4/2N2NP1/PP2PPBP/R2Q1RK1 b - -', 'toKey' => 'r1bq1rk1/pp2bppp/2n2n2/3p2B1/3p4/2N2NP1/PP2PPBP/R2Q1RK1 w - -'],
        ['uci' => 'f3d4', 'san' => 'Nxd4', 'fromKey' => 'r1bq1rk1/pp2bppp/2n2n2/3p2B1/3p4/2N2NP1/PP2PPBP/R2Q1RK1 w - -', 'toKey' => 'r1bq1rk1/pp2bppp/2n2n2/3p2B1/3N4/2N3P1/PP2PPBP/R2Q1RK1 b - -'],
        ['uci' => 'h7h6', 'san' => 'h6', 'fromKey' => 'r1bq1rk1/pp2bppp/2n2n2/3p2B1/3N4/2N3P1/PP2PPBP/R2Q1RK1 b - -', 'toKey' => 'r1bq1rk1/pp2bpp1/2n2n1p/3p2B1/3N4/2N3P1/PP2PPBP/R2Q1RK1 w - -'],
        ['uci' => 'g5e3', 'san' => 'Be3', 'fromKey' => 'r1bq1rk1/pp2bpp1/2n2n1p/3p2B1/3N4/2N3P1/PP2PPBP/R2Q1RK1 w - -', 'toKey' => 'r1bq1rk1/pp2bpp1/2n2n1p/3p4/3N4/2N1B1P1/PP2PPBP/R2Q1RK1 b - -'],
        ['uci' => 'f8e8', 'san' => 'Re8', 'fromKey' => 'r1bq1rk1/pp2bpp1/2n2n1p/3p4/3N4/2N1B1P1/PP2PPBP/R2Q1RK1 b - -', 'toKey' => 'r1bqr1k1/pp2bpp1/2n2n1p/3p4/3N4/2N1B1P1/PP2PPBP/R2Q1RK1 w - -'],
    ];

    public const CLASSICAL_ADVANCE_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp3ppp/4p3/2pp4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'c4d5', 'san' => 'cxd5', 'fromKey' => 'rnbqkbnr/pp3ppp/4p3/2pp4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp3ppp/4p3/2pP4/3P4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'e6d5', 'san' => 'exd5', 'fromKey' => 'rnbqkbnr/pp3ppp/4p3/2pP4/3P4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp3ppp/8/2pp4/3P4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pp3ppp/8/2pp4/3P4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp3ppp/8/2pp4/3P4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pp3ppp/8/2pp4/3P4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pp3ppp/2n5/2pp4/3P4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -'],
        ['uci' => 'g2g3', 'san' => 'g3', 'fromKey' => 'r1bqkbnr/pp3ppp/2n5/2pp4/3P4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pp3ppp/2n5/2pp4/3P4/2N2NP1/PP2PP1P/R1BQKB1R b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'r1bqkbnr/pp3ppp/2n5/2pp4/3P4/2N2NP1/PP2PP1P/R1BQKB1R b KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2n2n2/2pp4/3P4/2N2NP1/PP2PP1P/R1BQKB1R w KQkq -'],
        ['uci' => 'f1g2', 'san' => 'Bg2', 'fromKey' => 'r1bqkb1r/pp3ppp/2n2n2/2pp4/3P4/2N2NP1/PP2PP1P/R1BQKB1R w KQkq -', 'toKey' => 'r1bqkb1r/pp3ppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQK2R b KQkq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'r1bqkb1r/pp3ppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQK2R b KQkq -', 'toKey' => 'r1bqk2r/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQK2R w KQkq -', 'toKey' => 'r1bqk2r/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQ1RK1 b kq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQ1RK1 b kq -', 'toKey' => 'r1bq1rk1/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQ1RK1 w - -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'r1bq1rk1/pp2bppp/2n2n2/2pp4/3P4/2N2NP1/PP2PPBP/R1BQ1RK1 w - -', 'toKey' => 'r1bq1rk1/pp2bppp/2n2n2/2pp2B1/3P4/2N2NP1/PP2PPBP/R2Q1RK1 b - -'],
        ['uci' => 'c5c4', 'san' => 'c4', 'fromKey' => 'r1bq1rk1/pp2bppp/2n2n2/2pp2B1/3P4/2N2NP1/PP2PPBP/R2Q1RK1 b - -', 'toKey' => 'r1bq1rk1/pp2bppp/2n2n2/3p2B1/2pP4/2N2NP1/PP2PPBP/R2Q1RK1 w - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Tarrasch Defense course: Classical Variation, Main Line; Classical Variation, Advance Variation';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. d4 and 2. c4 with ...d5, ...e6 and ...c5. Instead of only holding d5, you strike at d4 at once and accept that your d-pawn may become isolated; in return your pieces develop freely on open lines. Both lines are complete named Classical Variation records, in which White fianchettoes the bishop on g2 to aim at d5. The Main Line teaches you to exchange on d4 and play with an isolated pawn on d5 and active pieces. The Advance Variation teaches you to push ...c4 instead, gaining queenside space while White\'s pawn stays on d4. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['classical-main-line', 'Classical Main Line: the isolated d-pawn', 'Eleven Black moves. Strike at d4 with ...c5, recapture on d5 with the e-pawn, develop and castle, then exchange on d4 and play with an isolated d-pawn and active pieces. Source: Classical Variation, Main Line.', self::CLASSICAL_MAIN_LINE_SOURCE, self::CLASSICAL_MAIN_LINE_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a diagonal for the bishop on c1.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Occupy the centre with your d-pawn. It controls e4 and c4, so White cannot easily advance the e-pawn two squares.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White offers the c-pawn to pull your d-pawn away from the centre. This is the Queen\'s Gambit.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Decline the pawn and support d5 with the e-pawn. This opens the diagonal for your bishop on f8, but for now it shuts in your bishop on c8.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops a knight and adds another attacker to d5.'],
                ['c5', 'Move your pawn from c7 to c5.', 'This is the Tarrasch Defense. Strike at d4 with the c-pawn and fight for the centre actively. You accept that your d-pawn may become isolated in return for free development for your pieces.'],
                ['cxd5', 'Move your pawn from c4 to d5, capturing the black pawn.', 'White exchanges on d5 at once. Your recapture decides the pawn structure for the rest of the opening.'],
                ['exd5', 'Move your pawn from e6 to d5, capturing the white pawn.', 'Recapture with the e-pawn. This opens the diagonal for your bishop on c8, and your queen on d8 defends the pawn on d5. Your pawn on c5 still attacks d4.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the second knight, defends d4 and controls e5.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and add a second attacker to d4, together with the pawn on c5.'],
                ['g3', 'Move your pawn from g2 to g3.', 'White prepares to fianchetto the bishop on g2. From there it will aim along the long diagonal at d5 once the knight on f3 moves.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the kingside knight. It defends d5, controls e4 and clears g8 on the way to castling.'],
                ['Bg2', 'Move your bishop from f1 to g2.', 'White completes the fianchetto. The bishop stands on the long diagonal behind the knight on f3, pointing towards d5.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Develop the bishop to e7, where it protects the knight on f6, and prepare to castle.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles, putting the king behind the fianchettoed bishop.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle and bring your king to safety. The rook comes to f8, and the bishop on c8 is now your only undeveloped minor piece.'],
                ['Bg5', 'Move your bishop from c1 to g5.', 'White develops the bishop and attacks your knight on f6, the piece that defends d5 together with your queen.'],
                ['cxd4', 'Move your pawn from c5 to d4, capturing the white pawn.', 'Here the Main Line takes its own path. Exchange on d4. Your d-pawn becomes isolated, with no black pawn left on the c- or e-file to support it, but the c-file is now open and your pieces get free squares.'],
                ['Nxd4', 'Move your knight from f3 to d4, capturing the black pawn.', 'White recaptures with the knight, which now stands on d4, directly in front of your isolated pawn. With the knight gone from f3, the bishop on g2 also bears down on d5.'],
                ['h6', 'Move your pawn from h7 to h6.', 'Ask the bishop on g5 to decide. Nothing defends it there, so White must retreat it or exchange it for your knight on f6.'],
                ['Be3', 'Move your bishop from g5 to e3.', 'White retreats the bishop to e3, where it defends the knight on d4.'],
                ['Re8', 'Move your rook from f8 to e8.', 'Put the rook on the half-open e-file, where it protects the bishop on e7 and frees f8 for that bishop. The named Main Line ends here: your next task is to develop your bishop on c8 and keep enough pieces defending the isolated pawn on d5.'],
            ]],
            ['classical-advance', 'Classical Advance: ...c4 and queenside space', 'Nine Black moves. Develop and castle as in the Main Line, then answer Bg5 with ...c4, gaining queenside space and keeping a pawn beside d5. Source: Classical Variation, Advance Variation.', self::CLASSICAL_ADVANCE_SOURCE, self::CLASSICAL_ADVANCE_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a diagonal for the bishop on c1.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Occupy the centre with your d-pawn. It controls e4 and c4, so White cannot easily advance the e-pawn two squares.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White offers the c-pawn to pull your d-pawn away from the centre. This is the Queen\'s Gambit.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Decline the pawn and support d5 with the e-pawn. This opens the diagonal for your bishop on f8, but for now it shuts in your bishop on c8.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops a knight and adds another attacker to d5.'],
                ['c5', 'Move your pawn from c7 to c5.', 'This is the Tarrasch Defense. Strike at d4 with the c-pawn and fight for the centre actively. You accept that your d-pawn may become isolated in return for free development for your pieces.'],
                ['cxd5', 'Move your pawn from c4 to d5, capturing the black pawn.', 'White exchanges on d5 at once. Your recapture decides the pawn structure for the rest of the opening.'],
                ['exd5', 'Move your pawn from e6 to d5, capturing the white pawn.', 'Recapture with the e-pawn. This opens the diagonal for your bishop on c8, and your queen on d8 defends the pawn on d5. Your pawn on c5 still attacks d4.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the second knight, defends d4 and controls e5.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and add a second attacker to d4, together with the pawn on c5.'],
                ['g3', 'Move your pawn from g2 to g3.', 'White prepares to fianchetto the bishop on g2. From there it will aim along the long diagonal at d5 once the knight on f3 moves.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the kingside knight. It defends d5, controls e4 and clears g8 on the way to castling.'],
                ['Bg2', 'Move your bishop from f1 to g2.', 'White completes the fianchetto. The bishop stands on the long diagonal behind the knight on f3, pointing towards d5.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Develop the bishop to e7, where it protects the knight on f6, and prepare to castle.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles, putting the king behind the fianchettoed bishop.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle and bring your king to safety. The rook comes to f8, and the bishop on c8 is now your only undeveloped minor piece.'],
                ['Bg5', 'Move your bishop from c1 to g5.', 'White develops the bishop and attacks your knight on f6, the piece that defends d5 together with your queen.'],
                ['c4', 'Move your pawn from c5 to c4.', 'Here the Advance Variation takes its own path. Push the pawn to c4 instead of exchanging on d4. It gains queenside space, controls b3 and d3, and keeps a pawn beside d5, so your d-pawn is not isolated; in return the pressure on d4 eases. The named Advance Variation ends here: your next task is to develop your bishop on c8, for example to e6 where it supports d5, and prepare to advance your queenside pawns with ...b5.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='tarrasch'")) {
            throw new \RuntimeException('The Tarrasch Defense course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Tarrasch Defense teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::CLASSICAL_MAIN_LINE_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Tarrasch Defense start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('tarrasch',NULL,18)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('tarrasch',1,?,?,?,UTC_TIMESTAMP(),'b')", ['Tarrasch Defense', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('tarrasch',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('tarrasch',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Tarrasch Defense edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='tarrasch'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Tarrasch Defense sessions and progress.');
    }
}
