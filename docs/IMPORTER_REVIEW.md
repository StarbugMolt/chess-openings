# Importer spike and proposed persistence decisions

**Approved by the owner on 2026-10-15:** canonical keys, imported tables and the update lifecycle below. The owner explicitly authorised continuing with M2b, satisfying [AGENTS.md rule 7](../AGENTS.md). M2a–M2c are now locally verified, including complete sync/status/rollback and catalogue browsing; see [MIGRATIONS.md](MIGRATIONS.md) and [CATALOGUE.md](CATALOGUE.md). Accounts and authored curriculum remain later work.

## Implemented evidence

`app:openings:validate` accepts either a local five-file source directory or an immutable upstream SHA. The Symfony HttpClient fetcher downloads `a.tsv` through `e.tsv` from that exact SHA into a private temporary directory, with HTTPS, no redirects, timeouts and a 4 MiB limit per file. Downloads are removed on success/failure. Local mode reports hashes without inventing remote provenance. The command does not connect to or write to the database. It is not the planned weekly `app:openings:sync` command.

The TSV parser requires the exact three-column header, UTF-8, ECO codes, names, PGNs and proper doubled-quote TSV escaping. It rejects duplicate exact records, malformed quoting, extra fields and empty files. The bounded main-line PGN parser supports move numbers, SAN, castling, brace/semicolon comments, numeric annotation glyphs, `!?` suffixes and terminal results. Tags, recursive variations, inconsistent move numbers, malformed SAN and trailing moves after a result fail explicitly. Legal replay requires canonical SAN and identifies source file, logical record row and failing ply. This intentionally supports named opening main lines, not arbitrary uploaded PGN games.

`ChessMoveReplayerInterface` isolates **p-chess/chess 1.2.0** (MIT; exact package revision is in Composer's lock file). Its raw FEN includes phantom en-passant targets; the adapter normalises them using legal en-passant moves, including pins. The [upstream library](https://github.com/p-chess/chess) began as a chess.js port, so the two libraries are separate implementations with related ancestry, not fully independent historical oracles. Shared saved fixtures cover the specific rules cases in [their provenance guide](../backend/tests/Fixtures/Openings/README.md).

An in-memory graph deduplicates full legal position keys and `(from key, UCI)` edges, checks edge destination/SAN consistency and keeps every named ordered source path. Sorting positions, edges and paths before hashing makes the graph checksum independent of traversal order. No curriculum or progress is generated.

Verified full-source command:

```bash
docker compose exec -T --user www-data app bin/console app:openings:validate \
  --source-sha=a6189a30dc273ccb21fc2536a9a2fefd5592a67a --env=prod --no-debug
```

| Result | Measured value |
| --- | --- |
| Upstream revision | [`a6189a30dc273ccb21fc2536a9a2fefd5592a67a`](https://github.com/lichess-org/chess-openings/tree/a6189a30dc273ccb21fc2536a9a2fefd5592a67a) |
| Named entries / replayed plies | 3,865 / 37,698 |
| Canonical positions / move edges | 7,983 / 8,192 |
| Graph SHA-256 | `32b6eadeecd0a9620100f19613b55595e96e5cb72d1addd8cbd49fa9f7b009e3` |
| Wall time including downloads | 39.320 seconds |
| Peak PHP memory | 48,238,592 bytes (46 MiB) |
| Database writes | 0 |

This is one local PHP 8.3.30 container run, not an O2Switch performance guarantee. Full-file SHA-256 values are recorded in [provenance.json](../backend/tests/Fixtures/Openings/provenance.json); tests use only ten selected records. Full-source replay is an explicit network check, not part of normal offline CI.

Also verified with `DATABASE_URL=mysql://unused:unused@127.0.0.1:1/unused`: local fixture validation succeeds with 10 entries, 93 plies, 81 positions, 80 edges, graph SHA-256 `22129f13270ba55297b88fdf13491ecc5882f9d1929571162992bb1edf054d23`. This demonstrates that the spike does not require a reachable DB.

## Approved canonical state and source identity

Store the four space-separated FEN fields **placement, side to move, castling rights in `KQkq` order, legal en-passant target or `-`** as `VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin`. Do not include half-move/full-move counters in identity; retain them while replaying each path. This represents opening legal move state, not repetition/draw history. Moving a rook/king back does not restore rights. Positions with a pinned en-passant pawn equal the corresponding `-` position; legally capturable targets remain distinct.

Use lower-case UCI, including promotion suffix, as `VARCHAR(5) ascii_bin`. SAN is a canonical property of an edge's source position and UCI. A conflicting destination or SAN aborts the entire batch; never overwrite the previous edge to hide the conflict.

The approved `source_uid` is SHA-256 of an ordered JSON array `[eco, name, original_pgn]`, encoded as UTF-8 with PHP `JSON_UNESCAPED_UNICODE` and its normal slash escaping. Parsed values are preserved exactly: no case folding, trimming or Unicode normalisation. File quoting, CRLF versus LF outside fields and row ordering do not change identity. Changes inside PGN/name values do. The source filename and row are provenance fields, not identity. Names/ECO may repeat. Identical records are rejected. A rename/path correction is removal/addition for identity; heuristic rename suggestions never change lesson IDs. Later changes to this policy need an explicit version/migration plan that preserves historical identity.

## Approved imported tables and constraints

All numeric IDs below use `BIGINT UNSIGNED`, timestamps use UTC `DATETIME(6)`, text uses `utf8mb4`, and all foreign keys use **ON DELETE RESTRICT / ON UPDATE RESTRICT** unless the child-only cleanup noted below is explicitly implemented. No catalogue deletion cascades into lessons or progress. Application checks complement SQL constraints; MariaDB cannot enforce path continuity with ordinary FKs alone.

| Table | Concrete columns | Keys and integrity |
| --- | --- | --- |
| `opening_import_batch` | `id`, `upstream_sha CHAR(40) ascii_bin`, `content_checksum CHAR(64) ascii_bin`, `importer_version VARCHAR(32) ascii_bin`, `status VARCHAR(16) ascii_bin`, `created_at`, nullable `validated_at`, nullable `finished_at`, nullable `error_summary VARCHAR(512)` | PK `id`; index `(status,created_at)`; CHECK status in `staging,ready,active,retired,failed`; checksum indexed, not unique so a source snapshot can be rebuilt for a changed importer |
| `opening_import_file` | `batch_id`, `filename CHAR(5) ascii_bin`, `sha256 CHAR(64) ascii_bin`, `byte_count INT UNSIGNED`, `row_count INT UNSIGNED` | PK `(batch_id,filename)`; FK batch; CHECK filename in `a.tsv` through `e.tsv`; require all five before validation |
| `chess_position` | `id`, `position_key VARCHAR(128) ascii_bin` | PK `id`; UNIQUE `position_key`; append-only shared facts, no clocks |
| `chess_move` | `id`, `from_position_id`, `to_position_id`, `uci VARCHAR(5) ascii_bin`, `san VARCHAR(16) ascii_bin` | PK `id`; UNIQUE `(from_position_id,uci)`; index `to_position_id`; FKs both positions; UCI shape CHECK; DBAL validates existing destination/SAN on duplicate insert |
| `opening_entry` | `id`, `batch_id`, `source_uid CHAR(64) ascii_bin`, `source_file CHAR(5) ascii_bin`, `source_row INT UNSIGNED`, `eco CHAR(3) ascii_bin`, `full_name VARCHAR(512)`, `original_pgn TEXT`, `terminal_position_id` | PK `id`; UNIQUE `(batch_id,source_uid)`; indexes `(batch_id,eco,id)`, `(batch_id,full_name,id)`, `(batch_id,terminal_position_id)` and terminal FK; FKs batch, terminal position and composite `(batch_id,source_file)` to imported file; ECO shape/family CHECK; row > 1 |
| `opening_entry_step` | `opening_entry_id`, `ply SMALLINT UNSIGNED`, `chess_move_id` | PK `(opening_entry_id,ply)`; index `chess_move_id`; FKs entry and edge; CHECK ply between 1 and 512; verify consecutive plies, initial state, continuity and terminal position before activation |
| `opening_catalogue_state` | `id TINYINT UNSIGNED`, nullable `active_batch_id`, `catalogue_revision BIGINT UNSIGNED DEFAULT 0`, nullable `last_checked_at`, nullable `last_updated_at`, nullable `last_check_status VARCHAR(16) ascii_bin`, nullable `last_error_summary VARCHAR(512)` | PK `id`, CHECK `id=1`; FK active batch; singleton created in schema migration; active pointer and revision changed in one short transaction |

`opening_catalogue_change` is deferred: derive a bounded diff for logs/status first. Do not add a table until it has a concrete consumer. Use prefix search (`name LIKE 'term%'`) and bounded pagination initially; substring search may scan the active few thousand rows until measurements justify full text search. The full-name index fits MariaDB 10.11's 3,072-byte InnoDB key limit; verify the actual host before deployment. Doctrine ORM must not generate/drop these DBAL-managed tables: use an explicit managed-table/schema filter and test the real diff command.

The approved reference DDL is [IMPORTER_SCHEMA_PROPOSAL.sql](IMPORTER_SCHEMA_PROPOSAL.sql), implemented by [Version20261015000100.php](../backend/migrations/Schema/Version20261015000100.php). `bash tests/import/schema-proposal-test.sh` exercises 18 FK/unique/CHECK rejection cases in a disposable database. `bash tests/import/migrations-test.sh` verifies actual migrated DDL, separate lesson history, ordering/retry, transactional failure and safe ORM diffs. Both are included in `scripts/check.sh`, alongside the M2c catalogue failure/rollback suite. The local development database now contains the full pinned active source catalogue at revision 1; authored courses/accounts remain absent.

## Approved refresh, rollback and migration lifecycle

1. Acquire one named MariaDB advisory lock on the same DBAL connection for sync, operator rollback and deployment preparation. Release in `finally`; connection loss releases it. The singleton pointer is also locked in the activation transaction. Weekly sync never runs migrations.
2. Resolve upstream to one immutable SHA (or accept an explicit SHA/local source), fetch bounded files, parse/replay/validate and compute sorted semantic content checksums. The full file hashes remain provenance. Same validated content and importer version is a successful no-op, even if the upstream Git SHA changed; update last-check status without incrementing revision.
3. Compared with an existing active catalogue, abort unattended activation on **any missing/empty file**, any illegal/conflicting record, **more than 5% removed source identities** or **more than 10% fewer entries in any ECO-family file**. First bootstrap has no prior-count comparison. Counts include renames as removals. An operator may accept a reviewed diff using an explicit override; the decision and bounded summary are logged. Never invent an override for failed chess validation.
4. Append shared nodes/edges with prepared DBAL batch writes. Commit a new `staging` source snapshot in bounded chunks; API queries always scope to one captured active batch. After five-file counts, ordered paths and graph integrity pass, mark it `ready`. A failed/retried import can leave harmless shared facts, but cannot expose partial labels or alter authored content.
5. In one short transaction, lock the singleton, verify the expected prior pointer, switch to the ready batch, increment the monotonic revision, timestamp it and retire the old active batch. Read APIs capture batch/revision once per request; use a consistent transaction/snapshot for multi-query responses. Vue refreshes metadata only on next navigation/new session; an active lesson retains its graph/revision.
6. Operator rollback chooses a retained ready/retired batch under the same lock, switches the pointer and **increments** the revision again. Code rollback does not undo schema, content or DB data. Keep at least the active plus **two prior successful snapshots**, and all snapshots referenced by immutable lesson provenance. Failed staging snapshots older than seven days can have child steps/files/entries removed in explicit FK order; never delete shared facts or referenced historical rows automatically.
7. Separate Doctrine migration configurations and metadata tables for schema and lesson data. Deployment preparation acquires the shared lock and runs **schema -> first pinned catalogue sync only if no active catalogue -> lesson migrations -> cache warmup**. A failed required phase prevents release activation. Weekly source refresh changes no lessons/progress and runs neither migration stream. Prove first bootstrap and retry on a fresh DB before enabling either host cron.

Proposed lesson policy for the subsequent curriculum milestone: stable lesson/line UUIDs, immutable published `lesson_revision` and revision-scoped ordered paths/notes. A session pins that revision. Existing retained revisions remain valid for submissions; retired/unavailable revisions receive an explicit conflict response. Progress is keyed to lesson/line revision, with explicit migration decisions for any carried-forward mastery. Source provenance stores the source batch UID/SHA and record fingerprint independently of active catalogue labels. No automatic pruning of referenced versions. Concrete lesson/account DDL and the first Italian lesson text remain a separate review before publishing curriculum.

## Remaining implementation

M2b's constraints/migration separation and M2c's complete sync/status/rollback, staging/cutover, failure/no-op tests and catalogue API/cache behavior are implemented. `app:openings:validate` remains the in-memory spike command. Next are account foundation and reviewed curriculum/training. The O2Switch target, deployment polling and weekly source cron still need host configuration and verification.
