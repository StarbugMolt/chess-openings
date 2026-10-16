<?php

declare(strict_types=1);

namespace AppMigrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261015000300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add immutable authored lesson revisions, taught paths and private idempotent completions';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE lesson (
    id VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
    current_revision SMALLINT UNSIGNED NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    CONSTRAINT ck_lesson_revision CHECK (current_revision > 0)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE lesson_revision (
    lesson_id VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    revision SMALLINT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    introduction VARCHAR(800) NOT NULL,
    start_position_id BIGINT UNSIGNED NOT NULL,
    published_at DATETIME NOT NULL,
    PRIMARY KEY (lesson_id, revision),
    CONSTRAINT fk_lesson_revision_lesson FOREIGN KEY (lesson_id) REFERENCES lesson(id),
    CONSTRAINT fk_lesson_revision_position FOREIGN KEY (start_position_id) REFERENCES chess_position(id),
    CONSTRAINT ck_lesson_revision_number CHECK (revision > 0)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE lesson_line (
    lesson_id VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    revision SMALLINT UNSIGNED NOT NULL,
    id VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(500) NOT NULL,
    sort_order SMALLINT UNSIGNED NOT NULL,
    ply_count SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (lesson_id, revision, id),
    CONSTRAINT fk_lesson_line_revision FOREIGN KEY (lesson_id, revision) REFERENCES lesson_revision(lesson_id, revision),
    CONSTRAINT ck_lesson_line_length CHECK (ply_count BETWEEN 2 AND 64)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE lesson_line_step (
    lesson_id VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    revision SMALLINT UNSIGNED NOT NULL,
    line_id VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    ply SMALLINT UNSIGNED NOT NULL,
    chess_move_id BIGINT UNSIGNED NOT NULL,
    hint VARCHAR(280) NOT NULL,
    explanation VARCHAR(600) NOT NULL,
    PRIMARY KEY (lesson_id, revision, line_id, ply),
    KEY idx_lesson_step_move (chess_move_id),
    CONSTRAINT fk_lesson_step_line FOREIGN KEY (lesson_id, revision, line_id) REFERENCES lesson_line(lesson_id, revision, id),
    CONSTRAINT fk_lesson_step_move FOREIGN KEY (chess_move_id) REFERENCES chess_move(id),
    CONSTRAINT ck_lesson_step_ply CHECK (ply BETWEEN 1 AND 64)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL);
        $this->addSql(<<<'SQL'
CREATE TABLE user_line_completion (
    user_id INT NOT NULL,
    attempt_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    lesson_id VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    revision SMALLINT UNSIGNED NOT NULL,
    line_id VARCHAR(48) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    playing_color CHAR(1) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    hint_count SMALLINT UNSIGNED NOT NULL,
    completed_at DATETIME NOT NULL,
    PRIMARY KEY (user_id, attempt_id),
    KEY idx_completion_lesson (user_id, lesson_id, revision),
    CONSTRAINT fk_completion_user FOREIGN KEY (user_id) REFERENCES user_account(id) ON DELETE CASCADE,
    CONSTRAINT fk_completion_line FOREIGN KEY (lesson_id, revision, line_id) REFERENCES lesson_line(lesson_id, revision, id),
    CONSTRAINT ck_completion_color CHECK (playing_color IN ('w','b')),
    CONSTRAINT ck_completion_hints CHECK (hint_count <= 64)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Published lesson revisions and private progress require explicit recovery.');
    }
}
