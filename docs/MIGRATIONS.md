# Database migrations

The owner approved the imported-table schema, canonical keys and refresh lifecycle on 2026-10-15 in [IMPORTER_REVIEW.md](IMPORTER_REVIEW.md). M2b implements the seven imported tables in [Version20261015000100.php](../backend/migrations/Schema/Version20261015000100.php). `Version20261015000410` later adds the nullable `automatic_updates_held_at` rollback hold without rewriting the approved initial migration/reference. The application database starts with **no active catalogue**: singleton ID 1, null batch and revision 0. No catalogue, account or lesson data is seeded by schema migrations.

The current tree includes **nine schema migrations and 19 lesson migrations**, publishing ten courses with 25 current taught lines. Its separately owner-approved private recall addition is [Version20261015000430.php](../backend/migrations/Schema/Version20261015000430.php); see [practice and review](PROGRESS.md) for current behavior/results. Importer, curriculum and earlier migration approvals remain settled.

## Separate execution and histories

| Phase | Namespace / location | Command | Metadata table |
| --- | --- | --- | --- |
| Schema | `AppMigrations\Schema`, `backend/migrations/Schema` | `app:schema:migrate` | `doctrine_schema_versions` |
| Source catalogue | PHP source sync / immutable batches | `app:openings:sync` | Versioned source batch/state tables |
| Authored lesson data | `AppMigrations\LessonData`, `backend/migrations/LessonData` | `app:lessons:migrate` | `doctrine_lesson_versions` |

`app:schema:migrate` wraps the real Doctrine migration engine using the bundle's schema-only configuration. Direct `doctrine:migrations:migrate` and `doctrine:migrations:execute` writes are rejected by the Console entrypoint guard; use the application commands to preserve locking and ordering. `app:lessons:migrate` builds a separate Doctrine dependency factory with only the lesson path, a separate history table, and transactional/all-or-nothing execution. It refuses pending schema migrations and requires an active catalogue and a positive revision before initializing lesson history. Each authored migration must then resolve and validate its specific required edges. Both commands, source sync, rollback and database preparation share one database-scoped MariaDB advisory lock. Nested phases verify and share its ownership; the outer operation releases it in `finally`, preserving the original result if release fails.

```bash
# local-up already performs this after starting MariaDB/PHP; rerunning is a no-op.
docker compose exec -T --user www-data app bin/console app:schema:migrate --no-interaction

# Inspect schema history; this command never discovers lesson migrations.
docker compose exec -T --user www-data app bin/console doctrine:migrations:list

# Schema -> first pinned source catalogue only if absent -> lesson data migrations.
docker compose exec -T --user www-data app bin/console app:database:prepare --no-interaction

# Apply later authored lesson migrations once an active catalogue exists.
docker compose exec -T --user www-data app bin/console app:lessons:migrate --no-interaction
```

Lesson execution fails with an actionable catalogue prerequisite message until real sync activates a source snapshot. The fixture validator remains read-only and cannot satisfy that prerequisite. `scripts/deploy/prepare.sh` uses `app:database:prepare --lock-timeout=120` to wait at most two minutes for an active import, hold the shared lock across schema, first pinned import and lesson data phases, and then clear/warm cache. The accepted lock timeout range is 0–600 seconds; sync, rollback and standalone migration commands otherwise fail fast. A missing singleton state row is a schema-repair error rather than an existing catalogue. Preparation preserves shared sessions/logs. Existing catalogues are retained on later deployments, so deployment does not become a weekly source refresh. No host tasks have been installed.

MariaDB DDL implicitly commits, so schema generation is configured with `transactional: false`, and the initial schema migration explicitly requires MariaDB 10.11+. Its `down()` refuses to erase shared chess facts or source history. After a partial DDL failure, inspect/repair the schema or restore a verified database backup before retrying; a code rollback does not reverse DB changes. Later migrations must preserve referenced facts and published content. Lesson migrations must contain data changes rather than DDL, so their transactions can roll back.

## ORM schema tools

The imported graph/source tables are managed with explicit DBAL SQL, not ORM entity mappings. `ManagedSchemaSubscriber` excludes those seven tables, DBAL-managed `account_email_token` / `account_request_limit`, and both migration-history tables from `doctrine:migrations:diff`, `doctrine:schema:update`, `doctrine:schema:drop` and `doctrine:schema:validate`. Lesson/provenance/progress DBAL tables are protected too. The disposable-DB regression checks every actual non-ORM table against that protection, so a future unprotected table fails verification. The filter applies to the schema-tool operation, not ordinary migration execution, so Doctrine can still see and reuse its history tables. A custom diff filter that would replace this protection is rejected. Review generated migrations before applying them.

```bash
docker compose exec -T --user www-data app bin/console doctrine:migrations:diff --allow-empty-diff --no-interaction
```

With the mapped account entity matching the applied schema, this returns success and “No changes detected”; it generates no migration that drops the imported/token/limit tables. The fresh-DB test also adds an ordinary probe table and verifies that it still appears in the diff, while protected tables do not.

## Private accounts

M5a adds `Version20261015000200`, independently of catalogue import and lesson authoring. It creates ORM-mapped `user_account` and DBAL-managed hashed email tokens/request limits. The token account FK cascades on account deletion; no source FK points into private data. Migration execution remains schema -> optional first source import -> lesson data. The account schema migration refuses destructive down operations. See [ACCOUNTS.md](ACCOUNTS.md) for exact fields, constraints, lifetimes and remaining public-launch work.

## Approved private recall migration

The owner approved the [M5b storage and review design](M5B_REVIEW.md) and its [exact SQL](M5B_SCHEMA_PROPOSAL.sql) on 2026-10-15. Schema `Version20261015000430` adds only nullable `assessment_json VARCHAR(4096)` with ASCII/binary collation to the existing `user_line_completion` ledger. Database checks require a version-1 JSON object, boolean assistance flags and a decisions array of 1–32 items; the application validates every nested decision against the exact retained taught path before writing. This is an additive, nontransactional MariaDB migration; its destructive down operation is refused.

Existing rows remain NULL with unknown recall. No backfill, aggregate table, new source fact or lesson rewrite is performed. The existing user/attempt primary key, exact lesson/revision/line references and account-deletion cascade stay authoritative. A compact assessment and its completion save atomically in one row; normalized retries cannot double-count or replace historical evidence. Existing DBAL table protection covers the added column, and migration parity/constraint tests verify the approved SQL.

Run the ordinary schema command before serving the new completion writes. `app:database:prepare` retains its schema -> first source import only if absent -> lesson order, and `app:lessons:migrate` still rejects pending schema. The private reader derives current recall, review dates and separate earlier-version history from the immutable ledger and retained paths, without storing seeds, raw rejected moves or abandoned-position logs. The private API/page and review policy are documented in [PROGRESS.md](PROGRESS.md).

## Verified integration checks

```bash
bash tests/import/schema-proposal-test.sh
bash tests/import/migrations-test.sh
bash tests/import/catalogue-test.sh
bash tests/import/migrations-test.sh accounts-test.php
bash scripts/check.sh
```

The DB scripts create uniquely named disposable local databases using Compose's root account and drop only their own databases afterward. The migration test also temporarily grants the local app user access and revokes that grant during cleanup. No production host is involved. The first exercises 18 SQL constraint rejection cases against the approved reference DDL. The second runs actual Symfony Console/Doctrine migration code and verifies:

- Fresh schema creation, inactive pointer and repeatable schema execution.
- Exact `SHOW CREATE TABLE` parity with the approved reference, including collations, CHECKs, indexes and FKs.
- Separate discovery and history with adjacent schema/lesson fixture directories.
- Rejection before schema/catalogue/required graph edges exist.
- Ordered schema -> test catalogue fixture -> lesson migration, safe retries and rollback of a partially executed failed lesson.
- Shared-lock exclusion, bounded waits, nested ownership/loss checks and release on success/failure.
- Pending-schema and missing-singleton guards, direct Doctrine write rejection and nontransactional generated DDL.
- Complete DBAL table protection for ORM diff/validation, including the additive rollback hold.
- A real ORM diff that preserves imported/history tables and still detects an ordinary table.
- Refusal of a destructive schema down migration.

The M2b migration test's catalogue row/edge and authored lesson are minimal fixtures. The M2c catalogue suite separately runs actual PHP sync/bootstrap and failure/rollback tests against offline source fixtures. Full pinned network sync and default latest-source no-op were also verified locally; see [CATALOGUE.md](CATALOGUE.md). These importer checks alone do not establish lesson/trainer acceptance; see [current practice verification](PROGRESS.md) and the README.

## Review regression verification (2026-10-15)

`bash tests/import/migrations-test.sh` passed 81 checks after these fixes, including the additive rollback hold, original approved-DDL parity, pending schema/missing state guards, bounded/nested/lost locks, schema validation protection and nontransactional generated DDL. Schema fixture discovery now copies all schema `Version*.php` files dynamically rather than assuming a fixed version count. All databases were disposable; the shared development catalogue was not changed by this verification.

## M5b verification (2026-10-15)

The final `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` passed with schema migration `00430` present: **194 PHP tests / 4,247 assertions**, **219 frontend unit tests** and **45 desktop/mobile browser passes**, plus one intentional mobile mouse-drag skip. Disposable SQL, migration, catalogue, account, lesson, variation and practice suites passed **18/81/108/93/61/109/150** checks respectively. The practice suite includes legacy compatibility, exact assessment attribution, normalized retry conflicts, per-user isolation and current/old revision boundaries. Shared services, the source catalogue and retained learner were preserved. See [PROGRESS.md](PROGRESS.md) for the complete current review/storage verification; these local results do not configure or verify production hosting.
