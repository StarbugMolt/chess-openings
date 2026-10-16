<?php

declare(strict_types=1);

namespace AppMigrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261016000440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add owner-approved console-granted administrator marker and latest sign-in time to accounts, without backfill';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
ALTER TABLE user_account
  ADD COLUMN admin_granted_at DATETIME DEFAULT NULL,
  ADD COLUMN last_login_at DATETIME DEFAULT NULL
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Administrator grants and account sign-in times must not be silently discarded.');
    }
}
