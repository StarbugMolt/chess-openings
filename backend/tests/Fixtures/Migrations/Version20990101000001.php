<?php

declare(strict_types=1);

namespace AppMigrations\LessonData;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Test-only partial-write failure to prove lesson transactions roll back. */
final class Version20990101000001 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('INSERT INTO migration_test_lesson_result (id,move_id) VALUES (2,1)');
        $this->addSql('INSERT INTO migration_test_lesson_result (id,move_id) VALUES (3,999)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Test-only failure fixture.');
    }
}
