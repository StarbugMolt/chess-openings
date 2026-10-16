<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** New Evans Gambit course: complete named pinned paths with original teaching notes. */
final class Version20261016020200 extends AbstractMigration
{
    public const ACCEPTED_STEINITZ_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. b4 Bxb4 5. c3 Bc5 6. d4 exd4 7. O-O d6 8. cxd4 Bb6 9. Nc3 Na5 10. Bg5 f6 11. Be3';
    public const ACCEPTED_LEVENFISH_SOURCE = '1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. b4 Bxb4 5. c3 Ba5 6. d4 d6 7. Qb3 Qd7 8. dxe5 dxe5 9. O-O Bb6 10. Ba3 Na5 11. Nxe5';

    public const ACCEPTED_STEINITZ_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1c4', 'san' => 'Bc4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'f8c5', 'san' => 'Bc5', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'b2b4', 'san' => 'b4', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/1PB1P3/5N2/P1PP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'c5b4', 'san' => 'Bxb4', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/1PB1P3/5N2/P1PP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/4p3/1bB1P3/5N2/P1PP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'c2c3', 'san' => 'c3', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/4p3/1bB1P3/5N2/P1PP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/4p3/1bB1P3/2P2N2/P2P1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'b4c5', 'san' => 'Bc5', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/4p3/1bB1P3/2P2N2/P2P1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/2P2N2/P2P1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/2P2N2/P2P1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2BPP3/2P2N2/P4PPP/RNBQK2R b KQkq -'],
        ['uci' => 'e5d4', 'san' => 'exd4', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2BPP3/2P2N2/P4PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b5/2BpP3/2P2N2/P4PPP/RNBQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b5/2BpP3/2P2N2/P4PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b5/2BpP3/2P2N2/P4PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b5/2BpP3/2P2N2/P4PPP/RNBQ1RK1 b kq -', 'toKey' => 'r1bqk1nr/ppp2ppp/2np4/2b5/2BpP3/2P2N2/P4PPP/RNBQ1RK1 w kq -'],
        ['uci' => 'c3d4', 'san' => 'cxd4', 'fromKey' => 'r1bqk1nr/ppp2ppp/2np4/2b5/2BpP3/2P2N2/P4PPP/RNBQ1RK1 w kq -', 'toKey' => 'r1bqk1nr/ppp2ppp/2np4/2b5/2BPP3/5N2/P4PPP/RNBQ1RK1 b kq -'],
        ['uci' => 'c5b6', 'san' => 'Bb6', 'fromKey' => 'r1bqk1nr/ppp2ppp/2np4/2b5/2BPP3/5N2/P4PPP/RNBQ1RK1 b kq -', 'toKey' => 'r1bqk1nr/ppp2ppp/1bnp4/8/2BPP3/5N2/P4PPP/RNBQ1RK1 w kq -'],
        ['uci' => 'b1c3', 'san' => 'Nc3', 'fromKey' => 'r1bqk1nr/ppp2ppp/1bnp4/8/2BPP3/5N2/P4PPP/RNBQ1RK1 w kq -', 'toKey' => 'r1bqk1nr/ppp2ppp/1bnp4/8/2BPP3/2N2N2/P4PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'c6a5', 'san' => 'Na5', 'fromKey' => 'r1bqk1nr/ppp2ppp/1bnp4/8/2BPP3/2N2N2/P4PPP/R1BQ1RK1 b kq -', 'toKey' => 'r1bqk1nr/ppp2ppp/1b1p4/n7/2BPP3/2N2N2/P4PPP/R1BQ1RK1 w kq -'],
        ['uci' => 'c1g5', 'san' => 'Bg5', 'fromKey' => 'r1bqk1nr/ppp2ppp/1b1p4/n7/2BPP3/2N2N2/P4PPP/R1BQ1RK1 w kq -', 'toKey' => 'r1bqk1nr/ppp2ppp/1b1p4/n5B1/2BPP3/2N2N2/P4PPP/R2Q1RK1 b kq -'],
        ['uci' => 'f7f6', 'san' => 'f6', 'fromKey' => 'r1bqk1nr/ppp2ppp/1b1p4/n5B1/2BPP3/2N2N2/P4PPP/R2Q1RK1 b kq -', 'toKey' => 'r1bqk1nr/ppp3pp/1b1p1p2/n5B1/2BPP3/2N2N2/P4PPP/R2Q1RK1 w kq -'],
        ['uci' => 'g5e3', 'san' => 'Be3', 'fromKey' => 'r1bqk1nr/ppp3pp/1b1p1p2/n5B1/2BPP3/2N2N2/P4PPP/R2Q1RK1 w kq -', 'toKey' => 'r1bqk1nr/ppp3pp/1b1p1p2/n7/2BPP3/2N1BN2/P4PPP/R2Q1RK1 b kq -'],
    ];

    public const ACCEPTED_LEVENFISH_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e5', 'san' => 'e5', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Nf3', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnbqkbnr/pppp1ppp/8/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R b KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -'],
        ['uci' => 'f1c4', 'san' => 'Bc4', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq -', 'toKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'f8c5', 'san' => 'Bc5', 'fromKey' => 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'b2b4', 'san' => 'b4', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/2B1P3/5N2/PPPP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/1PB1P3/5N2/P1PP1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'c5b4', 'san' => 'Bxb4', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/2b1p3/1PB1P3/5N2/P1PP1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/4p3/1bB1P3/5N2/P1PP1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'c2c3', 'san' => 'c3', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/4p3/1bB1P3/5N2/P1PP1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/4p3/1bB1P3/2P2N2/P2P1PPP/RNBQK2R b KQkq -'],
        ['uci' => 'b4a5', 'san' => 'Ba5', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/4p3/1bB1P3/2P2N2/P2P1PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/b3p3/2B1P3/2P2N2/P2P1PPP/RNBQK2R w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/b3p3/2B1P3/2P2N2/P2P1PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk1nr/pppp1ppp/2n5/b3p3/2BPP3/2P2N2/P4PPP/RNBQK2R b KQkq -'],
        ['uci' => 'd7d6', 'san' => 'd6', 'fromKey' => 'r1bqk1nr/pppp1ppp/2n5/b3p3/2BPP3/2P2N2/P4PPP/RNBQK2R b KQkq -', 'toKey' => 'r1bqk1nr/ppp2ppp/2np4/b3p3/2BPP3/2P2N2/P4PPP/RNBQK2R w KQkq -'],
        ['uci' => 'd1b3', 'san' => 'Qb3', 'fromKey' => 'r1bqk1nr/ppp2ppp/2np4/b3p3/2BPP3/2P2N2/P4PPP/RNBQK2R w KQkq -', 'toKey' => 'r1bqk1nr/ppp2ppp/2np4/b3p3/2BPP3/1QP2N2/P4PPP/RNB1K2R b KQkq -'],
        ['uci' => 'd8d7', 'san' => 'Qd7', 'fromKey' => 'r1bqk1nr/ppp2ppp/2np4/b3p3/2BPP3/1QP2N2/P4PPP/RNB1K2R b KQkq -', 'toKey' => 'r1b1k1nr/pppq1ppp/2np4/b3p3/2BPP3/1QP2N2/P4PPP/RNB1K2R w KQkq -'],
        ['uci' => 'd4e5', 'san' => 'dxe5', 'fromKey' => 'r1b1k1nr/pppq1ppp/2np4/b3p3/2BPP3/1QP2N2/P4PPP/RNB1K2R w KQkq -', 'toKey' => 'r1b1k1nr/pppq1ppp/2np4/b3P3/2B1P3/1QP2N2/P4PPP/RNB1K2R b KQkq -'],
        ['uci' => 'd6e5', 'san' => 'dxe5', 'fromKey' => 'r1b1k1nr/pppq1ppp/2np4/b3P3/2B1P3/1QP2N2/P4PPP/RNB1K2R b KQkq -', 'toKey' => 'r1b1k1nr/pppq1ppp/2n5/b3p3/2B1P3/1QP2N2/P4PPP/RNB1K2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'r1b1k1nr/pppq1ppp/2n5/b3p3/2B1P3/1QP2N2/P4PPP/RNB1K2R w KQkq -', 'toKey' => 'r1b1k1nr/pppq1ppp/2n5/b3p3/2B1P3/1QP2N2/P4PPP/RNB2RK1 b kq -'],
        ['uci' => 'a5b6', 'san' => 'Bb6', 'fromKey' => 'r1b1k1nr/pppq1ppp/2n5/b3p3/2B1P3/1QP2N2/P4PPP/RNB2RK1 b kq -', 'toKey' => 'r1b1k1nr/pppq1ppp/1bn5/4p3/2B1P3/1QP2N2/P4PPP/RNB2RK1 w kq -'],
        ['uci' => 'c1a3', 'san' => 'Ba3', 'fromKey' => 'r1b1k1nr/pppq1ppp/1bn5/4p3/2B1P3/1QP2N2/P4PPP/RNB2RK1 w kq -', 'toKey' => 'r1b1k1nr/pppq1ppp/1bn5/4p3/2B1P3/BQP2N2/P4PPP/RN3RK1 b kq -'],
        ['uci' => 'c6a5', 'san' => 'Na5', 'fromKey' => 'r1b1k1nr/pppq1ppp/1bn5/4p3/2B1P3/BQP2N2/P4PPP/RN3RK1 b kq -', 'toKey' => 'r1b1k1nr/pppq1ppp/1b6/n3p3/2B1P3/BQP2N2/P4PPP/RN3RK1 w kq -'],
        ['uci' => 'f3e5', 'san' => 'Nxe5', 'fromKey' => 'r1b1k1nr/pppq1ppp/1b6/n3p3/2B1P3/BQP2N2/P4PPP/RN3RK1 w kq -', 'toKey' => 'r1b1k1nr/pppq1ppp/1b6/n3N3/2B1P3/BQP5/P4PPP/RN3RK1 b kq -'],
    ];

    public function getDescription(): string
    {
        return 'Publish the Evans Gambit course: Evans Gambit, Steinitz Variation; Evans Gambit, Levenfish Variation';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play White with 1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. b4, the Evans Gambit. You offer a pawn: if the bishop takes it, c3 and d4 follow with gain of time against the bishop, and your pieces quickly aim at f7 and Black\'s king. Both lines start with 4...Bxb4 5. c3, where Black accepts the pawn. The Steinitz Variation teaches the d4 and e4 centre after 5...Bc5, with castling and development while Black\'s knight chases your bishop. The Levenfish Variation teaches play after 5...Ba5, where your queen on b3 and bishop on a3 work against f7 and Black\'s uncastled king. Black replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['accepted-steinitz', 'Steinitz: the d4 and e4 centre after ...Bc5', 'Eleven White moves. Give up the b-pawn, build a d4 and e4 centre while attacking the bishop, then castle and develop while Black\'s knight chases your bishop on c4. Source: Evans Gambit, Steinitz Variation.', self::ACCEPTED_STEINITZ_SOURCE, self::ACCEPTED_STEINITZ_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open diagonals for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop with a threat: the knight attacks the pawn on e5.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 by developing a knight.'],
                ['Bc4', 'Move your bishop from f1 to c4.', 'The bishop aims at f7, the square next to Black\'s king that only the king defends. Moving it also clears the way for castling.'],
                ['Bc5', 'Move your bishop from f8 to c5.', 'Black mirrors your idea: this bishop aims at f2, the sensitive square next to your king.'],
                ['b4', 'Move your pawn from b2 to b4.', 'This is the Evans Gambit. Offer your b-pawn: it attacks the bishop on c5. If Black takes it with the bishop, c3 attacks the bishop again and prepares d4, so you build your centre with gain of time.'],
                ['Bxb4', 'Move your bishop from c5 to b4, capturing the white pawn.', 'Black accepts the gambit and takes the pawn with the bishop. You are now a pawn down.'],
                ['c3', 'Move your pawn from c2 to c3.', 'Attack the bishop again. The pawn on c3 also prepares d4, so you gain time for your centre while the bishop moves.'],
                ['Bc5', 'Move your bishop from b4 to c5.', 'Black retreats the bishop to c5, back on the diagonal towards f2. This is where the Steinitz line chooses its own path.'],
                ['d4', 'Move your pawn from d2 to d4.', 'Strike in the centre. The pawn attacks the bishop on c5 and the pawn on e5, so Black must react at once.'],
                ['exd4', 'Move your pawn from e5 to d4, capturing the white pawn.', 'Black takes on d4. For the moment Black is two pawns up, and the pawn on d4 attacks your c3 pawn.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle before recapturing. Your king is safe and the rook comes to f1. Black could take another pawn with ...dxc3; this line follows ...d6.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Black protects the bishop on c5 with a pawn and opens the diagonal of the bishop on c8.'],
                ['cxd4', 'Move your pawn from c3 to d4, capturing the black pawn.', 'Recapture with the c-pawn. You have pawns on d4 and e4, the bishop on c5 is attacked again, and Black is still a pawn up.'],
                ['Bb6', 'Move your bishop from c5 to b6.', 'Black retreats the bishop to b6. It stays on the same diagonal and keeps pressure on your d4 pawn.'],
                ['Nc3', 'Move your knight from b1 to c3.', 'Develop your other knight. It defends e4 and controls d5 and b5.'],
                ['Na5', 'Move your knight from c6 to a5.', 'Black\'s knight attacks your bishop on c4, which nothing defends. Black wants to exchange the knight for this bishop, which aims at f7.'],
                ['Bg5', 'Move your bishop from c1 to g5.', 'Develop the bishop with a threat: it attacks the queen on d8. If Black takes your bishop on c4 now, Bxd8 takes the queen.'],
                ['f6', 'Move your pawn from f7 to f6.', 'Black blocks the attack on the queen with a pawn that also attacks your bishop on g5. With the f7 pawn gone, your bishop on c4 now reaches e6 and g8.'],
                ['Be3', 'Move your bishop from g5 to e3.', 'Retreat the bishop to e3, where it defends d4. If Black takes your bishop with ...Nxc4, Qa4+ checks the king and attacks the knight, so you win the piece back. Even after the knight on g8 moves, Black cannot castle kingside while your bishop on c4 controls g8. The named Steinitz Variation ends here: your next task is to keep your d4 and e4 centre protected and bring a rook to the half-open c-file.'],
            ]],
            ['accepted-levenfish', 'Levenfish: Qb3 and Ba3 after ...Ba5', 'Eleven White moves. Give up the b-pawn, aim your queen and bishop at f7, keep Black\'s king in the centre with Ba3 and win the pawn back on e5. Source: Evans Gambit, Levenfish Variation.', self::ACCEPTED_LEVENFISH_SOURCE, self::ACCEPTED_LEVENFISH_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'Take space in the centre and open diagonals for your queen and the bishop on f1.'],
                ['e5', 'Move your pawn from e7 to e5.', 'Black claims an equal share of the centre and stops your e-pawn from advancing.'],
                ['Nf3', 'Move your knight from g1 to f3.', 'Develop with a threat: the knight attacks the pawn on e5.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Black defends e5 by developing a knight.'],
                ['Bc4', 'Move your bishop from f1 to c4.', 'The bishop aims at f7, the square next to Black\'s king that only the king defends. Moving it also clears the way for castling.'],
                ['Bc5', 'Move your bishop from f8 to c5.', 'Black mirrors your idea: this bishop aims at f2, the sensitive square next to your king.'],
                ['b4', 'Move your pawn from b2 to b4.', 'This is the Evans Gambit. Offer your b-pawn: it attacks the bishop on c5. If Black takes it with the bishop, c3 attacks the bishop again and prepares d4, so you build your centre with gain of time.'],
                ['Bxb4', 'Move your bishop from c5 to b4, capturing the white pawn.', 'Black accepts the gambit and takes the pawn with the bishop. You are now a pawn down.'],
                ['c3', 'Move your pawn from c2 to c3.', 'Attack the bishop again. The pawn on c3 also prepares d4, so you gain time for your centre while the bishop moves.'],
                ['Ba5', 'Move your bishop from b4 to a5.', 'Black retreats the bishop to a5, where it presses on your c3 pawn. Once your d-pawn moves, the bishop will pin that pawn to your king. This is where the Levenfish line chooses its own path.'],
                ['d4', 'Move your pawn from d2 to d4.', 'Strike in the centre: the pawn attacks e5. Your c3 pawn is now pinned to your king by the bishop on a5, so it could not recapture if Black took on d4.'],
                ['d6', 'Move your pawn from d7 to d6.', 'Black supports e5 with a pawn instead of capturing. The move also opens the diagonal of the bishop on c8.'],
                ['Qb3', 'Move your queen from d1 to b3.', 'Bring the queen behind your bishop on c4. Together they aim at f7, which only the king defends: Bxf7+ would win a pawn with check, with the queen supporting the bishop. The queen also eyes b7.'],
                ['Qd7', 'Move your queen from d8 to d7.', 'Black\'s queen defends f7 along the seventh rank.'],
                ['dxe5', 'Move your pawn from d4 to e5, capturing the black pawn.', 'Exchange pawns on e5. After the recapture the d-file is open, while Black\'s king is still in the centre.'],
                ['dxe5', 'Move your pawn from d6 to e5, capturing the white pawn.', 'Black recaptures with the pawn. The d-file is now open, and Black is still a pawn up from the gambit.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Castle. With your king off e1, the bishop on a5 no longer pins your c3 pawn, and the rook comes to f1.'],
                ['Bb6', 'Move your bishop from a5 to b6.', 'Black retreats the bishop to b6, where it aims at f2 next to your king. Moving it also clears a5 for the knight.'],
                ['Ba3', 'Move your bishop from c1 to a3.', 'Develop the bishop on the a3-f8 diagonal. It controls e7 and f8, so Black\'s king cannot castle kingside while the bishop stays there.'],
                ['Na5', 'Move your knight from c6 to a5.', 'Black\'s knight attacks your queen on b3 and your bishop on c4 at the same time.'],
                ['Nxe5', 'Move your knight from f3 to e5, capturing the black pawn.', 'Take the e5 pawn instead of moving the queen. Material is level again, and your knight attacks Black\'s queen on d7 and, together with the bishop on c4, the pawn on f7. If Black takes your queen with ...Nxb3, your knight takes Black\'s queen on d7. Of the queen\'s possible moves, only d8 is a square that none of your pieces attack. The named Levenfish Variation ends here: your next task is to keep the pressure on f7 and Black\'s uncastled king, then bring the knight on b1 and the rook on a1 into play.'],
            ]],
        ];

        if (false !== $this->connection->fetchOne("SELECT id FROM lesson WHERE id='evans-gambit'")) {
            throw new \RuntimeException('The Evans Gambit course already exists.');
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('Evans Gambit teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::ACCEPTED_STEINITZ_FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('The imported Evans Gambit start position is missing.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('evans-gambit',NULL,12)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('evans-gambit',1,?,?,?,UTC_TIMESTAMP(),'w')", ['Evans Gambit', $introduction, (int) $start]);
        foreach ($lines as $index => [$lineId, $title, $description, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('evans-gambit',1,?,?,?,?,?)", [$lineId, $title, $description, $index, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('evans-gambit',1,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved Evans Gambit edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='evans-gambit'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve Evans Gambit sessions and progress.');
    }
}
