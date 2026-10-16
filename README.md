# Chess Openings

An open-source, interactive chess opening trainer. Learn a precise opening line first, then practise against a computer that selects from the *recognised variations of that opening* so you learn to respond, not just memorise one move sequence.

> **Status: M0–M5 and M6a/M6b are implemented locally.** Nineteen courses/44 current lines offer guided and selected-line variation practice, board hints, sound, accounts, private progress and scheduled reviews. See [practice and review behaviour](docs/PROGRESS.md), [M4 verification](docs/M4_IMPLEMENTATION.md), [accounts](docs/ACCOUNTS.md), [catalogue operations](docs/CATALOGUE.md) and the [implementation plan](docs/IMPLEMENTATION_PLAN.md). M6 adds password-confirmed account download/deletion, the owner-configured privacy notice and bounded retention maintenance. See [privacy/retention operations](docs/PRIVACY_RETENTION.md) for the daily cron and remaining host checks.

The [execution roadmap](docs/IMPLEMENTATION_PLAN.md#12-implementation-milestones-and-acceptance-criteria) includes a complete local Docker environment and automatic O2Switch deployment using the existing [deployment template](https://github.com/Starbugstone/test-auto-deploy). The owner confirms O2Switch is deployed at [chessopening.starbugstone.com](https://chessopening.starbugstone.com/); its public HTTPS health endpoint returned `{"status":"ok"}` on 2026-10-16. Detailed scheduled-job, email and recovery verification remains separate.

## The idea

Opening courses often teach one continuation at a time. Real opponents choose different variations. Chess Openings bridges that gap while deliberately staying focused on **opening theory**, not full-game AI or an unrestricted chess sandbox.

### Two core modes

| Mode | Behaviour | Goal |
| --- | --- | --- |
| **Guided Learning** | Select an opening, colour and exact line. The computer follows that line; the player learns its intended responses, with optional hints and explanations. | Learn classical main lines and named variations accurately. |
| **Variation Training** | Select an opening family or a subset of learned variations. The computer chooses among supported theoretical branches; the player chooses among the recognised continuations included in the lesson. | Recognise variations, make decisions and build recall across branches. |

Both modes use the same opening graph. In strict lessons, a valid but different move is **not automatically a chess mistake**; it simply leaves the assigned line. In variation training, any continuation included in the selected lesson scope can be followed. Off-lesson moves get a neutral explanation and retry/return option, not an invented engine verdict.

### Planned learning experience

- A large, responsive chessboard with drag/drop and tap-to-move.
- Current opening, ECO code and variation name; optionally hide/reveal the name during practice.
- Main line first, then progressively unlock common sidelines and deeper named variations.
- Minimal, friendly coaching; explanation and hint controls are available **on demand**, not constantly overlaid on the board.
- Move history, previous/next/restart, board orientation, branch switching and training review.
- Saved account progress across devices; practise one line or mix already learned branches.
- Opening recognition handles **transpositions** (different move orders leading to the same position).

This is a friendly, approachable teaching experience inspired by clear chess educators, with its **own** voice and no third-party branding or copied lessons.

## Languages

English, French and Spanish are available through the header language button. The first visit follows the browser's preferred supported language, falling back to English. A manual choice is saved in that browser and overrides automatic detection on later visits. Interface text, the ten current courses' teaching notes and account emails are translated. Switching languages keeps an active exercise and its saved progress intact. See [translation behavior and maintenance](docs/I18N.md).

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
| UI | Native HTML forms and lightweight application CSS today; **Element Plus** remains the planned forms/dialogs library | Board-first, touch-friendly; the library is not currently installed |
| Backend | **Symfony 7.4 LTS**, PHP 8.3+, Doctrine, HttpClient, Mailer, Security | PHP 8.3 with sodium locally; verify hosting extensions and PHP availability |
| Persistence | **MariaDB**, Doctrine ORM + DBAL + Migrations | Opening data and per-user data in separate logical tables |
| Backend importer | **Symfony Console (PHP)** + HttpClient + Doctrine DBAL + PHP chess library | Fetch all five upstream TSVs by immutable Git SHA, replay PGN, validate, upsert/stage/promote locally |
| Automatic updates | **Weekly O2Switch/cPanel cron** calling `app:openings:sync` | Symfony PHP fetches, validates and applies Lichess updates; app refreshes catalogue version |
| Testing | **Vitest**, **Playwright**, **PHPUnit** | Opening graph validation and end-to-end training flows |
| Local development | **Docker Compose**: PHP/Apache + Composer, Vue/Vite, MariaDB, local email capture | Container-based setup/build/tests; Vite proxies same-origin API requests |
| Deployment | Existing **O2Switch host-pull template** | Every main push with successful CI becomes eligible; host builds with Composer/Node and activates PHP/static assets |
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

Current foundations and planned feature locations:

```text
frontend/                   # Vue app, Chessground wrapper, training composables
backend/                    # Symfony API, entities, auth and migrations
backend/src/Service/OpeningImport/ # PHP fetch, TSV/PGN replay, graph staging and activation
backend/migrations/         # Schema and versioned curated-lesson data migrations
docs/IMPLEMENTATION_PLAN.md # Agent handoff, migrations and implementation roadmap
docs/OPENING_UPDATES.md     # Weekly Lichess refresh and app-reaction contract
ops/cron/                   # Weekly source/daily privacy cron examples
.github/workflows/          # Build/test CI checked by the host deployment poller
compose.yaml                # Complete Docker development environment
.docker/                    # PHP/Apache and Node development images
scripts/o2switch/            # Template host deployment controller
scripts/deploy/              # Application build/prepare hooks
```

## Weekly Lichess opening updates

The **Symfony backend owns the entire refresh**. An O2Switch/cPanel cron task will invoke a PHP CLI command **each Monday at 05:17 in the host server timezone (to be verified)**:

```cron
17 5 * * 1 /bin/bash /home/ACCOUNT/apps/chess-openings/control/opening-sync.sh /opt/alt/php83/usr/bin/php
```

The PHP binary/path and account directory are illustrative. The installed wrapper bounds private importer output separately from deployment logs and invokes the PHP command. **The command is implemented and locally verified; host-cron activation has not been independently verified in this session.** Symfony HttpClient retrieves all five TSVs at one resolved immutable Git SHA; the tested p-chess/chess adapter legally replays them; DBAL stages and atomically activates a complete snapshot. No-op checks keep the revision stable; failed checks keep the previous catalogue readable. See [catalogue operations](docs/CATALOGUE.md) for safeguards, dry-run, rollback and retention.

Once an import is activated, the API publishes a new `catalogueRevision` and Vue/Pinia refreshes stale opening names/metadata at the next navigation or training session. **Current sessions, curated Doctrine-migration-managed lessons and private progress must never be silently modified by upstream data.** A newly catalogued line is searchable but cannot be played by the training computer until it is deliberately added to a reviewed lesson migration. On bad source data, network failure or interrupted import, keep the last valid catalogue.

No Node.js importer, GitHub Actions scheduled importer or remote bundle pipeline is required. Node is used for **building/testing the Vue frontend only**. Read [OPENING_UPDATES.md](docs/OPENING_UPDATES.md) and the [O2Switch cron example](ops/cron/opening-catalogue.crontab.example) for the full command, error handling, acceptance checks and host installation steps.

## Local development

Requires **Git, Bash, Docker and Docker Compose**. Native PHP, Composer and Node are optional. From the repository root:

```bash
bash scripts/local-up.sh
```

The script builds images, installs locked dependencies, prepares writable volumes, starts the services and applies schema migrations under the database operation lock. It can be rerun after dependency changes; it briefly stops the PHP/Vite containers while installing. Linux user IDs are detected automatically. To change published ports, copy `.env.example` to root `.env` before starting.

Populate the opening library with `make catalogue` (or the `app:database:prepare` command below). This downloads/replays the pinned first catalogue in PHP, then runs the separate lesson migration phase. Later bootstrap calls retain an already active catalogue. Network access is required for first source population; normal checks use offline fixtures.

| Local service | Address |
| --- | --- |
| Vue/Vite preview | [localhost:5173](http://localhost:5173) |
| Guided lessons | [localhost:5173/learn](http://localhost:5173/learn) |
| Variation practice (sign in first) | [Italian](http://localhost:5173/train/italian) / [Caro-Kann as Black](http://localhost:5173/train/caro-kann) |
| Your practice and reviews (signed in) | [localhost:5173/progress](http://localhost:5173/progress) |
| Opening library | [localhost:5173/openings](http://localhost:5173/openings) |
| Register / sign in | [localhost:5173/register](http://localhost:5173/register) / [localhost:5173/login](http://localhost:5173/login) |
| Development board lab | [localhost:5173/dev/board](http://localhost:5173/dev/board) |
| Symfony readiness (checks MariaDB) | [localhost:8080/api/health](http://localhost:8080/api/health) |
| Captured development email | [localhost:8025](http://localhost:8025) |

Vite proxies `/api` to Symfony. The board lab supports drag/tap moves, keyboard coordinate entry, legal destinations, move history, promotion choices, undo, restart and orientation. Accepted moves play a short, low wooden knock, generated locally without external audio assets. **Sound on/off** persists in this browser; illegal moves, pending promotion, undo and board flips stay silent. The lab is a development harness excluded from production builds; the home board is read-only. Both training modes require a verified account and support keyboard input, on-board hints, neutral move feedback and finite automatic replies.

After `make catalogue`, `/learn` offers **nineteen courses/44 current lines**: Italian, London, Queen's Gambit, Ruy Lopez, Catalan, Scotch Game, Four Knights Game and Evans Gambit recommended for White; Caro-Kann, French, Sicilian, King's Indian, Slav, Petrov's Defense, Alekhine Defense, Nimzo-Indian Defense, Grünfeld Defense, Benoni Defense and Tarrasch Defense recommended for Black. London teaches eight White moves through castling, the most the pinned data supports. Black defences start with White's automatic move. Every other current line is a complete pinned named record that gives the learner at least nine moves (9–18); the short introductions remain only in retained earlier revisions. See [revision-2 sources](docs/lessons/NAMED_LINES_REVISION_2.md), [new-course sources](docs/lessons/NAMED_LINES_NEW_COURSES.md), [revision-3 sources](docs/lessons/NAMED_LINES_REVISION_3.md) and [phase-5 course sources](docs/lessons/NAMED_LINES_PHASE_5.md). These are finite lessons. Saved practice stays attached to its exact revision; a catalogue update cannot rewrite an active exercise or its completion.

Choose **Practise variations** on a course card after learning its lines. Select your pieces and training lines, then start. Previously completed lines in the same course revision and colour are selected by default; a new learner can explicitly choose available lines or return to guidance. The opponent favours supported lines with less practice or a due review. Any taught response in the selected scope is accepted. One selected line is described as a fixed sequence. Names can stay hidden until reveal, a coaching hint or completion. Only the exact line played is saved.

Choose **Your practice and reviews** in Account, or **See your practice and reviews** after finishing. Current coverage, measured first-try recall and review suggestions are separate for White and Black. Earlier-version history stays visible; old completions have recall marked unknown. Reviews begin after one day without help, advance on successful due reviews and return in ten minutes after practice with help. Hints and takebacks are welcome and are described honestly. Review links open the exact current line and side. See [the approved M5b contract and verification](docs/PROGRESS.md).

To try accounts, register with an `@example.test` address and password, open its verification link in Mailpit, confirm, then sign in with that same password. The confirmation page asks for no password. Session cookies keep you signed in across refreshes. **Remember me for 30 days** is optional and restores sign-in after the session/browser closes; signing out clears the browser's cookie and password reset invalidates existing sessions and remembered sign-ins. Reset and verification emails remain in local Mailpit. See [account setup, API and lifecycle](docs/ACCOUNTS.md). Account also provides **Download my data** and a separate, cancellable **Delete my account…** form, each requiring the current password. Deletion removes the account, email tokens and all saved practice, then signs out; other browsers cannot restore it. Downloads retain older course revisions and exclude password/token secrets. See [account data controls](docs/ACCOUNT_DATA.md). The [privacy notice](http://localhost:5173/privacy) identifies StarbugStone and chessopenings@starbugstone.com, O2Switch hosting/email delivery and 30-day provider backups. Accounts remain until owner deletion, with no automatic unverified-account purge. Daily maintenance removes expired tokens/limits and known stale session/log/snapshot files; [the operations guide](docs/PRIVACY_RETENTION.md) covers setup, provider boundaries and safe recovery.

```bash
# Static analysis, formatting, unit/API tests, build, Apache smoke checks,
# deployment controller tests and desktop/mobile browser tests.
bash scripts/check.sh

# With other sessions using the stack, require healthy services and reuse them.
CHECK_REUSE_SERVICES=1 bash scripts/check.sh

# Separate network check: fresh DB -> complete immutable source -> all lessons.
bash tests/import/migrations-test.sh pinned-lessons-test.php

# Build the production frontend only.
docker compose run --rm --no-deps frontend npm run build

# Check public privacy configuration and preview cleanup (no deletion).
docker compose exec -T --user www-data app bin/console app:privacy:cleanup --check-config
docker compose exec -T --user www-data app bin/console app:privacy:cleanup

# Send an email to the local Mailpit instance.
docker compose exec -T --user www-data app bin/console app:dev:send-test-email

# Schema -> first pinned catalogue -> separate lesson data migrations.
docker compose exec -T --user www-data app bin/console app:database:prepare --no-interaction

# Check the actual latest upstream source; inspect with --dry-run first if desired.
docker compose exec -T --user www-data app bin/console app:openings:sync --dry-run --env=prod --no-debug
docker compose exec -T --user www-data app bin/console app:openings:status

# Validate the ten pinned opening fixtures entirely in PHP (offline, no DB writes).
docker compose exec -T --user www-data app bin/console app:openings:validate \
  --source-directory=tests/Fixtures/Openings/source

# Download/replay the complete five-file catalogue at one immutable SHA.
docker compose exec -T --user www-data app bin/console app:openings:validate \
  --source-sha=a6189a30dc273ccb21fc2536a9a2fefd5592a67a --env=prod --no-debug

# Inspect the applied schema; lesson migrations use a separate command/history.
docker compose exec -T --user www-data app bin/console doctrine:migrations:list

# View logs; stop while preserving the database/dependency volumes.
docker compose logs -f app frontend mailpit
docker compose down
```

`make dev`, `make catalogue`, `make check`, `make build`, `make mail` and `make stop` are optional shortcuts. Browser tests use the optional `browser` Compose service, so the first check also downloads its Playwright image. All application tooling runs in containers. DB and SMTP ports are private to the Docker network; HTTP ports bind to loopback. Container versions are pinned in Compose/Dockerfiles; dependency versions are locked. MariaDB is locally 10.11.14; the actual hosting version remains to be verified.

Schema setup starts with an inactive revision 0; first catalogue bootstrap activates 3,865 pinned opening records at revision 1. Schema setup does not seed accounts or courses. `app:schema:migrate`, source sync and `app:lessons:migrate` share a database lock; source refresh never runs either migration stream. The API and `/openings` view provide paginated name/ECO-prefix search and revision-based ETags. Pinia rechecks at navigation/new-session boundaries, never on a timer. See [docs/MIGRATIONS.md](docs/MIGRATIONS.md) and [docs/CATALOGUE.md](docs/CATALOGUE.md). The earlier `app:openings:validate` command remains read-only. This session has not installed or independently verified host cron jobs.

M6b verification on 2026-10-16: `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` exited **0** on a fixed snapshot of base `5acdce9` plus the privacy changes, isolated from other agents' active admin/curriculum work. Results: **211 PHPUnit tests / 4,861 assertions, 230 frontend tests and 53 desktop/mobile browser passes**, plus one intentional mobile mouse-drag skip. Static/build/Apache/deployment and actual private MariaDB backup/restore passed. Disposable suites passed **18 constraint, 81 migration, 108 catalogue, 93 account, 84 account-data, 43 privacy, 62 lesson, 109 variation and 146 practice checks**. That snapshot used nine schema/19 lesson migrations, ten courses/25 current lines and 46 retained lines/880 steps; account controls and privacy maintenance added no schema. The shared-source browser run overlapped admin-schema edits, so it is not claimed as a green combined admin release. See [privacy verification and operations](docs/PRIVACY_RETENTION.md) and [current shared-workspace coordination](docs/REVIEW_COORDINATION.md). Compiled dependency notices are linked at `/third-party-notices.txt`.

Combined release verification on 2026-10-16: `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` exited **0** in an isolated Compose project with the redesigned English/French/Spanish frontend and the admin area: **237 PHPUnit tests / 5,039 assertions, 245 frontend units, 79 browser passes and one intentional mobile mouse-drag skip**. All disposable database, static/build, Apache, deployment and backup/restore checks passed. The admin page and updated privacy notice follow the selected language; account identifiers, permissions and password-confirmation rules stay stable during language changes. See [integration evidence](docs/REVIEW_COORDINATION.md).

## Automatic deployment

Reuse [Starbugstone/test-auto-deploy](https://github.com/Starbugstone/test-auto-deploy) and its current **host-pull** flow. Every push to `main` runs CI. An O2Switch cron poller checks for successful push CI on that exact commit, fetches it, installs locked dependencies, builds Vue with Node/npm, prepares the database/cache and switches to a verified release. Apache/PHP serves the result; no persistent Node server is needed. Failed builds keep the previous code active; health failures restore it. Retain three successful releases and treat database recovery separately from code rollback.

The owner has deployed the target. Repository setup examples start the controller disabled; preserve the host’s working private configuration when updating it. Project preparation preserves shared sessions/logs and runs the tested **schema -> first pinned catalogue import -> lesson data migrations** bootstrap before cache warmup. Later deployments retain the active source catalogue. See [template provenance and setup](docs/DEPLOYMENT_TEMPLATE.md). Enable polling after staging verification. The separate PHP-only weekly refresh command exists; install its cron only after deployed verification.

See the [deployment implementation plan](docs/IMPLEMENTATION_PLAN.md#14-automatic-o2switch-deployment) for the pinned template reference, host checklist and acceptance tests. Daily [privacy maintenance](docs/PRIVACY_RETENTION.md) is a third, separate cron role. [GitHub CI](https://github.com/Starbugstone/chess-openings/actions/runs/38861773058) passed on 2026-10-16 for `4e484ef`; every new main push receives its own CI run. This session verified public readiness, but did not install cron or exercise production cutover/SMTP/restore. The new daily privacy cron still needs activation after this increment is deployed.

## Admin area

`/admin` gives administrators usage statistics derived from stored data, plus a user list with search and password-confirmed account deletion (see [ADMIN_REVIEW.md](docs/ADMIN_REVIEW.md)). Admins can only be appointed on the server; the web interface cannot grant or revoke admin:

```bash
docker compose exec -T --user www-data app bin/console app:admin:grant you@example.com   # account must exist and be verified
docker compose exec -T --user www-data app bin/console app:admin:list
docker compose exec -T --user www-data app bin/console app:admin:revoke you@example.com
```

On the host, run the same commands with the host PHP binary and `--env=prod --no-debug`. A grant or revoke signs that account out in every browser; sign in again to use the new role. Administrator deletions are logged to `var/log/admin-*.log` with account IDs only.

## Privacy / RGPD

Accounts collect an email address, hashed password and internal identifier; no display name is currently collected. First-party Symfony sessions use HttpOnly, SameSite cookies, with Secure required in production; authentication tokens are not stored in localStorage. Email verification/password reset are implemented. Password-confirmed personal-data download and account deletion are implemented in Account, with CSRF, persistent limits and owner isolation. Deletion removes live account/token/practice data; it does not rewrite existing backups, logs, temporary abuse limits or captured local email. The owner-approved notice and retention maintenance are implemented: keep accounts until owner deletion, expire temporary records daily, application logs after 14 days without activity and application SQL snapshots after 30 days (also capped at three). O2Switch recovery backups have a separate 30-day cycle. See [the tested account boundaries](docs/ACCOUNT_DATA.md) and [daily maintenance/recovery instructions](docs/PRIVACY_RETENTION.md). Hosting backups can include older application snapshots; these periods may add together.

The MVP has **no advertising or non-essential trackers**. An authentication/session cookie that is strictly necessary does not ordinarily require a cookie-consent banner, but privacy information is still necessary. The privacy notice describes actual controls and owner choices; it is not a compliance certification. Provider access/email-log settings, maintenance activation and safe recovery still need operational verification.

## Contributing / development workflow

Start at [docs/IMPLEMENTATION_PLAN.md](docs/IMPLEMENTATION_PLAN.md), the [navigation/accessibility contract](docs/UX.md) and the [visual design reference](docs/DESIGN.md); implement in small, tested milestones. Keep source-derived chess knowledge separate from editorial teaching content and user progress. Document any deviations from the chosen stack or schema before coding them.

## Upstream references

- [Lichess opening names (CC0)](https://github.com/lichess-org/chess-openings)
- [Chessground (GPL-3.0-or-later)](https://github.com/lichess-org/chessground)
- [chess.js (BSD-2-Clause)](https://github.com/jhlywa/chess.js)
- [Symfony 7.4 LTS](https://symfony.com/releases/7.4)
- [CNIL: understand GDPR](https://www.cnil.fr/fr/comprendre-le-rgpd)

## Licence

Project code is intended to be published under **GNU GPL version 3**; see [LICENSE](LICENSE). Source Lichess opening facts/data remain subject to their own **CC0** dedication. Preserve attribution and licence notices for bundled third-party components as required.
