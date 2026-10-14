# Chess Openings — Implementation Plan

> **Status (2026-10-14): specification for an empty repository. No application code is implemented.**
>
> **Agent start here.** Treat the "Accepted decisions" as requirements. The importer/schema is a **proposed concrete baseline** to review with the owner before its first irreversible migration; keep changes small and testable. Update this document when a decision changes. Do not quietly replace the chosen stack or expand scope.

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
| Rules, PGN, FEN, UCI | [chess.js](https://github.com/jhlywa/chess.js) | Authoritative legal moves and SAN calculation on frontend/offline importer |
| UI | Element Plus for forms/dialogues + minimal scoped CSS / variables | Keep chessboard focus; accessible/touch friendly |
| Backend | Symfony **7.4 LTS**, PHP 8.2+ (prefer PHP 8.3/8.4 if host permits) | SecurityBundle, Validator, Mailer, Serializer |
| Persistence | MariaDB (target 10.11+), Doctrine ORM/Migrations, DBAL for bulk import | Charset utf8mb4; explicit indexes and FKs |
| Auth | Symfony Security sessions, password hasher, login throttling, CSRF | Same-origin API and Secure/HttpOnly/SameSite session cookies |
| Source importer | Standalone Node/TypeScript tool with chess.js; Symfony Console DBAL loader | Run offline/local/CI; not as an on-demand production HTTP endpoint |
| Testing | Vitest (unit), Playwright (e2e), PHPUnit (API), static analysis/lints | Validate full opening input and edge invariants |
| Local tooling | Composer, Node LTS, package lock, optional Docker Compose MariaDB | Agent selects/pins compatible runtime versions; document exact commands |
| Deployment | Build Vue in CI/local; deploy built static assets + Symfony/PHP API on shared host | SSH deployment, same-origin routing, DB migrations, secrets outside Git |

**Licences:** repository code GPL-3.0-only, official text in `LICENSE`. Preserve upstream notices for Chessground and chess.js. Lichess named-opening data: CC0; preserve provenance in importer tables and documentation. Check licence of **any additional datasets** independently; Lichess broadcasts are not all CC0. Do not use a Lichess account or request their API for runtime training.

## 4. Suggested code organisation

```text
/
  README.md
  LICENSE
  docs/IMPLEMENTATION_PLAN.md
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
      Service/Progress/                    # progress write/reads
      Command/                             # import loader commands
    migrations/
    tests/
  tools/opening-importer/
    src/                                   # parse, normalise, validate, emit
    fixtures/                              # small deterministic TSV test inputs
    tests/
  lessons/
    italian-game/                          # first manually authored lesson
  .github/workflows/
```

This is a proposed layout, not a promise that those files exist today. Minimise circular dependencies. A shared TS type package is optional; prefer a small OpenAPI/schema contract over introducing monorepo complexity prematurely.

**Critical layering:**
1. **Chessground renders** pieces, drag/drop and arrows; it is *not* the game-rules authority.
2. **chess.js validates** moves and converts PGN/SAN/UCI/FEN; create a fresh controlled game state for each session.
3. **Training domain service** decides which *legal* moves are in the selected lesson, tracks current path and exercises policy.
4. **Symfony API** serves imported catalogue + curated lesson graphs, accounts and progress; it does not need to run Stockfish.
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

## 6. Proposed importer (agent design baseline; review before schema freeze)

### 6.1 Two-step, offline pipeline

**Step 1 — Extract/validate (TypeScript tool, runs locally or in CI):**
1. Pin a specific upstream commit SHA in an importer manifest. Download/clone official TSVs as a build task; no runtime remote call.
2. Record upstream repository URL, SHA, file checksums, importer version and run timestamp; support offline reruns from already downloaded TSVs.
3. Stream/parse TSV as tab-separated text with correct quoting (use a mature parser, not naive `split('\t')`). Reject missing required fields or malformed ECO/PGN.
4. For each source row: initialise chess.js at standard start, replay the PGN and collect verbose legal moves, UCI, SAN and *every intermediate canonical position* including root and terminal.
5. Canonical position key should represent **board placement + side to move + castling rights + legally relevant en-passant**. Exclude halfmove and fullmove counters. Apply one tested normalisation consistently. The key cannot simply be `piecePlacement` or raw entire FEN.
6. Create a **position node** for each unique canonical key and a **directed edge** for each distinct `(fromPosition, UCI)` pair; confirm edge's `toPosition` is always identical when repeated.
7. Preserve every named source entry with ECO, full name, original PGN, stable source fingerprint, terminal position and complete ordered edge sequence. Do not collapse different named lines just because they transpose.
8. Produce deterministic, sorted `positions.ndjson`, `edges.ndjson`, `opening_entries.ndjson`, `entry_steps.ndjson`, and `import-report.json`; use reproducible output ordering and no timestamps inside record IDs.
9. Compare against a fixed collection of known paths/positions; fail generation on illegal moves, inconsistent edge targets or silently missing rows. Print counts of source rows, duplicate positions, transpositions, names sharing a position and invalid records.

**Step 2 — Load to MariaDB (Symfony CLI / Doctrine DBAL):**
1. Validate generated manifest/checksums and read the files outside HTTP, in bounded batches.
2. Run under a migration-safe import lock; write an `opening_import_batch` record and use transactions/staging where practical.
3. Upsert positions by `position_key`, edges by `(from_position_id, uci)`, entries by source fingerprint and line steps by `(entry_id, ply)`.
4. Store the source batch/active version; deactivate old named entries only **after successful validation** and only if no active lesson depends on deleting them. Never overwrite editorial notes or personal progress during imports.
5. A second run of the same manifest must be a **no-op on logical data**. Re-import with an updated upstream SHA should be deterministic and idempotent.
6. Emit counts/diffs before applying an upstream update; support `--dry-run`, `--validate-only` and a reproducible import report. Never run destructive imports as a public HTTP request.

**Why two steps?** chess.js is shared with the Vue client and handles PGN/FEN consistently, while the server deployment remains PHP-only. Node is a **build/import tool**, not a production service. If a single PHP importer is preferred later, it must use a fully tested legal-move implementation and preserve the exact same canonical key semantics.

### 6.2 Transposition and naming rules

- **Position identity:** same legal position => one node even if move order differs.
- **Line identity:** do not merge a sequence/history into a node. Preserve ordered path per source entry and curated lesson line.
- **Name identity:** a position can have multiple labels. Never store one global `variation_name` on `chess_position`.
- **Recognition:** given the played history, examine named positions reached, prefer the deepest/specific matching name in the **selected lesson's context**, then fall back to a neutral family label. If names are equally valid at the same node, support aliases rather than falsely declaring one universal name.
- **Training traversal:** resolve allowed edges from **selected authored lesson lines**, not from the entire imported catalogue graph. After a transposition, matching a supported position and its lesson-scope outgoing edge is valid, but preserve depth limits and selected lesson context.
- **Cycles:** graph may contain repeated positions. Cap lesson depth/plies and disallow accidental looping through an "unbounded training" mode.

**Canonical-key tests:** two legal move orders that reach the same position must yield the same key (example: `1.d4 d5 2.c4 c6` and `1.d4 c6 2.c4 d5`); positions with different castling rights or capturable en-passant possibilities must stay distinct. For en-passant, do not keep a phantom target square when there is no legal capture.

## 7. Proposed MariaDB schema (review before implementation)

This design deliberately separates **source graph**, **editorial lesson graph** and **user data**. Create it with Doctrine Migrations, proper FKs/indexes and realistic data fixtures; names may be adjusted to project conventions.

### 7.1 Imported chess facts (shared/read mostly)

| Table | Key columns | Constraints / purpose |
| --- | --- | --- |
| `opening_import_batch` | `id`, `source_repo`, `upstream_sha`, `source_checksum`, `importer_version`, `status`, `created_at` | Provenance, repeatable import and safe cutover |
| `chess_position` | `id`, `position_key` (canonical 4-part FEN/EPD) | **UNIQUE(position_key)**; one position per legal state |
| `chess_move` | `id`, `from_position_id`, `to_position_id`, `uci`, `san` | **UNIQUE(from_position_id, uci)**; FK on both nodes; index destination |
| `opening_entry` | `id`, `source_uid`, `eco`, `full_name`, `original_pgn`, `terminal_position_id`, `import_batch_id`, `active` | **UNIQUE(source_uid)**; ECO/name may repeat |
| `opening_entry_step` | `opening_entry_id`, `ply`, `chess_move_id` | **PK(opening_entry_id, ply)**; ordered source line |

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

### 7.2 Editorial learning content (version-controlled source)

| Table | Key columns | Constraints / purpose |
| --- | --- | --- |
| `lesson` | `id`, `slug`, `title`, `playing_color` ('w'/'b'), `description`, `status`, `sort_order` | One curated opening-family course for one colour; **UNIQUE(slug)** |
| `lesson_line` | `id`, `lesson_id`, `slug`, `title`, `tier`, `sort_order`, `source_opening_entry_id` nullable, `enabled` | An explicitly curated **finite playable line**; **UNIQUE(lesson_id, slug)** |
| `lesson_line_step` | `lesson_line_id`, `ply`, `chess_move_id` | **PK(lesson_line_id, ply)**; line's complete ordered path; validate `from -> to` continuity |
| `lesson_note` | `id`, `lesson_id`, `lesson_line_id` nullable, `position_id` nullable, `chess_move_id` nullable, `kind`, `locale`, `text`, `sort_order` | Optional introduction, concept, hint, "why" or plan at a position/edge, with scope/priority |

Use version-controlled YAML/JSON definitions under `lessons/`, imported with a **separate** CLI/seed command after the graph import. Example:

```yaml
slug: italian-white
title: Italian Game (White)
playing_color: w
status: published
lines:
  - slug: giuoco-piano-c3
    title: Giuoco Piano — 4.c3
    tier: 1
    uci: [e2e4, e7e5, g1f3, b8c6, f1c4, f8c5, c2c3]
    notes:
      - after_ply: 7
        kind: why
        text: "c3 prepares d4 to challenge Black's centre."
  - slug: giuoco-pianissimo-d3
    title: Giuoco Pianissimo — 4.d3
    tier: 2
    uci: [e2e4, e7e5, g1f3, b8c6, f1c4, f8c5, d2d3]
```

This is **illustrative seed data**, not a comprehensive or sufficiently deep Italian course. Every authored `uci` step must exist in or be deliberately added to the validated graph; a valid custom teaching line may extend beyond any Lichess named entry but must still pass chess.js legality validation. Do not rewrite/edit the upstream `opening_entry` to add lesson prose.

**Editorial invariants:** Published lessons have a reachable, legal path from their declared start position; a stop/end condition; a supported colour; correctly configured opponent/user turns; no dangling steps; no empty branch after a computer-selected move; and a coherent label when the line crosses a variation boundary.

### 7.3 Private accounts and learning state

| Table | Key columns | Constraints / purpose |
| --- | --- | --- |
| `app_user` | `id`, `email` unique (normalised), `password_hash`, `display_name` nullable, `verified_at`, `created_at`, `last_login_at` | Minimal identity; no names, DOB or address required |
| `user_line_progress` | `user_id`, `lesson_line_id`, `attempts`, `completions`, `successes_without_hint`, `last_practised_at`, `next_review_at` | **UNIQUE(user_id, lesson_line_id)**; coverage/mastery per line |
| `user_position_progress` | `user_id`, `lesson_id`, `position_id`, `attempts`, `accepted_moves`, `hints_used`, `last_seen_at` | **UNIQUE(user_id, lesson_id, position_id)**; identify weak decision points |
| `practice_session` (optional minimal) | `id`, `user_id`, `lesson_id`, `mode`, `started_at`, `finished_at`, `covered_line_ids` or lightweight summary, `error_count` | Only retain what improves learning; define retention |

Choose an explicit deletion policy: `ON DELETE CASCADE` for user-owned progress when account is removed; `RESTRICT` for published curriculum references to shared chess facts. Treat `covered_line_ids` as a short JSON summary initially only if necessary; don't store raw move-level interaction logs indefinitely. Record a lesson content revision when changing published line IDs/steps so existing progress remains interpretable (migrate or reset with notice).

**Progress model:** A "completed lesson" and "understands all variants" are not equivalent. Track individual line exposure plus accuracy at decision positions; favour rarely practiced lines for the next computer branch. Any percentage shown to users must define its denominator (e.g. unlocked line completions, not all Lichess names).

### 7.4 Index and integrity notes

- `chess_position.position_key` unique; `chess_move(from_position_id,uci)` unique; destination indexed.
- `opening_entry(eco)`, `opening_entry(full_name)` for lookup/search, `opening_entry(terminal_position_id)` for labelling.
- `opening_entry_step(entry_id,ply)`, `lesson_line_step(line_id,ply)`, `lesson_line(lesson_id,tier)`.
- `user_line_progress(user_id,next_review_at)`, `user_position_progress(user_id,lesson_id)`.
- FKs with deliberate delete behaviour; no cascades that erase editorial content during an upstream data refresh.
- Import in consistent dependency order: positions -> moves -> source entries -> entry steps -> authored lessons -> progress.
- Validate uniqueness on the same SQL collation rules used by the application. Normalise emails explicitly; don't rely on case-sensitive raw input.
- Use prepared statements/batched inserts; never store or query legal move lists via large comma-separated strings.

## 8. Minimal HTTP API contract (proposal)

Keep browser and Symfony on the **same origin** to simplify auth/cookies and deployment.

**Auth (public unless noted):**
- `POST /api/auth/register`, `POST /api/auth/login`, `POST /api/auth/logout`, `GET /api/me`.
- Verify email, request/reset password via tokenized, expiring links. Rate-limit and avoid account enumeration.
- `GET /api/me/data-export` and `DELETE /api/me` (require recent password confirmation / re-auth for deletion).

**Catalogue and lessons:**
- `GET /api/openings?search=italian&color=w`: names/ECO/family suggestions (not raw personal data).
- `GET /api/lessons?color=w`: available published courses, tiers and counts.
- `GET /api/lessons/{slug}`: lesson metadata, line selection, progress summary if authenticated.
- `GET /api/lessons/{slug}/graph?tiers=1,2&lines=...`: only the relevant positions, legal *lesson-scope* edges, line paths and optional notes, with labels. Cache as versioned public content if privacy permits; otherwise normal private request.

**Progress (all authorised for current session's user):**
- `GET /api/me/progress`: per lesson/line coverage and review due.
- `POST /api/me/practice-sessions`: bounded completed-session summary (or an equivalent idempotent progress endpoint). Validate lesson/line identifiers and path consistency on server before storing progression; never accept a client-supplied user ID.
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
- Parse all five official files and verify declared counts/checksums; run twice and compare logical table data.
- Every source PGN replays legally; every edge has unique `(from,uci)` and a consistent destination.
- A different move order to same board produces one canonical node but separate source paths.
- Different castling rights or a legally available en-passant capture produce different position keys.
- An upstream catalogue update preserves curated lesson paths/notes and user progress; invalid import leaves previous active dataset intact.
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

## 12. Implementation milestones and acceptance criteria

Use small PRs or commits. **Do not wait for the entire product to be complete before running tests.**

### M0 — Repository bootstrap (first agent task)
- Add actual standard GPLv3 licence (if not present), .gitignore, frontend/backend skeleton, Node/Composer lockfiles, formatting/lint configs, env examples, Docker Compose MariaDB (development only), basic CI.
- Document exact run/build/migrate/test commands; create a working empty Vue page + Symfony health-check.
- **Done when:** fresh clone can boot locally using the documented commands and CI passes.

### M1 — Chessboard and rule engine
- Vue wrapper for Chessground, lifecycle cleanup, accessibility, mobile/tap controls, highlights, board orientation and chess.js-driven legal-move state.
- Move history/restart/undo and unit tests.
- **Done when:** local test page can play and rewind legal moves on mobile/desktop.

### M2 — Opening catalogue importer
- Pinned Lichess TSV input, TypeScript generator, manifest/report, canonical graph, DBAL loader, Doctrine migrations, repeatable fixtures.
- Search/browse API for named openings; no frontend training yet.
- **Done when:** full dataset imports idempotently, known transpositions deduplicate, catalogue responds with labels, updated import does not destroy curated records.

### M3 — Curated lessons and strict Guided Learning
- Lesson YAML/JSON authoring schema, validator, DB seed loader, first Italian Game family lines (main and common branch) reviewed for chess accuracy.
- Guided training route, minimal explanation/hint drawer and exact-line policy.
- **Done when:** a signed-in player can finish a selected line with correct feedback and replay.

### M4 — Variation Training
- Lesson-scope move graph, deterministic weighted opponent choice, branching accepted replies, name hide/reveal, line selection.
- Show neutral scope limits (not false "bad move" labels) and session summary.
- **Done when:** player can practise multiple Italian branches in a single session, including choice of own recognised continuation and transpositions.

### M5 — Accounts and progress
- Registration, email verification, Symfony session auth, reset, /me endpoints, per-line and per-position progress, simple review weighting.
- Protected API checks and per-user e2e tests.
- **Done when:** account progress survives logout, a new browser and a different device with no cross-user leakage.

### M6 — Privacy, quality and deployment
- Privacy/legal pages, data export/deletion, retention policy, security review, responsive visual polish, CI and shared-host release guide.
- **Done when:** production-like smoke tests pass, essential-only cookies verified, secrets excluded, backend migrates and deploys without Node runtime.

**Sequencing note:** M5 account scaffolding/identity can begin alongside M2/M3; because login is required for training, provide a minimal working registration/login before declaring M3/M4 user-facing complete. Don't gate importer correctness on auth implementation.

## 13. Next implementation handoff

**Do first:** M0 bootstrap, M1 board/rules test harness, and write a small M2 importer spike that processes ~10 sample PGNs and demonstrates transposition + idempotent output. Do not bulk-import the complete dataset before the position-key and lesson-schema review.

Before building the permanent M2/M3 migrations, confirm with owner:
1. **Position graph + preserved named paths + curated lesson lines** as three separate layers (recommended).
2. Import only opening *names* now; defer large Lichess game datasets and statistical probabilities.
3. Initial learning depth per line should be editorially chosen (often around 8–12 moves, not a fixed universal depth).
4. Default scope of variation practice: already learned lines vs all enabled course lines (make configurable; default learned).
5. Source licence identifier: GPL-3.0-only chosen for project code, CC0 for opening names. Check all additional packages.

### Source links / references

- Lichess openings (read upstream README): https://github.com/lichess-org/chess-openings
- Lichess open database (separate datasets and licences): https://database.lichess.org/
- Chessground: https://github.com/lichess-org/chessground
- chess.js: https://github.com/jhlywa/chess.js
- Symfony LTS: https://symfony.com/releases/7.4
- CNIL GDPR: https://www.cnil.fr/fr/comprendre-le-rgpd

---

**Design principle:** Keep chess facts, selected theoretical lesson paths, and personal progress separate. The user learns named theory; the software should never present absence from a curated line as a proven chess mistake.
