<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Test-only authored path; never discovered by the application lesson factory. */
final class Version20990101000000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $edge = $this->connection->fetchOne('SELECT m.id FROM chess_move m JOIN chess_position p ON p.id=m.from_position_id WHERE p.position_key=? AND m.uci=?', [
            'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq -', 'e2e4',
        ]);
        $this->abortIf(false === $edge, 'Fixture lesson requires the imported e2e4 edge.');
        $this->addSql('INSERT INTO migration_test_lesson_result (id,move_id) VALUES (1,?)', [$edge]);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Test-only forward lesson migration.');
    }
}
