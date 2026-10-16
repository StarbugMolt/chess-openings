<?php

declare(strict_types=1);

namespace AppMigrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Hold automatic catalogue activation after an operator rollback until an explicit reviewed force import';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE opening_catalogue_state ADD automatic_updates_held_at DATETIME(6) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('An operator rollback hold must not be silently discarded.');
    }
}
