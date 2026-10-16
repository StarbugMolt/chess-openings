<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Alekhine Defense course: complete named pinned paths with original teaching notes. */
final class Version20261016020400 extends AbstractMigration
{
    public const FOUR_PAWNS_TARTAKOWER_SOURCE = '1. e4 Nf6 2. e5 Nd5 3. d4 d6 4. c4 Nb6 5. f4 dxe5 6. fxe5 Nc6 7. Be3 Bf5 8. Nc3 e6 9. Nf3 Qd7 10. Be2 O-O-O 11. O-O Be7';
    public const EXCHANGE_KARPOV_SOURCE = '1. e4 Nf6 2. e5 Nd5 3. d4 d6 4. c4 Nb6 5. exd6 cxd6 6. Nc3 g6 7. h3 Bg7 8. Nf3 O-O 9. Be2 Nc6 10. O-O Bf5 11. Bf4';

    public const FOUR_PAWNS_TARTAKOWER_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'e4e5', 'san' => 'e5', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/4P3/8/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'f6d5', 'san' => 'Nd5', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/4P3/8/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/8/3nP3/8/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkb1r/pppppppp/8/3nP3/8/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/8/3nP3/3P4/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'rnbqkb1r/pppppppp/8/3nP3/3P4/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/3p4/3nP3/3P4/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/ppp1pppp/3p4/3nP3/3P4/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/3p4/3nP3/2PP4/8/PP3PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd5b6', 'san' => 'Nb6', 'fromKey' => 'rnbqkb1r/ppp1pppp/3p4/3nP3/2PP4/8/PP3PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/1n1p4/4P3/2PP4/8/PP3PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'f2f4', 'san' => 'f4', 'fromKey' => 'rnbqkb1r/ppp1pppp/1n1p4/4P3/2PP4/8/PP3PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/1n1p4/4P3/2PP1P2/8/PP4PP/RNBQKBNR b KQkq -'],
        ['uci' => 'd6e5', 'san' => 'dxe5', 'fromKey' => 'rnbqkb1r/ppp1pppp/1n1p4/4P3/2PP1P2/8/PP4PP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/1n6/4p3/2PP1P2/8/PP4PP/RNBQKBNR w KQkq -'],
        ['uci' => 'f4e5', 'san' => 'fxe5', 'fromKey' => 'rnbqkb1r/ppp1pppp/1n6/4p3/2PP1P2/8/PP4PP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/1n6/4P3/2PP4/8/PP4PP/RNBQKBNR b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkb1r/ppp1pppp/1n6/4P3/2PP4/8/PP4PP/RNBQKBNR b KQkq -', 'toKey' => 'r1bqkb1r/ppp1pppp/1nn5/4P3/2PP4/8/PP4PP/RNBQKBNR w KQkq -'],
        ['uci' => 'c1e3', 'san' => 'Be3', 'fromKey' => 'r1bqkb1r/ppp1pppp/1nn5/4P3/2PP4/8/PP4PP/RNBQKBNR w KQkq -', 'toKey' => 'r1bqkb1r/ppp1pppp/1nn5/4P3/2PP4/4B3/PP4PP/RN1QKBNR b KQkq -'],
        ['uci' => 'c8f5', 'san' => 'Bf5', 'fromKey' => 'r1bqkb1r/ppp1pppp/1nn5/4P3/2PP4/4B3/PP4PP/RN1QKBNR b KQkq -', 'toKey' => 'r2qkb1r/ppp1pppp/1nn5/4Pb2/2PP4/4B3/PP4PP/RN1QKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r2qkb1r/ppp1pppp/1nn5/4Pb2/2PP4/4B3/PP4PP/RN1QKBNR w KQkq -', 'toKey' => 'r2qkb1r/ppp1pppp/1nn5/4Pb2/2PP4/2N1B3/PP4PP/R2QKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'r2qkb1r/ppp1pppp/1nn5/4Pb2/2PP4/2N1B3/PP4PP/R2QKBNR b KQkq -', 'toKey' => 'r2qkb1r/ppp2ppp/1nn1p3/4Pb2/2PP4/2N1B3/PP4PP/R2QKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'r2qkb1r/ppp2ppp/1nn1p3/4Pb2/2PP4/2N1B3/PP4PP/R2QKBNR w KQkq -', 'toKey' => 'r2qkb1r/ppp2ppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP4PP/R2QKB1R b KQkq -'],
        ['uci' => 'd8d7', 'san' => 'Qd7', 'fromKey' => 'r2qkb1r/ppp2ppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP4PP/R2QKB1R b KQkq -', 'toKey' => 'r3kb1r/pppq1ppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP4PP/R2QKB1R w KQkq -'],
        ['uci' => 'f1e2', 'san' => 'Be2', 'fromKey' => 'r3kb1r/pppq1ppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP4PP/R2QKB1R w KQkq -', 'toKey' => 'r3kb1r/pppq1ppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP2B1PP/R2QK2R b KQkq -'],
        ['uci' => 'e8c8', 'san' => 'O-O-O', 'fromKey' => 'r3kb1r/pppq1ppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP2B1PP/R2QK2R b KQkq -', 'toKey' => '2kr1b1r/pppq1ppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP2B1PP/R2QK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => '2kr1b1r/pppq1ppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP2B1PP/R2QK2R w KQ -', 'toKey' => '2kr1b1r/pppq1ppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP2B1PP/R2Q1RK1 b - -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => '2kr1b1r/pppq1ppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP2B1PP/R2Q1RK1 b - -', 'toKey' => '2kr3r/pppqbppp/1nn1p3/4Pb2/2PP4/2N1BN2/PP2B1PP/R2Q1RK1 w - -'],
    ];

    public const EXCHANGE_KARPOV_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'e4e5', 'san' => 'e5', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/4P3/8/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'f6d5', 'san' => 'Nd5', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/4P3/8/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/8/3nP3/8/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkb1r/pppppppp/8/3nP3/8/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/8/3nP3/3P4/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'rnbqkb1r/pppppppp/8/3nP3/3P4/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/3p4/3nP3/3P4/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/ppp1pppp/3p4/3nP3/3P4/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/3p4/3nP3/2PP4/8/PP3PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd5b6', 'san' => 'Nb6', 'fromKey' => 'rnbqkb1r/ppp1pppp/3p4/3nP3/2PP4/8/PP3PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/1n1p4/4P3/2PP4/8/PP3PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'e5d6', 'san' => 'exd6', 'fromKey' => 'rnbqkb1r/ppp1pppp/1n1p4/4P3/2PP4/8/PP3PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp1pppp/1n1P4/8/2PP4/8/PP3PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'c7d6', 'san' => 'cxd6', 'fromKey' => 'rnbqkb1r/ppp1pppp/1n1P4/8/2PP4/8/PP3PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/1n1p4/8/2PP4/8/PP3PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pp2pppp/1n1p4/8/2PP4/8/PP3PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp2pppp/1n1p4/8/2PP4/2N5/PP3PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'g7g6', 'san' => 'g6', 'fromKey' => 'rnbqkb1r/pp2pppp/1n1p4/8/2PP4/2N5/PP3PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pp2pp1p/1n1p2p1/8/2PP4/2N5/PP3PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'h2h3', 'san' => 'h3', 'fromKey' => 'rnbqkb1r/pp2pp1p/1n1p2p1/8/2PP4/2N5/PP3PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pp2pp1p/1n1p2p1/8/2PP4/2N4P/PP3PP1/R1BQKBNR b KQkq -'],
        ['uci' => 'f8g7', 'san' => 'Bg7', 'fromKey' => 'rnbqkb1r/pp2pp1p/1n1p2p1/8/2PP4/2N4P/PP3PP1/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk2r/pp2ppbp/1n1p2p1/8/2PP4/2N4P/PP3PP1/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqk2r/pp2ppbp/1n1p2p1/8/2PP4/2N4P/PP3PP1/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk2r/pp2ppbp/1n1p2p1/8/2PP4/2N2N1P/PP3PP1/R1BQKB1R b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/pp2ppbp/1n1p2p1/8/2PP4/2N2N1P/PP3PP1/R1BQKB1R b KQkq -', 'toKey' => 'rnbq1rk1/pp2ppbp/1n1p2p1/8/2PP4/2N2N1P/PP3PP1/R1BQKB1R w KQ -'],
        ['uci' => 'f1e2', 'san' => 'Be2', 'fromKey' => 'rnbq1rk1/pp2ppbp/1n1p2p1/8/2PP4/2N2N1P/PP3PP1/R1BQKB1R w KQ -', 'toKey' => 'rnbq1rk1/pp2ppbp/1n1p2p1/8/2PP4/2N2N1P/PP2BPP1/R1BQK2R b KQ -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbq1rk1/pp2ppbp/1n1p2p1/8/2PP4/2N2N1P/PP2BPP1/R1BQK2R b KQ -', 'toKey' => 'r1bq1rk1/pp2ppbp/1nnp2p1/8/2PP4/2N2N1P/PP2BPP1/R1BQK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bq1rk1/pp2ppbp/1nnp2p1/8/2PP4/2N2N1P/PP2BPP1/R1BQK2R w KQ -', 'toKey' => 'r1bq1rk1/pp2ppbp/1nnp2p1/8/2PP4/2N2N1P/PP2BPP1/R1BQ1RK1 b - -'],
        ['uci' => 'c8f5', 'san' => 'Bf5', 'fromKey' => 'r1bq1rk1/pp2ppbp/1nnp2p1/8/2PP4/2N2N1P/PP2BPP1/R1BQ1RK1 b - -', 'toKey' => 'r2q1rk1/pp2ppbp/1nnp2p1/5b2/2PP4/2N2N1P/PP2BPP1/R1BQ1RK1 w - -'],
        ['uci' => 'c1f4', 'san' => 'Bf4', 'fromKey' => 'r2q1rk1/pp2ppbp/1nnp2p1/5b2/2PP4/2N2N1P/PP2BPP1/R1BQ1RK1 w - -', 'toKey' => 'r2q1rk1/pp2ppbp/1nnp2p1/5b2/2PP1B2/2N2N1P/PP2BPP1/R2Q1RK1 b - -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Alekhine Defense course: Four Pawns Attack, Tartakower Variation; Exchange Variation, Karpov Variation';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. e4 with ...Nf6. Your knight attacks the e4 pawn at once and invites White\'s pawns forward; once they have advanced, you attack them with your pieces and pawns, especially the pawn on d4. In both lines White builds a large centre with d4 and c4 and chases your knight to b6. The Four Pawns Attack, Tartakower Variation teaches you to exchange on e5, press d4 with your knight and queen, and castle queenside. The Exchange Variation, Karpov Variation teaches you to recapture with ...cxd6, fianchetto your bishop against d4 and castle kingside. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['four-pawns-tartakower', 'Four Pawns Attack: undermining the big centre', 'Eleven Black moves. Let White build pawns on c4, d4, e5 and f4, exchange on e5, attack d4 with your knight and queen, develop your bishop to f5 before ...e6 and castle queenside. Source: Four Pawns Attack, Tartakower Variation.', self::FOUR_PAWNS_TARTAKOWER_SOURCE, self::FOUR_PAWNS_TARTAKOWER_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens lines for the queen and the bishop on f1. The Alekhine Defense is a reply to this first move.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight and attack the pawn on e4 at once. This is the Alekhine Defense: you invite White\'s pawns forward and plan to attack them later with your pieces and pawns.'],
                ['e5', 'Move your pawn from e4 to e5.', 'White advances the pawn with gain of time. It attacks your knight on f6 and takes space.'],
                ['Nd5', 'Move your knight from f6 to d5.', 'Move the knight to the centre. On d5 no white pawn attacks it yet. White can chase it with c4, but each pawn advance leaves squares behind that you can use later.'],
                ['d4', 'Move your pawn from d2 to d4.', 'White builds a broad centre. The pawn on d4 protects e5 and opens a diagonal for the bishop on c1.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Challenge the pawn on e5 at once. Your d-pawn attacks it and opens a diagonal for your bishop on c8.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White chases your knight again and takes more space. White now has three pawns side by side on c4, d4 and e5.'],
                ['Nb6', 'Move your knight from d5 to b6.', 'Retreat the knight to b6, where your pawns on a7 and c7 protect it. From there it keeps an eye on c4 and d5.'],
                ['f4', 'Move your pawn from f2 to f4.', 'This is the Four Pawns Attack, and here this line takes its own path. White supports e5 with the f-pawn and claims as much space as possible. The price is that the advanced pawns need constant protection, and White has used five moves on pawns without developing a piece.'],
                ['dxe5', 'Move your pawn from d6 to e5, capturing the white pawn.', 'Exchange on e5. Your queen on d8 now looks down the half-open d-file at the pawn on d4, and White has to decide how to recapture.'],
                ['fxe5', 'Move your pawn from f4 to e5, capturing the black pawn.', 'White recaptures with the f-pawn and keeps a pawn on e5. Only the queen on d1 defends the pawn on d4, which your queen attacks.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and add a second attacker to d4, next to your queen. The knight also attacks e5, which the pawn on d4 protects.'],
                ['Be3', 'Move your bishop from c1 to e3.', 'White defends d4 with the bishop. The pawn now has two attackers and two defenders.'],
                ['Bf5', 'Move your bishop from c8 to f5.', 'Develop the light-squared bishop before ...e6 shuts it in. On f5 it controls e4 and d3 and attacks the knight on b1, which the rook on a1 and the queen on d1 protect.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the knight, which controls e4 and d5.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Support the bishop on f5 and add control of d5. The pawn move also opens the diagonal for your bishop on f8.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the last knight and adds protection to d4 and e5.'],
                ['Qd7', 'Move your queen from d8 to d7.', 'Move the queen off d8 to prepare queenside castling. The queen stays on the d-file, still aiming at d4, and your rook will come to d8 behind it.'],
                ['Be2', 'Move your bishop from f1 to e2.', 'White develops the bishop modestly and prepares to castle.'],
                ['O-O-O', 'Castle queenside: move your king from e8 to c8. The rook moves from a8 to d8 automatically.', 'Castle queenside. Your king goes to c8 and the rook arrives on d8 behind the queen, so both pieces line up against d4.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles kingside, so the kings are on opposite wings. The rook on f1 stands on the half-open f-file, behind the knight on f3.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Develop your last minor piece. The bishop controls f6, g5 and h4, and your rooks on d8 and h8 are now connected. The named Tartakower Variation ends here: your next task is to bring the rook on h8 into play and prepare ...f6 to challenge the pawn on e5.'],
            ]],
            ['exchange-karpov', 'Exchange Variation: ...cxd6 and the fianchetto', 'Ten Black moves. Recapture on d6 with the c-pawn, fianchetto your bishop against d4, castle, and bring your knight to c6 and your bishop to f5. Source: Exchange Variation, Karpov Variation.', self::EXCHANGE_KARPOV_SOURCE, self::EXCHANGE_KARPOV_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens lines for the queen and the bishop on f1. The Alekhine Defense is a reply to this first move.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight and attack the pawn on e4 at once. This is the Alekhine Defense: you invite White\'s pawns forward and plan to attack them later with your pieces and pawns.'],
                ['e5', 'Move your pawn from e4 to e5.', 'White advances the pawn with gain of time. It attacks your knight on f6 and takes space.'],
                ['Nd5', 'Move your knight from f6 to d5.', 'Move the knight to the centre. On d5 no white pawn attacks it yet. White can chase it with c4, but each pawn advance leaves squares behind that you can use later.'],
                ['d4', 'Move your pawn from d2 to d4.', 'White builds a broad centre. The pawn on d4 protects e5 and opens a diagonal for the bishop on c1.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Challenge the pawn on e5 at once. Your d-pawn attacks it and opens a diagonal for your bishop on c8.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White chases your knight again and takes more space. White now has three pawns side by side on c4, d4 and e5.'],
                ['Nb6', 'Move your knight from d5 to b6.', 'Retreat the knight to b6, where your pawns on a7 and c7 protect it. From there it keeps an eye on c4 and d5.'],
                ['exd6', 'Move your pawn from e5 to d6, capturing the black pawn.', 'This is the Exchange Variation, and here this line takes its own path. White exchanges on d6 instead of keeping the pawn on e5, so you have no advanced pawn left to attack. The pawn on d6 attacks your pawns on c7 and e7, so you recapture at once.'],
                ['cxd6', 'Move your pawn from c7 to d6, capturing the white pawn.', 'Recapture with the c-pawn. Your pawn on d6 controls e5 and c5, and with no black c-pawn left the c-file is half-open for your rook and queen.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the knight. It controls d5 and e4, so d5 is now guarded by both the knight and the pawn on c4.'],
                ['g6', 'Move your pawn from g7 to g6.', 'Prepare to fianchetto your bishop on g7. From there it will look along the long diagonal towards d4.'],
                ['h3', 'Move your pawn from h2 to h3.', 'White takes g4 away from your bishop on c8. From g4 it could attack the queen on d1, or later pin a knight on f3.'],
                ['Bg7', 'Move your bishop from f8 to g7.', 'Fianchetto the bishop. It now aims at the pawn on d4, which only the queen on d1 defends, and it prepares castling.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the knight and adds a second defender to d4.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle your king to safety behind the fianchetto. Your rook also comes to f8.'],
                ['Be2', 'Move your bishop from f1 to e2.', 'White develops the bishop modestly and prepares to castle.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and attack d4 a second time, together with the bishop on g7. The knight on f3 and the queen on d1 hold the pawn for now.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles as well. Both kings are now safe on the kingside.'],
                ['Bf5', 'Move your bishop from c8 to f5.', 'Develop your last minor piece. On f5 the bishop controls the diagonal through e4, d3 and c2, and it clears c8 for a rook on the half-open c-file.'],
                ['Bf4', 'Move your bishop from c1 to f4.', 'White develops the last minor piece. The bishop attacks your pawn on d6, which your queen and the pawn on e7 protect, and adds control of e5. The named Karpov Variation ends here: your next task is to bring a rook to c8 on the half-open c-file and keep pressing d4 with the knight on c6 and the bishop on g7, while keeping d6 protected.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='alekhine'")) {
            throw new \RuntimeException('The Alekhine Defense course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Alekhine Defense teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::FOUR_PAWNS_TARTAKOWER_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Alekhine Defense start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('alekhine',NULL,14)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('alekhine',1,?,?,?,UTC_TIMESTAMP(),'b')", ['Alekhine Defense', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('alekhine',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('alekhine',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Alekhine Defense edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='alekhine'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Alekhine Defense sessions and progress.');
    }
}
