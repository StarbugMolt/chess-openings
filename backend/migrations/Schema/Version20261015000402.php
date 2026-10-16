<?php

declare(strict_types=1);

namespace AppMigrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000402 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Label lessons with their recommended playing colour, including Black defences';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE lesson_revision ADD recommended_color CHAR(1) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'w', ADD CONSTRAINT ck_lesson_recommended_color CHECK (recommended_color IN ('w','b'))");
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Retain lesson playing-side metadata.');
    }
}
