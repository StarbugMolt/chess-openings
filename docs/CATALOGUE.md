# Opening catalogue operations and API

M2c is implemented and locally verified. PHP/Symfony owns downloading, legal replay, DBAL staging, activation, status and rollback. No Node importer, browser-triggered sync or scheduled GitHub Action exists. O2Switch has not been configured and no cron has been installed.

## Local setup and commands

`bash scripts/local-up.sh` starts Docker and applies schema migrations without downloading source data. To populate the opening library and run any authored lesson data migrations in order:

```bash
make catalogue
# Equivalent: schema -> first pinned source catalogue -> lesson data migrations.
docker compose exec -T --user www-data app bin/console app:database:prepare --no-interaction
```

First bootstrap uses the committed immutable SHA `a6189a30dc273ccb21fc2536a9a2fefd5592a67a`. It imports only if no active batch exists; later deployments retain the active catalogue and run only pending schema/lesson migrations. The shared advisory lock covers the database phases. Preparation waits at most 120 seconds for an active import, configurable with `--lock-timeout=0` through `--lock-timeout=600`. Cache clear/warmup follows in the deployment preparation hook. Lesson migrations validate their own source/provenance prerequisites after the catalogue exists.

```bash
# Weekly/manual refresh: resolve upstream master ONCE, download all five files at that SHA.
docker compose exec -T --user www-data app bin/console app:openings:sync --env=prod --no-debug --no-interaction

# Validate, replay and compare with no persistent database changes.
docker compose exec -T --user www-data app bin/console app:openings:sync --dry-run --env=prod --no-debug

# Deterministic upstream revision.
docker compose exec -T --user www-data app bin/console app:openings:sync \
  --source-ref=a6189a30dc273ccb21fc2536a9a2fefd5592a67a --env=prod --no-debug

# Offline fixture/checkout. Explicit provenance is mandatory in local-directory mode.
docker compose exec -T --user www-data app bin/console app:openings:sync \
  --source-directory=tests/Fixtures/Openings/source \
  --source-ref=a6189a30dc273ccb21fc2536a9a2fefd5592a67a --dry-run --force

# Active source revision, last check and ten recent batches (operator CLI only).
docker compose exec -T --user www-data app bin/console app:openings:status

# Restore a retained validated batch, using its ID from status.
docker compose exec -T --user www-data app bin/console app:openings:rollback 1
```

The small committed fixtures are ten records, not a full catalogue. Against a populated database their removal/count safeguards will fire; the example explicitly uses `--dry-run --force` so it can inspect that comparison without replacing production-scale source data. For a real offline import, the operator supplies the correct immutable SHA for their checkout. Local mode records actual file checksums but does not contact GitHub to authenticate the directory's claimed origin.

`app:openings:validate` remains available for the independent in-memory spike; it does not persist or activate anything. Sync/rollback/prepare are CLI commands only. The application PHP container has no Node executable.

## Activation, safeguards and recovery

Sync downloads and legally replays its candidate before acquiring the database-scoped MariaDB advisory lock shared with schema/lesson preparation and rollback. It then reads the current active snapshot, compares safeguards and performs all mutations under that lock. A competing sync/rollback fails before changing status or data; preparation waits for its bounded timeout. Nested bootstrap phases share the outer preparation lock. Lock ownership is checked against the actual MariaDB connection before mutations, each staged insert and cutover; connection loss cannot silently permit an unlocked activation or failure-status write. A release failure preserves the original result/error. Remote mode uses HTTPS with no redirects, bounded metadata/download sizes, timeouts and one immutable SHA. Local files are copied into an equally bounded private workspace before validation. Both modes delete temporary source files on success/failure.

All five files must have valid nonempty TSV records. PGN parsing and PHP legal replay must pass. The graph is capped at 20,000 named records / 250,000 total source plies. An identical semantic graph and importer version is a no-op even if source row order, outer TSV quoting or Git SHA changed. No-op checks update last-check metadata without duplicating snapshots or changing the catalogue revision. Active source provenance continues to identify the immutable snapshot originally imported; the CLI/private log records the newly checked SHA.

Before staging, unattended sync rejects more than **5% removed source identities** or more than **10% fewer records in any ECO-family file**. A renamed record counts as removal/addition; bounded rename suggestions are informational. Inspect with `--dry-run --force` and accept a reviewed change using an explicit `--force`. Force overrides only those count/removal thresholds: it cannot bypass missing files, bad UTF-8/headers, malformed/illegal PGN, conflicting chess facts or path-integrity checks.

DBAL writes at most 200 rows per insert transaction. Shared positions/edges are append-only, and an existing edge's destination/SAN must equal legal replay. A batch stays `staging` while its five file records, named entries and ordered paths are written. Counts, consecutive plies, initial state, path continuity and terminal positions must pass before `ready`. A short transaction locks the singleton, checks the expected previous pointer, retires the old batch, activates the complete new batch and increments the revision. Ordinary read requests capture one batch in a repeatable-read transaction; labels/counts cannot mix snapshots during activation.

Failure before activation returns a nonzero exit code, records bounded operational status and leaves the previous pointer/revision intact. Partial source rows stay invisible; shared facts added before failure are harmless and retained. A process killed without exception handling may leave a `checking` status or `staging/ready` batch; connection loss releases its lock, and a retry builds/validates a new candidate. Optional cleanup/logging failures after activation do not relabel a committed import as failed; an unavailable structured log adds an operator warning to CLI output. Logging failures also cannot mask an original import error. Weekly sync never runs migrations or writes lesson/progress tables.

Rollback accepts retained `ready/retired` validated snapshots, rechecks stored paths and every file's row count, and switches the pointer under the same lock. It issues a **new increasing revision**, so caches notice restoration. It also persists `automatic_updates_held_at`: subsequent cron/manual syncs may validate or report unchanged content, but cannot activate different content until an operator reviews it and runs an explicit successful `--force` import. A changed candidate under the hold returns successful CLI JSON `result: "held"`, persists `last_check_status=held` and writes a notice with the resume instruction; this expected operator pause is distinct from a failed import. `--dry-run --force` previews the hold and safeguards without clearing them; a failed forced import also retains the hold. A successful forced no-op explicitly resumes automatic updates while preserving the revision. Selecting the already-active rollback target remains a no-op. The private status CLI exposes the hold; it is not part of the public API. Code rollback and source rollback do not undo database schema or authored content.

Cleanup retains the active batch and at least two prior successful snapshots. Older snapshots referenced by lesson/provenance FKs are retained in full: child deletion and parent deletion share a transaction, so an FK refusal restores all children too. Failed/interrupted unreferenced snapshots become eligible after seven days. Each non-dry-run sync, including a failed source check while the lock is available, considers at most 25 candidates and removes at most one snapshot, using explicit FK order; it never deletes shared chess facts. Keep authored provenance relationally referenced when adding curriculum tables. Successful cleanup is not a reason to prune a still-needed published lesson revision.

CLI output is JSON plus normal Console migration output where applicable. Sync emits source SHA/mode, checksums, counts, bounded diff/safeguards, batch/revision, elapsed time and PHP peak memory. Last-check status and a bounded UTF-8-safe diagnostic are persisted. Structured failure records include a bounded sanitised message with transport/status details and redact URL credentials, password/token fields and authorization values; full exceptions remain in private CLI output. A dedicated `opening_catalogue` Monolog channel writes JSON to date-rotated `var/log/openings-YYYY-MM-DD.log`, retaining 12 files; general production error logs retain 14 files. Deployment links this directory to private shared logs. Keep raw cron output outside the webroot and configure its host rotation before enabling cron. No updater URL or operational error summaries are exposed publicly.

## Public HTTP contract

| Request | Response |
| --- | --- |
| `GET /api/openings/catalogue-status` | `{catalogueRevision, sourceSha, updatedAt, entryCount}`; SHA/time are null before first activation |
| `GET /api/openings?search=Italian&eco=C&page=1&limit=20` | `{catalogueRevision, items:[{id,sourceId,eco,name,sequenceCount,sequences:[{pgn,eco}]}], page,limit,total}` |

`id` is a snapshot row ID encoded as a string; `sourceId` is the exact-record SHA-256 fingerprint, not a lesson ID. Search matches a case-insensitive substring anywhere in the name, with literal SQL wildcard characters escaped. Identical exact names are grouped into one item with `sequenceCount` and up to three representative source sequences; `total` counts matching names. The group's ID, source fingerprint, ECO and name identify one real representative source row. ECO is an optional uppercase family/code prefix (`A` through `E99`). Page is 1–9999, limit is 1–50 (default 20), search is at most 100 Unicode characters. Sort order is name, then representative source fingerprint. Invalid queries return JSON 400; writes return 405. No playing-colour semantics are inferred from named source records; that belongs to curated courses.

Responses have content/revision-based ETags and `Cache-Control: public, max-age=0, must-revalidate`. Matching `If-None-Match` returns bodyless 304. Public responses do not start sessions, expose raw operational failures or contain progress. Database outages return generic JSON 503. The API returns bounded metadata rather than the complete move graph.

The Vue `/openings` view supports name/ECO search, family shortcuts and paging. Pinia checks status at catalogue navigation/search boundaries, reuses conditional responses and invalidates page caches on a changed revision. A switch between status/list requests causes one bounded retry to obtain a matching pair. Older navigation responses cannot overwrite a newer request. There is no timer, websocket or mid-view update. M3's lesson response independently captures the current catalogue revision alongside its finite authored lesson/revision; the browser retains that payload for the session. The separate `forNewSession()` helper remains available for catalogue-metadata consumers without cancelling an active catalogue view request. Source updates cannot change an active authored path.

## Verified on 2026-10-15

| Actual command/check | Result |
| --- | --- |
| `app:database:prepare --env=prod --no-debug --no-interaction` in the PHP container | Full pinned source activated locally: 3,865 entries, 37,698 plies, 7,983 positions, 8,192 edges; batch 1 / catalogue revision 1; 43.564 seconds for sync, 35,651,584 bytes peak PHP memory |
| `app:openings:sync --env=prod --no-debug --no-interaction` with no source options | Actual upstream resolution and all-file replay succeeded; same SHA/content was `unchanged`, revision stayed 1; 45.633 seconds, 33,558,528 bytes peak memory |
| `bash tests/import/catalogue-test.sh` | 79 checks in a disposable MariaDB DB: full PHP fixture bootstrap, no-op/dry-run, row-order identity, thresholds, forced rename, graph conflicts, staging/cutover/network/PGN/missing-file failures, competing locks, atomic reads, monotonic rollback, logging failures, retention and preserved lesson/progress FKs |
| HTTP tests in that integration suite | Status/search/paging/typed IDs, literal wildcard escaping, public/no-session cache policy, ETags/304, invalid queries and read-only methods |
| `bash scripts/check.sh` | 81 PHPUnit tests / 338 assertions, 34 Vitest tests, 17 desktop/mobile browser checks plus one intentional mobile mouse-drag skip; schema and migration integration, static/build/routing/deployment-helper checks pass |
| `tests/smoke.php ... --expect-db-unavailable` with local DB stopped then restored | Health and catalogue APIs return generic JSON 503 |
| Collaborative browser | Built Apache-served catalogue renders real imported data; Italian/C prefix returns 187 records |

The full graph checksum remains `32b6eadeecd0a9620100f19613b55595e96e5cb72d1addd8cbd49fa9f7b009e3`, matching the M2a replay evidence. These are local PHP 8.3.30/MariaDB 10.11.14 measurements, not hosting benchmarks. Fresh source fixture tests are offline; the two full-source commands above were explicit network checks.

The owner confirms O2Switch is deployed; public HTTPS readiness passed on 2026-10-16. This session has not inspected weekly-cron execution or detailed runtime/DB/path/timezone and sync dry-run/live evidence. Preserve existing verified host configuration. Enable the separate weekly job in [opening-catalogue.crontab.example](../ops/cron/opening-catalogue.crontab.example) only after its deployed gate passes. The separate daily [privacy maintenance](PRIVACY_RETENTION.md) job has its own gate; this local increment installs no hosting cron.

## Review regression verification (2026-10-15)

`bash tests/import/catalogue-test.sh` passed 108 checks in a uniquely named disposable MariaDB database after the final reader/held-result changes. The importer PHPUnit subset passed 80 tests / 328 assertions. Targeted PHPStan analysis of importer/migration services, preparation/schema/lesson commands, migration guards and `Version20261015000410` passed. The corresponding 15 modified PHP files passed targeted PHP CS Fixer checks. New integration cases cover durable cross-process rollback hold/resume, held CLI/status/notice reporting, per-file rollback validation, connection replacement during source fetch, lost-lock activation refusal, Unicode diagnostics, redacted private transport errors, failed-check cleanup and substring search.

The real hosting MariaDB version, SQL mode (including the existing CHECK-regex requirement that `NO_BACKSLASH_ESCAPES` be absent), connection timeouts and cron execution remain host-gate checks. The approved MariaDB 10.11+ baseline has not been lowered.
