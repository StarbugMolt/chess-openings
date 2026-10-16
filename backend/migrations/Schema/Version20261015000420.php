<?php

declare(strict_types=1);

namespace AppMigrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000420 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bind verification links to registration passwords and deterministically order pending token issuance';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account_email_token ADD pending_password_hash VARCHAR(255) DEFAULT NULL, ADD issued_sequence BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, ADD UNIQUE INDEX uniq_account_token_issued (issued_sequence), ADD INDEX idx_account_token_latest (user_id, purpose, issued_sequence)');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Discarding pending password bindings would change the credentials activated by outstanding verification links.');
    }
}
