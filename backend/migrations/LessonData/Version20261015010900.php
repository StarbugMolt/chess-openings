<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** French revision 2: keeps the Advance line and adds two complete named pinned paths against 3. Nc3. */
final class Version20261015010900 extends AbstractMigration
{
    public const STAHLBERG_SOURCE = '1. e4 e6 2. d4 d5 3. Nc3 Nf6 4. Bg5 Be7 5. e5 Nfd7 6. Bxe7 Qxe7 7. f4 O-O 8. Nf3 c5 9. Qd2 Nc6 10. O-O-O c4';
    public const WINAWER_SOURCE = '1. e4 e6 2. d4 d5 3. Nc3 Bb4 4. e5 c5 5. a3 Bxc3+ 6. bxc3 Ne7 7. a4 Nbc6 8. Nf3 Qa5 9. Bd2 Bd7';
    private const KEPT_LINES = ['advance-centre' => 14];

    public const STAHLBERG_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/4p3/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppp1ppp/4p3/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/4p3/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pppp1ppp/4p3/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/2N5/PPP2PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/2N5/PPP2PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/3PP3/2N5/PPP2PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/3PP3/2N5/PPP2PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p2B1/3PP3/2N5/PPP2PPP/R2QKBNR b KQkq -'],
        ['uci' => 'f8e7', 'san' => 'Be7', 'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p2B1/3PP3/2N5/PPP2PPP/R2QKBNR b KQkq -', 'toKey' => 'rnbqk2r/ppp1bppp/4pn2/3p2B1/3PP3/2N5/PPP2PPP/R2QKBNR w KQkq -'],
        ['uci' => 'e4e5', 'san' => 'e5', 'fromKey' => 'rnbqk2r/ppp1bppp/4pn2/3p2B1/3PP3/2N5/PPP2PPP/R2QKBNR w KQkq -', 'toKey' => 'rnbqk2r/ppp1bppp/4pn2/3pP1B1/3P4/2N5/PPP2PPP/R2QKBNR b KQkq -'],
        ['uci' => 'f6d7', 'san' => 'Nfd7', 'fromKey' => 'rnbqk2r/ppp1bppp/4pn2/3pP1B1/3P4/2N5/PPP2PPP/R2QKBNR b KQkq -', 'toKey' => 'rnbqk2r/pppnbppp/4p3/3pP1B1/3P4/2N5/PPP2PPP/R2QKBNR w KQkq -'],
        ['uci' => 'g5e7', 'san' => 'Bxe7', 'fromKey' => 'rnbqk2r/pppnbppp/4p3/3pP1B1/3P4/2N5/PPP2PPP/R2QKBNR w KQkq -', 'toKey' => 'rnbqk2r/pppnBppp/4p3/3pP3/3P4/2N5/PPP2PPP/R2QKBNR b KQkq -'],
        ['uci' => 'd8e7', 'san' => 'Qxe7', 'fromKey' => 'rnbqk2r/pppnBppp/4p3/3pP3/3P4/2N5/PPP2PPP/R2QKBNR b KQkq -', 'toKey' => 'rnb1k2r/pppnqppp/4p3/3pP3/3P4/2N5/PPP2PPP/R2QKBNR w KQkq -'],
        ['uci' => 'f2f4', 'san' => 'f4', 'fromKey' => 'rnb1k2r/pppnqppp/4p3/3pP3/3P4/2N5/PPP2PPP/R2QKBNR w KQkq -', 'toKey' => 'rnb1k2r/pppnqppp/4p3/3pP3/3P1P2/2N5/PPP3PP/R2QKBNR b KQkq -'],
        ['uci' => 'e8g8', 'san' => 'O-O', 'fromKey' => 'rnb1k2r/pppnqppp/4p3/3pP3/3P1P2/2N5/PPP3PP/R2QKBNR b KQkq -', 'toKey' => 'rnb2rk1/pppnqppp/4p3/3pP3/3P1P2/2N5/PPP3PP/R2QKBNR w KQ -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnb2rk1/pppnqppp/4p3/3pP3/3P1P2/2N5/PPP3PP/R2QKBNR w KQ -', 'toKey' => 'rnb2rk1/pppnqppp/4p3/3pP3/3P1P2/2N2N2/PPP3PP/R2QKB1R b KQ -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnb2rk1/pppnqppp/4p3/3pP3/3P1P2/2N2N2/PPP3PP/R2QKB1R b KQ -', 'toKey' => 'rnb2rk1/pp1nqppp/4p3/2ppP3/3P1P2/2N2N2/PPP3PP/R2QKB1R w KQ -'],
        ['uci' => 'd1d2', 'san' => 'Qd2', 'fromKey' => 'rnb2rk1/pp1nqppp/4p3/2ppP3/3P1P2/2N2N2/PPP3PP/R2QKB1R w KQ -', 'toKey' => 'rnb2rk1/pp1nqppp/4p3/2ppP3/3P1P2/2N2N2/PPPQ2PP/R3KB1R b KQ -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnb2rk1/pp1nqppp/4p3/2ppP3/3P1P2/2N2N2/PPPQ2PP/R3KB1R b KQ -', 'toKey' => 'r1b2rk1/pp1nqppp/2n1p3/2ppP3/3P1P2/2N2N2/PPPQ2PP/R3KB1R w KQ -'],
        ['uci' => 'e1c1', 'san' => 'O-O-O', 'fromKey' => 'r1b2rk1/pp1nqppp/2n1p3/2ppP3/3P1P2/2N2N2/PPPQ2PP/R3KB1R w KQ -', 'toKey' => 'r1b2rk1/pp1nqppp/2n1p3/2ppP3/3P1P2/2N2N2/PPPQ2PP/2KR1B1R b - -'],
        ['uci' => 'c5c4', 'san' => 'c4', 'fromKey' => 'r1b2rk1/pp1nqppp/2n1p3/2ppP3/3P1P2/2N2N2/PPPQ2PP/2KR1B1R b - -', 'toKey' => 'r1b2rk1/pp1nqppp/2n1p3/3pP3/2pP1P2/2N2N2/PPPQ2PP/2KR1B1R w - -'],
    ];

    public const WINAWER_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/4p3/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppp1ppp/4p3/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/4p3/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pppp1ppp/4p3/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/2N5/PPP2PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'f8b4', 'san' => 'Bb4', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/2N5/PPP2PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk1nr/ppp2ppp/4p3/3p4/1b1PP3/2N5/PPP2PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'e4e5', 'san' => 'e5', 'fromKey' => 'rnbqk1nr/ppp2ppp/4p3/3p4/1b1PP3/2N5/PPP2PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk1nr/ppp2ppp/4p3/3pP3/1b1P4/2N5/PPP2PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqk1nr/ppp2ppp/4p3/3pP3/1b1P4/2N5/PPP2PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk1nr/pp3ppp/4p3/2ppP3/1b1P4/2N5/PPP2PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'a2a3', 'san' => 'a3', 'fromKey' => 'rnbqk1nr/pp3ppp/4p3/2ppP3/1b1P4/2N5/PPP2PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk1nr/pp3ppp/4p3/2ppP3/1b1P4/P1N5/1PP2PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'b4c3', 'san' => 'Bxc3+', 'fromKey' => 'rnbqk1nr/pp3ppp/4p3/2ppP3/1b1P4/P1N5/1PP2PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk1nr/pp3ppp/4p3/2ppP3/3P4/P1b5/1PP2PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'b2c3', 'san' => 'bxc3', 'fromKey' => 'rnbqk1nr/pp3ppp/4p3/2ppP3/3P4/P1b5/1PP2PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk1nr/pp3ppp/4p3/2ppP3/3P4/P1P5/2P2PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'g8e7', 'san' => 'Ne7', 'fromKey' => 'rnbqk1nr/pp3ppp/4p3/2ppP3/3P4/P1P5/2P2PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqk2r/pp2nppp/4p3/2ppP3/3P4/P1P5/2P2PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'a3a4', 'san' => 'a4', 'fromKey' => 'rnbqk2r/pp2nppp/4p3/2ppP3/3P4/P1P5/2P2PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqk2r/pp2nppp/4p3/2ppP3/P2P4/2P5/2P2PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nbc6', 'fromKey' => 'rnbqk2r/pp2nppp/4p3/2ppP3/P2P4/2P5/2P2PPP/R1BQKBNR b KQkq -', 'toKey' => 'r1bqk2r/pp2nppp/2n1p3/2ppP3/P2P4/2P5/2P2PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'r1bqk2r/pp2nppp/2n1p3/2ppP3/P2P4/2P5/2P2PPP/R1BQKBNR w KQkq -', 'toKey' => 'r1bqk2r/pp2nppp/2n1p3/2ppP3/P2P4/2P2N2/2P2PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'd8a5', 'san' => 'Qa5', 'fromKey' => 'r1bqk2r/pp2nppp/2n1p3/2ppP3/P2P4/2P2N2/2P2PPP/R1BQKB1R b KQkq -', 'toKey' => 'r1b1k2r/pp2nppp/2n1p3/q1ppP3/P2P4/2P2N2/2P2PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'c1d2', 'san' => 'Bd2', 'fromKey' => 'r1b1k2r/pp2nppp/2n1p3/q1ppP3/P2P4/2P2N2/2P2PPP/R1BQKB1R w KQkq -', 'toKey' => 'r1b1k2r/pp2nppp/2n1p3/q1ppP3/P2P4/2P2N2/2PB1PPP/R2QKB1R b KQkq -'],
        ['uci' => 'c8d7', 'san' => 'Bd7', 'fromKey' => 'r1b1k2r/pp2nppp/2n1p3/q1ppP3/P2P4/2P2N2/2PB1PPP/R2QKB1R b KQkq -', 'toKey' => 'r3k2r/pp1bnppp/2n1p3/q1ppP3/P2P4/2P2N2/2PB1PPP/R2QKB1R w KQkq -'],
    ];

    public function getDescription(): string
    {
        return 'Publish French Defense revision 2: add the complete Classical Stahlberg and Winawer Positional lines';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $opening = [
            ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens a diagonal for the bishop on f1.'],
            ['e6', 'Move your pawn from e7 to e6.', 'Prepare to put the d-pawn on d5 with support from this pawn. This is the first move of the French Defense.'],
            ['d4', 'Move your pawn from d2 to d4.', 'White builds a broad pawn centre. Black will challenge it rather than let both pawns advance freely.'],
            ['d5', 'Move your pawn from d7 to d5.', 'Challenge the pawn on e4. Your pawn on e6 supports d5, so White cannot simply win that pawn by capturing it.'],
            ['Nc3', 'Move your knight from b1 to c3.', 'White defends e4 with a knight and keeps the pawn tension.'],
        ];
        $introduction = 'Play Black against 1. e4 with ...e6 and ...d5. When White advances to e5, attack the base of the pawn chain with ...c5. Start with the Advance Variation, then play two complete named main lines against 3. Nc3: the Classical Variation with castling on opposite sides, and the Winawer, where you pin and exchange the c3 knight to weaken White\'s pawns. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['classical-stahlberg', 'Classical Variation: castle and attack the chain', 'Ten Black moves. Break the pin, castle, strike at d4 with ...c5 and ...Nc6, then gain queenside space. Source: Classical Variation, Stahlberg Variation.', self::STAHLBERG_SOURCE, self::STAHLBERG_FACTS, [...$opening,
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop and attack e4 a second time. This is the Classical Variation.'],
                ['Bg5', 'Move your bishop from c1 to g5.', 'White pins your knight against the queen on d8, so the knight cannot freely defend or move.'],
                ['Be7', 'Move your bishop from f8 to e7.', 'Put the bishop between the knight and queen. This ends the pin and prepares castling.'],
                ['e5', 'Move your pawn from e4 to e5.', 'White gains space and attacks your knight. The centre is now locked, with White\'s pawns pointing towards your kingside.'],
                ['Nfd7', 'Move your knight from f6 to d7.', 'Retreat the knight; from d7 it will support the ...c5 break. Your bishop on e7 now attacks the bishop on g5.'],
                ['Bxe7', 'Move your bishop from g5 to e7, capturing the black bishop.', 'White exchanges bishops.'],
                ['Qxe7', 'Move your queen from d8 to e7, capturing the white bishop.', 'Recapture with the queen. From e7 it protects your kingside and watches the dark squares.'],
                ['f4', 'Move your pawn from f2 to f4.', 'White supports e5 with another pawn, forming a strong chain on d4, e5 and f4.'],
                ['O-O', 'Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Put your king into safety before the fight begins.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops and adds support to d4 and e5.'],
                ['c5', 'Move your pawn from c7 to c5.', 'This is the main French plan: attack d4, the base of White\'s pawn chain. If d4 falls, the pawn on e5 loses its support.'],
                ['Qd2', 'Move your queen from d1 to d2.', 'White clears the back rank to castle on the queenside.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop and add a second attacker to d4.'],
                ['O-O-O', 'Castle queenside: move your king from e1 to c1. The rook moves from a1 to d1 automatically.', 'White castles on the opposite side. Now each side will try to attack the other\'s king with pawns.'],
                ['c4', 'Move your pawn from c5 to c4.', 'Gain space on the queenside and prepare ...b5 and ...b4 to open lines towards White\'s king. The named Stahlberg Variation ends here.'],
            ]],
            ['winawer-positional', 'Winawer: pin, exchange and pressure', 'Nine Black moves. Pin the c3 knight, double White\'s pawns, then pressure them with your queen and knights. Source: Winawer Variation, Positional Variation.', self::WINAWER_SOURCE, self::WINAWER_FACTS, [...$opening,
                ['Bb4', 'Move your bishop from f8 to b4.', 'Pin the knight on c3, which defends e4, against White\'s king. This is the Winawer Variation.'],
                ['e5', 'Move your pawn from e4 to e5.', 'White locks the centre and gains space.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Attack the base of White\'s pawn chain on d4 straight away.'],
                ['a3', 'Move your pawn from a2 to a3.', 'White asks your bishop to decide: retreat or exchange itself for the knight.'],
                ['Bxc3+', 'Move your bishop from b4 to c3, capturing the white knight with check.', 'Give up the bishop for the knight. In return, White will have doubled pawns on the c-file, which are harder to defend.'],
                ['bxc3', 'Move your pawn from b2 to c3, capturing the black bishop.', 'White recaptures. White now has two bishops, but also doubled c-pawns.'],
                ['Ne7', 'Move your knight from g8 to e7.', 'Develop the knight to e7, where the e5 pawn cannot attack it, unlike on f6.'],
                ['a4', 'Move your pawn from a3 to a4.', 'White prepares Ba3, putting a bishop on the dark-square diagonal your bishop left behind.'],
                ['Nbc6', 'Move your knight from b8 to c6.', 'Develop your other knight and add pressure to d4.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'White develops the knight and defends d4.'],
                ['Qa5', 'Move your queen from d8 to a5.', 'Attack the weak pawns on c3 and a4. The c3 pawn is also pinned against White\'s king. This pressure is your reward for giving up the bishop.'],
                ['Bd2', 'Move your bishop from c1 to d2.', 'White defends c3 and blocks the pin.'],
                ['Bd7', 'Move your bishop from c8 to d7.', 'Develop your last minor piece. It covers the a4-e8 diagonal, so White cannot use Bb5 to pin your knight on c6. The named Positional Variation ends here.'],
            ]],
        ];

        if (1 !== (int) $this->connection->fetchOne("SELECT current_revision FROM lesson WHERE id='french'")) {
            throw new \RuntimeException('French revision 2 must be published directly after revision 1.');
        }
        foreach (self::KEPT_LINES as $lineId => $plies) {
            if ($plies !== (int) $this->connection->fetchOne("SELECT ply_count FROM lesson_line WHERE lesson_id='french' AND revision=1 AND id=?", [$lineId])) {
                throw new \RuntimeException('The retained French revision 1 line is missing or changed: '.$lineId);
            }
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('French teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }

        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) SELECT lesson_id,2,title,?,start_position_id,UTC_TIMESTAMP(),recommended_color FROM lesson_revision WHERE lesson_id='french' AND revision=1", [$introduction]);
        foreach (array_keys(self::KEPT_LINES) as $lineId) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) SELECT lesson_id,2,id,title,description,sort_order,ply_count FROM lesson_line WHERE lesson_id='french' AND revision=1 AND id=?", [$lineId]);
            $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) SELECT lesson_id,2,line_id,ply,chess_move_id,hint,explanation FROM lesson_line_step WHERE lesson_id='french' AND revision=1 AND line_id=?", [$lineId]);
        }
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('french',2,?,?,?,?,?)", [$lineId, $title, $description, count(self::KEPT_LINES) + $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('french',2,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved French edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=2 WHERE id='french'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve previous French sessions and progress.');
    }
}
