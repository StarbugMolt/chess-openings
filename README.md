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
| Rules & notation | **chess.js** | Legal moves, SAN, UCI, PGN, FEN |
| UI | **Element Plus** for forms/dialogs, lightweight application CSS | Board-first, touch-friendly; avoid UI bloat |
| Backend | **Symfony 7.4 LTS** (PHP 8.2+), SecurityBundle, Mailer | Target modern PHP; verify hosting PHP availability |
| Persistence | **MariaDB**, Doctrine ORM + DBAL + Migrations | Opening data and per-user data in separate logical tables |
| Import pipeline | **TypeScript + chess.js** offline, Symfony CLI/DBAL for database loading | No runtime Lichess API; pin upstream dataset revision |
| Testing | **Vitest**, **Playwright**, **PHPUnit** | Opening graph validation and end-to-end training flows |
| Deployment | Static Vite assets + Symfony API on compatible shared PHP hosting | Prefer same-origin HTTPS; build JS in local/CI, not on the host |
| Licence | **GPLv3** (GPL-3.0-only for this project's code) | Lichess opening data is separately CC0; honour third-party notices |

See the full [engineering plan](docs/IMPLEMENTATION_PLAN.md) for architecture, proposed SQL schema, importer stages, endpoints, test cases, security/GDPR and execution milestones.

## How the data works

1. Pin and obtain the official [Lichess chess-openings](https://github.com/lichess-org/chess-openings) TSV files (`a.tsv` through `e.tsv`).
2. Offline, replay each PGN with chess.js; validate every move; construct **position nodes** and **legal move edges**, deduplicating transpositions by a canonical position key.
3. Keep each imported opening's ECO/name/source path as independent **catalogue metadata**.
4. Curate lessons (main lines, accepted alternative paths, annotation/hints) **separately** from the imported catalogue.
5. Serve only a selected lesson's relevant graph to the browser. No call to the Lichess API at play time.

A named opening is **not** synonymous with a lesson, an ECO code is **not** a unique opening ID, and a position may have multiple names/paths.

### Approved storage architecture

The project uses **three distinct layers**: **(1)** an imported chess position/move graph with separately preserved Lichess names and source paths, **(2)** our curated lessons and theoretical branches, and **(3)** private accounts and progress. An upstream opening-data update must never overwrite lessons or player progress.

**Lesson content is authored in Git as YAML under `lessons/` (source of truth).** A separate, repeatable CLI loader validates the named sequences, optional explanations and lesson metadata, then synchronises them into MariaDB for fast training queries. No database-only editing or lesson editor is planned for v1. The detailed table layout remains a proposal; the layer separation and YAML-based authoring are accepted design decisions.

## Proposed repository layout

This is a target layout; directories will be created as the implementation progresses.

```text
frontend/                   # Vue app, Chessground wrapper, training composables
backend/                    # Symfony API, entities, auth and migrations
tools/opening-importer/     # Offline TSV -> validated position graph
lessons/                    # Version-controlled, curated lesson definitions
docs/IMPLEMENTATION_PLAN.md # Authoritative implementation notes
.github/workflows/          # Tests, build and optional deployment
```

## Local development

**Not available yet.** The implementation agent will create the Symfony/Vue scaffolding, Docker Compose development database, example environment files, migration commands, repeatable import commands and seed lessons as the first milestones. Do not assume `npm install`, `composer install` or a database import is runnable in this documentation-only state.

Suggested local requirements: supported PHP/Composer, Node.js LTS with package manager, MariaDB 10.11+, and Docker Compose optionally for local database services. Versions and commands should be recorded once the scaffolding is committed.

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
