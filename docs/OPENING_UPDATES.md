# PHP opening importer and weekly catalogue refresh

> **Status: M2c sync/status/rollback and catalogue browsing are implemented and locally verified. No hosting cron is installed.** Full pinned PHP/DBAL import and default latest-source no-op passed locally; see [CATALOGUE.md](CATALOGUE.md) and [MIGRATIONS.md](MIGRATIONS.md). Deploy and verify the command on the actual O2Switch account before installing the weekly task. Merely committing this document does **not** install a cron.
>
> **Owner's decision:** the complete importer and scheduler integration belong to the **Symfony/PHP backend**. No Node/TypeScript opening-import script, GitHub Actions scheduled importer, SSH-delivered bundles or remote worker is required. Vue/TypeScript and chess.js remain frontend-only. CI can still build Vue and run tests.

> **Deployment addition (2026-10-15):** application deployment will reuse the existing template's host-pull/build controller, as specified in [implementation plan section 14](IMPLEMENTATION_PLAN.md#14-automatic-o2switch-deployment). Its deployment-polling cron is separate from the single weekly opening-data cron below. Node may build Vue on hosting; catalogue importing remains PHP-only.

## Meaning of "latest news"

Fetch the latest **official Lichess named-opening data**: new named lines, renamed openings, corrected PGN paths, aliases and transpositions. This is **not** a general chess-news or master-games feed. The data source is the CC0 [lichess-org/chess-openings](https://github.com/lichess-org/chess-openings) repository, whose **root** files `a.tsv` ... `e.tsv` contain columns `eco`, `name`, `pgn`. Do not assume generated `dist/` files exist in the checkout.

Lichess labels are **catalogue metadata**; source changes do not constitute an authoritative modification of any of our manually maintained training lessons.

## Confirmed architecture

```text
O2Switch / cPanel opening-data cron — weekly (single opening-data scheduler)
    |
    v
PHP CLI: backend/bin/console app:openings:sync
    |
    +--> Symfony HttpClient: resolve upstream Git SHA + download all 5 TSVs at that SAME SHA
    |
    +--> PHP TSV parser + PHP chess rules/notation library
    |      validate SAN/PGN, replay every move, compute UCI, SAN, canonical positions
    |
    +--> compare old/new; lock; stage SQL data (Doctrine DBAL)
    |      validate foreign keys, integrity and unacceptable large drops
    |
    +--> atomic promotion of catalogue revision or rollback on failure
    |
    v
MariaDB — imported knowledge only (NOT curated lessons or user progress)
    |
    v
Symfony API revision/ETag -> Vue/Pinia refreshes opening metadata on next navigation/new session
```

### PHP chess-rules dependency

**Preferred candidate: [p-chess/chess](https://github.com/p-chess/chess)**, a MIT-licensed PHP port originally based on chess.js, available through Composer (`composer require p-chess/chess`). Packagist shows v1.2.0 (2026-09-01), PHP `^8.1`, `ext-ctype`. It supports playing SAN moves, legal-move validation and FEN export. It is a candidate to **confirm via implementation spike**, not a promise that it perfectly handles every source PGN out of the box.

- Encapsulate it behind a `ChessMoveReplayerInterface` / `OpeningLineParser` adapter. Do not tightly couple import/application services to the vendor's API.
- Use a robust PHP TSV parser (`fgetcsv` with `\t` delimiter and configured quoting/escaping), and a dedicated PGN movetext tokenizer. Handle SAN, castling, checks/mates, annotations, move numbers, results, escaped text/UTF-8 and malformed records. Lichess opening TSV values are typically single-line main lines, not arbitrary annotated studies; nevertheless reject unexpected input instead of silently skipping moves.
- Use the chess library to **legally replay** tokens; convert each move to canonical SAN, UCI (`from+to+promotion`) and resulting FEN. Implement and test canonical position keys as board placement, side to move, castling rights and **legally capturable en-passant**; omit FEN move clocks. Never assume a raw FEN string or the library's E.P. field is already canonical.
- Build shared **golden fixtures**: PHP output must match frontend chess.js replay results for normal lines, two equivalent transpositions, castling, ambiguous SAN, promotion, checkmate and en-passant. Use these tests to decide whether `p-chess/chess` meets the importer contract; document and explicitly choose an alternative maintained PHP library only if the spike fails.
- Node is only needed for **Vue builds/frontend tests**, including the approved host-side frontend build. It is not used for catalogue import/refresh or for serving production requests.

References: [p-chess/chess on Packagist](https://packagist.org/packages/p-chess/chess), [chess.js](https://github.com/jhlywa/chess.js).

## Backend command specification

The implemented Symfony Console command uses HttpClient, DBAL and the shared MariaDB advisory lock for the complete fetch-parse-validate-stage-activate sequence:

```bash
# Direct command for deployed/manual verification before cron installation.
php backend/bin/console app:openings:sync --env=prod --no-debug --no-interaction

# Safe inspection without writes.
php backend/bin/console app:openings:sync --dry-run --env=prod --no-debug --no-interaction

# Deterministic fixture/offline run for testing, no GitHub access.
php backend/bin/console app:openings:sync --source-directory=/absolute/path/to/tsv --source-ref=a6189a30dc273ccb21fc2536a9a2fefd5592a67a --dry-run

# Inspect active version, last check and status.
php backend/bin/console app:openings:status --env=prod
```

These commands exist. `--source-ref` accepts only a full immutable SHA; local-directory mode requires it as explicit provenance. `--force` accepts reviewed count/removal thresholds and cannot bypass invalid source/chess data. Normal scheduled runs need only the production environment. `app:openings:rollback <batch-id>` restores a retained validated batch with a new monotonic revision. See [CATALOGUE.md](CATALOGUE.md) for exact commands, measured results, retention and API behavior. There is **no public HTTP endpoint** that triggers updates.

### Weekly O2Switch cron

After the deployed verification gate passes, create one job in **O2Switch cPanel → Tâches Cron**. The [sample crontab](../ops/cron/opening-catalogue.crontab.example) calls the installed log wrapper, which invokes only the PHP importer and bounds its private console log independently of deployment-log pruning. Typical example:

```cron
# Every Monday at 05:17 in the server cron timezone (verify O2Switch/cPanel TZ).
17 5 * * 1 /bin/bash /home/ACCOUNT/apps/chess-openings/control/opening-sync.sh /opt/alt/php83/usr/bin/php
```

**Replace** the placeholder account directory, actual Symfony path and PHP executable with values verified on the deployed host. `php83` is an **example**, not confirmation that production is on 8.3. The log directory must exist, be writable and be **outside the public webroot**. Check the host's timezone before interpreting the scheduled hour; cPanel may use the server clock, not Europe/Paris. Configure optional failure email/monitoring. Do not use a web-facing `wget` URL; this is a CLI cron with PHP's CLI limits. A weekly job is much lighter than any persistent scheduler service.

The template moves the built backend into each release root, so the stable deployed command is `current/bin/console`; repository-local commands still use `backend/bin/console`. Use a shared/DB import lock across releases and coordinate it with deployment migration preparation. The deployment poller must not run weekly sync on every code release.

O2Switch specifically documents `php /absolute/path/to/cron.php` and versioned CLI binary paths for cron jobs: https://faq.o2switch.fr/cpanel/outils-avances/tache-planifiee-cron/ and https://faq.o2switch.fr/cpanel/logiciels/hebergement-php-multi-version/.

### Processing algorithm

1. **Lock and inspect state:** acquire a single import lock. Read `opening_catalogue_state` (current active batch/revision; last successful check). Prevent overlapping/manual competing imports.
2. **Resolve upstream SHA:** using Symfony HttpClient, fetch GitHub repository metadata/branch head, verify TLS/status/timeout and pin one immutable commit SHA. If GitHub is unavailable, exit nonzero and keep old data. Respect API rate limits; use conditional requests or optional token only if necessary, stored in environment secrets.
3. **Check for change:** ideally compare file content hashes for `a.tsv` ... `e.tsv`; the branch SHA may change due to unrelated files. Unchanged content => successful no-op, update `last_checked_at` without incrementing `catalogue_revision`. Never mark failed checks successful.
4. **Download consistent snapshot:** fetch all five TSV files by **same SHA** into a bounded, private temporary directory. Validate size caps, checksum, exact header/columns, UTF-8, row count, duplicate/repeated rows and valid ECO.
5. **PHP replay:** stream TSV lines; parse source PGN, replay legal moves through the selected PHP chess library. Generate full ordered edge paths and canonical position keys. Fail the entire batch on a required invalid record, or apply a deliberate reviewed quarantine policy—not silent skipping.
6. **Diff and safeguards:** compare current active source entries with candidate entries. Record additions, removals, corrections and renames; check threshold for anomalous drops, missing files, changed edge target or extra data. Surface suspected new teaching variations as **review candidates**, not automatically enabled lesson moves.
7. **Stage and validate:** write versioned source entries/paths (or equivalent safely staged representation), append new shared positions/edges without deleting lesson-referenced records, validate integrity, and record the pending import batch. Use bounded DBAL inserts/upserts; do not use an unbounded one-shot ORM flush.
8. **Atomic activation:** promote a complete validated catalogue version (e.g. switch an `active_batch_id` in `opening_catalogue_state` within one transaction). Keep a known-good previous revision for rollback. **Important:** updating `import_batch_id` in-place on source rows is *not* versioning; snapshot semantics must be explicit and testable.
9. **Reactivity:** commit a monotonic `catalogue_revision`, UTC `updated_at`, source hash/SHA and change-summary metadata. Invalidate relevant cache by version; leave `lesson_revision` unchanged.
10. **Report:** log import ID, SHA, counts, no-op/updated/failure, run duration, and actionable exception. On any failure use nonzero exit code and do not promote partial data. Keep log and snapshot retention bounded. Do not leak private URLs, tokens or account data.

**Database access:** the command must operate on the existing **imported chess-data layer** only; user accounts/progress and curated lessons are not writable from this service. Prefer strict service boundaries and tests rather than relying solely on developer discipline.

## Schema and doctrine migration relationship

Retain our **three layers**:

1. **Imported knowledge:** `chess_position`, `chess_move`, `opening_entry`, `opening_entry_step`, source import batches and active catalogue revision; imported source labels may be versioned independently.
2. **Curated lessons:** `lesson`, `lesson_line`, `lesson_line_step`, `lesson_note` and `lesson_revision`. They are created/modified intentionally via **version-controlled Doctrine PHP data migrations**, **not** by the weekly importer.
3. **Private users:** accounts, line/position mastery, optional minimal practice sessions. Do not modify them during refresh.

Proposed additional/import tables:

| Table | Purpose |
| --- | --- |
| `opening_import_batch` | Import provenance (SHA, file hashes, timestamps, validation, failure or success summary) |
| `opening_catalogue_state` | Single active batch pointer, monotonic revision, last checked/updated times |
| `opening_catalogue_change` (optional) | Small audit/diff of added, renamed, removed entries for review |

**Fresh database ordering:** Doctrine **schema migrations** -> first `app:openings:sync` (or `--source-directory` for deterministic bootstrap) -> Doctrine **lesson data migrations** -> users/progress. The importer must **not** run schema or lesson migrations every week. Implementation must document and test staged Doctrine migration configuration/ordering so a fresh DB cannot try to insert lesson steps before their chess edges exist.

Consider retaining source `opening_entry` history by batch (or explicit versioned labels), with an atomic active pointer. New shared positions/moves can be append-only; **never delete** facts referenced by authored content. Do not use an opening's ECO or display name as a unique identifier.

## What "the code should react to it" means

**Symfony API**
- Opening catalogue/search/recognition endpoints query only the **active** source revision.
- Expose `GET /api/openings/catalogue-status` with `catalogueRevision`, `updatedAt`, `sourceSha` and optional change summary. Return versioned ETags/cache keys.
- Lesson graph responses expose `catalogueRevision` **and independently managed** `lessonRevision`. Apply contextual lesson names when appropriate rather than overwriting the authored name with the latest source alias.
- New source-only paths become searchable in the catalogue but do **not** expand playable computer branches until a reviewed Doctrine lesson migration adds them.

**Vue / Pinia**
- Check `catalogueRevision` on entering catalogue/search or starting the next lesson/session. If stale, refetch names/metadata with ETag rather than reloading the whole app.
- **Pin** active session to its original `lessonRevision`/graph: no mid-session move graph changes, popups or progress rewrites after cron.
- The app may show a subtle "opening catalogue updated" indicator in browsing views; explanations stay optional and low-noise.
- Keep public cached catalogue data separate from authenticated user state. No websockets, constant polling or new Node backend needed.

## Automated tests and acceptance

1. A correct fixture sequence replays to the same position/UCI/SAN as chess.js (including castling, promotions, SAN disambiguation and legal en passant).
2. Same legal state reached through transpositions deduplicates to one canonical position with separate source paths.
3. Unchanged five TSVs => successful no-op, stable active revision.
4. A new Lichess entry appears in catalogue search after sync; it **does not become an uncurated training opponent move**.
5. Rename/change/removal is reflected in active source labels, while existing lesson names/edges, saved progress and current sessions survive.
6. Missing/corrupted upstream file, invalid PGN, timeout, suspicious large drop => fail safely; previous active revision still queryable.
7. Two simultaneous invocations lock correctly, and retry of the exact same source is idempotent.
8. Cron uses selected production PHP binary/path; command runs without Node, GitHub Actions scheduled workflow, browser request or exposed HTTP trigger.
9. Source reports preserve provenance; logs/errors do not disclose tokens or personal data.
10. Fresh install executes schema/import/lesson data migrations in a supported documented sequence; weekly sync never runs migrations.
11. Vue cache refreshes only at navigation/new-session boundary after a catalogue revision change.

## Implementation checklist

- **M0, implemented locally**: Symfony backend, HttpClient, DBAL, console setup, baseline PHP CLI and test fixtures; locked Composer dependencies.
- **M2a–M2c, implemented locally**: PHP/chess.js replay fixtures, parser/graph, staged source imports, real pinned TSV verification, update/rollback tests, sync/status and dry-run/local-directory commands; public revision API, ETags and Pinia revalidation.
- **M3, implemented locally**: five courses/seven current finite guided lines, reviewed Doctrine lesson migrations and pinned lesson revisions; tests verify source refresh cannot modify teaching, provenance or saved completion.
- **M6, pending host gate**: deployed runtime/SMTP/HTTPS checks and importer dry-run/live tests, privacy review, rollback and log/alert verification; only then install the single cPanel weekly cron and record its first actual execution.

## References

- [Lichess official opening dataset (CC0)](https://github.com/lichess-org/chess-openings)
- [p-chess/chess — PHP chess library (MIT)](https://github.com/p-chess/chess)
- [Packagist package details](https://packagist.org/packages/p-chess/chess)
- [O2Switch cPanel cron](https://faq.o2switch.fr/cpanel/outils-avances/tache-planifiee-cron/)
- [O2Switch PHP CLI versions](https://faq.o2switch.fr/cpanel/logiciels/hebergement-php-multi-version/)
- [Symfony Console](https://symfony.com/doc/current/console.html)
- [Symfony HttpClient](https://symfony.com/doc/current/http_client.html)

**Design principle:** one Symfony/PHP command owns data acquisition, chess validation, database promotion and reporting. The Vue app only consumes revisions; the imported graph, curated lessons and private player progress remain separate.
