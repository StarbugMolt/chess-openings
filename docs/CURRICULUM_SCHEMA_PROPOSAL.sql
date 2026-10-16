-- Owner approved 2026-10-15. Reference DDL implemented by Version20261015000400.
CREATE TABLE authored_chess_position (
    position_id BIGINT UNSIGNED PRIMARY KEY,
    authored_in VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_authored_position FOREIGN KEY (position_id) REFERENCES chess_position(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE authored_chess_move (
    move_id BIGINT UNSIGNED PRIMARY KEY,
    authored_in VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_authored_move FOREIGN KEY (move_id) REFERENCES chess_move(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
