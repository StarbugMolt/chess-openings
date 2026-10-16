<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Benoni Defense course: complete named pinned paths with original teaching notes. */
final class Version20261016020700 extends AbstractMigration
{
    public const CLASSICAL_TAL_LINE_SOURCE = '1. d4 Nf6 2. c4 c5 3. d5 e6 4. Nc3 exd5 5. cxd5 d6 6. e4 g6 7. Nf3 Bg7 8. Be2 O-O 9. O-O Re8 10. Nd2 Nbd7';
    public const FOUR_PAWNS_MAIN_LINE_SOURCE = '1. d4 Nf6 2. c4 c5 3. d5 e6 4. Nc3 exd5 5. cxd5 d6 6. e4 g6 7. f4 Bg7 8. Nf3 O-O 9. Be2 Nbd7 10. O-O Re8';

    public const CLASSICAL_TAL_LINE_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp1ppppp/5n2/2p5/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd4d5', 'san' => 'd5', 'fromKey' => 'rnbqkb1r/pp1ppppp/5n2/2p5/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp1ppppp/5n2/2pP4/2P5/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkb1r/pp1ppppp/5n2/2pP4/2P5/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp1p1ppp/4pn2/2pP4/2P5/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pp1p1ppp/4pn2/2pP4/2P5/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp1p1ppp/4pn2/2pP4/2P5/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'e6d5', 'san' => 'exd5', 'fromKey' => 'rnbqkb1r/pp1p1ppp/4pn2/2pP4/2P5/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp1p1ppp/5n2/2pp4/2P5/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'c4d5', 'san' => 'cxd5', 'fromKey' => 'rnbqkb1r/pp1p1ppp/5n2/2pp4/2P5/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp1p1ppp/5n2/2pP4/8/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'rnbqkb1r/pp1p1ppp/5n2/2pP4/8/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp3ppp/3p1n2/2pP4/8/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkb1r/pp3ppp/3p1n2/2pP4/8/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp3ppp/3p1n2/2pP4/4P3/2N5/PP3PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'g7g6', 'san' => 'g6', 'fromKey' => 'rnbqkb1r/pp3ppp/3p1n2/2pP4/4P3/2N5/PP3PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp3p1p/3p1np1/2pP4/4P3/2N5/PP3PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkb1r/pp3p1p/3p1np1/2pP4/4P3/2N5/PP3PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp3p1p/3p1np1/2pP4/4P3/2N2N2/PP3PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'f8g7', 'san' => 'Bg7', 'fromKey' => 'rnbqkb1r/pp3p1p/3p1np1/2pP4/4P3/2N2N2/PP3PPP/R1BQKB1R b KQkq -', 'toKey' => 'rnbqk2r/pp3pbp/3p1np1/2pP4/4P3/2N2N2/PP3PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'f1e2', 'san' => 'Be2', 'fromKey' => 'rnbqk2r/pp3pbp/3p1np1/2pP4/4P3/2N2N2/PP3PPP/R1BQKB1R w KQkq -', 'toKey' => 'rnbqk2r/pp3pbp/3p1np1/2pP4/4P3/2N2N2/PP2BPPP/R1BQK2R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/pp3pbp/3p1np1/2pP4/4P3/2N2N2/PP2BPPP/R1BQK2R b KQkq -', 'toKey' => 'rnbq1rk1/pp3pbp/3p1np1/2pP4/4P3/2N2N2/PP2BPPP/R1BQK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'rnbq1rk1/pp3pbp/3p1np1/2pP4/4P3/2N2N2/PP2BPPP/R1BQK2R w KQ -', 'toKey' => 'rnbq1rk1/pp3pbp/3p1np1/2pP4/4P3/2N2N2/PP2BPPP/R1BQ1RK1 b - -'],
        ['uci' => 'f8e8', 'san' => 'Re8', 'fromKey' => 'rnbq1rk1/pp3pbp/3p1np1/2pP4/4P3/2N2N2/PP2BPPP/R1BQ1RK1 b - -', 'toKey' => 'rnbqr1k1/pp3pbp/3p1np1/2pP4/4P3/2N2N2/PP2BPPP/R1BQ1RK1 w - -'],
        ['uci' => 'f3d2', 'san' => 'Nd2', 'fromKey' => 'rnbqr1k1/pp3pbp/3p1np1/2pP4/4P3/2N2N2/PP2BPPP/R1BQ1RK1 w - -', 'toKey' => 'rnbqr1k1/pp3pbp/3p1np1/2pP4/4P3/2N5/PP1NBPPP/R1BQ1RK1 b - -'],
        ['uci' => 'b8d7', 'san' => 'Nbd7', 'fromKey' => 'rnbqr1k1/pp3pbp/3p1np1/2pP4/4P3/2N5/PP1NBPPP/R1BQ1RK1 b - -', 'toKey' => 'r1bqr1k1/pp1n1pbp/3p1np1/2pP4/4P3/2N5/PP1NBPPP/R1BQ1RK1 w - -'],
    ];

    public const FOUR_PAWNS_MAIN_LINE_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp1ppppp/5n2/2p5/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd4d5', 'san' => 'd5', 'fromKey' => 'rnbqkb1r/pp1ppppp/5n2/2p5/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp1ppppp/5n2/2pP4/2P5/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkb1r/pp1ppppp/5n2/2pP4/2P5/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp1p1ppp/4pn2/2pP4/2P5/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pp1p1ppp/4pn2/2pP4/2P5/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp1p1ppp/4pn2/2pP4/2P5/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'e6d5', 'san' => 'exd5', 'fromKey' => 'rnbqkb1r/pp1p1ppp/4pn2/2pP4/2P5/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp1p1ppp/5n2/2pp4/2P5/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'c4d5', 'san' => 'cxd5', 'fromKey' => 'rnbqkb1r/pp1p1ppp/5n2/2pp4/2P5/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp1p1ppp/5n2/2pP4/8/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'rnbqkb1r/pp1p1ppp/5n2/2pP4/8/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp3ppp/3p1n2/2pP4/8/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkb1r/pp3ppp/3p1n2/2pP4/8/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp3ppp/3p1n2/2pP4/4P3/2N5/PP3PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'g7g6', 'san' => 'g6', 'fromKey' => 'rnbqkb1r/pp3ppp/3p1n2/2pP4/4P3/2N5/PP3PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp3p1p/3p1np1/2pP4/4P3/2N5/PP3PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'f2f4', 'san' => 'f4', 'fromKey' => 'rnbqkb1r/pp3p1p/3p1np1/2pP4/4P3/2N5/PP3PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp3p1p/3p1np1/2pP4/4PP2/2N5/PP4PP/R1BQKBNR b KQkq -'],
        ['uci' => 'f8g7', 'san' => 'Bg7', 'fromKey' => 'rnbqkb1r/pp3p1p/3p1np1/2pP4/4PP2/2N5/PP4PP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk2r/pp3pbp/3p1np1/2pP4/4PP2/2N5/PP4PP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqk2r/pp3pbp/3p1np1/2pP4/4PP2/2N5/PP4PP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk2r/pp3pbp/3p1np1/2pP4/4PP2/2N2N2/PP4PP/R1BQKB1R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/pp3pbp/3p1np1/2pP4/4PP2/2N2N2/PP4PP/R1BQKB1R b KQkq -', 'toKey' => 'rnbq1rk1/pp3pbp/3p1np1/2pP4/4PP2/2N2N2/PP4PP/R1BQKB1R w KQ -'],
        ['uci' => 'f1e2', 'san' => 'Be2', 'fromKey' => 'rnbq1rk1/pp3pbp/3p1np1/2pP4/4PP2/2N2N2/PP4PP/R1BQKB1R w KQ -', 'toKey' => 'rnbq1rk1/pp3pbp/3p1np1/2pP4/4PP2/2N2N2/PP2B1PP/R1BQK2R b KQ -'],
        ['uci' => 'b8d7', 'san' => 'Nbd7', 'fromKey' => 'rnbq1rk1/pp3pbp/3p1np1/2pP4/4PP2/2N2N2/PP2B1PP/R1BQK2R b KQ -', 'toKey' => 'r1bq1rk1/pp1n1pbp/3p1np1/2pP4/4PP2/2N2N2/PP2B1PP/R1BQK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bq1rk1/pp1n1pbp/3p1np1/2pP4/4PP2/2N2N2/PP2B1PP/R1BQK2R w KQ -', 'toKey' => 'r1bq1rk1/pp1n1pbp/3p1np1/2pP4/4PP2/2N2N2/PP2B1PP/R1BQ1RK1 b - -'],
        ['uci' => 'f8e8', 'san' => 'Re8', 'fromKey' => 'r1bq1rk1/pp1n1pbp/3p1np1/2pP4/4PP2/2N2N2/PP2B1PP/R1BQ1RK1 b - -', 'toKey' => 'r1bqr1k1/pp1n1pbp/3p1np1/2pP4/4PP2/2N2N2/PP2B1PP/R1BQ1RK1 w - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Benoni Defense course: Classical Variation, Czerniak Defense, Tal Line; Four Pawns Attack, Main Line';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. d4 and 2. c4. With ...c5 you challenge d4 at once, and when White pushes past with d5 you exchange on d5 with ...e6 and ...exd5. The result is the Benoni structure: White has more space in the centre, while you get three pawns against two on the queenside, a half-open e-file and a bishop on the long diagonal. In the Classical Tal Line, White develops with Nf3, Be2 and castling, and you press on e4 with ...Re8 before developing with ...Nbd7. In the Four Pawns Attack, White adds f4 to prepare e4-e5, and you castle quickly and guard e5 with ...Nbd7 and ...Re8. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['classical-tal-line', 'Classical Tal Line: ...Re8 against e4 and Nd2', 'Ten Black moves. Exchange on d5 to reach the Benoni structure, fianchetto and castle, press on e4 with ...Re8 and develop your last knight to d7. Source: Classical Variation, Czerniak Defense, Tal Line.', self::CLASSICAL_TAL_LINE_SOURCE, self::CLASSICAL_TAL_LINE_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a diagonal for the bishop on c1.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight to f6, where it controls e4 and d5. White cannot play e2-e4 yet without losing the pawn.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White gains space on the queenside and adds control of d5.'],
                ['c5', 'Move your pawn from c7 to c5.', 'This is the Benoni. Strike at d4 with your c-pawn straight away. White has to choose between pushing past it with d5, exchanging on c5 or defending d4.'],
                ['d5', 'Move your pawn from d4 to d5.', 'White pushes past your c-pawn and gains space. The pawn on d5 takes c6 and e6 away from your pieces.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Challenge the advanced pawn at once. Your e6 pawn attacks d5 together with the knight on f6, and it opens the diagonal for the bishop on f8.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the knight and adds a defender to d5. The pawn is now protected by the c4 pawn, the knight and the queen.'],
                ['exd5', 'Move your pawn from e6 to d5, capturing the white pawn.', 'Exchange on d5. Without your e-pawn, the e-file is half-open for your rook and queen.'],
                ['cxd5', 'Move your pawn from c4 to d5, capturing the black pawn.', 'White recaptures with the c-pawn and keeps a pawn on d5. This is the Benoni pawn structure: you have three pawns against two on the queenside, while White has an extra pawn in the centre.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Stop the d5 pawn from advancing further and control e5. The pawn also protects c5 and opens the diagonal for your bishop on c8.'],
                ['e4', 'Move your pawn from e2 to e4.', 'White builds the centre. The e4 pawn supports d5 and prepares e4-e5, which would attack your knight on f6 and open the centre. It also opens the diagonal of the bishop on f1.'],
                ['g6', 'Move your pawn from g7 to g6.', 'Prepare to fianchetto your bishop on g7. There it will guard your king and work along the long diagonal towards White\'s queenside.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Here the Classical line chooses its own path: White develops the kingside knight. It controls e5, supporting the e4-e5 advance, and prepares to develop the bishop and castle.'],
                ['Bg7', 'Move your bishop from f8 to g7.', 'Complete the fianchetto. Once your knight on f6 moves, the bishop will look down the long diagonal through e5 and d4 towards the knight on c3. Your king can now castle.'],
                ['Be2', 'Move your bishop from f1 to e2.', 'White develops the bishop and prepares to castle. From e2 it also covers b5, a square you will want for your queenside pawns.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle. Your king is safe behind the fianchetto, and the rook comes to f8, from where it can move to the half-open e-file.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles too. Both kings are safe; White looks for the e4-e5 break, while you prepare play on the e-file and the queenside.'],
                ['Re8', 'Move your rook from f8 to e8.', 'Put the rook on the half-open e-file. Your rook and the knight on f6 now both attack e4, which only the knight on c3 defends, so White has to look after that pawn.'],
                ['Nd2', 'Move your knight from f3 to d2.', 'White defends e4 a second time. The knight can continue to c4, where it would attack your d6 pawn, and leaving f3 frees the f-pawn to advance.'],
                ['Nbd7', 'Move your knight from b8 to d7.', 'Develop your last knight. From d7 it guards c5 and e5, but it blocks your bishop on c8 and stands between your queen and the d6 pawn, so watch for Nc4. The named Tal Line ends here: your next task is to fight for c4, for example with ...Ne5, and then prepare ...a6 and ...b5 with your queenside majority.'],
            ]],
            ['four-pawns-main-line', 'Four Pawns Attack: castle and guard e5', 'Ten Black moves. Exchange on d5, fianchetto and castle quickly against White\'s pawns on d5, e4 and f4, then guard e5 with ...Nbd7 and ...Re8. Source: Four Pawns Attack, Main Line.', self::FOUR_PAWNS_MAIN_LINE_SOURCE, self::FOUR_PAWNS_MAIN_LINE_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a diagonal for the bishop on c1.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight to f6, where it controls e4 and d5. White cannot play e2-e4 yet without losing the pawn.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White gains space on the queenside and adds control of d5.'],
                ['c5', 'Move your pawn from c7 to c5.', 'This is the Benoni. Strike at d4 with your c-pawn straight away. White has to choose between pushing past it with d5, exchanging on c5 or defending d4.'],
                ['d5', 'Move your pawn from d4 to d5.', 'White pushes past your c-pawn and gains space. The pawn on d5 takes c6 and e6 away from your pieces.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Challenge the advanced pawn at once. Your e6 pawn attacks d5 together with the knight on f6, and it opens the diagonal for the bishop on f8.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the knight and adds a defender to d5. The pawn is now protected by the c4 pawn, the knight and the queen.'],
                ['exd5', 'Move your pawn from e6 to d5, capturing the white pawn.', 'Exchange on d5. Without your e-pawn, the e-file is half-open for your rook and queen.'],
                ['cxd5', 'Move your pawn from c4 to d5, capturing the black pawn.', 'White recaptures with the c-pawn and keeps a pawn on d5. This is the Benoni pawn structure: you have three pawns against two on the queenside, while White has an extra pawn in the centre.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Stop the d5 pawn from advancing further and control e5. The pawn also protects c5 and opens the diagonal for your bishop on c8.'],
                ['e4', 'Move your pawn from e2 to e4.', 'White builds the centre. The e4 pawn supports d5 and prepares e4-e5, which would attack your knight on f6 and open the centre. It also opens the diagonal of the bishop on f1.'],
                ['g6', 'Move your pawn from g7 to g6.', 'Prepare to fianchetto your bishop on g7. There it will guard your king and work along the long diagonal towards White\'s queenside.'],
                ['f4', 'Move your pawn from f2 to f4.', 'Here the Four Pawns Attack chooses its own path: White adds the f-pawn to the centre, so pawns stand on d5, e4 and f4. The f4 pawn supports e4-e5, which would attack your knight on f6 and your d6 pawn.'],
                ['Bg7', 'Move your bishop from f8 to g7.', 'Complete the fianchetto. Once your knight on f6 moves, the bishop will look down the long diagonal through e5 and d4 towards the knight on c3. Your king can now castle.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the kingside knight. Together with the f4 pawn it controls e5, so the e4-e5 advance now has two supporters.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle now and take your king out of the centre before White can open it with e4-e5. Your rook comes to f8.'],
                ['Be2', 'Move your bishop from f1 to e2.', 'White develops the bishop and prepares to castle. From e2 it also covers b5, a square you will want for your queenside pawns.'],
                ['Nbd7', 'Move your knight from b8 to d7.', 'Develop your last knight. From d7 it adds a second guard to e5, alongside the d6 pawn, and it also protects c5. It blocks your bishop on c8 for now.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles. All of White\'s minor pieces are developed except the bishop on c1.'],
                ['Re8', 'Move your rook from f8 to e8.', 'Put the rook on the half-open e-file. It adds a third guard to e5 and, with the knight on f6, attacks e4, which only the knight on c3 defends. The named Main Line ends here: your next task is to prepare ...b5 with ...a6 and ...Rb8, using your queenside majority, while keeping pressure on e4.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='benoni'")) {
            throw new \RuntimeException('The Benoni Defense course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Benoni Defense teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::CLASSICAL_TAL_LINE_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Benoni Defense start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('benoni',NULL,17)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('benoni',1,?,?,?,UTC_TIMESTAMP(),'b')", ['Benoni Defense', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('benoni',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('benoni',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Benoni Defense edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='benoni'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Benoni Defense sessions and progress.');
    }
}
