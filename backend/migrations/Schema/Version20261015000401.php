<?php

declare(strict_types=1);

namespace AppMigrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Constrain a published lesson pointer to an existing immutable revision';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        // NULL means unpublished while its first revision is inserted in the lesson transaction.
        $this->addSql('ALTER TABLE lesson MODIFY current_revision SMALLINT UNSIGNED NULL');
        $this->addSql('ALTER TABLE lesson ADD CONSTRAINT fk_lesson_current_revision FOREIGN KEY (id,current_revision) REFERENCES lesson_revision(lesson_id,revision)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Keep published lesson references intact.');
    }
}
