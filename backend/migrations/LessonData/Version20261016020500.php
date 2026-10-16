<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Nimzo-Indian Defense course: complete named pinned paths with original teaching notes. */
final class Version20261016020500 extends AbstractMigration
{
    public const RUBINSTEIN_BERNSTEIN_SOURCE = '1. d4 Nf6 2. c4 e6 3. Nc3 Bb4 4. e3 O-O 5. Bd3 d5 6. a3 Bxc3+ 7. bxc3 dxc4 8. Bxc4 c5 9. Nf3 Nc6 10. O-O Qc7 11. Qc2';
    public const SAMISCH_CAPABLANCA_SOURCE = '1. d4 Nf6 2. c4 e6 3. Nc3 Bb4 4. e3 c5 5. Bd3 Nc6 6. a3 Bxc3+ 7. bxc3 O-O 8. Ne2 b6 9. e4 Ne8';

    public const RUBINSTEIN_BERNSTEIN_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'f8b4', 'san' => 'Bb4', 'fromKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk2r/pppp1ppp/4pn2/8/1bPP4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'e2e3', 'san' => 'e3', 'fromKey' => 'rnbqk2r/pppp1ppp/4pn2/8/1bPP4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk2r/pppp1ppp/4pn2/8/1bPP4/2N1P3/PP3PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnbqk2r/pppp1ppp/4pn2/8/1bPP4/2N1P3/PP3PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbq1rk1/pppp1ppp/4pn2/8/1bPP4/2N1P3/PP3PPP/R1BQKBNR w KQ -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'rnbq1rk1/pppp1ppp/4pn2/8/1bPP4/2N1P3/PP3PPP/R1BQKBNR w KQ -', 'toKey' => 'rnbq1rk1/pppp1ppp/4pn2/8/1bPP4/2NBP3/PP3PPP/R1BQK1NR b KQ -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbq1rk1/pppp1ppp/4pn2/8/1bPP4/2NBP3/PP3PPP/R1BQK1NR b KQ -', 'toKey' => 'rnbq1rk1/ppp2ppp/4pn2/3p4/1bPP4/2NBP3/PP3PPP/R1BQK1NR w KQ -'],
        ['uci' => 'a2a3', 'san' => 'a3', 'fromKey' => 'rnbq1rk1/ppp2ppp/4pn2/3p4/1bPP4/2NBP3/PP3PPP/R1BQK1NR w KQ -', 'toKey' => 'rnbq1rk1/ppp2ppp/4pn2/3p4/1bPP4/P1NBP3/1P3PPP/R1BQK1NR b KQ -'],
        ['uci' => 'b4c3', 'san' => 'Bxc3+', 'fromKey' => 'rnbq1rk1/ppp2ppp/4pn2/3p4/1bPP4/P1NBP3/1P3PPP/R1BQK1NR b KQ -', 'toKey' => 'rnbq1rk1/ppp2ppp/4pn2/3p4/2PP4/P1bBP3/1P3PPP/R1BQK1NR w KQ -'],
        ['uci' => 'b2c3', 'san' => 'bxc3', 'fromKey' => 'rnbq1rk1/ppp2ppp/4pn2/3p4/2PP4/P1bBP3/1P3PPP/R1BQK1NR w KQ -', 'toKey' => 'rnbq1rk1/ppp2ppp/4pn2/3p4/2PP4/P1PBP3/5PPP/R1BQK1NR b KQ -'],
        ['uci' => 'd5c4', 'san' => 'dxc4', 'fromKey' => 'rnbq1rk1/ppp2ppp/4pn2/3p4/2PP4/P1PBP3/5PPP/R1BQK1NR b KQ -', 'toKey' => 'rnbq1rk1/ppp2ppp/4pn2/8/2pP4/P1PBP3/5PPP/R1BQK1NR w KQ -'],
        ['uci' => 'd3c4', 'san' => 'Bxc4', 'fromKey' => 'rnbq1rk1/ppp2ppp/4pn2/8/2pP4/P1PBP3/5PPP/R1BQK1NR w KQ -', 'toKey' => 'rnbq1rk1/ppp2ppp/4pn2/8/2BP4/P1P1P3/5PPP/R1BQK1NR b KQ -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbq1rk1/ppp2ppp/4pn2/8/2BP4/P1P1P3/5PPP/R1BQK1NR b KQ -', 'toKey' => 'rnbq1rk1/pp3ppp/4pn2/2p5/2BP4/P1P1P3/5PPP/R1BQK1NR w KQ -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbq1rk1/pp3ppp/4pn2/2p5/2BP4/P1P1P3/5PPP/R1BQK1NR w KQ -', 'toKey' => 'rnbq1rk1/pp3ppp/4pn2/2p5/2BP4/P1P1PN2/5PPP/R1BQK2R b KQ -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbq1rk1/pp3ppp/4pn2/2p5/2BP4/P1P1PN2/5PPP/R1BQK2R b KQ -', 'toKey' => 'r1bq1rk1/pp3ppp/2n1pn2/2p5/2BP4/P1P1PN2/5PPP/R1BQK2R w KQ -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bq1rk1/pp3ppp/2n1pn2/2p5/2BP4/P1P1PN2/5PPP/R1BQK2R w KQ -', 'toKey' => 'r1bq1rk1/pp3ppp/2n1pn2/2p5/2BP4/P1P1PN2/5PPP/R1BQ1RK1 b - -'],
        ['uci' => 'd8c7', 'san' => 'Qc7', 'fromKey' => 'r1bq1rk1/pp3ppp/2n1pn2/2p5/2BP4/P1P1PN2/5PPP/R1BQ1RK1 b - -', 'toKey' => 'r1b2rk1/ppq2ppp/2n1pn2/2p5/2BP4/P1P1PN2/5PPP/R1BQ1RK1 w - -'],
        ['uci' => 'd1c2', 'san' => 'Qc2', 'fromKey' => 'r1b2rk1/ppq2ppp/2n1pn2/2p5/2BP4/P1P1PN2/5PPP/R1BQ1RK1 w - -', 'toKey' => 'r1b2rk1/ppq2ppp/2n1pn2/2p5/2BP4/P1P1PN2/2Q2PPP/R1B2RK1 b - -'],
    ];

    public const SAMISCH_CAPABLANCA_FACTS = [
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'c2c4', 'san' => 'c4', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkb1r/pppppppp/5n2/8/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -'],
        ['uci' => 'f8b4', 'san' => 'Bb4', 'fromKey' => 'rnbqkb1r/pppp1ppp/4pn2/8/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk2r/pppp1ppp/4pn2/8/1bPP4/2N5/PP2PPPP/R1BQKBNR w KQkq -'],
        ['uci' => 'e2e3', 'san' => 'e3', 'fromKey' => 'rnbqk2r/pppp1ppp/4pn2/8/1bPP4/2N5/PP2PPPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk2r/pppp1ppp/4pn2/8/1bPP4/2N1P3/PP3PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqk2r/pppp1ppp/4pn2/8/1bPP4/2N1P3/PP3PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk2r/pp1p1ppp/4pn2/2p5/1bPP4/2N1P3/PP3PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'f1d3', 'san' => 'Bd3', 'fromKey' => 'rnbqk2r/pp1p1ppp/4pn2/2p5/1bPP4/2N1P3/PP3PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk2r/pp1p1ppp/4pn2/2p5/1bPP4/2NBP3/PP3PPP/R1BQK1NR b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqk2r/pp1p1ppp/4pn2/2p5/1bPP4/2NBP3/PP3PPP/R1BQK1NR b KQkq -', 'toKey' => 'r1bqk2r/pp1p1ppp/2n1pn2/2p5/1bPP4/2NBP3/PP3PPP/R1BQK1NR w KQkq -'],
        ['uci' => 'a2a3', 'san' => 'a3', 'fromKey' => 'r1bqk2r/pp1p1ppp/2n1pn2/2p5/1bPP4/2NBP3/PP3PPP/R1BQK1NR w KQkq -', 'toKey' => 'r1bqk2r/pp1p1ppp/2n1pn2/2p5/1bPP4/P1NBP3/1P3PPP/R1BQK1NR b KQkq -'],
        ['uci' => 'b4c3', 'san' => 'Bxc3+', 'fromKey' => 'r1bqk2r/pp1p1ppp/2n1pn2/2p5/1bPP4/P1NBP3/1P3PPP/R1BQK1NR b KQkq -', 'toKey' => 'r1bqk2r/pp1p1ppp/2n1pn2/2p5/2PP4/P1bBP3/1P3PPP/R1BQK1NR w KQkq -'],
        ['uci' => 'b2c3', 'san' => 'bxc3', 'fromKey' => 'r1bqk2r/pp1p1ppp/2n1pn2/2p5/2PP4/P1bBP3/1P3PPP/R1BQK1NR w KQkq -', 'toKey' => 'r1bqk2r/pp1p1ppp/2n1pn2/2p5/2PP4/P1PBP3/5PPP/R1BQK1NR b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'r1bqk2r/pp1p1ppp/2n1pn2/2p5/2PP4/P1PBP3/5PPP/R1BQK1NR b KQkq -', 'toKey' => 'r1bq1rk1/pp1p1ppp/2n1pn2/2p5/2PP4/P1PBP3/5PPP/R1BQK1NR w KQ -'],
        ['uci' => 'g1e2', 'san' => 'Ne2', 'fromKey' => 'r1bq1rk1/pp1p1ppp/2n1pn2/2p5/2PP4/P1PBP3/5PPP/R1BQK1NR w KQ -', 'toKey' => 'r1bq1rk1/pp1p1ppp/2n1pn2/2p5/2PP4/P1PBP3/4NPPP/R1BQK2R b KQ -'],
        ['uci' => 'b7b6', 'san' => 'b6', 'fromKey' => 'r1bq1rk1/pp1p1ppp/2n1pn2/2p5/2PP4/P1PBP3/4NPPP/R1BQK2R b KQ -', 'toKey' => 'r1bq1rk1/p2p1ppp/1pn1pn2/2p5/2PP4/P1PBP3/4NPPP/R1BQK2R w KQ -'],
        ['uci' => 'e3e4', 'san' => 'e4', 'fromKey' => 'r1bq1rk1/p2p1ppp/1pn1pn2/2p5/2PP4/P1PBP3/4NPPP/R1BQK2R w KQ -', 'toKey' => 'r1bq1rk1/p2p1ppp/1pn1pn2/2p5/2PPP3/P1PB4/4NPPP/R1BQK2R b KQ -'],
        ['uci' => 'f6e8', 'san' => 'Ne8', 'fromKey' => 'r1bq1rk1/p2p1ppp/1pn1pn2/2p5/2PPP3/P1PB4/4NPPP/R1BQK2R b KQ -', 'toKey' => 'r1bqnrk1/p2p1ppp/1pn1p3/2p5/2PPP3/P1PB4/4NPPP/R1BQK2R w KQ -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Nimzo-Indian Defense course: Normal Variation, Bernstein Defense; Sämisch Variation, Capablanca Variation';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. d4 and 2. c4. After ...Nf6 and ...e6, you answer Nc3 with ...Bb4 and pin the knight that supports e2-e4. You are ready to give up that bishop for the knight to double White\'s c-pawns; in return White gets the bishop pair. Both lines meet White\'s quiet e3 setup. In the Bernstein Defense you castle, claim the centre with ...d5, take on c3 and c4, then develop with ...c5, ...Nc6 and ...Qc7. In the Capablanca Variation you hit d4 with ...c5 and ...Nc6, prepare ...Ba6 against c4 and answer White\'s e4 with ...Ne8. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['rubinstein-bernstein', 'Bernstein Defense: ...d5, ...c5 and ...Qc7 against e3', 'Ten Black moves. Pin with ...Bb4, castle, claim the centre with ...d5, double White\'s pawns with ...Bxc3+, take on c4 and develop with ...c5, ...Nc6 and ...Qc7. Source: Normal Variation, Bernstein Defense.', self::RUBINSTEIN_BERNSTEIN_SOURCE, self::RUBINSTEIN_BERNSTEIN_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a diagonal for the bishop on c1.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight to f6, where it controls e4 and d5. White cannot play e2-e4 yet without losing the pawn.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White gains space on the queenside and adds control of d5.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Open the diagonal for the bishop on f8 and add control of d5. The bishop can now develop actively, for example to b4.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the knight, controls d5 and e4, and prepares to play e2-e4.'],
                ['Bb4', 'Move your bishop from f8 to b4.', 'This is the Nimzo-Indian. Pin the knight on c3 against White\'s king: while the pin lasts, the knight cannot recapture on e4, so White needs more preparation before playing e2-e4. You are also ready to give up the bishop for this knight if that doubles White\'s c-pawns.'],
                ['e3', 'Move your pawn from e2 to e3.', 'White supports d4 with a pawn and opens the diagonal for the bishop on f1. This quiet setup prepares Bd3 and kingside castling.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Here this line chooses its own path: castle first. Your king is safe, the rook comes to f8, and you keep your central pawn moves for later.'],
                ['Bd3', 'Move your bishop from f1 to d3.', 'White develops the bishop to d3, where it guards c4, adds control of e4 and aims along the b1-h7 diagonal towards h7. Your king and the knight on f6 defend h7.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Claim a share of the centre. Your pawn on d5 attacks c4 and adds a second guard on e4, together with the knight on f6.'],
                ['a3', 'Move your pawn from a2 to a3.', 'White attacks the bishop on b4 with a pawn and asks it to decide: retreat or take the knight on c3.'],
                ['Bxc3+', 'Move your bishop from b4 to c3, capturing the white knight with check.', 'Take the knight with check. Recapturing with the b-pawn doubles White\'s c-pawns; in return White keeps both bishops.'],
                ['bxc3', 'Move your pawn from b2 to c3, capturing the black bishop.', 'White recaptures towards the centre. The pawn on c3 supports d4 and the b-file is half-open for White, but the pawns on c3 and c4 are doubled.'],
                ['dxc4', 'Move your pawn from d5 to c4, capturing the white pawn.', 'Take the pawn on c4. White\'s bishop on d3 has to spend a second move recapturing it, and the d-file is now half-open for your queen.'],
                ['Bxc4', 'Move your bishop from d3 to c4, capturing the black pawn.', 'White recaptures. The doubled pawns are gone and White keeps the bishop pair, but the pawn on a3 is now isolated.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Strike at d4 with your c-pawn before White can support the centre further. The pawn and your queen both attack d4, and the queen now has the d8-a5 diagonal as well.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the knight, adds another defender to d4 and prepares to castle.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and add a third attacker on d4, alongside the c5 pawn and your queen. The knight also controls e5.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles and keeps the central tension for now.'],
                ['Qc7', 'Move your queen from d8 to c7.', 'This is the Bernstein Defense. Move the queen off the d-file to c7, where it adds control of e5, the square for your later ...e5 advance. It also clears d8 for a rook.'],
                ['Qc2', 'Move your queen from d1 to c2.', 'White develops the queen to c2. From there it supports a future e3-e4 advance and aims along the b1-h7 diagonal at h7, which your king and the knight on f6 defend. The named Bernstein Defense ends here: your next task is to prepare ...e5 to challenge White\'s centre and open the diagonal of your bishop on c8, then bring a rook to d8.'],
            ]],
            ['samisch-capablanca', 'Sämisch Capablanca: ...b6 and ...Ne8 against the big centre', 'Nine Black moves. Pin with ...Bb4, hit d4 with ...c5 and ...Nc6, double White\'s pawns, castle, prepare ...Ba6 with ...b6 and retreat the knight to e8 when White plays e4. Source: Sämisch Variation, Capablanca Variation.', self::SAMISCH_CAPABLANCA_SOURCE, self::SAMISCH_CAPABLANCA_FACTS, [
                ['d4', 'Move your pawn from d2 to d4.', 'White takes space in the centre and opens a diagonal for the bishop on c1.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop the knight to f6, where it controls e4 and d5. White cannot play e2-e4 yet without losing the pawn.'],
                ['c4', 'Move your pawn from c2 to c4.', 'White gains space on the queenside and adds control of d5.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Open the diagonal for the bishop on f8 and add control of d5. The bishop can now develop actively, for example to b4.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'White develops the knight, controls d5 and e4, and prepares to play e2-e4.'],
                ['Bb4', 'Move your bishop from f8 to b4.', 'This is the Nimzo-Indian. Pin the knight on c3 against White\'s king: while the pin lasts, the knight cannot recapture on e4, so White needs more preparation before playing e2-e4. You are also ready to give up the bishop for this knight if that doubles White\'s c-pawns.'],
                ['e3', 'Move your pawn from e2 to e3.', 'White supports d4 with a pawn and opens the diagonal for the bishop on f1. This quiet setup prepares Bd3 and kingside castling.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Here this line chooses its own path: strike at d4 with your c-pawn before castling. The pawn on c5 also protects your bishop on b4.'],
                ['Bd3', 'Move your bishop from f1 to d3.', 'White develops the bishop to d3, where it guards c4, adds control of e4 and prepares kingside castling. It now stands in front of the queen, so only the e3 pawn defends d4.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and add a second attacker on d4, alongside your c5 pawn.'],
                ['a3', 'Move your pawn from a2 to a3.', 'White attacks the bishop on b4 with a pawn and asks it to decide: retreat or take the knight on c3.'],
                ['Bxc3+', 'Move your bishop from b4 to c3, capturing the white knight with check.', 'Take the knight with check. Recapturing with the b-pawn doubles White\'s c-pawns; in return White keeps both bishops.'],
                ['bxc3', 'Move your pawn from b2 to c3, capturing the black bishop.', 'White recaptures towards the centre. With a3 and bxc3 the position has the pawn structure of the Sämisch Variation: doubled c-pawns, a half-open b-file and the bishop pair for White. The new pawn on c3 also adds a second defender to d4.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Castle. Your king is safe and the rook comes to f8, behind the f-pawn, which may later advance to f5.'],
                ['Ne2', 'Move your knight from g1 to e2.', 'White develops the knight to e2 rather than f3. It adds a third defender to d4 and leaves the f-pawn free to advance.'],
                ['b6', 'Move your pawn from b7 to b6.', 'Support your c5 pawn and open the way for your bishop to a6, where it would attack c4. Only the bishop on d3 defends that doubled pawn.'],
                ['e4', 'Move your pawn from e3 to e4.', 'White builds a broad centre with pawns on c4, d4 and e4. The e4 pawn prepares e4-e5, which would attack your knight on f6, and with the e3 pawn gone the bishop on c1 can come to g5 and pin that knight against your queen.'],
                ['Ne8', 'Move your knight from f6 to e8.', 'This is the Capablanca Variation. Retreat the knight before e4-e5 or Bg5 can hit it. Your f-pawn is now free to advance to f5 against e4, and the knight can later go to d6, where it would attack c4 and e4. The named Capablanca Variation ends here: your next task is to put pressure on the c4 pawn with ...Ba6 and ...Na5, and to answer a white kingside advance with ...f5.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='nimzo-indian'")) {
            throw new \RuntimeException('The Nimzo-Indian Defense course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Nimzo-Indian Defense teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::RUBINSTEIN_BERNSTEIN_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Nimzo-Indian Defense start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('nimzo-indian',NULL,15)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('nimzo-indian',1,?,?,?,UTC_TIMESTAMP(),'b')", ['Nimzo-Indian Defense', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('nimzo-indian',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('nimzo-indian',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Nimzo-Indian Defense edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='nimzo-indian'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Nimzo-Indian Defense sessions and progress.');
    }
}
