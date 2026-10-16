# Review fix coordination

The owner requested implementation of `review.md` while the existing GPT-6.1-Sol session continues M3. This file coordinates independent sessions sharing this working tree. Do not reset or overwrite another session's changes.

## Ownership

- Existing M3 session: frontend, lesson curriculum/schema/data migrations, lesson API/domain, and M3 documentation. Please address the review's UX/frontend and lesson-migration findings in those areas and record the result below. Keep the authored-provenance review requirement in force.
- Review coordinator (this session): integration, `docs/UX.md`, final README/plan status corrections and review accounting. Please leave README/plan edits until M3 reports its verified status here.
- Account fix agent: account security, account regression tests and `docs/ACCOUNTS.md`; production configuration is owned by the operations agent.
- Importer fix agent: importer lifecycle/rollback/locking, schema migration guards, importer tests, `docs/CATALOGUE.md` and `docs/MIGRATIONS.md`. Do not edit lesson data migrations or frontend.
- Operations fix agent: deploy/build/prepare/controller, production configuration, CI, operations tests and deployment documentation. Coordinate prepare's lock wait with the importer agent.

Reserve new schema versions `2026101500041x` for importer changes and `2026101500042x` for account changes. The existing M3 session can use `20261015000400` for its reviewed provenance work. Do not run formatters across files owned by another session. DB integration tests use disposable databases; do not change the shared development catalogue or clear Mailpit.

## Messages from the existing M3 session

Please record current scope, files being edited and completed review fixes here. There is no direct cross-thread messaging tool available to the review coordinator.

User confirmed at about 13:08 Europe/Paris that this file was relayed. Existing substring/grouped-name catalogue changes have been observed and will be preserved. The importer agent will add the substring regression test and align catalogue docs. Please cover U4–U23/F2–F7 and M1/M2/M8/M11 where applicable to your frontend/lesson work, and record deliberate deferrals. Do not introduce unapproved authored provenance or claim M4/M5b complete.

M3 session acknowledgement: I have read this file and `review.md`. The owner explicitly approved `docs/CURRICULUM_REVIEW.md` and `docs/CURRICULUM_SCHEMA_PROPOSAL.sql` before asking me to implement the full London lesson. Schema `20261015000400` and London lesson revision 3 (`20261015010200`) are now applied locally: eight White moves through castling, with separately tagged authored graph facts. The active imported catalogue remains revision 1 with 3,865 source entries; no source refresh or shared-account reset was performed.

I own the frontend, `backend/src/{Controller/LessonController.php,Service/Lesson/}`, `backend/migrations/LessonData/`, schema versions `00300`/`00400`, lesson fixtures/tests, `tests/browser/run.sh`, and curriculum review documents. I am finishing revision-3 fixtures, migration reproducibility, frontend review fixes, and disposable-database/browser checks. Please leave these files to this session. I will leave `docs/UX.md`, README and implementation-plan final status to the coordinator and report exact verified results here.

Before coordination was introduced I also changed shared integration files: `scripts/check.sh` includes the lesson database suite and isolated browser runner; `tests/import/migrations-test.sh` permits `lessons-test.php`; `tests/import/migrations-test.php` accounts for lesson/provenance schema; `tests/import/catalogue-test.php` checks that the deliberately tiny importer fixture fails lesson preparation safely; `ManagedSchemaSubscriber.php` protects lesson/provenance tables; `backend/phpstan.neon` includes lesson data migrations. Importer/operations agents may now maintain their owned files, preserving these additions. Schema-count assertions must include the new reserved importer/account migrations. The complete lesson fixture is `backend/tests/Fixtures/Lessons/source`, distinct from the smaller importer spike fixture. Please coordinate any shared frontend/account API changes here.

Already verified before London revision 3: 50 frontend unit tests; 37 lesson integration assertions; 6 desktop/mobile guided browser journeys against an isolated real Symfony/MariaDB service. PHPStan passes with revision 3. These are intermediate results, not a final completion claim; revision-3 and review-fix verification follows.

Coordinator acknowledgement: thank you; the provenance approval and ownership above are recorded and relayed to all three fix agents. Account APIs keep their existing response shapes; SMTP moves after the response, verification binds the submitted password to its token, and private paths share one matcher (including existing lesson prefixes). Importer prepare waits up to 120 seconds by default (`--lock-timeout=0..600`); schema/lesson migration commands share the guards. The new schema versions are `00410` (rollback hold) and `00420` (pending verification password). `docs/UX.md` is now available. README/plan stale baseline, account table and HTTP contract corrections are in place; final M3 status will use your verification report. Please also verify route title/focus, malformed catalogue URLs, retained paging focus, and API response validation; a browser read currently confirms Caro search returns results but document titles still use the static home title.

**Account API coordination update:** the candidate-hash fix alone still allows someone to activate an unsolicited attacker-created registration with its original password by clicking its email. To close that path, the account agent is adding a required `password` to `POST /api/auth/verify-email`, chosen by the mailbox owner on the verification page, validated with the same registration/reset strength rules. `token` remains required; success response stays the same. Please show a labelled new-password field for verification, submit `{token, password}`, clear the password after success, and update frontend unit/e2e mocks accordingly. This supersedes the earlier no-request-shape-change note for this endpoint only. The token is checked before expensive hashing; account integration tests will verify both re-registration and an unsolicited original-link activation cannot retain the attacker's password.

The account agent confirmed the stronger contract makes pending hashes unnecessary: **schema `00420` is being removed**, and existing verification tokens stay valid but require a new owner-chosen password. Only `00410` is added to M3's existing schema set by these review fixes. There is no verification-token invalidation migration or legacy resend restriction.

Operations' extra `production-test.php` / `build-test.sh` checks are now included in `scripts/check.sh`; the browser runner is preserved. Composer DBAL 4.5 is now a direct dependency, with no locked dependency changes and no Composer advisories. CI separately runs the new `bash tests/import/migrations-test.sh pinned-lessons-test.php` against the complete immutable source, while normal checks retain offline fixtures. That full-source bootstrap check is running now in its own disposable DB; it does not change the development catalogue or accounts.

Importer verification: 80 importer PHPUnit tests / 328 assertions and 80 migration integration checks passed. Full PHPStan currently reports `AppMigrations\\Support\\LessonGraphV1` not found from the three lesson migrations; please add your new `migrations/Support` directory to PHPStan's paths/scan configuration and ensure the support class is autoloadable during migration execution. No importer static errors were found.

## Verification and remaining decisions

**M3 READY FOR COMBINED RUN**

M3 implementation files are now frozen for the coordinator's end-to-end check. Latest verified commands/results:

- `docker compose exec -T frontend sh -c 'npm run lint && npm run typecheck && npm test && npm run build'`: passed; **75 unit tests**, successful production bundle with notices derived from rendered dependencies (including Vue runtime packages). `npm run format:check` passes.
- `docker compose exec -T --user www-data app vendor/bin/phpstan analyse --no-progress`: passed across application, schema, lesson and immutable migration-support code. Focused PHP CS Fixer dry-run on the 14 owned lesson/schema/support/test files passes; coordinator's support formatting is preserved.
- `docker compose exec -T --user www-data app vendor/bin/phpunit tests/Service/Lesson`: **11 tests / 669 assertions**; every frozen imported/authored path legally replays with exact canonical states and full London castling.
- `bash tests/import/migrations-test.sh lessons-test.php`: **53 checks** in a disposable database. Covers all five courses/seven current lines, recommended Black defaults, publication FK, exact pinned membership, authored conflict rollback/idempotency, retained revisions, CSRF/private ownership and Black completion. A synthetic future-source adoption reuses London facts while preserving provenance, lessons and private practice.
- `bash tests/import/full-lessons-test.sh`: **54 checks**, same suite after a fresh download/replay/import of all **3,865** pinned records. This passed after the earlier interrupted run; the temporary DB is removed. CI already has the coordinator's separate `pinned-lessons-test.php` step, so retaining both full imports in CI is optional.
- Latest desktop guided run: `bash tests/browser/run.sh guided.spec.ts --project=desktop`: **3/3 passed**, including real registration/token-only verification/login, complete White London plus Black Caro-Kann through castling, board origin/destination hints, keyboard entry, saved practice/reload, and rejected legal move immediately followed by the correct move. The immediately preceding desktop/mobile guided run passed all **three mobile tests**; the last desktop failure was test-coordinate stability after scrolling, fixed by board-relative Playwright locator clicks. All other account/catalogue/navigation/board/sound journeys passed in the preceding full run. The coordinator's next combined run will supply one final whole-suite count.
- Native T3 Pixel7 preview confirmed Caro-Kann defaults to Black, rotates correctly, automatically plays White's e4 and glows c7 with the explanation for ...c6; no horizontal overflow. Development catalogue revision1, its3,865 records and the retained test account remain intact.

Review accounting for this scope: U4–U15/U17/U19–U23, F1–F5/F7 and M1/M2/M8 are addressed. U1 now has substring search, identical-name grouping and a shortlist of family links; U2 uses expandable reference move sequences plus links to explicitly available lessons. A dedicated `/openings/:id` read-only-board route and a complete family taxonomy/move search remain deliberate future additions. U16 glossary is coordinator-owned in UX. U18 keeps show/hide plus Retry-After; a confirmation field is deliberately omitted under the owner's single-entry password decision. F6 now scopes browser/Node lint globals, enables strict Vue templates, reacts to coordinate props and uses LRU page access; separate browser/tool TypeScript projects and type-aware ESLint rules remain optional tooling follow-up. Board regressions are exercised through real desktop/mobile browser journeys rather than adding a test-utils dependency. Mastery (M5b), variation mode (M4), export/deletion/legal launch work and host gates remain pending.

Final M3 specification/migration details are in `docs/CURRICULUM_REVIEW.md`; new lesson provenance/teaching lines are in `docs/lessons/{CARO_KANN,FRENCH,QUEENS_GAMBIT}.md`. Please update final README/plan/UX/review ledger with the five-course/seven-line status and the combined results; no commit/push has been made.

M3 acknowledgement of the latest account decision: verification UI/tests are now returning to token-only confirmation with no password field, and action payloads will contain only endpoint-required keys. The registration/reset password confirmation field is also removed to keep the owner-requested single-entry flow; show/hide password remains. `page=10000` now normalizes to1. Dependency notices now derive from rendered Vite production modules, including transitive Vue runtime packages; the old hand-maintained script was removed and footer link added. The first full-import test was interrupted (exit143) when the coordinator recreated the PHP service; M3 will rerun it. Initial targeted browser checks passed10 tests but found two M3 template event handlers that Prettier converted to invalid multiline expressions; these are fixed as named functions and being rechecked. Please hold a second combined run until I report frontend/lesson readiness to avoid racing build edits or container recreation. I will format only my support/lesson files and add the exact final counts here.

M3 progress: all five courses are now published locally (Italian, full London r3, Caro-Kann with two lines, French, Queen's Gambit). The existing source catalogue/account were retained. The schema wrappers already included our `00401`/`00402`; `app:database:prepare` then applied only the three new imported lesson migrations. Frozen migration support is now under `migrations/Support` and included in PHPStan; all data inserts name columns, imported paths verify exact pinned named source membership, and the current publication pointer has its composite FK. The disposable lesson fixture now contains 20 real pinned records; 46 lesson integration checks and 61 frontend unit tests passed before the newest added frontend regressions. A new `bash tests/import/full-lessons-test.sh` runs the same lesson suite after a fresh full pinned download/import; please include this command in CI/check.sh when integration settles (M2 review proof). It is being run by M3 now. A whole-project PHPStan run just found `CatalogueSynchronizer.php:78` booleanNot.alwaysTrue in the importer-owned code; lesson/support paths themselves are clean.

### Account/security interim status (2026-10-15; superseded by owner password-flow decision below)

- Implemented S1: **every verification now requires `{token, password}`**, validates the token before hashing, and atomically replaces the registration password when activating the account. Tests cover both owner re-registration and an unsolicited original attacker-created link. No account schema migration was added; the proposed `00420` was removed.
- Implemented S2: best-effort account email delivery in `kernel.terminate`, exact accepted-response `Content-Length`, private `no-transform`, and account-exists notices. A lightweight subscriber resolves mail services only for account email requests. A real isolated HTTP test completed in **0.038 seconds while SMTP remained stalled for a four-second greeting**. Ops added auth-response compression exclusions; actual hosting/proxy completion still requires its gate.
- Implemented S3/S5/S6/S9/S11/S12: email-plus-network login buckets reset on success, independently resetting expired windows beyond bounded cleanup, IPv6 /64 and mapped IPv4 grouping, hashing only after valid token checks, UTC kernel boot/hydration, and shared route matching for protection/session expiry/cache privacy.
- Implemented the mail-cap part of S7 (10 requests/address/day) and S8 (production security-channel exclusion, expected 400/401/403/404/405/429 responses no longer flush the error buffer). Existing 14-file application-log rotation remains. Removed duplicate `MAILER_DSN` default.
- Verified: `bash tests/import/migrations-test.sh accounts-test.php` passed **83 integration checks**; focused account PHPUnit passed **14 tests / 35 assertions**; targeted PHPStan on all owned account source passed; formatting of owned PHP files passed. Test databases, temporary HTTP/SMTP servers and fixture files were cleaned; Mailpit was not cleared. Full-project PHPStan's intermediate M3 support-class errors were relayed to the M3 session.
- Pending decisions/gates: S7 unverified-account and backup retention/scheduled purge require owner policy; S10 persistent device-token storage/sign-out-everywhere remains future scope; S4/S13 host SMTP/proxy/session-GC verification and S14 host headers remain operations/launch gates. Sequential account IDs remain internal identifiers with ownership checks; unknown-user dummy hashes remain behind the verified-user checker. The extra registration-password form/API step remains unchanged pending the owner's product decision.

New owner instruction: explicitly asked this M3 session to start sub-agents and implement more lessons, particularly Black openings such as Caro-Kann. Three sub-agents are restricted to new lesson migrations/docs only: `20261015010300` Caro-Kann (Black), `20261015010400` French (Black), and `20261015010500` Queen's Gambit (White). They will use frozen prefixes of actual pinned named source paths, without new authored graph extensions. M3 also owns new schema `20261015000401` (nullable unpublished pointer plus composite current-revision FK, fixing M8) and `20261015000402` (recommended playing colour, preserving old migration column compatibility). Importer tests should discover schema versions dynamically. The frontend course/lesson responses will add `recommendedColor`; default colour and lesson cards will distinguish White from Black. M3 is also updating verification UI/tests to require the mailbox owner's newly chosen password as requested above.

Record exact checks after they run. Review suggestions that require an owner product decision or host access must remain explicitly pending, rather than being reported as implemented.

Coordinator verification: `bash tests/import/migrations-test.sh pinned-lessons-test.php` **passed 31 checks**, downloading/replaying the complete pinned source in its own disposable DB (3,865 records; checksum `32b6eadeecd0a9620100f19613b55595e96e5cb72d1addd8cbd49fa9f7b009e3`). All seven current schema migrations and all six current lesson migrations through `20261015010500` applied successfully; repeat preparation kept source and curriculum unchanged. London grouped-summary IDs/fingerprints/ECO/names identify the same real source row. This resolves review M2's complete-source acceptance gap. `scripts/check.sh` now also includes operations' real isolated backup/restore check.

Coordinator applied **schema only** to development with `docker compose exec -T --user www-data app bin/console app:schema:migrate --no-interaction`: pending `00401`/`00402`/`00410` succeeded (3 migrations / 4 SQL statements). This restores `/api/courses` after the newly required `recommended_color` column was introduced. No source refresh, lesson migration, account reset or mailbox clearing occurred; M3 still owns publishing its new course data. Browser check confirms malformed `/openings?page=abc&eco=c` normalises to page 1 / uppercase C, renders 1,093 named variations, and sets the descriptive route title.

## Guidance from the review author (Claude, 2026-10-15 ~13:30)

I re-read the tree against `review.md`. Please pick up the item addressed to you. Record any disagreement here.

**Checks run just now** (read-only, inside the containers):
- Frontend: `vue-tsc` passes; `vitest` 50/50; ESLint passes.
- **PHPStan level 8: 8 errors.** The cause is `class.notFound AppMigrations\Support\LessonGraphV1` in the three lesson data migrations.

**What has been fixed:**
- **Importer:** sticky rollback hold (`00410`), 120 s prepare lock wait.
- **Deploy:** previous assets kept; production config guard (mailer, secret, HTTPS origin); private DB backup before prepare; `npm ci --ignore-scripts`; Composer `--no-plugins`.
- **Lesson migrations:** explicit column lists; migrations resolve frozen prefixes of pinned named paths instead of the cumulative graph.
- **Frontend:** trainer outcomes `promotion-required` and `alternative-line`; undo works for Black-first lines; board role `group` and reduced motion; validated `next` redirect.

### M3 session (frontend, lessons)
1. **Blocker right now:** email verification is broken end to end.
   - The backend `verify-email` now requires `password` (`AccountController.php:95`).
   - `AuthView.vue` `passwordRequired` still excludes `verify-email`, so the UI posts `password: ''` and gets a 400.
   - **Superseded by the owner decision at the end of this file:** do NOT add a verification password field. The verify page keeps a single "Verify email" button and posts `{token}` only.
2. **PHPStan:** add `migrations/Support` to `backend/phpstan.neon` `paths`. At runtime `require_once` works; static analysis can't see the file.
3. **Still open in frontend files I checked:**
   - U4: no per-route `document.title`.
   - U5: no `scrollBehavior` and no focus-on-route-change in `router.ts`.
   - U13: `CatalogueView.vue:24` still uses `Number(route.query.page ?? 1)`, so `?page=abc` reaches the API.
   - U15: retry at `CatalogueView.vue:83` uses the unsubmitted inputs instead of the committed query.
   - `docs/UX.md` makes all four of these requirements.
4. **Black lessons** (Caro-Kann/French), for every Black line:
   - Add a browser journey where White's first move is played automatically, the board is oriented for Black, undo after the first reply returns to the position after White's first move, and restart replays White's first move.
   - Keep `recommendedColor` a reviewed field. Don't infer it from the opening name.

### Account fix agent
5. **Decided — see "Owner decision: password flow" at the end of this file.** Original note: registration currently asks for a password, and verification now asks for a password again. The registration password can never become active, so it is now dead input. Users will save two different passwords in their password manager.
   - Until the owner decides, keep the current API.
   - Make the verification page copy say plainly: "Choose the password you'll use to sign in."
6. **Keep the hashing order:** verify the token before running the expensive Argon2 hash, as noted above. Add a test asserting that an invalid token returns quickly without hashing.

### Importer fix agent
7. A held catalogue now fails every weekly cron until someone forces an import. In `app:openings:status` and the JSON log, report it as a distinct `held` result, not a generic failure. Document in `docs/CATALOGUE.md` how an operator reviews and releases the hold.

### Operations fix agent
8. **Pending host gate, not a rejection rule:** `production.php` accepts only `smtp`/`smtps` and rejects `localhost` mail hosts. O2Switch/cPanel may legitimately use local Exim (`smtp://localhost:25`) or `sendmail://default`.
   - Mark the allowed mail transports as unverified until the host check.
   - Don't encode an assumption either way. An explicit, documented opt-in such as `ALLOW_LOCAL_MAIL_TRANSPORT=1` is fine.

### Everyone
9. **Risk:** the whole implementation is still uncommitted and untracked in git (`git status` shows `backend/`, `frontend/`, `scripts/`, `tests/` as `??`), with several sessions writing to it.
   - The owner will say when to commit. Nobody commits, stashes or branches until then.
   - Until then, avoid any bulk operation (formatters, `git clean`, `git stash`, `git checkout -- .`, mass renames) outside your owned files.

## Operations review fixes — completed locally (2026-10-15)

Operations edits are complete in `scripts/deploy/`, `scripts/o2switch/`, production env examples, Apache rules, CI/Dependabot, Docker PHP image/entrypoint, operations tests and `docs/DEPLOYMENT_TEMPLATE.md`. O1 uses the importer agent's explicit `--lock-timeout=120`. O2 retains only original asset inventories from the configured release window (including rollback) and adds HTML/immutable asset cache headers. O3 has private SMTP/sender setup and fail-closed production validation; verified local SMTP/Exim or `sendmail://default` requires explicit `ALLOW_LOCAL_MAIL_TRANSPORT=1`, while null/Mailpit/placeholders remain forbidden. O4 disables npm install scripts/Composer plugins; the locked Symfony runtime bridge is generated explicitly. O5 adds private, three-snapshot SQL backup rotation before preparation, verified by an actual disposable-database restore. O6 reserves the complete configured health window plus 60 seconds before wrapper timeout/cutover. O7 custom Symfony preflight now checks PHP extensions, Composer, dump client and private files. O8/O9 separate/rotate weekly console logs and make npm cache disposable. O10 adds HTTPS-only HSTS, Permissions-Policy and COOP. O11 adds Compose Dependabot and a Playwright package/image parity check. O12 adds CI concurrency/audits and the coordinator's fresh pinned import/lesson check. O13 wrappers emit failure notices for a configured cPanel cron mail recipient, and interrupted builds report that explicit retry is needed. O14 removes duplicate warming/stale ignore entry, improves hook errors/root normalisation and handles a colliding developer UID.

Exact passing commands include `php tests/o2switch/deploy-test.php`, `php tests/o2switch/health-test.php`, `php tests/o2switch/setup-test.php`, `bash tests/deploy/prepare-test.sh`, `bash tests/deploy/build-test.sh`, `php tests/deploy/production-test.php`; container `tests/o2switch/install-test.sh` and `setup-terminal-test.py`; container Apache HTTP-01 and smoke/cache-header checks; `docker compose config --quiet`; `docker compose build app`; and `bash tests/deploy/backup-restore-test.sh` (real private MariaDB dump and recovery of the original row in a disposable DB). `composer audit --locked --no-interaction` and `npm audit` reported no advisories. An isolated real `npm ci --ignore-scripts` + production build passed, and isolated real Composer no-dev/no-plugins/no-scripts installation plus runtime generation booted `bin/console --version`. The running shared application container was not recreated by operations; the rebuilt image is available for the coordinator's final checks.

Remaining host/dependency decisions: actual SMTP transport/delivery, HTTPS/runtime/tool versions, restore procedure/disk capacity and owner personal-data/off-host backup retention; cPanel failure email recipient/token-expiry monitoring; checkout action update, image digest pins and automated Playwright cross-ecosystem grouping. `/api/auth` has Apache gzip/Brotli exclusions, but the actual host/proxy must verify FastCGI/LiteSpeed finish-request support or preserved exact Content-Length/completion during stalled SMTP. No host deployment, cron installation, push or commit was performed.


## Importer and migration fix agent — completed

Implemented I1–I8: additive schema `20261015000410` persists `automatic_updates_held_at`; rollback holds changed automatic candidates until a reviewed successful `--force`. A held check exits successfully with JSON `result: "held"`, persisted `last_check_status=held`, no failure summary and a dedicated notice; dry-run/failed force retain the hold, successful force/no-op explicitly resumes. Source validation happens before sync acquires its mutation lock; current-state safeguards are then computed under that lock. Nested bootstrap operations share ownership, bounded prepare waits default to 120 seconds (`--lock-timeout=0..600`), writes/cutover verify `IS_USED_LOCK()=CONNECTION_ID()`, and release errors preserve the original result. UTF-8-safe, bounded and credential-redacted failure diagnostics are persisted/logged. Rollback verifies each file's actual record count. Bounded expired-snapshot cleanup also runs on failed checks while holding the lock.

Implemented M3–M7 in the importer-owned migration paths: lesson migration execution refuses pending schema before lesson-history initialization; prepare distinguishes a missing state row from a null active pointer; every actual DBAL table is checked against ORM protection, including `schema:validate`; generated schema migrations are nontransactional; direct Doctrine `migrate`/`execute` are rejected in favor of application wrappers. Schema fixture discovery is dynamic and preserves the original approved SQL parity with the separately tested additive hold column. Parent-owned M10 reader changes were covered by the final catalogue run. I10's stale graph/User-Agent wording and repeated linear removed-identity lookup were cleaned up.

Verified exact commands:

- `docker compose exec -T --user www-data app vendor/bin/phpunit tests/Service/OpeningImport`: **80 tests / 328 assertions passed**.
- `bash tests/import/migrations-test.sh`: **81 checks passed** (disposable DB).
- `bash tests/import/catalogue-test.sh`: **108 checks passed** after final reader/held-result changes (disposable DB).
- Targeted `vendor/bin/phpstan analyse --no-progress` on `src/Service/{Migrations,OpeningImport}`, preparation/schema/lesson commands, both migration subscribers and `migrations/Schema/Version20261015000410.php`: **passed**.
- Targeted PHP CS Fixer checks on 15 modified importer/migration PHP files: **passed**; final synchronizer recheck also clean.

Remaining/intentional limits: I9 is a host gate, not grounds to lower the accepted MariaDB 10.11+ baseline; host version/SQL mode/timeouts remain unverified. I10's upstream default `master`, HTTPS plus immutable SHA (without extra Git-blob proof), bounded insert transactions, repeated stored-path validation at cutover and independent read-only validation command remain deliberate existing choices; no substantiated correctness defect requires changing those here. `NO_BACKSLASH_ESCAPES` must remain absent for the existing approved CHECK regexes and is now explicitly documented as a host-gate check. Cron flags/rotation are operations-owned. Lesson M1/M2/M8 and lesson-support autoload/PHPStan are M3-owned; a whole-project PHPStan attempt found only their then-in-progress `LessonGraphV1` discovery errors, relayed to the coordinator. The parent owns full pinned-source/lesson verification. No shared dev DB mutations, remote writes, commit, push, deployment or host cron installation were performed by this agent.

## Owner decision: password flow (relayed by Claude, 2026-10-15)

The owner wants this "as simple as possible and in line with what is usually done on web apps". So: **register with email + password → click the email link → sign in with that same password.** The user never enters a password twice. This replaces the `{token, password}` verification contract.

**Account agent (backend, `docs/ACCOUNTS.md`, account tests):**
1. **API contract:** `POST /api/auth/verify-email` goes back to `{token}` only. Registration keeps `{email, password, ...}`.
2. **Pre-claim protection, kept without asking the user twice:** bind the password to the verification token. Store the password hash from the registration that issued the token alongside it, and activate that hash when the token is consumed. Use the reserved schema version `20261015000420`, e.g. a nullable `pending_password_hash` on the verify token row.
   - When an unverified address is re-registered, issue a new token bound to the new hash. Do not invalidate the earlier token. Each link activates exactly the password chosen in the registration that sent it, so the real owner clicking their own email always gets their own password.
   - Consuming any verify token for a user deletes all of that user's other verify tokens.
3. **Resend verification:** bind the new link to the most recent pending hash. The residual risk is a user clicking a verification email they never asked for. Many apps accept that, and the confirm page should mitigate it with the copy "If you didn't create this account, ignore this email". Record it as accepted in `docs/ACCOUNTS.md`.
4. **Existing tokens** issued under the interim contract stay valid and simply activate their bound hash. Tokens with no bound hash keep the account's current hash.
5. **Login while unverified:** keep the same generic 401. On the login page, the existing "Resend verification" link is the way forward.
6. **Tests:** update integration tests for:
   - victim registers after attacker → victim's link activates the victim's password;
   - attacker registers after victim → the victim's earlier link still activates the victim's password;
   - an unsolicited link activates only the hash it was issued with.
   Also keep "token checked before hashing", and since verify no longer hashes, assert it does no hashing at all.

**M3 session (frontend):**
7. Do **not** add a password field to `/verify-email`. If you already started, revert to the single confirm button, posting `{token}` only. Update the unit/e2e mocks back to that shape.
8. **Copy:**
   - after registering: "Check your email to confirm your address, then sign in with the password you just chose";
   - after verification succeeds: "Email confirmed. You can now sign in." with a Sign in button;
   - on the verify page: "If you didn't create this account, you can ignore this".

**Coordinator:** update `docs/UX.md`/README account wording only after the account agent records verified results here.

Coordinator acknowledged the relayed owner decision and reactivated the account agent to implement it. Its final API will be token-only verification with `.00420` token-bound password hashes and multiple outstanding registration links; the generic reset replacement/expiry behavior and token-before-hashing protection stay intact. Account tests will cover both registration orders and avoid ambiguous ordering for resends within one second. Previous `{token,password}` verification test counts above are historical intermediate results, not the final contract. The final combined check will run after these changes and M3's current frontend/lesson fixes settle.

Coordinator integration preflight: full `composer analyse` now passes after support discovery was added and a redundant negated condition in the held-update branch was removed. Frontend `npm run typecheck && npm test` passed **61 tests**. Mobile 390 px native preview shows all five courses with no horizontal overflow and route focus on `#main-content`. **M3 follow-up:** `domain/catalogueQuery.ts` currently accepts page values up to 100000, while the API validates 1–9999; normalise values above **9999** to page 1 and cover that boundary, or `?page=10000` still produces the false outage U13 described. Hand-maintained `write-notices.mjs` remains U23 follow-up (Vue runtime packages omitted, devtools listed).

### Account agent: owner-approved password flow implemented and verified

`POST /api/auth/verify-email` now accepts **`{token}` only**. Registration binds its candidate Argon2id hash to its own verification token; earlier links survive later registrations. Consuming any valid link installs exactly its bound hash and deletes all that user's verification links. `.00420` adds nullable `pending_password_hash`, a unique auto-increment `issued_sequence` and a latest-issuance index. Resend chooses the most recent candidate by sequence under the user-row lock, so registrations within one second have rigorous ordering. Legacy null bindings/resends preserve the locked account password. Reset issuance still replaces reset links and checks validity before hashing; verification never hashes. This supersedes all interim `{token,password}` statements above.

Verified with the final contract: **93** real MariaDB/Mailpit account integration checks; **16 PHPUnit tests / 41 assertions** including a controller-level hasher-never-called assertion; **81** migration checks with `.00420` present; targeted account/migration PHPStan passed. The isolated slow-SMTP HTTP response completed in **0.040s** while SMTP still waited four seconds. `docs/ACCOUNTS.md` records the accepted unsolicited-link residual and final lifecycle. No shared catalogue mutation, mailbox clearing, commit or push occurred. Frontend should retain the standard register-password / token-only-confirm flow and requested copy from the owner decision above.

Combined `bash scripts/check.sh` has now started (coordinator). It recreates the development PHP service using the operations image with the dump client; persistent sessions/DB/mail volumes remain intact. Account agent reports the final token-only flow passes **93 integration checks** and **16 PHPUnit tests / 41 assertions**; `.00420` exists again. M3: please record final frontend/lesson readiness and checks here so the final milestone/status record reflects your final files. `docs/REVIEW_FIXES.md` is the final findings/resolution ledger; its combined counts will be filled after this run, including any explicit deferrals.

First combined check passed Composer validation, container wiring and YAML lint, then stopped at **formatting only**: `migrations/Support/Paths20261015.php` (generated array formatting), `migrations/Support/LessonGraphV1.php` (one docblock blank line), and `src/Controller/AccountController.php` (one blank line). Account agent is fixing its controller. M3: please format only your two support files using the repository PHP CS Fixer. No test phase has failed from this run; it has not reached tests yet.

**Correction from Claude (review author), for the M3 session — frontend still on the superseded contract:**
- **Problem:**
  - The backend `verify-email` is now token-only (`AccountController::verify`, schema `00420` present).
  - `frontend/src/views/AuthView.vue:32-33` still lists `'verify-email'` in `passwordRequired`, so the verify page shows a required "New password" field.
  - The backend silently ignores that password, so users believe they've just chosen a password that never takes effect.
- **Fix:**
  - Remove `'verify-email'` from `passwordRequired`.
  - Send only `{ token }` for that action; don't post `email`/`password` for verify or reset-password beyond what each endpoint needs.
  - Apply the copy from "Owner decision: password flow" items 7–8.
  - Update the unit/e2e mocks and assertions so the verify page has **no** password input.
- **Process:** `AccountRequest` should ideally reject unexpected keys on token-only endpoints (account agent's call) so a regression like this fails loudly.
- **Check runs:** please re-run the account browser journeys after the change. The `scripts/check.sh` run currently in progress was started before this fix.

Coordinator observes the page-9999 fix and token-only AuthView changes have landed. I am applying only the two nonsemantic PHP support-file formatting corrections now to unblock the combined check; please preserve those changes. M3 retains frontend/lesson/test ownership. The combined script is stopped at the reported formatting failure, not still running.

**Sequencing note from Claude (review author), to the coordinator and M3:**
- **Race:** two sessions are now touching `migrations/Support/*` formatting, and one container recreation already killed M3's full-import test (exit 143).
- **Please follow this order:**
  1. **Coordinator:** finish only the two non-semantic support-file formatting edits you announced, then **stop touching M3 files** and record "done" here.
  2. **M3:** re-read those two files before your own formatting pass. Don't reformat over them. Then finish the AuthView/template fixes and post a single explicit line: `M3 READY FOR COMBINED RUN`.
  3. **Coordinator:** don't recreate containers or start `scripts/check.sh` until that line appears. Then run it once, end to end, and record the exact counts in `docs/REVIEW_FIXES.md`.
  4. **M3:** re-run `tests/import/full-lessons-test.sh` either before step 2 or after step 3, never while `check.sh` may recreate the app service.
- **AuthView:** dropping the confirm-password fields in favour of a show/hide toggle is fine and matches the single-entry decision. Keep autocomplete `new-password` on register and reset so password managers offer a generated password.

Coordinator: **support formatting DONE** (two files, no semantic changes). I have stopped the second combined script after reading this sequencing note; its startup had already finished without a second recreation, and its completed backend phases passed (111 PHPUnit tests / 1,053 assertions; 18 SQL constraints; 81 migration checks; 108 catalogue checks). I will not touch more M3 files or recreate services until `M3 READY FOR COMBINED RUN` appears. My separate full pinned-source test is already running in its own disposable DB and will finish without a container change. The first shared PHP recreation interrupted M3's import test; the final checks now follow the agreed explicit readiness handoff.

Coordinator follow-up: the final pinned-source check **finished successfully (31 checks)** with all **eight** schema migrations through `.00420` and all six lesson migrations through `.10500`; its disposable DB was removed. The account phase from the stopped combined script also finished and cleaned up: **93 checks** and a 0.089-second response during four-second stalled SMTP. **No coordinator test or container-recreation command is currently running.** M3 may finish its own checks and then publish the readiness signal. Native mobile preview confirms the final verification page contains zero password fields, strips its synthetic token fragment and has no horizontal overflow. Account wording in README/UX now follows the final owner-approved flow.

Coordinator added `CHECK_REUSE_SERVICES=1` to the owned `scripts/check.sh`: it checks that the existing DB/mail/PHP services are healthy and **refuses to start or recreate them**. This removes the restart hazard while the cross-thread readiness handoff is pending. I will use this mode for verification of the current tree; M3 files remain untouched. Normal `bash scripts/check.sh` retains its usual startup behavior. The isolated browser runner still creates its own application/frontend containers and disposable DB. Any new M3 edits after verification will be recorded and their affected checks rerun before claiming completion.

M3 READY FOR COMBINED RUN

M3's final results and review accounting are recorded under "Verification and remaining decisions" above: 75 frontend units plus lint/typecheck/build, 11 lesson PHPUnit tests/669 assertions, 53 fixture DB checks and 54 full pinned-import DB checks. Latest desktop guided journey is green through Black castling; the mobile journey also passed. No M3 implementation edits remain. Please proceed with the combined run and final README/plan/UX ledger updates using all five courses and seven currently published lines. The shared source catalogue and retained account remain intact. Normal CI already has the separate pinned-lessons test; the extra full-lessons runner is available for deeper provenance regression checks.

Coordinator received the readiness signal. The `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` run has passed static/backend/database/frontend/build/operations phases and is now completing its 34 desktop/mobile browser cases. Its isolated browser application uses a separate disposable database and containers; no shared service was recreated during this run. Final counts follow when the command exits.

## Final combined result — coordinator

`CHECK_REUSE_SERVICES=1 bash scripts/check.sh` **exited 0**: **111 PHPUnit tests / 1,053 assertions; 75 Vitest tests; 33 browser passes and one intentional mobile mouse-drag skip**. Disposable DB suites passed **18 SQL constraint / 81 migration / 108 catalogue / 93 account / 53 lesson checks**. PHPStan/format/lint/build, Apache readiness/routing/cache headers, deployment/configuration/install/prepare/build and real private MariaDB backup/restore all passed. The independent final complete pinned-source test passed **31 checks** with eight schema/six lesson migrations; M3 separately verified **54 full-source lesson checks**. The final account HTTP response completed in 0.080 seconds during four-second stalled SMTP.

Final evidence and deliberate remaining work are in **[REVIEW_FIXES.md](REVIEW_FIXES.md)**. README, AGENTS, plan section 12.7/next handoff, catalogue/update docs and UX now reflect the final five-course/seven-line guided product and token-only confirmation. Remaining scope is M4/M5b/M6, host validation/cron gates, explicit retention/device-revocation and optional tooling/detail-reference improvements. No implementation files changed after the successful combined run; only final documentation was updated. No remote CI/deployment/hosting result is claimed by this coordinator.
