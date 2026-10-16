-- Owner-approved reference DDL for M2b (2026-10-15). Not a runnable Doctrine migration.
-- Implemented by backend/migrations/Schema/Version20261015000100.php.
-- Validate only in a disposable schema using tests/import/schema-proposal-test.sh.
-- Owner review: docs/IMPORTER_REVIEW.md. Target MariaDB 10.11+ / InnoDB.

CREATE TABLE opening_import_batch (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    upstream_sha CHAR(40) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    content_checksum CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    importer_version VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    status VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME(6) NOT NULL,
    validated_at DATETIME(6) NULL,
    finished_at DATETIME(6) NULL,
    error_summary VARCHAR(512) NULL,
    KEY idx_import_batch_status_created (status, created_at),
    KEY idx_import_batch_content (content_checksum, importer_version),
    CONSTRAINT ck_import_batch_status CHECK (status IN ('staging','ready','active','retired','failed'))
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE opening_import_file (
    batch_id BIGINT UNSIGNED NOT NULL,
    filename CHAR(5) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    byte_count INT UNSIGNED NOT NULL,
    row_count INT UNSIGNED NOT NULL,
    PRIMARY KEY (batch_id, filename),
    CONSTRAINT ck_import_file_name CHECK (filename IN ('a.tsv','b.tsv','c.tsv','d.tsv','e.tsv')),
    CONSTRAINT fk_import_file_batch FOREIGN KEY (batch_id) REFERENCES opening_import_batch(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE chess_position (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    position_key VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    UNIQUE KEY uq_chess_position_key (position_key)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE chess_move (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    from_position_id BIGINT UNSIGNED NOT NULL,
    to_position_id BIGINT UNSIGNED NOT NULL,
    uci VARCHAR(5) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    san VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    UNIQUE KEY uq_chess_move_from_uci (from_position_id, uci),
    KEY idx_chess_move_to (to_position_id),
    CONSTRAINT ck_chess_move_uci CHECK (uci REGEXP '\\A[a-h][1-8][a-h][1-8][qrbn]?\\z'),
    CONSTRAINT fk_chess_move_from FOREIGN KEY (from_position_id) REFERENCES chess_position(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_chess_move_to FOREIGN KEY (to_position_id) REFERENCES chess_position(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE opening_entry (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NOT NULL,
    source_uid CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    source_file CHAR(5) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    source_row INT UNSIGNED NOT NULL,
    eco CHAR(3) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    full_name VARCHAR(512) NOT NULL,
    original_pgn TEXT NOT NULL,
    terminal_position_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_opening_entry_batch_uid (batch_id, source_uid),
    KEY idx_opening_entry_eco (batch_id, eco, id),
    KEY idx_opening_entry_name (batch_id, full_name, id),
    KEY idx_opening_entry_terminal (batch_id, terminal_position_id),
    KEY idx_opening_entry_terminal_fk (terminal_position_id),
    KEY idx_opening_entry_file (batch_id, source_file),
    CONSTRAINT ck_opening_entry_eco CHECK (eco REGEXP '\\A[A-E][0-9]{2}\\z'),
    CONSTRAINT ck_opening_entry_family CHECK (LEFT(eco,1) = UPPER(LEFT(source_file,1))),
    CONSTRAINT ck_opening_entry_row CHECK (source_row > 1),
    CONSTRAINT fk_opening_entry_batch FOREIGN KEY (batch_id) REFERENCES opening_import_batch(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_opening_entry_file FOREIGN KEY (batch_id, source_file) REFERENCES opening_import_file(batch_id, filename) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_opening_entry_terminal FOREIGN KEY (terminal_position_id) REFERENCES chess_position(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE opening_entry_step (
    opening_entry_id BIGINT UNSIGNED NOT NULL,
    ply SMALLINT UNSIGNED NOT NULL,
    chess_move_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (opening_entry_id, ply),
    KEY idx_opening_entry_step_move (chess_move_id),
    CONSTRAINT ck_opening_entry_step_ply CHECK (ply BETWEEN 1 AND 512),
    CONSTRAINT fk_opening_entry_step_entry FOREIGN KEY (opening_entry_id) REFERENCES opening_entry(id) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT fk_opening_entry_step_move FOREIGN KEY (chess_move_id) REFERENCES chess_move(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE opening_catalogue_state (
    id TINYINT UNSIGNED PRIMARY KEY,
    active_batch_id BIGINT UNSIGNED NULL,
    catalogue_revision BIGINT UNSIGNED NOT NULL DEFAULT 0,
    last_checked_at DATETIME(6) NULL,
    last_updated_at DATETIME(6) NULL,
    last_check_status VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NULL,
    last_error_summary VARCHAR(512) NULL,
    CONSTRAINT ck_opening_catalogue_singleton CHECK (id = 1),
    CONSTRAINT fk_opening_catalogue_batch FOREIGN KEY (active_batch_id) REFERENCES opening_import_batch(id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO opening_catalogue_state (id, catalogue_revision) VALUES (1, 0);
