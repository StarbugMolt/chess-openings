<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** French revision 3: every line is a complete named pinned path with at least nine learner moves. */
final class Version20261015011700 extends AbstractMigration
{
    public const TARRASCH_SOURCE = '1. e4 e6 2. d4 d5 3. Nd2 c5 4. exd5 Qxd5 5. Ngf3 cxd4 6. Bc4 Qd6 7. O-O Nf6 8. Nb3 Nc6 9. Nbxd4 Nxd4 10. Nxd4 a6';
    /** @var array<string, array{int, int}> retained line => [previous ply count, new sort order] */
    private const KEPT_LINES = ['classical-stahlberg' => [20, 0], 'winawer-positional' => [18, 1]];

    public const TARRASCH_FACTS = [
        ['uci' => 'e2e4', 'san' => 'e4', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'e7e6', 'san' => 'e6', 'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/4p3/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'd2d4', 'san' => 'd4', 'fromKey' => 'rnbqkbnr/pppp1ppp/4p3/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pppp1ppp/4p3/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -'],
        ['uci' => 'd7d5', 'san' => 'd5', 'fromKey' => 'rnbqkbnr/pppp1ppp/4p3/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -'],
        ['uci' => 'b1d2', 'san' => 'Nd2', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPPN1PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'c7c5', 'san' => 'c5', 'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPPN1PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnbqkbnr/pp3ppp/4p3/2pp4/3PP3/8/PPPN1PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'e4d5', 'san' => 'exd5', 'fromKey' => 'rnbqkbnr/pp3ppp/4p3/2pp4/3PP3/8/PPPN1PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnbqkbnr/pp3ppp/4p3/2pP4/3P4/8/PPPN1PPP/R1BQKBNR b KQkq -'],
        ['uci' => 'd8d5', 'san' => 'Qxd5', 'fromKey' => 'rnbqkbnr/pp3ppp/4p3/2pP4/3P4/8/PPPN1PPP/R1BQKBNR b KQkq -', 'toKey' => 'rnb1kbnr/pp3ppp/4p3/2pq4/3P4/8/PPPN1PPP/R1BQKBNR w KQkq -'],
        ['uci' => 'g1f3', 'san' => 'Ngf3', 'fromKey' => 'rnb1kbnr/pp3ppp/4p3/2pq4/3P4/8/PPPN1PPP/R1BQKBNR w KQkq -', 'toKey' => 'rnb1kbnr/pp3ppp/4p3/2pq4/3P4/5N2/PPPN1PPP/R1BQKB1R b KQkq -'],
        ['uci' => 'c5d4', 'san' => 'cxd4', 'fromKey' => 'rnb1kbnr/pp3ppp/4p3/2pq4/3P4/5N2/PPPN1PPP/R1BQKB1R b KQkq -', 'toKey' => 'rnb1kbnr/pp3ppp/4p3/3q4/3p4/5N2/PPPN1PPP/R1BQKB1R w KQkq -'],
        ['uci' => 'f1c4', 'san' => 'Bc4', 'fromKey' => 'rnb1kbnr/pp3ppp/4p3/3q4/3p4/5N2/PPPN1PPP/R1BQKB1R w KQkq -', 'toKey' => 'rnb1kbnr/pp3ppp/4p3/3q4/2Bp4/5N2/PPPN1PPP/R1BQK2R b KQkq -'],
        ['uci' => 'd5d6', 'san' => 'Qd6', 'fromKey' => 'rnb1kbnr/pp3ppp/4p3/3q4/2Bp4/5N2/PPPN1PPP/R1BQK2R b KQkq -', 'toKey' => 'rnb1kbnr/pp3ppp/3qp3/8/2Bp4/5N2/PPPN1PPP/R1BQK2R w KQkq -'],
        ['uci' => 'e1g1', 'san' => 'O-O', 'fromKey' => 'rnb1kbnr/pp3ppp/3qp3/8/2Bp4/5N2/PPPN1PPP/R1BQK2R w KQkq -', 'toKey' => 'rnb1kbnr/pp3ppp/3qp3/8/2Bp4/5N2/PPPN1PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'g8f6', 'san' => 'Nf6', 'fromKey' => 'rnb1kbnr/pp3ppp/3qp3/8/2Bp4/5N2/PPPN1PPP/R1BQ1RK1 b kq -', 'toKey' => 'rnb1kb1r/pp3ppp/3qpn2/8/2Bp4/5N2/PPPN1PPP/R1BQ1RK1 w kq -'],
        ['uci' => 'd2b3', 'san' => 'Nb3', 'fromKey' => 'rnb1kb1r/pp3ppp/3qpn2/8/2Bp4/5N2/PPPN1PPP/R1BQ1RK1 w kq -', 'toKey' => 'rnb1kb1r/pp3ppp/3qpn2/8/2Bp4/1N3N2/PPP2PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'b8c6', 'san' => 'Nc6', 'fromKey' => 'rnb1kb1r/pp3ppp/3qpn2/8/2Bp4/1N3N2/PPP2PPP/R1BQ1RK1 b kq -', 'toKey' => 'r1b1kb1r/pp3ppp/2nqpn2/8/2Bp4/1N3N2/PPP2PPP/R1BQ1RK1 w kq -'],
        ['uci' => 'b3d4', 'san' => 'Nbxd4', 'fromKey' => 'r1b1kb1r/pp3ppp/2nqpn2/8/2Bp4/1N3N2/PPP2PPP/R1BQ1RK1 w kq -', 'toKey' => 'r1b1kb1r/pp3ppp/2nqpn2/8/2BN4/5N2/PPP2PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'c6d4', 'san' => 'Nxd4', 'fromKey' => 'r1b1kb1r/pp3ppp/2nqpn2/8/2BN4/5N2/PPP2PPP/R1BQ1RK1 b kq -', 'toKey' => 'r1b1kb1r/pp3ppp/3qpn2/8/2Bn4/5N2/PPP2PPP/R1BQ1RK1 w kq -'],
        ['uci' => 'f3d4', 'san' => 'Nxd4', 'fromKey' => 'r1b1kb1r/pp3ppp/3qpn2/8/2Bn4/5N2/PPP2PPP/R1BQ1RK1 w kq -', 'toKey' => 'r1b1kb1r/pp3ppp/3qpn2/8/2BN4/8/PPP2PPP/R1BQ1RK1 b kq -'],
        ['uci' => 'a7a6', 'san' => 'a6', 'fromKey' => 'r1b1kb1r/pp3ppp/3qpn2/8/2BN4/8/PPP2PPP/R1BQ1RK1 b kq -', 'toKey' => 'r1b1kb1r/1p3ppp/p2qpn2/8/2BN4/8/PPP2PPP/R1BQ1RK1 w kq -'],
    ];

    public function getDescription(): string
    {
        return 'Publish French revision 3: complete named lines only (tarrasch-chistyakov)';
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $introduction = 'Play Black against 1. e4 with ...e6 and ...d5, then challenge White\'s centre, usually with ...c5. Play three complete named main lines from the opening reference: the Classical Variation against 3. Nc3 Nf6, with castling on opposite sides; the Winawer against 3. Nc3 Bb4, where you pin and exchange the c3 knight to weaken White\'s pawns; and the Tarrasch Variation against 3. Nd2, where you strike with ...c5 and recapture with the queen. White replies automatically along the selected line; real opponents can choose other continuations.';
        /** @var list<array{string, string, string, int, string, list<array{uci: string, san: string, fromKey: string, toKey: string}>, list<array{string, string, string}>}> $lines */
        $lines = [
            ['tarrasch-chistyakov', 'Tarrasch Variation: recapture with the queen', 'Ten Black moves. Against 3. Nd2, strike with ...c5, recapture on d5 with the queen, and trade into a solid position with active pieces. Source: Tarrasch Variation, Chistyakov Defense, Modern Line.', 2, self::TARRASCH_SOURCE, self::TARRASCH_FACTS, [
                ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens a diagonal for the bishop on f1.'],
                ['e6', 'Move your pawn from e7 to e6.', 'Prepare to put the d-pawn on d5 with support from this pawn. This is the first move of the French Defense.'],
                ['d4', 'Move your pawn from d2 to d4.', 'White builds a broad pawn centre. Black will challenge it rather than let both pawns advance freely.'],
                ['d5', 'Move your pawn from d7 to d5.', 'Challenge the pawn on e4. Your pawn on e6 supports d5, so White cannot simply win that pawn by capturing it.'],
                ['Nd2', 'Move your knight from b1 to d2.', 'This is the Tarrasch Variation. White defends e4 with the knight on d2 instead of c3. This keeps the c-pawn free to support the centre with c3 and avoids the Winawer pin on a knight on c3.'],
                ['c5', 'Move your pawn from c7 to c5.', 'Strike at d4 straight away. With the knight on d2 rather than c3, White\'s centre has less support, and your c-pawn challenges it before White has finished developing.'],
                ['exd5', 'Move your pawn from e4 to d5, capturing the black pawn.', 'White exchanges on d5 and opens the centre.'],
                ['Qxd5', 'Move your queen from d8 to d5, capturing the white pawn.', 'Recapture with the queen. This is the Chistyakov Defense: you avoid the isolated d-pawn that recapturing with the e-pawn often leads to, and no white knight on c3 is there to attack your queen.'],
                ['Ngf3', 'Move your knight from g1 to f3.', 'White develops the other knight and defends d4.'],
                ['cxd4', 'Move your pawn from c5 to d4, capturing the white pawn.', 'Take on d4. White will win this pawn back, but the exchange removes White\'s central pawn and leaves you with a solid pawn structure.'],
                ['Bc4', 'Move your bishop from f1 to c4.', 'White develops the bishop and attacks your queen on d5.'],
                ['Qd6', 'Move your queen from d5 to d6.', 'Move the queen back to d6, where it is safe and still protects your pawn on d4.'],
                ['O-O', 'Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'White castles first; winning back the d4 pawn can wait.'],
                ['Nf6', 'Move your knight from g8 to f6.', 'Develop your knight towards the centre and the kingside.'],
                ['Nb3', 'Move your knight from d2 to b3.', 'White\'s knight attacks your pawn on d4 to win it back.'],
                ['Nc6', 'Move your knight from b8 to c6.', 'Develop the knight and defend d4 again. You will not keep the pawn, but each exchange on d4 helps your development.'],
                ['Nbxd4', 'Move your knight from b3 to d4, capturing the black pawn.', 'White wins the pawn back with the knight from b3. Material is level.'],
                ['Nxd4', 'Move your knight from c6 to d4, capturing the white knight.', 'Exchange knights on d4.'],
                ['Nxd4', 'Move your knight from f3 to d4, capturing the black knight.', 'White recaptures with the other knight, which now stands strongly in the centre.'],
                ['a6', 'Move your pawn from a7 to a6.', 'Keep White\'s knight and bishop off b5, where the knight would attack your queen and the bishop would give check, and prepare ...b5 to gain queenside space. The named Modern Line ends here: your next tasks are to develop your bishops and castle.'],
            ]],
        ];

        if (2 !== (int) $this->connection->fetchOne("SELECT current_revision FROM lesson WHERE id='french'")) {
            throw new \RuntimeException('French revision 3 must be published directly after revision 2.');
        }
        foreach (self::KEPT_LINES as $lineId => [$plies]) {
            if ($plies !== (int) $this->connection->fetchOne("SELECT ply_count FROM lesson_line WHERE lesson_id='french' AND revision=2 AND id=?", [$lineId])) {
                throw new \RuntimeException('A retained French revision 2 line is missing or changed: '.$lineId);
            }
        }
        $graph = new LessonGraphV1($this->connection);
        $resolved = [];
        foreach ($lines as [$lineId, , , , $source, $facts, $steps]) {
            if (array_column($steps, 0) !== array_column($facts, 'san')) {
                throw new \LogicException('French teaching text and frozen path disagree: '.$lineId);
            }
            $resolved[$lineId] = $graph->resolveImported($facts, $source);
        }

        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) SELECT lesson_id,3,title,?,start_position_id,UTC_TIMESTAMP(),recommended_color FROM lesson_revision WHERE lesson_id='french' AND revision=2", [$introduction]);
        foreach (self::KEPT_LINES as $lineId => [, $order]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) SELECT lesson_id,3,id,title,description,?,ply_count FROM lesson_line WHERE lesson_id='french' AND revision=2 AND id=?", [$order, $lineId]);
            $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) SELECT lesson_id,3,line_id,ply,chess_move_id,hint,explanation FROM lesson_line_step WHERE lesson_id='french' AND revision=2 AND line_id=?", [$lineId]);
        }
        foreach ($lines as [$lineId, $title, $description, $order, , , $steps]) {
            $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('french',3,?,?,?,?,?)", [$lineId, $title, $description, $order, count($steps)]);
            foreach ($steps as $ply => [, $hint, $explanation]) {
                $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('french',3,?,?,?,?,?)", [$lineId, $ply + 1, $resolved[$lineId][$ply] ?? throw new \LogicException('Missing resolved French edge.'), $hint, $explanation]);
            }
        }
        $this->addSql("UPDATE lesson SET current_revision=3 WHERE id='french'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new revision; preserve previous French sessions and progress.');
    }
}
