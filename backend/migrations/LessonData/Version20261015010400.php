<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use AppMigrations\Support\LessonGraphV1;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

require_once __DIR__.'/../Support/LessonGraphV1.php';

/** Frozen imported prefix; teaching text is original. Publish later changes as a new revision. */
final class Version20261015010400 extends AbstractMigration
{
    public const SOURCE_PGN = '1. e4 e6 2. d4 d5 3. e5 c5 4. c3 Nc6 5. Nf3 Qb6 6. Bd3 cxd4 7. cxd4 Bd7 8. O-O';

    /** @var list<array{uci: string, san: string, fromKey: string, toKey: string}> */
    public const FACTS = [
        [
            'uci' => 'e2e4', 'san' => 'e4',
            'fromKey' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -',
            'toKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -',
        ],
        [
            'uci' => 'e7e6', 'san' => 'e6',
            'fromKey' => 'rnbqkbnr/pppppppp/8/8/4P3/8/PPPP1PPP/RNBQKBNR b KQkq -',
            'toKey' => 'rnbqkbnr/pppp1ppp/4p3/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -',
        ],
        [
            'uci' => 'd2d4', 'san' => 'd4',
            'fromKey' => 'rnbqkbnr/pppp1ppp/4p3/8/4P3/8/PPPP1PPP/RNBQKBNR w KQkq -',
            'toKey' => 'rnbqkbnr/pppp1ppp/4p3/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -',
        ],
        [
            'uci' => 'd7d5', 'san' => 'd5',
            'fromKey' => 'rnbqkbnr/pppp1ppp/4p3/8/3PP3/8/PPP2PPP/RNBQKBNR b KQkq -',
            'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -',
        ],
        [
            'uci' => 'e4e5', 'san' => 'e5',
            'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3p4/3PP3/8/PPP2PPP/RNBQKBNR w KQkq -',
            'toKey' => 'rnbqkbnr/ppp2ppp/4p3/3pP3/3P4/8/PPP2PPP/RNBQKBNR b KQkq -',
        ],
        [
            'uci' => 'c7c5', 'san' => 'c5',
            'fromKey' => 'rnbqkbnr/ppp2ppp/4p3/3pP3/3P4/8/PPP2PPP/RNBQKBNR b KQkq -',
            'toKey' => 'rnbqkbnr/pp3ppp/4p3/2ppP3/3P4/8/PPP2PPP/RNBQKBNR w KQkq -',
        ],
        [
            'uci' => 'c2c3', 'san' => 'c3',
            'fromKey' => 'rnbqkbnr/pp3ppp/4p3/2ppP3/3P4/8/PPP2PPP/RNBQKBNR w KQkq -',
            'toKey' => 'rnbqkbnr/pp3ppp/4p3/2ppP3/3P4/2P5/PP3PPP/RNBQKBNR b KQkq -',
        ],
        [
            'uci' => 'b8c6', 'san' => 'Nc6',
            'fromKey' => 'rnbqkbnr/pp3ppp/4p3/2ppP3/3P4/2P5/PP3PPP/RNBQKBNR b KQkq -',
            'toKey' => 'r1bqkbnr/pp3ppp/2n1p3/2ppP3/3P4/2P5/PP3PPP/RNBQKBNR w KQkq -',
        ],
        [
            'uci' => 'g1f3', 'san' => 'Nf3',
            'fromKey' => 'r1bqkbnr/pp3ppp/2n1p3/2ppP3/3P4/2P5/PP3PPP/RNBQKBNR w KQkq -',
            'toKey' => 'r1bqkbnr/pp3ppp/2n1p3/2ppP3/3P4/2P2N2/PP3PPP/RNBQKB1R b KQkq -',
        ],
        [
            'uci' => 'd8b6', 'san' => 'Qb6',
            'fromKey' => 'r1bqkbnr/pp3ppp/2n1p3/2ppP3/3P4/2P2N2/PP3PPP/RNBQKB1R b KQkq -',
            'toKey' => 'r1b1kbnr/pp3ppp/1qn1p3/2ppP3/3P4/2P2N2/PP3PPP/RNBQKB1R w KQkq -',
        ],
        [
            'uci' => 'f1d3', 'san' => 'Bd3',
            'fromKey' => 'r1b1kbnr/pp3ppp/1qn1p3/2ppP3/3P4/2P2N2/PP3PPP/RNBQKB1R w KQkq -',
            'toKey' => 'r1b1kbnr/pp3ppp/1qn1p3/2ppP3/3P4/2PB1N2/PP3PPP/RNBQK2R b KQkq -',
        ],
        [
            'uci' => 'c5d4', 'san' => 'cxd4',
            'fromKey' => 'r1b1kbnr/pp3ppp/1qn1p3/2ppP3/3P4/2PB1N2/PP3PPP/RNBQK2R b KQkq -',
            'toKey' => 'r1b1kbnr/pp3ppp/1qn1p3/3pP3/3p4/2PB1N2/PP3PPP/RNBQK2R w KQkq -',
        ],
        [
            'uci' => 'c3d4', 'san' => 'cxd4',
            'fromKey' => 'r1b1kbnr/pp3ppp/1qn1p3/3pP3/3p4/2PB1N2/PP3PPP/RNBQK2R w KQkq -',
            'toKey' => 'r1b1kbnr/pp3ppp/1qn1p3/3pP3/3P4/3B1N2/PP3PPP/RNBQK2R b KQkq -',
        ],
        [
            'uci' => 'c8d7', 'san' => 'Bd7',
            'fromKey' => 'r1b1kbnr/pp3ppp/1qn1p3/3pP3/3P4/3B1N2/PP3PPP/RNBQK2R b KQkq -',
            'toKey' => 'r3kbnr/pp1b1ppp/1qn1p3/3pP3/3P4/3B1N2/PP3PPP/RNBQK2R w KQkq -',
        ],
    ];

    public function getDescription(): string
    {
        return 'Publish French Defense revision 1 for Black: challenge the Advance centre';
    }

    public function up(Schema $schema): void
    {
        /** @var list<array{string, string, string}> $steps */
        $steps = [
            ['e4', 'Move your pawn from e2 to e4.', 'White takes space in the centre and opens a diagonal for the bishop on f1.'],
            ['e6', 'Move your pawn from e7 to e6.', 'Prepare to put the d-pawn on d5 with support from this pawn. This is the first move of the French Defense.'],
            ['d4', 'Move your pawn from d2 to d4.', 'White builds a broad pawn centre. Black will challenge it rather than let both pawns advance freely.'],
            ['d5', 'Move your pawn from d7 to d5.', 'Challenge the pawn on e4. Your pawn on e6 supports d5, so White cannot simply win that pawn by capturing it.'],
            ['e5', 'Move your pawn from e4 to e5.', 'White advances instead of exchanging pawns. The central pawns now block each other; this is the Advance Variation.'],
            ['c5', 'Move your pawn from c7 to c5.', 'Attack the pawn on d4, which supports White\'s advanced pawn on e5. Challenging the base of a pawn chain is a useful plan when the centre is blocked.'],
            ['c3', 'Move your pawn from c2 to c3.', 'White adds another defender to d4. These connected pawns help hold the centre together.'],
            ['Nc6', 'Move your knight from b8 to c6.', 'Develop a piece and add another attacker to d4. Your knight and c-pawn are working towards the same central target.'],
            ['Nf3', 'Move your knight from g1 to f3.', 'White brings a knight into play and defends the advanced pawn on e5.'],
            ['Qb6', 'Move your queen from d8 to b6.', 'The queen attacks b2 and lines up with d4. Your c5-pawn is still in the way; the queen will add pressure to d4 after that pawn is exchanged. This early queen move has a specific purpose.'],
            ['Bd3', 'Move your bishop from f1 to d3.', 'White develops the bishop towards the kingside and clears f1 for possible castling.'],
            ['cxd4', 'Move your pawn from c5 to d4, capturing White\'s pawn.', 'Exchange your c-pawn for White\'s central d-pawn. If White recaptures with the c3-pawn, your queen will have a clear diagonal from b6 to the new pawn on d4.'],
            ['cxd4', 'Move your pawn from c3 to d4, capturing Black\'s pawn.', 'White recaptures and keeps a pawn on d4. Black still has a target for the knight on c6 and queen on b6.'],
            ['Bd7', 'Move your bishop from c8 to d7.', 'Develop the bishop behind your central pawns and support your knight on c6. You have reached this lesson\'s stopping point: a French plan based on pressure against d4. Black still needs to develop the kingside and consider king safety as the game continues.'],
        ];
        if (array_column($steps, 0) !== array_column(self::FACTS, 'san')) {
            throw new \LogicException('French teaching text and frozen path disagree.');
        }
        $edges = (new LessonGraphV1($this->connection))->resolveImported(self::FACTS, self::SOURCE_PGN);
        $start = $this->connection->fetchOne('SELECT id FROM chess_position WHERE position_key=?', [self::FACTS[0]['fromKey']]);
        if (false === $start) {
            throw new \RuntimeException('Missing French lesson starting position.');
        }

        $this->addSql("INSERT INTO lesson (id,current_revision,sort_order) VALUES ('french',NULL,3)");
        $this->addSql("INSERT INTO lesson_revision (lesson_id,revision,title,introduction,start_position_id,published_at,recommended_color) VALUES ('french',1,'French Defense',?,?,UTC_TIMESTAMP(),'b')", ['Learn one French plan against White\'s Advance Variation: support your centre, challenge d4 with your c-pawn, then develop pieces towards that target. You play seven Black moves; White replies automatically along this fixed line. Other White replies need different plans.', $start]);
        $this->addSql("INSERT INTO lesson_line (lesson_id,revision,id,title,description,sort_order,ply_count) VALUES ('french',1,'advance-centre','Challenge the pawn centre','Advance Variation: pressure on d4 with the c-pawn, knight and queen, followed by an exchange and bishop development.',0,14)");
        foreach ($steps as $ply => [$san, $hint, $explanation]) {
            $this->addSql("INSERT INTO lesson_line_step (lesson_id,revision,line_id,ply,chess_move_id,hint,explanation) VALUES ('french',1,'advance-centre',?,?,?,?)", [$ply + 1, $edges[$ply] ?? throw new \LogicException('Missing French lesson edge.'), $hint, $explanation]);
        }
        $this->addSql("UPDATE lesson SET current_revision=1 WHERE id='french'");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Publish another immutable French lesson revision instead.');
    }
}
