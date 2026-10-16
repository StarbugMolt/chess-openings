<?php

declare(strict_types=1);

namespace AppMigrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add private accounts, hashed single-use email tokens and persistent authentication limits';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_account (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(254) NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, verified_at DATETIME DEFAULT NULL, UNIQUE INDEX uniq_account_email (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql(<<<'SQL'
CREATE TABLE account_email_token (
    token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    user_id INT NOT NULL,
    purpose VARCHAR(6) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    KEY idx_account_token_user (user_id, purpose),
    KEY idx_account_token_expiry (expires_at),
    CONSTRAINT fk_account_token_user FOREIGN KEY (user_id) REFERENCES user_account(id) ON DELETE CASCADE,
    CONSTRAINT ck_account_token_purpose CHECK (purpose IN ('verify', 'reset'))
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE account_request_limit (
    bucket_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL,
    expires_at BIGINT NOT NULL,
    KEY idx_account_limit_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Account deletion requires an explicit data recovery decision.');
    }
}
