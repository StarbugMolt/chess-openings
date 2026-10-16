<?php

declare(strict_types=1);

namespace AppMigrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Authored-fact provenance explicitly approved by the owner; see CURRICULUM_REVIEW.md. */
final class Version20261015000400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Record independent authorship for shared chess facts introduced by curated lessons';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE authored_chess_position (
    position_id BIGINT UNSIGNED PRIMARY KEY,
    authored_in VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_authored_position FOREIGN KEY (position_id) REFERENCES chess_position(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE authored_chess_move (
    move_id BIGINT UNSIGNED PRIMARY KEY,
    authored_in VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_authored_move FOREIGN KEY (move_id) REFERENCES chess_move(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Keep the provenance of published chess facts.');
    }
}
