# Chess Openings

An open-source, interactive chess opening trainer. Learn a precise opening line first, then practise against a computer that selects from the *recognised variations of that opening* so you learn to respond, not just memorise one move sequence.

> **Status: planning / not implemented yet.** This repository currently contains the product specification and implementation roadmap, not a runnable application. Coding agents should start with [docs/IMPLEMENTATION_PLAN.md](docs/IMPLEMENTATION_PLAN.md).

## The idea

Opening courses often teach one continuation at a time. Real opponents choose different variations. Chess Openings bridges that gap while deliberately staying focused on **opening theory**, not full-game AI or an unrestricted chess sandbox.

### Two core modes

| Mode | Behaviour | Goal |
| --- | --- | --- |
| **Guided Learning** | Select an opening, colour and exact line. The computer follows that line; the player learns its intended responses, with optional hints and explanations. | Learn classical main lines and named variations accurately. |
| **Variation Training** | Select an opening family or a subset of learned variations. The computer chooses among supported theoretical branches; the player chooses among the recognised continuations included in the lesson. | Recognise variations, make decisions and build recall across branches. |

Both modes use the same opening graph. In strict lessons, a valid but different move is **not automatically a chess mistake**; it simply leaves the assigned line. In variation training, any continuation included in the selected lesson scope can be followed. Off-lesson moves get a neutral explanation and retry/return option, not an invented engine verdict.

### Learning experience

- A large, responsive chessboard with drag/drop and tap-to-move.
- Current opening, ECO code and variation name; optionally hide/reveal the name during practice.
- Main line first, then progressively unlock common sidelines and deeper named variations.
- Minimal, friendly coaching; explanation and hint controls are available **on demand**, not constantly overlaid on the board.
- Move history, previous/next/restart, board orientation, branch switching and training review.
- Saved account progress across devices; practise one line or mix already learned branches.
- Opening recognition handles **transpositions** (different move orders leading to the same position).

This is a friendly, approachable teaching experience inspired by clear chess educators, with its **own** voice and no third-party branding or copied lessons.

## Scope

**MVP:** accounts, imported local opening catalogue, opening selection, responsive board, precise guided lessons, branching variation practice, simple authored explanations, saved progress, and GDPR-oriented account controls.

**Not MVP:** Stockfish/AI opponents, arbitrary off-book play, free sandbox, game analysis, PGN game imports, ratings, leaderboards, social features, ads, analytics, paid plans, and automated AI teaching.

**Important:** The [Lichess opening-name dataset](https://github.com/lichess-org/chess-openings) is a collection of *named positions and representative sequences*, not a complete course, master-game statistics database, exhaustive repertoire or move-quality oracle. Curated lessons are a separate layer.

## Technology choices

| Layer | Planned technology | Notes |
| --- | --- | --- |
| Frontend | **Vue 3**, **TypeScript**, **Vite** | Vue Composition API / single-page app |
| State & routing | **Pinia**, **Vue Router** | Domain logic separated from UI |
| Board | **Lichess Chessground** | Directly wrapped as a Vue component; GPL-compatible |
| Browser rules & notation | **chess.js** | Validate player moves, SAN, UCI, PGN, FEN in the Vue app |
| UI | **Element Plus** for forms/dialogs, lightweight application CSS | Board-first, touch-friendly; avoid UI bloat |
| Backend | **Symfony 7.4 LTS** (PHP 8.2+), SecurityBundle, Mailer | Target modern PHP; verify hosting PHP availability |
| Persistence | **MariaDB**, Doctrine ORM + DBAL + Migrations | Opening data and per-user data in separate logical tables |
| Backend importer | **Symfony Console (PHP)** + HttpClient + Doctrine DBAL + PHP chess library | Fetch all five upstream TSVs by immutable Git SHA, replay PGN, validate, upsert/stage/promote locally |
| Automatic updates | **Weekly O2Switch/cPanel cron** calling `app:openings:sync` | Symfony PHP fetches, validates and applies Lichess updates; app refreshes catalogue version |
| Testing | **Vitest**, **Playwright**, **PHPUnit** | Opening graph validation and end-to-end training flows |
| Deployment | Static Vite assets + Symfony API on compatible shared PHP hosting | Prefer same-origin HTTPS; build JS in local/CI, not on the host |
| Licence | **GPLv3** (GPL-3.0-only for this project's code) | Lichess opening data is separately CC0; honour third-party notices |

See the full [engineering plan](docs/IMPLEMENTATION_PLAN.md) for architecture, proposed SQL schema, importer stages, endpoints, test cases, security/GDPR and execution milestones.

## How the data works

1. Pin and obtain the official [Lichess chess-openings](https://github.com/lichess-org/chess-openings) TSV files (`a.tsv` through `e.tsv`).
2. In a PHP Symfony Console command, use a tested PHP chess-rules library to replay each PGN and validate every move; construct **position nodes** and **legal move edges**, deduplicating transpositions by a canonical position key. Cross-check representative positions against frontend chess.js tests.
3. Keep each imported opening's ECO/name/source path as independent **catalogue metadata**.
4. Maintain lessons (main lines, accepted alternative paths, annotations/hints) as version-controlled **Doctrine PHP data migrations**, separately from the imported catalogue.
5. Serve only a selected lesson's relevant graph to the browser. No call to the Lichess API at play time.

A named opening is **not** synonymous with a lesson, an ECO code is **not** a unique opening ID, and a position may have multiple names/paths.

### Approved storage architecture

The project uses **three distinct layers**: **(1)** an imported chess position/move graph with separately preserved Lichess names and source paths, **(2)** our curated lessons and theoretical branches, and **(3)** private accounts and progress. An upstream opening-data update must never overwrite lessons or player progress.

**Curated lesson content is authored as version-controlled Doctrine data migrations** (PHP in Git). Since named opening theory changes relatively slowly, we don't need YAML files, a special content parser or a synchronization loader in v1. Schema migrations establish tables; the initial local Lichess catalogue import establishes chess edges; **then** lesson data migrations insert the taught paths and explanatory notes. Preserve stable lesson IDs/revisions and user progress. The three-layer architecture and migration-based lessons are approved decisions; exact columns and migration execution configuration remain implementation details.

## Proposed repository layout

This is a target layout; directories will be created as the implementation progresses.

```text
frontend/                   # Vue app, Chessground wrapper, training composables
backend/                    # Symfony API, entities, auth and migrations
backend/src/Service/OpeningImport/ # PHP fetch, TSV/PGN replay, graph staging and activation
backend/migrations/         # Schema and versioned curated-lesson data migrations
docs/IMPLEMENTATION_PLAN.md # Agent handoff, migrations and implementation roadmap
docs/OPENING_UPDATES.md     # Weekly Lichess refresh and app-reaction contract
ops/cron/                   # Example alternative host crontab (not installed)
.github/workflows/          # Tests, build and optional deployment
```

## Weekly Lichess opening updates (planned)

The **Symfony backend owns the entire refresh**. An O2Switch/cPanel cron task will invoke a PHP CLI command **each Monday at 05:17 in the host server timezone (to be verified)**:

```cron
17 5 * * 1 /opt/alt/php83/usr/bin/php /home/ACCOUNT/chess-openings/backend/bin/console app:openings:sync --env=prod --no-interaction >> /home/ACCOUNT/logs/chess-openings-sync.log 2>&1
```

The PHP binary/path and account directory are illustrative. The command is **specified but not implemented or installed yet**. PHP's Symfony HttpClient retrieves all five official TSV source files at one immutable upstream Git SHA; a PHP chess library replays/validates them; Doctrine DBAL stages and atomically promotes the revised local MariaDB catalogue if the content changed. The recommended library for an initial proof of concept is [p-chess/chess](https://github.com/p-chess/chess) (MIT; Composer), to be tested against chess.js golden fixtures.

Once an import is activated, the API publishes a new `catalogueRevision` and Vue/Pinia refreshes stale opening names/metadata at the next navigation or training session. **Current sessions, curated Doctrine-migration-managed lessons and private progress must never be silently modified by upstream data.** A newly catalogued line is searchable but cannot be played by the training computer until it is deliberately added to a reviewed lesson migration. On bad source data, network failure or interrupted import, keep the last valid catalogue.

No Node.js importer, GitHub Actions scheduled importer or remote bundle pipeline is required. Node is used for **building/testing the Vue frontend only**. Read [OPENING_UPDATES.md](docs/OPENING_UPDATES.md) and the [O2Switch cron example](ops/cron/opening-catalogue.crontab.example) for the full command, error handling, acceptance checks and host installation steps.

## Local development

**Not available yet.** The implementation agent will create the Symfony/Vue scaffolding, Docker Compose development database, example environment files, migration commands, the Symfony/PHP importer command, scheduled host cron instructions and curated lesson data migrations as the first milestones. Do not assume `npm install`, `composer install` or a database import is runnable in this documentation-only state.

Suggested local requirements: supported PHP/Composer and PHP CLI with HttpClient/JSON/ctype support, Node.js LTS for the Vue frontend, MariaDB 10.11+, and optionally Docker Compose for the development database. Versions and commands should be recorded once the scaffolding is committed.

## Privacy / RGPD

Accounts are used to save and sync lesson progress. Collect the minimum needed: email address, hashed password, internal identifier and optional display name. Use first-party Symfony sessions with secure, HttpOnly, SameSite cookies; don't store authentication tokens in localStorage. Provide email verification/password reset, an accessible privacy notice, account deletion, personal-data export, documented retention, and an authorisation check on every user-data endpoint.

The MVP has **no advertising or non-essential trackers**. An authentication/session cookie that is strictly necessary does not ordinarily require a cookie-consent banner, but privacy information is still necessary. GDPR compliance must be **tested and reviewed before deployment**, not assumed from this README.

## Contributing / development workflow

Start at [docs/IMPLEMENTATION_PLAN.md](docs/IMPLEMENTATION_PLAN.md); implement in small, tested milestones. Keep source-derived chess knowledge separate from editorial teaching content and user progress. Document any deviations from the chosen stack or schema before coding them.

## Upstream references

- [Lichess opening names (CC0)](https://github.com/lichess-org/chess-openings)
- [Chessground (GPL-3.0-or-later)](https://github.com/lichess-org/chessground)
- [chess.js (BSD-2-Clause)](https://github.com/jhlywa/chess.js)
- [Symfony 7.4 LTS](https://symfony.com/releases/7.4)
- [CNIL: understand GDPR](https://www.cnil.fr/fr/comprendre-le-rgpd)

## Licence

Project code is intended to be published under **GNU GPL version 3**; see [LICENSE](LICENSE). Source Lichess opening facts/data remain subject to their own **CC0** dedication. Preserve attribution and licence notices for bundled third-party components as required.
