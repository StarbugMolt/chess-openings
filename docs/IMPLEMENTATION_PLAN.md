# Chess Openings — Implementation Plan

> **Status (2026-10-15): M0–M5 are implemented and verified locally.** Both modes, ten courses/27 current finite lines, accounts, measured private progress and scheduled reviews are available. The combined suite passes 194 PHP tests/4,247 assertions, 219 frontend units and 45 browser checks, plus one intentional mobile mouse-drag skip. CI is configured but has not run on GitHub; O2Switch is not provisioned. M6a account download/deletion is implemented; public-launch privacy, retention and hosting verification remain pending. See [PROGRESS.md](PROGRESS.md), [M4_IMPLEMENTATION.md](M4_IMPLEMENTATION.md) and [earlier review fixes](REVIEW_FIXES.md).
>
> **Agent start here.** Treat the "Accepted decisions" as requirements. The owner approved the imported keys/tables/lifecycle in [IMPORTER_REVIEW.md](IMPORTER_REVIEW.md), London authored provenance in [CURRICULUM_REVIEW.md](CURRICULUM_REVIEW.md) and private M5b progress design in [M5B_REVIEW.md](M5B_REVIEW.md); do not request these approvals again. Curated content and independently authored graph provenance have their own review requirements. Keep changes small and testable, and update this document when a decision changes. Do not quietly replace the chosen stack or expand scope.
>
> **Execution order:** follow [section 12](#12-implementation-milestones-and-acceptance-criteria). [Section 14](#14-automatic-o2switch-deployment) specifies automatic production deployment after successful CI on every push to `main`; the host is not configured yet.

## 1. Product mission and boundaries

Build a board-first, lightweight, friendly website where users **learn actual named chess openings and their variations**. It solves the problem of memorising one line and then being unprepared when the opponent chooses a different *theoretical* reply. It is a focused opening trainer, not a full chess coach or human-like chess engine.

**Primary user story:** "I select an opening I want to learn, practise its classical line, then let the computer choose from recognised variants so I learn to identify and respond to them."

### Accepted decisions

1. **Two modes in v1:** Guided Learning (strict selected line) and Variation Training (branching through selected known lines).
2. **Opening/variation scope is controlled.** Computer moves in v1 must come from supported, curated theory paths. No invented or arbitrarily weakened chess moves.
3. **Multiple recognised player replies** are valid in Variation Training. Guided Learning checks against the specific line being studied.
4. **No free sandbox, AI, Stockfish, model inference, engine grading, human imitation or out-of-book continuation in v1.** These may be future evolutions only after design/testing.
5. Name the current opening and variant accurately; show ECO where available. Allow hiding/revealing name during quizzes.
6. Explain plans/moves in a warm, concise voice, but do not clutter the board: expandable "Why?", hints, occasional short notifications at meaningful branch points.
7. Progress must persist between sessions/devices, **so account login is required to train**. Public marketing/catalogue pages may be readable anonymously.
8. Use a **local copy of Lichess opening-name data in MariaDB**; **never rely on the Lichess API at runtime**.
9. Publish source as **GPLv3**; third-party Chessground is GPL-3.0-or-later; Lichess opening data is CC0. The project is a free personal/hobby tool, with no planned monetisation.
10. GDPR/RGPD-minimal accounts: no ads or unnecessary trackers. Design privacy/security before production release.
11. Frontend is **Vue 3**, not React. Target mobile and desktop.
12. Hosting should be compatible with a shared PHP environment (e.g. O2Switch). No long-running Node server or always-on background jobs needed in production.
13. **APPROVED: three-layer data architecture.** (1) Imported Lichess-derived chess position/move graph **with preserved named opening paths** and provenance; (2) independently curated lesson lines, branches and explanatory notes; (3) private user accounts and training progress. These layers must remain independent so source-data refreshes cannot overwrite authored lessons or user history.
14. **APPROVED (revised): Doctrine data migrations for curated lessons.** Write intentional lesson records and later modifications as versioned PHP/Doctrine migrations in Git, not a lesson YAML loader. Upstream catalogue refreshes never change lesson content.
15. **APPROVED: weekly opening-catalogue refresh, owned entirely by Symfony/PHP.** O2Switch/cPanel cron invokes one Symfony Console command; PHP HttpClient fetches official Lichess TSV data, a PHP chess library validates/replays it, Doctrine DBAL stages/promotes the catalogue and Vue reacts to updated revisions. No Node/TypeScript importer or scheduled GitHub Actions pipeline. See [OPENING_UPDATES.md](OPENING_UPDATES.md).
16. **APPROVED (2026-10-15): automatic O2Switch deployment after successful CI on every push to `main`, reusing [Starbugstone/test-auto-deploy](https://github.com/Starbugstone/test-auto-deploy).** Adopt its current **host-pull** flow: GitHub CI validates the exact commit; a host cron poller fetches/builds it and activates the release. Node runs on hosting only as a frontend build tool. This supersedes the earlier CI-built/SSH-upload proposal. Initial host provisioning is still required; deployment polling is separate from the PHP-owned weekly catalogue refresh.
17. **APPROVED (2026-10-15): a complete Docker Compose development environment.** Run Symfony/PHP, Vue/Vite, MariaDB, a same-origin proxy and local email capture in containers. Host PHP, Composer and Node installations must not be required for the documented development workflow. Production remains ordinary PHP hosting.

### Not in the initial version

- Arbitrary position sandbox or engine opponent; full-game play/analysis.
- Importing the user's games, PGN/Chess.com/Lichess accounts, ELO estimation.
- Third-party OAuth, payment, ads, analytics, leaderboards, social or multiplayer features.
- Auto-generated lessons from an LLM, scraping proprietary opening courses, or copying educators' text/branding.
- Player-facing evaluations like "+0.4" unless a later chess-engine phase is deliberately approved.

## 2. User journeys and precise mode semantics

### A. Guided Learning (strict line)

1. User signs in; chooses opening family, playing colour and **one selected named line** (initially main line, then alternatives).
2. Trainer can demonstrate the sequence once. In practice mode, user makes moves for their chosen colour; computer plays the opposite colour's **fixed** moves in that line.
3. A correct next move advances. Optional hint displays a square, move, arrow or a one-sentence reason; hints affect mastery score but never stop learning.
4. If user's move is legal but exists in a *different* supported line, show "**valid alternative, but not the line we're practising**" and offer retry/reveal. Do **not** label it objectively bad.
5. If legal but absent from the selected opening's curated lines, show "outside this lesson" and offer undo/restart/explanation of the taught move.
6. Reaching the line's defined end completes the exercise; show name, moves learned, hints/attempts and simple next lesson/retry controls.

**Default behaviour:** the user's alternate move does not silently change the strict target line. A future "switch line" button can explicitly switch to another line if in scope.

### B. Variation Training (branching theory)

1. User selects an opening family, colour and a subset of **unlocked or explicitly selected** lines. A "mix all learned" preset is useful.
2. Computer chooses a next move from **the current position's allowed lesson-scope edges**, not from all legal chess moves. It should prefer under-practised branches and support a main-lines-only setting.
3. User responds with **any available continuation represented by the selected lesson-scope graph**. Their choice determines which supported branches remain relevant.
4. Variations can transpose into each other. The active view updates the position, possible next branches and most relevant opening/variation label.
5. If user's legal move is outside the selected scope, show a neutral "not covered in this lesson" notice and do not continue with a made-up opponent reply. Offer undo or select another supported line.
6. End when the current training path reaches a curated endpoint or configured opening depth; show coverage/accuracy review.
7. Optional "hide variation names until move/reveal" setting prevents giving away the answer.

**Rule:** importing an opening entry does *not* automatically make all its moves part of every lesson. Only an explicitly enabled lesson line/edge is eligible for computer selection and accepted as a lesson continuation.

### C. Common UI rules

- Large central board; touch + drag/drop; board flip; SAN move list; undo, restart, next line; explanatory drawer/collapsible panel.
- No modal after every move. Coach speaks on important transitions, mistakes or explicit requests.
- The same domain engine handles both modes; only the **training policy** changes.
- Do not conflate "not in our lesson", "different theoretical move" and "bad chess move"; without an evaluator the latter is unprovable.
- Do not claim that every named opening in source data has complete instructional coverage.

## 3. Technology (deliberate choices)

| Concern | Choice | Notes |
| --- | --- | --- |
| Frontend | Vue 3 Composition API, TypeScript, Vite | Strict TS, component-based; SPA |
| Navigation / state | Vue Router + Pinia | Store current session, app preferences, current user |
| Board | [@lichess-org/chessground](https://github.com/lichess-org/chessground) directly in a Vue wrapper | GPL-3.0-or-later; handle mount/unmount, destroy, orientation, shapes, moves |
| Browser chess rules, PGN, FEN, UCI | [chess.js](https://github.com/jhlywa/chess.js) | Validate moves and manage game state in Vue; NOT used by importer |
| UI | Native HTML forms today; Element Plus remains the planned forms/dialogues library | Minimal scoped CSS / variables; library not installed yet |
| Backend | Symfony **7.4 LTS**, PHP 8.2+ (prefer PHP 8.3/8.4 if host permits) | SecurityBundle, Validator, Mailer, Serializer |
| Persistence | MariaDB (target 10.11+), Doctrine ORM/Migrations, DBAL for bulk import | Charset utf8mb4; explicit indexes and FKs |
| Auth | Symfony Security sessions, password hasher, login throttling, CSRF | Same-origin API and Secure/HttpOnly/SameSite session cookies |
| Source importer | **Symfony Console PHP command** + HttpClient + Doctrine DBAL + PHP chess library (prefer [p-chess/chess](https://github.com/p-chess/chess), validate in spike) | PHP handles remote fetch, PGN replay, graph validation and database promotion; no Node importer |
| Testing | Vitest (unit), Playwright (e2e), PHPUnit (API), static analysis/lints | Validate full opening input and edge invariants |
| Local tooling | Docker Compose: PHP/Composer, Node/Vite, MariaDB, proxy and local mail capture | Pin compatible runtimes; run install/build/test commands in containers; production uses PHP hosting |
| Deployment | Reuse the template's host-pull controller: main push -> successful GitHub CI -> O2Switch fetch/build/activate | Node/Composer are build tools on hosting; PHP/static files serve requests; initial host setup pending |
| Weekly updater | **O2Switch/cPanel weekly PHP CLI cron** calling `app:openings:sync` | End-to-end Symfony import; no GitHub Actions scheduler, no intermediate SSH bundle |

**Licences:** repository code GPL-3.0-only, official text in `LICENSE`. Preserve upstream notices for Chessground, chess.js and the selected PHP chess library (preferred p-chess/chess is MIT). Lichess named-opening data: CC0; preserve provenance in importer tables and documentation. Check licence of **any additional datasets** independently; Lichess broadcasts are not all CC0. Do not use a Lichess account or request their API for runtime training.

## 4. Suggested code organisation

```text
/
  README.md
  LICENSE
  docs/IMPLEMENTATION_PLAN.md
  docs/OPENING_UPDATES.md                 # Weekly source sync and Vue reactions
  compose.yaml                           # Complete local development stack
  Makefile                               # Optional aliases for container commands
  .docker/                               # PHP/Apache and Node dev images
  ops/cron/                              # O2Switch weekly PHP crontab example
  frontend/
    src/
      components/chess/ChessBoard.vue       # Chessground adapter only
      components/trainer/                    # move list, mode controls, hints
      composables/useOpeningTrainer.ts       # framework-facing session API
      domain/                               # policy, graph traversal, models
      stores/                               # Pinia: auth/progress/session
      views/                                # login, catalog, lesson, account
    tests/
  backend/
    src/
      Entity/                              # Doctrine entities
      Controller/Api/                      # thin controllers
      Service/Chess/                       # lesson/catalogue lookups
      Service/OpeningImport/               # PHP TSV/PGN parser, chess replayer, diff/stage/promote
      Service/Progress/                    # progress write/reads
      Command/                             # app:openings:sync, app:openings:status
    migrations/                          # Schema + separate lesson data migrations
    tests/
  backend/tests/Fixtures/Openings/       # small, deterministic TSV/PGN transposition tests
  .github/workflows/                     # Build/test CI; host poller checks successful main push runs
  scripts/local-up.sh                    # Container startup
  scripts/o2switch/                      # Installer/configurator/poller; hosting setup pending
```

This layout includes both implemented foundations and proposed feature modules; consult README for runnable commands and current status. Minimise circular dependencies. A shared TS type package is optional; prefer a small OpenAPI/schema contract over introducing monorepo complexity prematurely.

**Critical layering:**
1. **Chessground renders** pieces, drag/drop and arrows; it is *not* the game-rules authority.
2. **chess.js validates browser moves** and converts PGN/SAN/UCI/FEN during frontend training. **On the backend, a PHP chess-rules adapter independently replays imported PGN**; shared golden fixtures must prove consistent positions and canonical keys between the two implementations.
3. **Training domain service** decides which *legal* moves are in the selected lesson, tracks current path and exercises policy.
4. **Symfony backend** is responsible for *all* source downloading, legal chess-line import, MariaDB revision promotion, catalogue API, lesson graphs, accounts and progress; it does not need to run Stockfish.
5. **DB** stores canonical chess facts, source/catalogue labels, curated lesson content and private user state in separate table groups.

## 5. Source data: what Lichess actually supplies

Source: https://github.com/lichess-org/chess-openings

Official root files `a.tsv` ... `e.tsv` contain:
- `eco`: ECO classification (not a unique opening identifier).
- `name`: English opening name, often `Family: Variation, Subvariation`.
- `pgn`: a representative move sequence reaching the named position.

Generated `dist/` includes `uci` and `epd` columns. We can use source PGN and derive UCI/key ourselves or optionally check against generated EPD. **Do not assume `dist/` is in every Git checkout.**

**Very important:** this is an **opening-name catalogue**. It does not reliably tell us a main line, move popularity, objective move quality, complete move-tree coverage or why moves are played. Lesson paths, priorities and explanatory prose are **curated editorial data**, not inferred to be authoritative from file order.

If expanding later to aggregate game-frequency statistics from https://database.lichess.org/ , plan a **separate** resource-heavy pipeline and inspect each source's licence. That is explicitly not required for v1.

## 6. Proposed importer — single Symfony PHP pipeline

The entire importer executes **inside Symfony/PHP**, both for first installation and subsequent weekly cron updates. No separate Node/TypeScript generator, CI-produced NDJSON bundle, GitHub Actions updater or production JavaScript runtime is needed. See [OPENING_UPDATES.md](OPENING_UPDATES.md) for the definitive operational and API details.

### 6.1 PHP fetch, replay, validate and promote

**Create Symfony Console commands** `app:openings:sync` and `app:openings:status`. `sync` must own the complete workflow via small backend services (`OpeningSourceFetcher`, `TsvOpeningParser`, `ChessMoveReplayer`, `OpeningGraphBuilder`, `ImportDiffValidator`, `CataloguePromoter`, naming illustrative). Implement `--dry-run` and `--source-directory` for local deterministic testing.

1. **Acquire lock** (DB or Symfony Lock) and record last-check status. An unchanged upstream dataset must be a successful no-op; don't issue a new active revision.
2. **Resolve immutable upstream SHA** using Symfony HttpClient; download `a.tsv` ... `e.tsv` from the *same* commit SHA using HTTPS into a private bounded temporary directory. Limit size and timeouts. Track source SHA and per-file checksums. Fail safely if offline.
3. **Stream TSV** using native PHP parsing and proper quoted/escaped field support. Validate expected `eco`, `name`, `pgn` schema, UTF-8, ECO codes, duplicates and row counts. Never blindly split PGN on spaces and assume move numbers/annotations are absent.
4. **Replay PGN with a PHP rules library.** Preferred initial candidate: [p-chess/chess](https://packagist.org/packages/p-chess/chess) (MIT, modern Composer PHP package). Wrap it in a narrow adapter so replacing it doesn't affect import logic. It supports SAN input, legal move validation and FEN; confirm via an importer spike that it correctly handles all five official files and edge cases before committing permanently to the package. Validate every move, derive UCI, canonical SAN and positions. No chess.js/Node invocation server-side.
5. **Canonicalise chess state** using placement, side-to-move, castling rights and legally relevant en-passant, excluding move clocks. Test against known transpositions and cross-language golden fixtures produced independently by frontend chess.js. Avoid equating board placement alone with a complete legal state.
6. **Build deduplicated graph**: one position per canonical key, one edge per `(fromPosition,UCI)`; verify destination consistency. Preserve named Lichess entries with source ECO/name, original PGN, every ordered edge, aliases and provenance. Never infer an authored "main line" from file order.
7. **Calculate diff and safety thresholds**: added/renamed/changed/removed entries, source checksums, unexpected count reductions, conflicts or missing files. Require manual review or abort on anomalous updates.
8. **Stage via Doctrine DBAL** in bounded batches without deleting lesson-referenced positions/moves. Use an import batch and a *versioned* source-label snapshot (or a proven equivalent) so reads can stay on the prior complete version while new data is loaded.
9. **Atomically activate** a validated source batch: switch the single active pointer/revision in a short transaction. Retain the previous known-good revision for rollback. If validation/import fails, leave old data active, log a nonzero error and permit safe retry.
10. **Publish revision metadata** to Symfony API (`catalogueRevision`, source SHA, updated time, summary) so Vue/Pinia refreshes catalogue/labels on the next navigation or new session. **Never change `lessonRevision` or player progress** during an upstream refresh.

**Weekly schedule:** create exactly one O2Switch/cPanel **opening-data** cron job after deployment, calling the **PHP** CLI command. Example schedule runs Monday at 05:17 **server-local time** (verify host timezone). `/opt/alt/php83/usr/bin/php /home/ACCOUNT/apps/chess-openings/current/bin/console app:openings:sync --env=prod --no-interaction`. Replace PHP binary/path/log destinations with actual host settings. [Complete example](../ops/cron/opening-catalogue.crontab.example). Deployment polling has a separate cron; neither job exposes a public HTTP update trigger.

**Why PHP end-to-end?** Symfony already owns the MariaDB connection, operational logs, locks, security and deployments, and O2Switch supports scheduled CLI PHP scripts. The maintained PHP chess rules package needs a one-time compatibility review, but avoids any ongoing Node/remote runner/import artifact chain. Node remains strictly for **Vue build and browser testing**.

### 6.2 Transposition and naming rules

- **Position identity:** same legal position => one node even if move order differs.
- **Line identity:** do not merge a sequence/history into a node. Preserve ordered path per source entry and curated lesson line.
- **Name identity:** a position can have multiple labels. Never store one global `variation_name` on `chess_position`.
- **Recognition:** given the played history, examine named positions reached, prefer the deepest/specific matching name in the **selected lesson's context**, then fall back to a neutral family label. If names are equally valid at the same node, support aliases rather than falsely declaring one universal name.
- **Training traversal:** resolve allowed edges from **selected authored lesson lines**, not from the entire imported catalogue graph. After a transposition, matching a supported position and its lesson-scope outgoing edge is valid, but preserve depth limits and selected lesson context.
- **Cycles:** graph may contain repeated positions. Cap lesson depth/plies and disallow accidental looping through an "unbounded training" mode.

**Canonical-key tests:** two legal move orders that reach the same position must yield the same key (example: `1.d4 d5 2.c4 c6` and `1.d4 c6 2.c4 d5`); positions with different castling rights or capturable en-passant possibilities must stay distinct. For en-passant, do not keep a phantom target square when there is no legal capture.

## 7. Proposed MariaDB schema (review before implementation)

**Architecture approved by the project owner:** keep the **imported chess graph and named paths**, **curated lesson definitions**, and **private account/progress records** as three independent layers. The specific table/column design below remains a technical proposal to validate before the first permanent migration. Create it with Doctrine Migrations, proper FKs/indexes and realistic data fixtures; names may be adjusted to project conventions.

### 7.1 Imported chess facts (shared/read mostly)

| Table | Key columns | Constraints / purpose |
| --- | --- | --- |
| `opening_import_batch` | `id`, `source_repo`, `upstream_sha`, `source_checksum`, `importer_version`, `status`, `created_at` | Provenance, repeatable import and safe cutover |
| `chess_position` | `id`, `position_key` (canonical 4-part FEN/EPD) | **UNIQUE(position_key)**; one position per legal state |
| `chess_move` | `id`, `from_position_id`, `to_position_id`, `uci`, `san` | **UNIQUE(from_position_id, uci)**; FK on both nodes; index destination |
| `opening_entry` | `id`, `source_uid`, `eco`, `full_name`, `original_pgn`, `terminal_position_id`, `import_batch_id` | Proposed snapshot row: **UNIQUE(import_batch_id, source_uid)**; ECO/name may repeat; active membership comes from the catalogue pointer |
| `opening_entry_step` | `opening_entry_id`, `ply`, `chess_move_id` | **PK(opening_entry_id, ply)**; ordered source line |
| `opening_catalogue_state` (proposed) | `active_batch_id`, `catalogue_revision`, `last_checked_at`, `last_updated_at` | Atomically promoted catalogue revision, separate from lesson revision |
| `opening_catalogue_change` (optional) | `batch_id`, `change_type`, `entry_id`, `old_summary`, `new_summary`, `review_required` | Bounded import diff/history and curriculum review candidates |

Sample essential SQL constraints (not complete migration DDL):

```sql
CREATE TABLE chess_position (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  position_key VARCHAR(128) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  UNIQUE KEY uq_chess_position_key (position_key)
);

CREATE TABLE chess_move (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  from_position_id BIGINT UNSIGNED NOT NULL,
  to_position_id BIGINT UNSIGNED NOT NULL,
  uci VARCHAR(5) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  san VARCHAR(16) NOT NULL,
  UNIQUE KEY uq_chess_move_from_uci (from_position_id, uci),
  KEY idx_chess_move_to (to_position_id),
  FOREIGN KEY (from_position_id) REFERENCES chess_position(id),
  FOREIGN KEY (to_position_id) REFERENCES chess_position(id)
);
```

Never use `eco` as a primary key, never assume `full_name` is unique, and never delete a referenced move when an upstream name changes.

**Snapshot detail to review:** upstream has no stable record ID. A proposed `source_uid` is a deterministic fingerprint of a normalised source record, scoped to its batch; its exact inputs/normalisation need spike evidence. A rename or path correction can therefore appear as removal/addition. Matching those into a human-friendly rename report is secondary and must not control lesson identity. Never mutate an old snapshot row to represent a new batch. Any lesson FK to a historical entry must be retained or deliberately replaced with immutable provenance before pruning that snapshot.

### 7.2 Editorial learning content (version-controlled source)

The M3 schema is implemented by `Version20261015000300` and additive schema migrations `00400`–`00402`. The original table sketches are superseded by these concrete tables; the M3 verification record remains separate from schema existence.

| Table | Key columns | Constraints / purpose |
| --- | --- | --- |
| `lesson` | `id`, `current_revision` nullable, `sort_order` | Stable authored identity; composite FK to its published revision; null permits transactional initial publication |
| `lesson_revision` | `lesson_id`, `revision`, `title`, `introduction`, `start_position_id`, `published_at`, `recommended_color` | **PK(lesson_id, revision)**; immutable published content and recommended White/Black side |
| `lesson_line` | `lesson_id`, `revision`, `id`, `title`, `description`, `sort_order`, `ply_count` | Composite identity/FK to the revision; finite 2–64-ply path |
| `lesson_line_step` | `lesson_id`, `revision`, `line_id`, `ply`, `chess_move_id`, `hint`, `explanation` | Composite ordered-path identity; restricted references to line and shared move facts |
| `authored_chess_position`, `authored_chess_move` | Shared fact ID, `authored_in`, `created_at` | Provenance only for facts first added by an explicitly reviewed authored migration; imported path membership is never fabricated |

**Approved lesson-storage decision:** authored lessons are maintained with **Doctrine PHP data migrations** in version control, not a separate declarative content loader. Versioned migrations publish stable lesson IDs with immutable revisions and finite steps. Hints and explanations currently live on steps; a separate localised `lesson_note` table remains a future extension. Weekly Lichess source refreshes must never change these records.

Current content migrations:

```text
backend/migrations/LessonData/Version20261015010000.php # Italian and initial London
backend/migrations/LessonData/Version20261015010100.php # London revision 2
backend/migrations/LessonData/Version20261015010200.php # Reviewed London revision 3
backend/migrations/LessonData/Version20261015010300.php # Caro-Kann, recommended Black
backend/migrations/LessonData/Version20261015010400.php # French, recommended Black
backend/migrations/LessonData/Version20261015010500.php # Queen's Gambit, recommended White
backend/migrations/LessonData/Version20261015010600.php # Italian revision 2
backend/migrations/LessonData/Version20261015010700.php # Caro-Kann revision 2
backend/migrations/LessonData/Version20261015010800.php # French revision 2
backend/migrations/LessonData/Version20261015010900.php # Queen's Gambit revision 2
backend/migrations/LessonData/Version20261015011000.php # Ruy Lopez
backend/migrations/LessonData/Version20261015011100.php # Catalan
backend/migrations/LessonData/Version20261015011200.php # Sicilian
backend/migrations/LessonData/Version20261015011300.php # King's Indian
backend/migrations/LessonData/Version20261015011400.php # Slav
```

**Verified bootstrapping:** `app:database:prepare` executes **schema migrations -> first pinned Lichess catalogue import -> separate lesson data migrations** under the shared DB lock. Each stream has its own discovery/history, and lesson execution refuses pending schema. A full pinned-source fresh-DB test runs separately from the offline fixtures in CI; plain Doctrine migration execution is blocked. See [MIGRATIONS.md](MIGRATIONS.md).

Write a **new migration** for each later published content correction. Freeze UCI/SAN/canonical position facts in versioned migration support and use explicit SQL column lists; old migrations must remain compatible with future schema additions. Imported lessons resolve an exact named path from the immutable bootstrap snapshot rather than arbitrary cumulative graph edges. The owner separately approved the [London authored extension/provenance](CURRICULUM_REVIEW.md); its frozen facts are cross-checked with PHP and chess.js. Do not write teaching prose into imported `opening_entry` records or silently introduce other authored extensions.

**Revision policy:** pin the browser's lesson graph/revision for its entire session. Retain published revisions and accept old-revision completion only against that exact retained path. A new revision does not inherit a changed line's completion automatically; old practice remains attached to its original revision. A catalogue rollback independently issues a new monotonic catalogue revision and holds automatic source activation until an explicit reviewed resume.

**Editorial invariants:** Published lessons have a reachable, legal path from their declared start position; a stop/end condition; a supported colour; correctly configured opponent/user turns; no dangling steps; no empty branch after a computer-selected move; and a coherent label when the line crosses a variation boundary.

### 7.3 Private accounts and learning state

The owner approved the implemented [M5b private progress design](M5B_REVIEW.md) and [exact SQL](M5B_SCHEMA_PROPOSAL.sql) on 2026-10-15. The existing ledger gains one nullable assessment column; no materialized progress/session tables are needed in v1.

| Table | Key columns | Constraints / purpose |
| --- | --- | --- |
| `user_account` | `id`, unique normalised `email`, `password`, `verified_at`, `created_at` | Minimal identity; no display name, DOB, address or last-login tracking |
| `account_email_token`, `account_request_limit` | Hashed token/password bindings and HMAC request-limit buckets | Account-owned token cascade and bounded persistent limits; see [ACCOUNTS.md](ACCOUNTS.md) |
| `user_line_completion` | `user_id`, `attempt_id`, `lesson_id`, `revision`, `line_id`, `playing_color`, `hint_count`, `completed_at`, nullable `assessment_json` | Idempotent user/attempt key; exact retained line/colour references; account deletion cascade |

Schema `Version20261015000430` adds bounded version-1 assessment JSON: whole-attempt hint/retry/undo flags and each final player decision's zero-based ply, 1–64 attempts and hint marker. The server validates exact path attribution and consistency before one atomic row is saved. Missing assessment preserves older clients; old NULL rows remain exposure with recall unknown. Normalized retries are idempotent; changed measured evidence or retrofitting a legacy attempt conflicts. No raw rejected moves, abandoned-path log, mode or seed is stored.

Derive current coverage, recall, contextual move counters and review dates from this private ledger and immutable retained paths. Context identity is `(lesson, revision, line, playing colour, ply)`; shared chess positions do not transfer credit. Current published revisions and colours remain separate; earlier versions stay visible as history. The reader uses a repeatable-read snapshot, iterates all relevant rows and caches each retained revision once. DBAL/PDO may buffer results and read cost grows with user history; a 10,000-row regression verifies no silent truncation.

The server schedules measured clean reviews at 1, 3, 7, 14 and 30 days, advancing only on due review; assisted practice schedules ten minutes. Early repeats preserve the due date. Legacy-only current practice is reviewable immediately with unknown recall; new lines are not overdue. Equal-second measured attempts are grouped conservatively. `/progress` shows concrete current-line coverage and observed recall rather than a mastery/accuracy percentage. Variation training freezes exact revision/colour evidence and favours less-practised/due lines while preserving selected-path eligibility. See [PROGRESS.md](PROGRESS.md) for the full policy and verified commands.

### 7.4 Index and integrity notes

- `chess_position.position_key` unique; `chess_move(from_position_id,uci)` unique; destination indexed.
- `opening_entry(eco)`, `opening_entry(full_name)` for lookup/search, `opening_entry(terminal_position_id)` for labelling.
- `opening_entry_step(entry_id,ply)`, `lesson_line_step(line_id,ply)`, `lesson_line(lesson_id,tier)`.
- `user_line_completion(user_id,attempt_id)` identifies an idempotent write; exact composite lesson/revision/line references and account ownership remain authoritative. Review dates and contextual counters are derived, not separately indexed aggregates.
- FKs with deliberate delete behaviour; no cascades that erase editorial content during an upstream data refresh.
- Bootstrap in dependency order: **schema migrations -> imported positions/moves/source paths -> Doctrine lesson data migrations -> user progress**. Weekly catalogue updates touch only imported chess knowledge and must not run pending lesson migrations.
- Validate uniqueness on the same SQL collation rules used by the application. Normalise emails explicitly; don't rely on case-sensitive raw input.
- Use prepared statements/batched inserts; never store or query legal move lists via large comma-separated strings.

## 8. HTTP API contract and planned extensions

Keep browser and Symfony on the **same origin** to simplify auth/cookies and deployment.

**Auth (public unless noted):**
- `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/me`.
- Verify email, request/reset password via tokenized, expiring links. Rate-limit and avoid account enumeration.
- `POST /api/me/data-export` and `DELETE /api/me` are implemented in M6a. Each accepts only `{password}` with authenticated session/CSRF and persistent confirmation limits; credentials stay out of URLs and no reusable elevated session is retained. See [ACCOUNT_DATA.md](ACCOUNT_DATA.md).

**Catalogue and lessons:**
- `GET /api/openings?search=italian&eco=C&page=1&limit=20`: public name substring/ECO search with bounded recorded sequences; no playing-colour inference. See [CATALOGUE.md](CATALOGUE.md) for the implemented contract.
- `GET /api/courses`: public, cacheable course metadata and line counts; this is the M3 course-list path replacing the originally proposed `/api/lessons?color=w`.
- `GET /api/lessons/{id}`: authenticated M3 lesson metadata and finite line paths, independent catalogue/lesson revision identities and the current user's completion summary. Return `private, no-store` because progress is included. Public metadata remains separate in `/api/courses`.
- A separate versioned public lesson graph endpoint and tier filters remain proposed extensions; do not infer implementation from the earlier example payload.
- `GET /api/openings/catalogue-status`: active `catalogueRevision`, update time, source SHA and optional change counts. Frontend compares revision on catalogue navigation and new-session start, refreshes only stale opening metadata, and leaves an active session pinned to its original lesson graph.

**Progress (all authorised for current session's user):**
- `GET /api/me/progress`: existing revision-bound completion summaries, retained for compatibility.
- `GET /api/me/practice`: implemented private current course/line/colour coverage, contextual recall, server review queue and earlier-version history; `private, no-store`.
- `POST /api/me/completions`: bounded, idempotent completed-line summary. M4 adds optional `mode: 'guided' | 'variation'` and, for variation, `selectedLineIds` (1–32 unique retained line IDs). Missing mode preserves guided requests. Validate revision, exact terminal path and selected-scope endpoint precedence before saving; never accept a client-supplied user ID. M5b adds optional bounded `assessment` with final-path player decisions and whole-attempt assistance, normalized and validated before atomic persistence. Legacy requests remain supported; mode/seed logs are not stored. See [M4_IMPLEMENTATION.md](M4_IMPLEMENTATION.md) and [PROGRESS.md](PROGRESS.md).
- `PATCH /api/me/preferences`: colour / hint / reveal preferences if later needed.

**Example lesson payload shape** (illustrative, not final OpenAPI):

```json
{
  "lesson": { "slug": "italian-white", "playingColor": "w", "revision": 1 },
  "startFen": "start",
  "positions": [{ "id": "p1", "key": "..." }],
  "edges": [{ "id": "m1", "from": "p1", "to": "p2", "uci": "e2e4", "san": "e4" }],
  "lines": [{ "id": "giuoco-piano-c3", "tier": 1, "edgeIds": ["m1"] }],
  "annotations": []
}
```

Publish and test a typed contract. Pagination for search, conditional responses/cache headers for catalogue; never ship entire Lichess data to the browser by default.

The [navigation and accessibility contract](UX.md) defines routes, catalogue/course terminology, trainer feedback, focus and redirect behaviour. Its requirements need implementation and verification; it does not extend the list of completed milestones.

## 9. Domain engine (framework-independent core)

Recommended API:

```ts
type TrainingMode = 'guided' | 'variation';
type TrainingState = {
  lessonId: string;
  mode: TrainingMode;
  playingColor: 'w' | 'b';
  currentFen: string;
  historyUci: string[];
  selectedLineId?: string;            // guided
  selectedLineIds: string[];           // variation scope
  allowedNextMoves: string[];
  status: 'playing' | 'outside-lesson' | 'completed';
};

function startLesson(config: TrainingConfig, graph: LessonGraph): TrainingState;
function playPlayerMove(state: TrainingState, uci: string): MoveEvaluation;
function chooseOpponentMove(state: TrainingState, rng: () => number): string;
function undoMove(state: TrainingState): TrainingState;
function getDisplayOpening(state: TrainingState): OpeningLabel;
```

Guided:
- `expectedEdge = selectedLine.steps[currentPly]`.
- If played legal move differs, return `alternative-in-scope` or `outside-lesson`, with a non-judgemental hint and no silent lesson switch.
- Else advance; computer executes next predetermined opposing move.

Variation:
- Compute allowed outgoing edges from the **intersection of current position and selected lesson-line scope**. Use reachable paths or transposition-aware position matching plus current ply cap; never use all `chess.moves()` as computer choice.
- Score/select opponent edges with small deterministic weighting: main-line bias, under-practiced response boost, optional variety, reproducible seeded RNG in tests.
- Player may choose any allowed theoretical continuation; branching must update eligible continuation state.
- If no scoped opponent edge exists, finish the current line. Never hallucinate a reply.
- Avoid revealing intended answer when name-hiding is enabled.
- Persist only after known, validated checkpoints; UI can be optimistic but must reconcile failed writes.

**Testable example:** Italian Game as White: `1.e4 e5 2.Nf3 Nc6 3.Bc4 Bc5`. In a strict `4.c3` lesson, `4.d3` is a valid different line, not a chess error; in mixed variation practice with both authored lines enabled, `4.c3` and `4.d3` both advance correctly.

## 10. Privacy / RGPD and security requirements

MVP includes compulsory sign-in for saved training. Registration should require email/password, optionally a public nickname; no full name, birth date, location, advertising ID or analytics profile.

**Required before public launch:**
1. Symfony password hasher (Argon2id where available), verified emails, expiring one-use reset tokens, email deliverability through configured SMTP; no plaintext passwords in logs.
2. Session cookie: `Secure`, `HttpOnly`, suitable `SameSite`, server session expiration, HTTPS/HSTS where safe; CSRF defence for mutating cookie-authenticated endpoints; login rate limiting.
3. Authorise every /me/progress endpoint and prevent IDOR; validate all client input with size caps; protect against account enumeration and excessive requests.
4. Privacy information: publisher/controller/contact, purposes + actual legal bases, processors/hosting, recipients, retention/deletion, rights and complaint route. Publish legal notices/terms suitable for a France-hosted public service.
5. In-app **personal data export** in a machine-readable file and account deletion (including progress), with a documented backup expiry/deletion process. Avoid indefinite logs. Inactive-account and backup retention periods must be explicitly decided/documented, not invented as statutory defaults.
6. Track the account's minimum necessary progress; no third-party trackers, profiling or ads in v1. First-party essential session cookies are generally exempt from cookie opt-in consent; nonetheless disclose them. Audit actual scripts/cookies before omitting a banner.
7. Keep secrets out of Git; use least-privilege DB credentials, protected SMTP secrets, migrations/backup strategy, patch dependencies and review CORS/CSP.
8. Consider minors if actively targeting underage users; age-related rules and notices need a dedicated review.
9. Include a short privacy verification checklist and documented handling for data-rights requests; **do not claim "fully GDPR compliant" until live configuration/legal notices are reviewed**.

Sources: [CNIL GDPR](https://www.cnil.fr/fr/comprendre-le-rgpd), [CNIL retention](https://www.cnil.fr/fr/passer-laction/les-durees-de-conservation-des-donnees), [CNIL erasure](https://www.cnil.fr/fr/comprendre-mes-droits/le-droit-leffacement-supprimer-vos-donnees-en-ligne), [Symfony security](https://symfony.com/doc/current/security.html).

## 11. Test plan / must-pass cases

### Source data / importer
- **PHP-only Symfony command** fetches/parses all five official source files and verifies checksums; run twice and compare logical tables. No Node/TS importer anywhere.
- Every source PGN replays legally using the PHP chess-library adapter; cross-check canonical UCI/SAN/FEN with chess.js golden fixtures; every edge has unique `(from,uci)` and a consistent destination.
- A different move order to same board produces one canonical node but separate source paths.
- Different castling rights or a legally available en-passant capture produce different position keys.
- A cPanel cron-driven upstream catalogue update preserves curated lesson paths/notes and user progress; invalid import leaves previous active dataset intact; import locks and manual reruns are safe.
- Names can collide/alias at one board position; no ECO or name uniqueness assumed.

### Core training
- Strict mode correctly accepts assigned moves, classifies recognised alternatives without calling them bad.
- Variation mode supports several player responses and randomised supported opponent branches; never plays a move outside scope.
- User playing Black receives correct first computer White move.
- Branch name/label correctly responds to selected line and transposition.
- Undo/restart restores board, labels, cursor and selected line scope; no duplicate writes.
- Line completion depth works; no infinite cycles or mid-line dead ends.
- Hint and line coverage/accuracy counters are consistent; returning sessions preserve progress.

### UI / full stack
- Drag and tap move, keyboard accessible controls, mobile layout and flipped board.
- Normal registration, verification/login/logout, password reset, session expiry, re-auth for deletion.
- Account A cannot read/write Account B's progress; unauthenticated API access denied where required.
- Export works and deletion removes account/progress while respecting documented retention/backup policy.
- No accidental third-party trackers; HTTPS/cookie flags/CSRF checked in production-like environment.
- DB migrations and Vite build deploy correctly on a PHP-only target.
- Weekly sync cases: unchanged upstream is no-op; new source lines appear in catalogue but not as uncurated computer replies; renamed labels refresh next session; corrupt/interrupted import keeps prior active catalogue; existing progress stays intact.

## 12. Implementation milestones and acceptance criteria

Use small, runnable changes. Milestone IDs remain stable, but accounts are split into **M5a** (before training) and **M5b** (complete progress/review). Production cron activation is an **M6** check; it does not block local importer acceptance.

### 12.1 Observed baseline and dependencies

At the initial planning audit on 2026-10-15, `git ls-files` contained six files: `AGENTS.md`, `README.md`, `LICENSE`, this plan, `OPENING_UPDATES.md` and the cron example. `main` was clean before the planning update; origin is `https://github.com/Starbugstone/chess-openings`. Application code, lockfiles, CI and Docker/test configuration were subsequently added in the first implementation increment.

Read-only local checks found Node 24.18.1, npm 11.16.0, PHP 8.4.19, Composer 2.2.6, Docker Engine 29.1.3 and Compose v5.0.0-desktop.1. Docker responded to `docker info`; PHP has `pdo_mysql`, `ctype`, `intl` and `mbstring`. Composer emits PHP deprecation notices. These are observations, **not** proof that the app or host is ready. Containerised tooling will supply the supported Composer/runtime versions; the installed MySQL client does not establish a running MariaDB server.

```text
M0 workspace + complete Docker dev stack + CI/deployment preparation
  -> M1 board and browser chess rules
  -> M2a PHP replay/parser spike (fixtures; no permanent domain schema)
  -> owner review of canonical state, schema and import/migration lifecycle
  -> M2b schema + M2c complete importer/catalogue API
  -> M5a accounts -> M3 first guided lesson + saved completion
  -> M4 variation training -> M5b progress and review
  -> M6 privacy, production validation and weekly cron activation
```

Host provisioning can start during M0. The deployment controller can first publish the M0 shell once the host is ready; M6 verifies the complete product. M5a can follow schema review while importer work continues, but a user-facing training milestone depends on it. Do not require owner input to complete M0, M1 or the fixture spike.

### 12.2 Verified first implementation increment (2026-10-15)

**M0 local acceptance and M1 are implemented.** The template was adapted into Vue 3/strict TypeScript/Pinia/Router, Symfony 7.4/PHP 8.3/Doctrine/HttpClient/Mailer, a complete local Compose stack and CI. Chessground wraps a separate chess.js rules adapter. Interactive board/rules fixtures are development-only; production serves a read-only preview. No permanent chess/account/lesson tables or opening-import command were added.

| Verification actually executed | Result |
| --- | --- |
| `bash scripts/local-up.sh` | Builds pinned images, installs locked dependencies and starts PHP/Vite/MariaDB/Mailpit; repeat startup passed |
| Same startup in a private clean source snapshot and isolated empty Docker volumes | Database/API readiness, Vite API proxy and captured email passed; test stack/volumes removed afterward |
| `bash scripts/check.sh` | Passed: Composer validation, Symfony container/YAML lint, PHP formatting/PHPStan, 4 PHPUnit tests/15 assertions, ESLint/Prettier/strict TypeScript, 5 Vitest rules tests, production build, Apache/SPA/assets/HTTP-01 checks and deployment controller/setup/preparation tests |
| Browser tests included in that command | 9 passed across desktop/mobile; mouse drag intentionally skipped for mobile, where actual touch input is tested |
| `docker compose exec -T --user www-data app bin/console app:dev:send-test-email` | Message captured in local Mailpit; also verified on the isolated fresh stack |
| `docker compose stop db` then `docker compose exec -T app php /app/tests/smoke.php http://127.0.0.1 --expect-db-unavailable` | Generic 503 verified; DB restored with `docker compose up -d --wait db` |
| Collaborative browser inspection | Built Apache-served home renders Chessground; development coordinate move advances board/history |
| `git diff --check` and shell syntax checks | Passed |

The startup script maps the PHP worker to the developer UID so private checkout directories remain private; generated dependencies/runtime state use writable named volumes. Build output includes dependency licence notices. The host controller retains the template's tested lifecycle; the setup helper now supports the project's custom Symfony build/prepare hooks. Preparation preserves shared sessions and refuses lesson migrations until the reviewed bootstrap exists. **No remote CI run, deployment, host configuration or scheduled job has occurred.**

### 12.3 Move sound and importer spike (2026-10-15)

**At this increment, M2a was completed and the schema/lifecycle review was presented. The owner subsequently approved M2b; see section 12.4.** The board lab plays an original, low wooden impact after accepted moves, with a persistent sound toggle. It stays silent for rejected moves, incomplete promotion, undo/restart and orientation changes; optional audio/storage failures do not prevent moves. The initial higher-pitched prototype was adjusted after owner feedback.

The Symfony command `app:openings:validate` downloads five TSVs from a supplied immutable SHA using PHP HttpClient, or validates a local five-file directory offline. Strict TSV/main-line PGN parsing, a replaceable `p-chess/chess` adapter and a deterministic in-memory graph preserve named paths/transpositions. There are **no permanent domain migrations or DB writes** in this command. This spike preceded `app:openings:sync`, whose later implementation is recorded in section 12.5.

| Verification actually executed | Result |
| --- | --- |
| `bash scripts/check.sh` after sound/importer changes | Passed: 73 PHPUnit tests/304 assertions, 29 Vitest tests, 13 desktop/mobile browser checks plus one intentional mobile mouse-drag skip; formatting, strict analysis, build, Apache and deployment-helper checks passed |
| Shared `backend/tests/Fixtures/Openings/golden.json` | 24 saved cases / 118 plies agree in PHP and chess.js, including both castles, file/rank disambiguation, promotion/checkmate, transpositions and legal/phantom/pinned en-passant |
| `docker compose exec -T --user www-data app bin/console app:openings:validate --source-sha=a6189a30dc273ccb21fc2536a9a2fefd5592a67a --env=prod --no-debug` | All five full source files pass: 3,865 named paths, 37,698 plies, 7,983 positions, 8,192 edges; 39.320 seconds including downloads, 46 MiB peak PHP memory; no DB writes |
| `docker compose exec -T --user www-data -e DATABASE_URL=mysql://unused:unused@127.0.0.1:1/unused app bin/console app:openings:validate --source-directory=tests/Fixtures/Openings/source --env=prod --no-debug` | Succeeds with an unreachable database: 10 source records, 93 plies, 81 positions, 80 edges; no remote calls or DB writes |
| `bash tests/import/schema-proposal-test.sh` (also added to the check script) | Review-only SQL creates seven tables in a uniquely named disposable MariaDB schema; indexes and 18 invalid-write rejection cases pass; schema dropped afterward; application DB still empty |
| Collaborative browser inspection | Accepted coordinate move starts the revised 140 ms audio buffer; toggle is visible and keyboard accessible |

See [IMPORTER_REVIEW.md](IMPORTER_REVIEW.md) for pinned checksums, limitations and the concrete canonical-state/source-identity/table/update proposals, and [IMPORTER_SCHEMA_PROPOSAL.sql](IMPORTER_SCHEMA_PROPOSAL.sql) for review-only DDL. These are awaiting owner review and are outside Doctrine migration discovery. Remote CI and O2Switch setup remain pending.

### 12.4 Approved schema and migration separation (2026-10-15)

**The owner approved the canonical keys, imported tables and update lifecycle in [IMPORTER_REVIEW.md](IMPORTER_REVIEW.md), explicitly authorising M2b. M2b is implemented and locally verified.** Approval of that imported baseline is complete; M2c can proceed without requesting it again.

`Version20261015000100` creates the seven approved source/graph/state tables and an inactive singleton revision 0. `app:schema:migrate` uses schema-only Doctrine discovery/history under a database-scoped MariaDB advisory lock. `app:lessons:migrate` uses a separate dependency factory/history and requires an active catalogue before executing transactional lesson migrations. The startup script applies schema automatically; deployment preparation uses the locked schema command and retains its temporary lesson-bootstrap guard until complete sync exists. The initial MariaDB DDL is explicitly nontransactional and its down migration refuses to erase shared facts/history.

| Verification actually executed | Result |
| --- | --- |
| `docker compose exec -T --user www-data app bin/console app:schema:migrate --no-interaction`, then rerun | Initial migration applied in the local development DB; rerun is a no-op; 7 domain tables plus schema history; no catalogue entries or active batch seeded |
| `bash tests/import/migrations-test.sh` | 47 checks in a fresh disposable MariaDB DB: exact migrated DDL/reference parity; schema/lesson discovery and metadata separation; ordering/prerequisite guards; retries; failed lesson transaction rollback; lock contention/release; real ORM diff preserving catalogue/history while detecting a probe table; destructive down refused |
| `bash tests/import/schema-proposal-test.sh` | 18 approved SQL FK/unique/CHECK rejection cases pass in a separate disposable DB; both test scripts clean up only their own databases |
| `bash scripts/check.sh` | Passed: 73 PHPUnit tests/304 assertions, 29 Vitest tests, 13 browser checks and one intentional mobile mouse-drag skip, integration/static/build/routing/deployment-helper checks |
| `bash scripts/local-up.sh` after adding automatic migrations | Repeat container startup passed; schema migration reported already current; frontend/API/Mailpit started |
| Native `doctrine:migrations:diff --allow-empty-diff --no-interaction` and `doctrine:schema:update --dump-sql --no-interaction` | No destructive migration or update SQL for the DBAL-managed tables |

See [MIGRATIONS.md](MIGRATIONS.md) for commands and the actual bootstrap boundary. The importer and lesson in the disposable DB tests are minimal test fixtures, not production sync or authored curriculum. **M2c sync/status/rollback/API, actual lesson/account tables, remote CI and O2Switch configuration remain pending.**

### 12.5 Complete catalogue sync and browsing (2026-10-15)

**M2c is implemented and locally verified.** `app:openings:sync` owns immutable-SHA resolution/download or explicit offline source copying, strict PHP replay, semantic no-op/diff safeguards, DBAL staging, stored-path validation and atomic activation. `app:openings:status` reports private operational state; `app:openings:rollback` restores a retained validated snapshot with a new increasing revision. Source/graph rows and snapshots remain separate from authored/private data. Dry-run changes no persistent DB state. Shared locking, bounded retention and rotating private logs are implemented.

`app:database:prepare` performs schema -> pinned first catalogue if absent -> lesson migrations under the shared lock. The deployment hook uses it before cache warmup; later releases retain an existing source catalogue. GET status/search APIs have typed bounded parameters, snapshot-consistent reads, revision-based ETags and generic outage responses. Vue `/openings` provides name/ECO search/paging. Pinia revalidates only at navigation/search/new-session boundaries. This M2c record preceded playable lessons; the later guided trainer and pinned lesson payload are verified in section 12.7.

| Verification actually executed | Result |
| --- | --- |
| `docker compose exec -T --user www-data app bin/console app:database:prepare --env=prod --no-debug --no-interaction` | Full pinned SHA `a6189a30dc273ccb21fc2536a9a2fefd5592a67a` activated locally: 3,865 entries, 37,698 plies, 7,983 positions, 8,192 edges; revision 1; sync 43.564 s / 34 MiB peak PHP memory |
| Default `app:openings:sync --env=prod --no-debug --no-interaction` | Resolves upstream master once and replays all five files; `unchanged`, revision stays 1; 45.633 s / approximately 32 MiB; PHP container has no Node executable |
| `bash tests/import/catalogue-test.sh` | 79 checks in disposable MariaDB: full PHP fixture bootstrap, semantic no-op/dry-run, row-order identity, thresholds/force, immutable facts, source/staging/cutover/network/logging failures, locks, atomic reads/rollback, retention and unchanged lesson/progress fixtures |
| `bash scripts/check.sh` | 81 PHPUnit tests/338 assertions, 34 Vitest tests, 17 browser checks + one intentional mobile mouse-drag skip; 18 SQL constraint and 47 migration checks, static/build/Apache/controller/preparation checks pass |
| Local DB stop -> `tests/smoke.php ... --expect-db-unavailable` -> DB restore | Health, source status and source search return generic JSON 503; successful readiness/catalogue checks pass afterward |
| Collaborative browser on built Apache site | `/openings` renders imported records; Italian/C prefix finds 187 entries; desktop/mobile search, paging, outage retry and navigation-only refresh are covered in browser tests |

See [CATALOGUE.md](CATALOGUE.md) for the exact operator/API contract, bounded cleanup/log policy, checksums and limitations. API/session data and authored curriculum are not generated from source names. **Next is M5a account foundation before M3 playable lessons. No remote CI run, O2Switch deployment or host cron has occurred.**

### 12.6 Account foundation and remember me (2026-10-15)

**M5a is implemented locally.** Registration, verification/resend, verified-email login/logout, password recovery, `/api/me` and account/privacy forms use Symfony Security sessions and CSRF. `Version20261015000200` adds a minimal private ORM account plus DBAL hashed email-token and request-limit tables; the importer and lesson streams remain independent. Passwords use sodium Argon2id, and verification/reset tokens are expiring and single-use. SMTP is exercised through local Mailpit.

Sessions survive page refreshes. Owner-requested **Remember me for 30 days** is optional and uses Symfony's signed HttpOnly cookie, with a rolling expiry when restoring sign-in. Logout clears this browser's remembered sign-in; a password reset invalidates existing sessions and remember cookies. Both cookies require Secure in production and SameSite=Lax. Bounded sessions expire after two idle/eight absolute hours; persistent database limits survive release changes. Existing private shared sessions/logs are preserved by deployment preparation.

| Verification actually executed | Result |
| --- | --- |
| `bash tests/import/migrations-test.sh accounts-test.php` | 58 checks against a disposable MariaDB database and real Mailpit: normalisation/hashing, verification/reset replacement/expiry/single-use, isolation, CSRF/session rotation, session expiry, throttling, remember-cookie restoration/tampering/logout/reset invalidation and local HTTP/production HTTPS flags |
| `docker compose run --rm browser npm run test:e2e -- accounts.spec.ts` | Four desktop/mobile form journeys: guarded account navigation, registration/explicit verification, fragment removal, sign-in/remember option, reload/logout, reset errors/password clearing and privacy navigation; deterministic API responses |
| `bash scripts/check.sh` | 81 PHPUnit tests/338 assertions, 39 Vitest tests and 21 browser checks plus one intentional mobile mouse-drag skip; 18 SQL constraint, 47 migration, 79 catalogue and 58 account checks, static/build/Apache/controller/preparation checks |
| Collaborative browser | Local Mailpit verification confirmation and responsive account forms; no horizontal overflow at 375 px |

See [ACCOUNTS.md](ACCOUNTS.md) for API, cookie/expiry, rate-limit, SMTP and storage details. Public legal information, export/deletion, retention decisions and hosting SMTP/HTTPS verification remain M6 work. The privacy view identifies the local preview. Actual lesson/progress routes and ownership checks arrive with M3/M5b; their API prefixes already require authentication. **Next: authored Italian curriculum/revision policy and M3 guided learning. No remote CI, O2Switch deployment or host cron has occurred.**

### 12.7 Guided learning and coordinated review fixes (2026-10-15)

**M3 is implemented and locally verified.** `/learn` exposes five deliberately authored courses and seven current lines: Italian Game, London System revision 3 through castling, Caro-Kann Defense, French Defense and Queen's Gambit. Recommended colour is explicit metadata; Black exercises begin with White's automatic move. Guided sessions support drag/tap/keyboard moves, promotion, hints/explanations, sound, undo/restart, neutral alternatives/outside-lesson feedback and idempotent owner-scoped completion. The browser pins its finite lesson/revision payload; old revisions remain interpretable after publication changes.

The owner approved London's independently authored facts/provenance separately from the imported layer. Lesson migrations freeze canonical facts in versioned support, name their SQL columns and validate imported paths against the immutable bootstrap snapshot. Additive migrations protect publication identity, recommended colour, rollback holds and token-bound registration passwords. Full-source testing verifies the real pinned catalogue rather than relying solely on reduced fixtures.

| Exact command/check | Result |
| --- | --- |
| `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` | Exit 0; **111 PHPUnit tests / 1,053 assertions**, **75 Vitest tests**, **33 browser passes / one intentional mobile mouse-drag skip**; static/format/build/Apache/deploy/configuration and actual backup/restore checks passed |
| Disposable suites within that command | **18 SQL constraints, 81 migration, 108 catalogue, 93 account and 53 lesson checks** |
| `bash tests/import/migrations-test.sh pinned-lessons-test.php` | **31 checks**; complete pinned 3,865-record source, eight schema and six lesson migrations, stable repeated preparation and coherent source identities |
| `bash tests/import/full-lessons-test.sh` (M3 session) | **54 checks**, including full-source authored provenance, publication/old-revision/private-progress preservation |
| Native T3 390 px preview | Five courses fit; route title/focus/scroll and malformed query recovery work; token-only confirmation has no password input and removes the fragment |

Account confirmation follows the owner's standard flow: register with a password, confirm the link, then sign in with that same password. Each link binds its registration hash, earlier pending links survive a later registration, and consumption deletes all other verification links. The accepted unsolicited-registration residual is documented in [ACCOUNTS.md](ACCOUNTS.md). Deferred SMTP, persistent network-scoped limits, explicit production configuration, safe importer holds/locks, migration guards, asset retention and private backup recovery are covered by the coordinated [review resolution](REVIEW_FIXES.md).

At this M3 checkpoint, M4/M5b and M6 remained pending; section 12.8 records the later M4 implementation. Completion records do not imply mastery or a full repertoire. No remote CI, O2Switch deployment or hosting cron has been verified.

### 12.8 Variation training and deeper curriculum integration (2026-10-15)

**M4 is implemented and locally verified.** Authenticated `/train/:id` uses the existing pinned lesson payload. Learners select pieces and taught lines, with learned defaults bound to the same revision/colour and explicit presets. Any supported player response in that scope is accepted; the computer selects uniformly among distinct supported replies using a session seed. One selected line is honestly described as a fixed sequence. Legal moves outside scope preserve the board and receive neutral retry feedback.

The independent trainer preserves complete selected prefixes rather than joining graph edges at transpositions. It continues past a prefix endpoint while a compatible selected path continues, caps all sessions at 64 plies and credits only an exact authored terminal line. Undo/restart restore candidate/random state. The existing completion endpoint now validates variation scopes and endpoints before its idempotent owner-scoped write; no schema, seed log or mastery score was added. Hint/Why actions disclose and reveal names alongside original coaching. Responsive board/keyboard input, glowing origin/destination hints, sound and delayed-save/retry handling are integrated.

An independent curriculum session published Italian/Caro-Kann/French/Queen's Gambit revision 2 from complete pinned named records; London remains revision 3. There are **five courses/15 current lines**, 23 stored lines/353 steps and ten lesson migrations. Earlier revisions/completions remain intact. The coordinator applied the four additive lesson migrations to the existing local catalogue and removed stale short-duration/move-count copy; longer line options fit at 390 px. See [source/revision notes](lessons/NAMED_LINES_REVISION_2.md).

| Exact command/check | Result |
| --- | --- |
| `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` | **Exit 0**; **120 PHPUnit tests / 2,271 assertions**, **125 Vitest tests**, **37 desktop/mobile browser passes**, one intentional mobile mouse-drag skip; all static/format/build/Apache/deployment/backup-restore checks passed |
| Disposable suites within that command | **18 SQL constraint, 81 migration, 108 catalogue, 93 account, 56 lesson and 109 variation checks** |
| `CHECK_REUSE_SERVICES=1 bash tests/browser/run.sh variation.spec.ts --repeat-each=3` | **12 passed**; each desktop/mobile variation journey repeated three times after synchronizing with Chessground's deferred move callback |
| Separate curriculum-owner full-source checks | Pinned bootstrap **31 checks**, deeper lesson suite **57 checks**; complete 3,865-record source, all schema/lesson migrations and revision preservation |
| Native T3 preview | Mobile/desktop variation board, hints, explanation, reply, undo/restart and longer guided-line options; no horizontal overflow at 390/412 px |

See [M4_IMPLEMENTATION.md](M4_IMPLEMENTATION.md) for the exact domain/API contract and verification. Shared services and learner data were preserved. **At this M4 checkpoint, M5b was next; section 12.9 records its later completion. M6 privacy/export/deletion and host verification remain pending.** No remote CI, O2Switch deployment or host cron was run.

### 12.9 Private progress, recall and review (2026-10-15)

**M5b is implemented and locally verified.** The owner approved [the private design](M5B_REVIEW.md) after 55 disposable SQL checks. Schema 430 adds only nullable bounded assessment JSON to the existing completion ledger. Both trainers record exact final-context decisions, preserve assistance across undo/branch changes, reset on restart and submit one validated idempotent completion. Older practice remains recall-unknown. No aggregate tables, engine score or historical inferred recall were added.

Authenticated `/progress` presents current course/colour coverage, observed recall, contextual move counters, up to 20 server-ordered review suggestions and separate earlier-version history. Current links select an exact guided line/side/revision; stale links explain the update. Account/Learn/completion entry points, session-expiry recovery and mobile cards/touch targets are integrated. Variation replies use frozen private exposure/due evidence without expanding the selected finite paths. Policy and limitations are in [PROGRESS.md](PROGRESS.md).

The independent curriculum session added Ruy Lopez, Catalan, Sicilian, King's Indian and Slav from exact pinned named records. There are now **ten courses/27 current lines**, 35 retained lines/645 steps, nine schema and 15 lesson migrations. Five courses recommend each colour; existing IDs/revisions and old practice are preserved. See [new-course sources](lessons/NAMED_LINES_NEW_COURSES.md).

| Exact command/check | Result |
| --- | --- |
| `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` | **Exit 0: 194 PHPUnit tests / 4,247 assertions, 219 Vitest tests, 45 desktop/mobile browser passes**, one intentional mobile mouse-drag skip; static/format/type/build, Apache/deploy and actual private MariaDB backup/restore passed |
| Disposable suites within that command | **18 SQL constraint, 81 migration, 108 catalogue, 93 account, 61 lesson, 109 variation and 150 practice checks** |
| `bash tests/import/practice-proposal-test.sh` | **56 checks**; legacy NULLs, bounds/constraints, account cascade, unchanged other tables and exact approved proposal/permanent-migration DDL parity |
| `docker compose exec -T --user www-data app php vendor/bin/phpunit tests/Service/Practice` | **62 tests / 152 assertions**; validation, revision/colour/context ownership, due/early/assisted/tie scheduling and 10,000-row projection |
| `CHECK_REUSE_SERVICES=1 bash tests/browser/run.sh guided.spec.ts variation.spec.ts practice.spec.ts` | **18 passed**, including real measured saves, second browser-session persistence, review links, outages and confirmed expiry recovery |
| Separate curriculum-owner checks | Full pinned-source bootstrap **31**, full-source lessons **62**; chess.js replay of all 12 new paths/292 plies |
| Shared local application | Locked `app:lessons:migrate --no-interaction` applied five new lessons through 11400; `app:schema:migrate --no-interaction` applied only approved schema 430. Shared services, pinned source catalogue, learner and progress were preserved |
| Native T3 preview | Signed-in `/progress` renders all ten current courses in one-column mobile cards at 390 px without console errors |

The isolated browser runner waits for HTTP/database readiness before schema/import/lesson preparation, avoiding a console/entrypoint startup race. No shared services were stopped or recreated. **Next: M6 privacy/legal information, export/deletion, retention and host verification.** Remote CI, O2Switch deployment and host cron activation remain unverified.

### 12.10 Account download and deletion (M6a, 2026-10-16)

Password-confirmed account download and deletion are implemented using the existing schema. Authenticated `POST /api/me/data-export` downloads versioned private JSON with owned account metadata, all retained completion/assessment rows and email-link metadata, excluding hashes/secrets. `DELETE /api/me` verifies the locked fresh password, commits existing token/progress cascades and executes configured Symfony logout. Each action requires session CSRF and shares persistent confirmation limits (five per user/30 per network per 15 minutes). Wrong passwords, rate limits and confirmed expiry have actionable feedback.

Account offers separate mobile cards, clears submitted passwords, supports cancellable explicit deletion confirmation and focuses the result. A failed deletion rolls back without signing out. Other accounts/public content remain intact; old sessions/remember/reset credentials cannot restore the deleted user, including after recreating the same email/password. No new database table, migration, elevated-session token or server export file was introduced. JSON download reads/encodes full history in request memory, with a 10,000-row completeness regression; read cost grows with user history. Backup/log/abuse-limit/Mailpit boundaries are described honestly, with public retention still pending. See [ACCOUNT_DATA.md](ACCOUNT_DATA.md).

Focused verification passed **84 disposable API checks**, **5 service tests/29 assertions**, **11 frontend account tests** and **4 desktop/mobile account-data browser checks**. The first browser run encountered another run deleting its shared trace directory; the isolated runner now uses a unique ignored `artifacts/browser/<run>` root. Full combined verification is pending coordination with the independent trainer/catalogue UI work. Shared services and retained users were preserved.

### M0 — Runnable workspace, Docker development and CI

- **M0a, scaffolds:** selectively reuse the template's Symfony/Vue workspace and lockfiles, recording its pinned source commit. Convert the Vue demo to strict TypeScript and add Router/Pinia; add backend Console, HttpClient, Doctrine and PHPUnit. Discard the template's demo controller/data migration rather than introducing sample tables. Retain this project's GPLv3 licence and product docs; add env examples, ignore rules, formatting and static analysis.
- **M0b, local containers:** adapt the template's PHP/Apache image with Composer/extensions, Node/Vite service and health-checked MariaDB; add Mailpit for captured email. Use the Vite origin (`http://localhost:5173` by default) for browser development, with `/api` proxied to the PHP container. The backend's loopback port supports readiness/smoke tests and serving a production build. Expose Mailpit locally; use named volumes for DB/dependencies, bind-mounted code and Linux-compatible file ownership. Keep secrets and DB data out of Git; stopping containers preserves data.
- Pin container runtime versions and MariaDB image after checking the intended host. Start with the specified MariaDB 10.11+ target; verify actual O2Switch availability before relying on version-specific SQL. Include the chosen PHP version in CI and Composer platform checks.
- **M0c, developer interface:** adapt `bash scripts/local-up.sh` and document container-backed install/build/test/stop commands; optional Make aliases can wrap them. Setup installs both locked dependency sets and prepares local config; development provides live Vue reload, PHP changes and email capture. Run smoke-test dependencies in containers too. A new developer needs Git, Bash and Docker/Compose; native PHP/Composer/Node are optional.
- **M0d, CI and deployment preparation:** extend the template's `ci.yml` with frontend types/lint/Vitest/build and backend formatting/static analysis/PHPUnit against MariaDB. Preserve useful deployer/installer/health tests; replace demo-specific assertions. Implement `/api/health` JSON plus `X-Release-Id`, SPA deep-link/API routing, and certificate-challenge tests. Adapt the controller/scripts from section 14, leaving polling disabled until host setup is verified. CI uploads no release artifacts and receives no production credentials; the template's legacy SSH workflow stays disabled.
- **Acceptance:** from a clean checkout and empty volumes, the documented container commands boot Vue + Symfony + MariaDB; browser requests use one origin, HMR works, PHP reaches MariaDB and a test message appears in local mail capture. Checks run without host PHP/Composer/Node. CI passes. README records commands actually executed; proposed commands are not represented as already available.

### M1 — Board and rule adapter

- Wrap Chessground with mount/update/destroy handling, orientation, legal destinations, promotion selection, drag/tap moves and accessible controls. Let chess.js own the legal game state; isolate this adapter from lesson policy.
- Add SAN history, rewind/restart, correct turn handling and fixtures usable by the later trainer. Keep the unrestricted board harness development-only; it is not a public sandbox mode.
- **Acceptance:** Vitest covers illegal moves, castling, en-passant, promotion and undo; a browser smoke test covers drag/tap, Black orientation, mobile sizing and route cleanup. No lesson or engine behaviour is inferred from Chessground.

### M2a — PHP importer spike and evidence for review

- Add `ChessMoveReplayerInterface`, the `p-chess/chess` adapter, strict TSV validation and a supported PGN movetext parser. Start with about ten deterministic source lines; use separate FEN-based rules fixtures for rare cases that require them. Preserve fixture origin/SHA and expected UCI, SAN, full FEN and canonical key after each ply.
- Check both PHP and chess.js against the same expected results: normal lines, transpositions, both castles, ambiguous SAN, promotion, checkmate, capturable/phantom en-passant, and an en-passant capture made illegal by a pinned pawn. Keep clocks in replay state but outside position identity.
- Demonstrate deterministic graph construction and duplicate handling in memory/disposable storage. Reject malformed tokens, illegal moves, bad headers, invalid ECO and unexpected columns rather than partially accepting a line. Then measure replay of all five files from one recorded immutable SHA; this is validation, not a permanent bulk DB import.
- **Acceptance:** cross-language fixtures pass; transpositions produce one node and separate ordered paths; invalid fixtures identify file/row/ply; full-source replay results and memory/time are recorded. The package remains replaceable behind its adapter.
- **Review gate:** present the concrete proposals in section 13 with this evidence before creating permanent domain migrations.

### M2b/M2c — Schema, complete sync and catalogue API

- **M2b:** implement reviewed keys/FKs/indexes, append-only shared chess facts, versioned source snapshots, active pointer and import status. Establish separate schema/lesson migration discovery and metadata as appropriate; test the actual Doctrine CLI/configuration rather than assuming two folders enforce ordering.
- **M2c:** implement `app:openings:sync` with lock, immutable-SHA fetch, bounded downloads, parsing/replay, checksums/diff safeguards, DBAL staging, short activation transaction and bounded cleanup. Add `--dry-run`, `--source-directory`, deterministic source-ref support, `app:openings:status` and an operator rollback procedure. Dry-run performs validation without persistent changes; weekly sync never executes migrations.
- Add paginated catalogue search and status responses, typed DTOs, revision-based ETags/cache keys, and Vue revalidation on catalogue entry/new lesson. Separate public catalogue responses from user-specific state.
- **Acceptance:** a full pinned source imports locally; identical content is a no-op even if the Git SHA changes; locks prevent competing activation. Failure during download, parsing, staging or activation keeps the previous source queryable. Rename/removal/rollback tests preserve curated/progress fixtures and a rollback increments the revision. API requests spanning activation see one consistent snapshot. Production cron is still not installed.

### M5a — Account foundation before training

- Add registration, verification, login/logout, password reset and `/api/me`, using session auth, login throttling and explicit CSRF acquisition/validation. Include the owner-requested optional remember-me cookie so returning browsers can restore sign-in; refreshes already preserve sessions. Develop email flows through local mail capture. Protect training routes and progress endpoints; keep account/progress responses private and uncached by shared caches.
- **Acceptance:** cookie/session and CSRF tests pass; anonymous training requests fail; verification/reset tokens expire and are single-use; a second account cannot access the first account's data. Test local HTTP cookie configuration separately from production HTTPS flags.

### M3 — First complete guided-learning journey

- Author a reviewed Italian Game lesson with a finite main line and one alternative using Doctrine PHP data migrations. Resolve shared graph edges by canonical position/UCI rather than environment-specific auto-increment IDs. Give lessons/lines stable identifiers and explicit revisions.
- Prove a fresh DB sequence: **schema migrations -> pinned catalogue import -> lesson data migrations**. A lesson migration fails clearly if a required edge is absent. Rerunning bootstrap is safe; later schema changes and content corrections remain separate migration steps.
- Implement a framework-independent guided policy, lesson/graph endpoints, lesson selection, hints/explanations, move history and completion. Handle playing Black and fixed opponent replies; legal alternatives keep neutral feedback and do not change the target line.
- Include a minimal idempotent completion write for the signed-in user. Validate submitted lesson revision, selected line and bounded path on the server; repeated delivery/restart must not create duplicate completions.
- **Acceptance:** a user can register/sign in, select the lesson, finish its exact line, use hints/undo/restart, reload and see saved completion. PHP/API/domain tests and one full browser journey cover this. A valid `4.d3` alternative does not advance a strict `4.c3` exercise.

### M4 — Variation training

- **Implemented:** see section 12.8 and [M4_IMPLEMENTATION.md](M4_IMPLEMENTATION.md) for acceptance evidence.
- Extend the same domain engine with selected-line scope, contextual names, transpositions, finite endpoints, name hide/reveal and seeded opponent selection. Begin with simple main-line/uniform weights; incorporate progress weighting in M5b.
- Keep eligible continuation/path state, not only a union of outgoing moves: transpositions must not join unrelated line fragments into an unauthored exercise or credit a line never practised. Define endpoint precedence when one authored line is a prefix of another, and enforce a ply cap for repeated positions.
- **Acceptance:** both Italian branches accept their taught player replies; computer choices always remain in selected scope; Black starts correctly; transpositions preserve context. Undo/restart restores branch state. Legal moves outside scope offer retry and never elicit an invented opponent reply. Upstream-only paths cannot become training branches.

### M5b — Durable progress and useful review

- **Implemented:** see section 12.9 and [PROGRESS.md](PROGRESS.md). Approved compact private assessments derive revision/colour/line-context recall, hint/attempt counters, review suggestions and under-practised supported opponent weighting. No mastery percentage or cross-line transposition credit. Writes are owner-scoped and idempotent; earlier practice remains separate and recall-unknown.
- **Acceptance:** progress survives logout and a second browser session; retries do not double-count; revision changes follow the chosen carry-forward/reset policy. Per-user tests prove isolation. Review weighting has deterministic tests and never expands the selected lesson scope.

### M6 — Privacy, release validation and live operations

- **M6a account download/deletion is implemented;** see section 12.10 and [ACCOUNT_DATA.md](ACCOUNT_DATA.md). Remaining work: privacy/legal notices, retention/backup policy, SMTP delivery checks, responsive/accessibility polish and production cookie/CSRF/cache checks. Finalise actual publisher/contact and retention values with the owner before public launch.
- Validate section 14 end to end: first bootstrap, automatic deployment after a successful `main` push, failure before/after cutover, preserved sessions/progress and rollback. Verify HTTPS routing, release SHA, backup restore and host runtime/platform requirements.
- Only after the deployed sync command has passed dry-run/live checks, configure the single Monday 05:17 host-timezone cPanel PHP cron. Verify paths, shared locks, private logs, failure reporting and a recorded cron execution. Catalogue refresh remains independent from deployment.
- **Acceptance:** the complete two-mode product passes production-like smoke tests; automatic deploy and rollback are demonstrated; export/deletion work; the host serves PHP/static assets without a persistent Node process; the scheduled import has executed successfully. Record exact commands and evidence in README/operations documentation.

## 13. Next implementation handoff

**Next implementation task: finish M6 privacy/retention and release verification.** M0–M5 are locally verified, including both modes, ten courses/27 current lines and private revision-bound recall/review. Read [PROGRESS.md](PROGRESS.md), [ACCOUNTS.md](ACCOUNTS.md) and [REVIEW_COORDINATION.md](REVIEW_COORDINATION.md) before concurrent edits. Imported-layer, London provenance and M5b private architecture approvals are settled; additional architecture remains separate review work. Account download/deletion is implemented in M6a without schema changes. Public-launch publisher/contact, retention and real O2Switch/SMTP/HTTPS/deployment/cron verification remain pending. Remote CI has not run.

**Already approved by owner:** the chosen stack, three independent data layers, Doctrine PHP lesson migrations, Symfony/PHP weekly sync through cPanel cron, a complete Docker development environment, automatic O2Switch host-pull deployment after successful main push CI, the M2a review package's canonical keys/imported tables/update lifecycle, London authored provenance and the one-column M5b assessment/review design. No YAML lesson synchroniser, Node importer or scheduled GitHub Actions importer. Do not reopen these decisions without a specific implementation problem.

**Imported-layer review completed; remaining curriculum and host details:**

| Detail | Proposed starting point / evidence required |
| --- | --- |
| Canonical state | Approved four-field key with legally relevant en-passant; implemented and cross-checked including pinned en-passant |
| Source identity | Approved batch-scoped exact-record JSON fingerprint; preserve separate names and ordered paths |
| Update lifecycle | Approved append/stage/activate, monotonic revision including rollback, 5% removed-identity and 10% family-count safeguards; implemented/tested in M2c |
| Relational constraints | Approved seven-table DDL implemented and tested against exact reference; source-path continuity and immutable edge destination remain importer invariants |
| Bootstrap/migrations | Separate discovery/history, guarded lesson execution, shared lock and real pinned sync bootstrap verified; deployment hook uses the complete PHP preparation command |
| Lesson/session revisions | Implemented immutable revisions, pinned browser payloads and exact old-revision completion; changed lines do not inherit completion automatically. M5b keeps old practice as separate history and legacy recall unknown; no inferred carry-forward. |
| Current curriculum | Ten courses/27 finite current lines; London revision 3, four courses revision 2 and five new courses revision 1. Frozen imported named-path membership and separately approved London facts are validated against PHP/chess.js and the full pinned source. Additional teaching requires deliberate migrations. |
| Host compatibility | Verify web/CLI PHP, extensions, real MariaDB version, SSH access, symlink cutover, document root and cron timezone; resolve any mismatch with the 10.11+ target |

Variation practice defaults to learned lines, with explicit selection of enabled course lines. Keep the first curriculum small enough to review accurately. Future datasets require their own licence review.

For v1 import opening **names/sequences**, not the large game-statistics database.

### Source links / references

- Lichess openings (read upstream README): https://github.com/lichess-org/chess-openings
- Lichess open database (separate datasets and licences): https://database.lichess.org/
- Chessground: https://github.com/lichess-org/chessground
- chess.js: https://github.com/jhlywa/chess.js
- Symfony LTS: https://symfony.com/releases/7.4
- O2Switch cPanel cron: https://faq.o2switch.fr/cpanel/outils-avances/tache-planifiee-cron/
- PHP chess library candidate: https://packagist.org/packages/p-chess/chess
- PHP-only weekly importer and cron specification: [OPENING_UPDATES.md](OPENING_UPDATES.md)
- CNIL GDPR: https://www.cnil.fr/fr/comprendre-le-rgpd

## 14. Automatic O2Switch deployment

### 14.1 Reuse the supplied template

The owner selected [Starbugstone/test-auto-deploy](https://github.com/Starbugstone/test-auto-deploy), inspected at commit **`76e1ca9f956bd5a1542c159ea44be1a1b0a591e5`** on 2026-10-15. Its current [deployment design](https://github.com/Starbugstone/test-auto-deploy/blob/76e1ca9f956bd5a1542c159ea44be1a1b0a591e5/docs/deployment-design.md), [reuse guide](https://github.com/Starbugstone/test-auto-deploy/blob/76e1ca9f956bd5a1542c159ea44be1a1b0a591e5/docs/new-project.md) and controller use **host-pull/build**, while `.github/workflows/deploy.yml` is a superseded manual SSH draft. Reuse the current controller, installer/configurator, local Docker stack and relevant tests; do not enable that old workflow or introduce a second deploy mechanism.

The template documents a successful deployment for its own demo. **This project has no configured host or verified deployment yet.** Selected tracked files have been adapted, preserving this repository's docs/licence and [recording provenance](DEPLOYMENT_TEMPLATE.md). Demo database migrations, React examples, secrets, caches and sample business endpoints were excluded.

```text
push to main -> GitHub CI tests/builds exact SHA
                         |
O2Switch deploy cron -> verify successful push CI for current main SHA
                      -> export source to private build directory
                      -> locked Composer install + Vue build with Node/npm
                      -> prepare schema/catalogue prerequisites/lesson migrations
                      -> warm cache -> switch current -> verify health + release ID

O2Switch weekly opening cron -> current/bin/console app:openings:sync
                               (PHP only; updates catalogue, not code or lessons)
```

CI validates pull requests and main pushes, without uploading build archives. A host-side poll checks approximately once a minute; delivery therefore includes CI time, polling delay and host build time. Only a successful **push** run of the configured workflow for the exact current `main` SHA qualifies. Retain the template's final SHA/CI recheck, build lock/time limit, failed-attempt tracking, explicit retry and rollback behaviour. Once enabled, ordinary qualifying updates deploy without a manual approval step.

### 14.2 One-time host setup (pending)

1. Select the project domain and a separate staging target/database for verification. Confirm web/CLI PHP and extensions, Composer, Node/npm, Git, `flock`/`timeout`, available disk/resources and the real MariaDB version. Align local/CI runtimes; resolve any conflict with the intended MariaDB 10.11+ baseline before permanent schema work. O2Switch's [Symfony guide](https://faq.o2switch.fr/guides/php/heberger-application-symfony/) is a starting point, not verification of this account.
2. Create this project's checkout and a private deployment root, proposed `/home/ACCOUNT/apps/chess-openings`. Use public HTTPS Git access if appropriate, otherwise a dedicated read-only deploy key with verified GitHub host fingerprints. Do not assume the template demo's connection/configuration applies to this repository.
3. Install the reviewed `scripts/o2switch/` control files outside the webroot using the template installer/configurator. Create host-only `control/config.json`, `shared/.env.local` and `shared/github-token` with private permissions. Set repository, `main`, CI workflow identity, PHP/Composer/Node paths, URL and retention. The GitHub token needs repository-appropriate **Actions: read**; optional deployment status reporting adds **Deployments: write**. Record token expiry. Production DB/SMTP/app secrets and Git credentials stay on hosting; CI needs none of them.
4. Create a dedicated DB/user and configure SMTP, stable `APP_SECRET`, `APP_ENV=prod`, `APP_DEBUG=0` and the canonical URL. Point the domain at **`DEPLOY_ROOT/current/public`**: the template releases the built backend as the release root, so there is no `backend/` segment there. Verify ancestor traversal, symlink support, HTTPS, `/api` routing, Vue Router deep links, hidden-file protection and ACME certificate renewal.
5. Keep sessions and operational logs in private shared paths across releases; keep Symfony compiled cache per release. Use DB/shared import locks so switching releases cannot create competing sync locks. Retain old content-hashed assets for the supported active-session lifetime and make them reachable after cutover; retaining inaccessible old release directories alone is insufficient.
6. Run controller preflight, a manual staging deployment, schema/bootstrap checks, release/readiness checks and rollback before enabling polling. Measure a full hosting build against account resource/time limits. Then enable the poller and install its cPanel cron. Credentials, actual paths/domain and access will be needed at that stage; no host change is performed by this planning update.

### 14.3 Chess-specific adaptations

The template's default `symfony-vite` preparation performs one generic `doctrine:migrations:migrate`. **Do not copy that call unchanged once lesson data migrations exist.** The current controller calls `custom_prepare_script` only for the **`custom`** profile, not `symfony-vite`.

Use the template's supported custom build/prepare hooks for this application: a small build recipe performs the same locked Symfony/Vite build and produces `NEW_RELEASE/public/index.html`; a preparation recipe links host-only configuration/shared state and performs the reviewed migration sequence. Install reviewed hook copies under `control/`, as required by the template. Avoid rewriting its Git/CI gate, locking, activation or retention machinery.

- **Fresh installation:** apply schema migrations; import a recorded immutable baseline with the PHP sync command; apply lesson data migrations; warm cache. Keep the initial source reference reproducible and validate all lesson prerequisites. Do not make first installation depend on whatever upstream happens to contain that day.
- **Later releases:** apply compatible schema migrations; validate imported graph prerequisites; apply intentional lesson data migrations; warm cache. Do not download/sync the upstream catalogue on every code deployment. A lesson requiring absent facts must fail clearly and receive a deliberate pinned catalogue preparation step.
- Preserve the template's health protocol: `/api/health` returns `{"status":"ok"}` only when required backend/DB readiness checks pass, and `X-Release-Id` identifies the executing release. An unavailable DB returns a generic 503. Keep bounded handling of stale PHP/realpath responses; do not accept an old release's healthy response as success.
- Preserve account sessions and in-progress lesson graphs through code changes; validate old lesson-revision submissions according to the reviewed policy. Retain previous assets and avoid shared-cache leakage of authenticated responses.
- Coordinate deployment preparation with catalogue sync: use a shared maintenance/import lock around schema/content preparation so the weekly command cannot race migrations. Bootstrap must coordinate importer lock ownership rather than deadlock by acquiring it twice. Do not hold that lock during the entire npm build. An active import may finish before preparation proceeds.
- Keep three successful releases by default, bounded private logs and clean abandoned builds. Automatic code rollback restores the previous release after failed health checks. Database migrations are not undone by a symlink switch: use backward-compatible changes, tested backups/restores and explicit recovery for incompatible failures.
- Installed controller/hooks are private control code; ordinary app deployments do not update them. Document the template's disable/wait/update/reinstall/check/enable maintenance procedure. Application source changes continue to deploy automatically.

### 14.4 Two independent cron entries and acceptance

After the controller and application commands exist, configure **two jobs with different purposes**:

```cron
# Deployment: poll for a new main SHA whose push CI succeeded (example paths).
* * * * * /bin/bash /home/ACCOUNT/apps/chess-openings/control/run.sh /ABSOLUTE/PHP poll
# Opening data: one weekly PHP importer; no Node/build/deployment work.
17 5 * * 1 /bin/bash /home/ACCOUNT/apps/chess-openings/control/opening-sync.sh /ABSOLUTE/PHP
```

These are future examples, not installed tasks. Verify host timezone before setting Monday 05:17 and configure bounded importer logs/failure reporting. Do not install the weekly importer before its command is deployed and tested. The separate deployment poller does not create a second opening-data scheduler.

**Must demonstrate on hosting:** a main push deploys after its CI succeeds with no manual deploy command; failed/pending/PR-only CI cannot deploy; secrets remain private; fresh bootstrap and upgrades use the correct migration phases; concurrent pollers do not overlap; build or migration failure leaves prior code active; health failure restores prior code; sessions/progress survive; rollback disables polling until deliberately re-enabled. Verify the live release ID and database readiness, not only green CI. The weekly PHP sync must also pass independently. Local controller/installer/custom-preparation tests pass, but no remote workflow or host operation has been executed.

---

**Design principle:** Keep chess facts, selected theoretical lesson paths, and personal progress separate. The user learns named theory; the software should never present absence from a curated line as a proven chess mistake.
