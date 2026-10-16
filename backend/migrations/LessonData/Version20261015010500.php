<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Frozen source facts and original teaching text; publish changes as a new lesson revision. */
final class Version20261015010500 extends AbstractMigration
{
    private const SOURCE_PGN = '1. d4 d5 2. c4 e6 3. Nc3 Nf6 4. Nf3 c5 5. e3 Nc6 6. Bd3 Bd6 7. O-O O-O';

    /** @var list<array{uci: string, san: string, fromKey: string, toKey: string}> */
    private const FACTS = [
        [
            'uci' => 'd2d4',
            'san' => 'd4',
            'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -',
            'toKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -',
        ],
        [
            'uci' => 'd7d5',
            'san' => 'd5',
            'fromKey' => 'rnbqkbnr/pppppppp/8/8/3P4/8/PPP1PPPP/RNBQKBNR b KQkq -',
            'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -',
        ],
        [
            'uci' => 'c2c4',
            'san' => 'c4',
            'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/3P4/8/PPP1PPPP/RNBQKBNR w KQkq -',
            'toKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -',
        ],
        [
            'uci' => 'e7e6',
            'san' => 'e6',
            'fromKey' => 'rnbqkbnr/ppp1pppp/8/3p4/2PP4/8/PP2PPPP/RNBQKBNR b KQkq -',
            'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -',
        ],
        [
            'uci' => 'b1c3',
            'san' => 'Nc3',
            'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/8/PP2PPPP/RNBQKBNR w KQkq -',
            'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -',
        ],
        [
            'uci' => 'g8f6',
            'san' => 'Nf6',
            'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR b KQkq -',
            'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -',
        ],
        [
            'uci' => 'g1f3',
            'san' => 'Nf3',
            'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/2N5/PP2PPPP/R1BQKBNR w KQkq -',
            'toKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -',
        ],
        [
            'uci' => 'c7c5',
            'san' => 'c5',
            'fromKey' => 'rnbqkb1r/ppp2ppp/4pn2/3p4/2PP4/2N2N2/PP2PPPP/R1BQKB1R b KQkq -',
            'toKey' => 'rnbqkb1r/pp3ppp/4pn2/2pp4/2PP4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -',
        ],
        [
            'uci' => 'e2e3',
            'san' => 'e3',
            'fromKey' => 'rnbqkb1r/pp3ppp/4pn2/2pp4/2PP4/2N2N2/PP2PPPP/R1BQKB1R w KQkq -',
            'toKey' => 'rnbqkb1r/pp3ppp/4pn2/2pp4/2PP4/2N1PN2/PP3PPP/R1BQKB1R b KQkq -',
        ],
        [
            'uci' => 'b8c6',
            'san' => 'Nc6',
            'fromKey' => 'rnbqkb1r/pp3ppp/4pn2/2pp4/2PP4/2N1PN2/PP3PPP/R1BQKB1R b KQkq -',
            'toKey' => 'r1bqkb1r/pp3ppp/2n1pn2/2pp4/2PP4/2N1PN2/PP3PPP/R1BQKB1R w KQkq -',
        ],
        [
            'uci' => 'f1d3',
            'san' => 'Bd3',
            'fromKey' => 'r1bqkb1r/pp3ppp/2n1pn2/2pp4/2PP4/2N1PN2/PP3PPP/R1BQKB1R w KQkq -',
            'toKey' => 'r1bqkb1r/pp3ppp/2n1pn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQK2R b KQkq -',
        ],
        [
            'uci' => 'f8d6',
            'san' => 'Bd6',
            'fromKey' => 'r1bqkb1r/pp3ppp/2n1pn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQK2R b KQkq -',
            'toKey' => 'r1bqk2r/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQK2R w KQkq -',
        ],
        [
            'uci' => 'e1g1',
            'san' => 'O-O',
            'fromKey' => 'r1bqk2r/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQK2R w KQkq -',
            'toKey' => 'r1bqk2r/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQ1RK1 b kq -',
        ],
        [
            'uci' => 'e8g8',
            'san' => 'O-O',
            'fromKey' => 'r1bqk2r/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQ1RK1 b kq -',
            'toKey' => 'r1bq1rk1/pp3ppp/2nbpn2/2pp4/2PP4/2NBPN2/PP3PPP/R1BQ1RK1 w - -',
        ],
    ];

    public function getDescription(): string
    {
        return "Publish a beginner Queen's Gambit lesson: central pressure, development and castling";
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $steps = [
            ['Move your pawn from d2 to d4.', 'Take space in the centre and open a path for the bishop on c1.'],
            ['Move your pawn from d7 to d5.', 'Black takes central space too and stops your d-pawn from moving straight ahead.'],
            ['Move your pawn from c2 to c4.', "Challenge Black's central pawn from the side. This is the Queen's Gambit: Black could capture this pawn, but does not have to."],
            ['Move your pawn from e7 to e6.', 'Black declines the offered pawn for now, supports d5 and opens a path for the bishop on f8.'],
            ['Move your knight from b1 to c3.', "Develop a knight towards the centre and add pressure to Black's pawn on d5."],
            ['Move your knight from g8 to f6.', 'Black develops a knight, supports the pawn on d5 and controls e4.'],
            ['Move your knight from g1 to f3.', 'Bring your other knight into play. It defends d4 and clears g1 for castling.'],
            ['Move your pawn from c7 to c5.', 'Black challenges your pawn on d4. Both sides now have pawns that could capture each other: this is central tension. Neither side must capture immediately.'],
            ['Move your pawn from e2 to e3.', 'Support d4 and open the diagonal for your bishop on f1. In this line you keep the central tension while developing.'],
            ['Move your knight from b8 to c6.', 'Black develops the second knight and adds another attacker to d4. The pawn on e3 and knight on f3 help you defend it.'],
            ['Move your bishop from f1 to d3.', 'Put the bishop on an open diagonal. With f1 and g1 clear, your king can prepare to castle.'],
            ['Move your bishop from f8 to d6.', 'Black develops the bishop towards the centre and clears f8 for castling.'],
            ['Castle: move your king from e1 to g1. The rook moves from h1 to f1 automatically.', 'Move your king towards the shelter of its pawns and bring the rook closer to the centre. Castling moves the king and rook together.'],
            ['Castle: move your king from e8 to g8. The rook moves from h8 to f8 automatically.', 'Black castles too. Both sides have developed knights and a bishop and sheltered their kings. This lesson ends here; the central pawn decisions and the rest of the game are still ahead.'],
        ];
        $edges = (new LessonGraphV1($this->connection))->resolveImported(self::FACTS, self::SOURCE_PGN);
        $start = $this->connection->fetchOne('SELECT from_position_id FROM chess_move WHERE id=?', [$edges[0]]);
        if (false === $start || count($edges) !== count($steps)) {
            throw new \LogicException("Incomplete Queen's Gambit teaching path.");
        }

        $this->addSql('INSERT INTO lesson (id,current_revision,sort_order) VALUES (?,NULL,?)', ['queens-gambit', 4]);
        $this->addSql('INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES (?,1,?,?,?,UTC_TIMESTAMP(),?)', [
            'queens-gambit',
            "Queen's Gambit",
            'Learn the Queen\'s Gambit as White against one declined response. Challenge the centre with c4, develop both knights and a bishop, then castle. You play seven White moves; Black replies automatically. The pawns keep their central tension in this line: capturing is a choice, not an obligation. This is one introductory line, not every possible Black response.',
            (int) $start,
            'w',
        ]);
        $this->addSql('INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES (?,1,?,?,?,?,?)', [
            'queens-gambit',
            'declined-development',
            'Develop and castle',
            'Seven White moves against the Semi-Tarrasch symmetrical setup. Learn central pressure, development and castling without a forced pawn exchange.',
            0,
            count($steps),
        ]);
        foreach ($steps as $ply => [$hint, $explanation]) {
            $this->addSql('INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES (?,1,?,?,?,?,?)', [
                'queens-gambit',
                'declined-development',
                $ply + 1,
                $edges[$ply],
                $hint,
                $explanation,
            ]);
        }
        $this->addSql('UPDATE lesson SET current_revision=1 WHERE id=?', ['queens-gambit']);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish a new lesson revision; preserve previous sessions and completions.');
    }
}
