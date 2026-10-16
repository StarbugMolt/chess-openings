# Active implementation coordination

The owner requested M4 implementation with multiple agents and will run independent agents in this workspace. This is the current coordination file. Completed M3/review history is preserved in [the archive](archive/REVIEW_COORDINATION_M3_2026-10-15.md); its old file reservations are closed.

## Shared workspace rules

- Preserve all existing tracked/untracked work. No reset, clean, stash, branch switch, mass rename or broad formatter. Commit/push only when requested: the owner explicitly authorised publishing the completed work on 2026-10-16. This supersedes the earlier no-commit restriction for this publishing task; use normal fast-forward pushes, preserving other sessions' work.
- Register your name, task and exact owned files here before editing; avoid another active owner's files. Resolve shared-file requests through this file.
- Never recreate/stop shared services, replace the development catalogue, delete the retained learner account or clear Mailpit. Use disposable DBs/isolated browser containers. Use `CHECK_REUSE_SERVICES=1` for the combined check.
- Format only owned files. Record exact tests and outcomes; intermediate checks do not mean a milestone is finished.
- Append updates in your own section. Read this file again before integration or a combined run.

## M6a complete — account download/deletion handoff (Codex, 2026-10-16)

**Owner-requested account controls are implemented and locally verified. All M6a reservations are released.** See [ACCOUNT_DATA.md](ACCOUNT_DATA.md) for the API, live-deletion boundaries and exact verification. Existing schema/cascades suffice; no migration or new private-data architecture was introduced. Imported, curriculum and M5b approvals remain settled.

`POST /api/me/data-export` and `DELETE /api/me` require the current password, authenticated session CSRF and shared persistent limits (five per user/30 per network per 15 minutes). POST keeps credentials out of URLs and avoids a reusable elevated session. JSON includes owned account metadata, all retained completion/assessment records with labels and email-link metadata, without hashes/secrets. Deletion verifies a fresh locked password, atomically cascades account/token/progress deletion and executes configured Symfony logout. Other sessions, remember cookies and reset links cannot restore the deleted account; recreating its email/password does not revive old credentials. Public content and other accounts remain intact.

Completed root scope: `AccountDataController`, `Service/Account/AccountData`, service/disposable API tests, `api/{account,http}.ts`, account store, Account/Privacy views, account unit/e2e tests, runner/check wiring, generated-artifact ignores and README/AGENTS/plan/account-data/account/UX/progress docs. Separate mobile cards support download, cancelled/failed/successful permanent deletion, cleared passwords, actionable errors/session recovery and focus restoration. No other agent was delegated this increment. The independent trainer/catalogue changes below were preserved and included in the final combined run.

| Exact verification | Result |
| --- | --- |
| `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` | **Exit 0: 199 PHP tests/4,276 assertions, 227 frontend tests, 49 desktop/mobile browser passes**, one intentional mobile mouse-drag skip; static/format/type/build, Apache/deploy/controller and real private MariaDB backup/restore passed |
| Disposable suites within that command | **18 constraint, 81 migration, 108 catalogue, 93 account, 84 account-data, 61 lesson, 109 variation, 150 practice checks** |
| `bash tests/import/migrations-test.sh account-data-test.php` | **84 checks**; ownership, complete retained evidence, private attachment/secret exclusions, fresh password/CSRF, rollback/cascades, sessions/remember/reset/recreation and persistent limits |
| `docker compose exec -T --user www-data app php vendor/bin/phpunit tests/Service/Account/AccountDataTest.php` | **5 tests/29 assertions**, including no silent truncation of 10,000 completions |
| `docker compose exec -T frontend npx vitest run tests/unit/AccountStore.test.ts` | **11 passed** |
| `CHECK_REUSE_SERVICES=1 bash tests/browser/run.sh account-data.spec.ts` | **4 desktop/mobile passes** |
| Native T3 inspection | Account cards fit at 390 px without horizontal overflow; no export/delete was submitted for the other agent's `claude-ui-check` account |

The first targeted browser run lost a trace when another run cleared shared `frontend/test-results`. The runner now uses unique ignored **`artifacts/browser/<run>`** output outside frontend source discovery. Another session used the short-lived intermediate `.test-artifacts` location, so Git/Prettier/ESLint ignore that generated directory instead of deleting its retained traces. The first full run stopped only on that artifact's formatting; the corrected full run passed. Do not clear another session's output. Shared services, pinned catalogue, retained learner, other agent's browser account and Mailpit were preserved. Fresh bootstrap remains nine schema/15 lesson migrations.

**Next: remaining M6 public privacy/legal details, actual retention/recovery procedures and host verification.** Deletion covers live account/token/practice records. Existing backups/logs/temporary abuse records, other browsers' unusable session files and local captured email follow separate cleanup/retention; do not claim immediate physical erasure of all copies or invent a public retention period. Remote CI and O2Switch remain unverified; neither host job is installed. New work must register scope here.

Publishing verification (2026-10-16): the application is already committed and on `origin/main` at `4e484ef`. Its [GitHub CI](https://github.com/Starbugstone/chess-openings/actions/runs/38861773058) run completed successfully. The owner explicitly requested commit/push of the remaining seven M6a verification/handoff documents; no executable change or service action is part of that docs commit. New main commits receive a fresh CI run. O2Switch and both host-job gates remain pending.

## M5b complete — coordinator handoff (2026-10-15)

**Owner approved and implementation complete.** The owner approved [M5B_REVIEW.md](M5B_REVIEW.md) and its exact SQL after 55 disposable checks, including permanent schema 430. Approval is settled; do not ask again. Schema 430 adds one nullable bounded assessment column to existing private completions, leaves old recall unknown and introduces no aggregate tables. Both trainers, private `/api/me/practice`, `/progress`, contextual recall/review and frozen supported-reply weighting are integrated. See [PROGRESS.md](PROGRESS.md) for behaviour, limits and exact verification.

All **M5b reservations are released** after the final combined exit 0 run and completed documentation handoff. The implementation ownership below is historical; independent curriculum reservations/status remain governed by that session's sections. README/AGENTS/plan/PROGRESS/review and UX/MIGRATIONS/ACCOUNTS now reflect the completed increment. No further executable changes are planned.

| Implementer | Completed scope |
| --- | --- |
| Root coordinator | Review/proposal/progress docs, completion API types, routes/AuthView/Account/Learn/trainer integration, ReviewLink and tests, guided/practice browser journeys, PracticeReader/controller action, private notice, runner/check wiring and shared DB application |
| Domain agent | PracticeAssessment, GuidedTrainer, VariationTrainer and units; exact final-context telemetry and private frozen weighting |
| Backend agent | AssessmentValidator/ReviewPolicy/PracticeProjector and units, approved schema 430, CompletionRecorder, proposal/parity and real practice API disposable tests |
| UI agent | ProgressView, practice API parser and units; final UX/migration/account documentation |

Independent curriculum phase 2 was marked ready before combined checks. Root applied its five additive lesson migrations through 11400 locally with locked `app:lessons:migrate --no-interaction` (358.5ms). After disposable private-schema verification, root applied `app:schema:migrate --no-interaction` (39.4ms, one approved additive ALTER). The local database now has **ten courses/27 current lines**, nine schema and 15 lesson migrations. The pinned 3,865-record catalogue, retained account/progress, shared services and Mailpit were preserved.

Final verification:

- `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` — **exit 0; 194 PHP tests/4,247 assertions, 219 frontend units, 45 desktop/mobile browser passes**, one intentional mobile mouse-drag skip. Static/format/type/build, Apache/deploy/controller and actual private MariaDB backup/restore passed.
- Disposable suites in that run — **18 constraints, 81 migration, 108 catalogue, 93 account, 61 lesson, 109 variation, 150 practice checks**.
- `bash tests/import/practice-proposal-test.sh` — **56 checks**, including exact proposal/permanent-migration DDL parity.
- `CHECK_REUSE_SERVICES=1 bash tests/browser/run.sh guided.spec.ts variation.spec.ts practice.spec.ts` — **18 passes**, including measured hints/saves, exact Black review links, separate browser-session persistence, stale links, outages and confirmed expiry recovery.
- Pure backend practice — **62 tests/152 assertions**; training domain 90, private response parser 53 and review links 22. Larger projection regression processes 10,000 rows without truncation.
- Native T3 signed-in `/progress` — all ten courses render at 390 px; no horizontal overflow or console errors.

The isolated browser runner now waits for app/database HTTP readiness before console preparation. Two immediate-console runs had stopped with 137 before schema notices; the same bootstrap in a ready app passed at 32 MB. Readiness ordering fixed the startup race without changing memory limits or shared services. The final full check includes this fix and fresh nine-schema/15-lesson bootstrap.

**Next: M6 privacy/legal information, export/deletion, retention and real hosting verification.** Remote CI has not run. O2Switch remains unprovisioned, and neither host job is installed; preserve both host gates. Start new work by registering ownership here.

### M5b domain implementation record

M5b domain implemented the reserved three training-domain files and their unit tests only. Final-path assessment uses complete UCI-prefix contexts, preserves whole-attempt assistance across undo, and keeps legacy hint counting. Variation evidence is privately frozen and restricted to exact lesson revision/colour; deduplicated weighted replies keep the current full-path and RNG invariants. No schema, API, UI, curriculum or shared-service changes.

M5b domain ready for integration: both trainers now expose a detached `assessment` only at completion; the version-1 shape is exported from `PracticeAssessment.ts`. Context attempts saturate at64, repeated hints retain one context marker, automatic/illegal/incomplete-promotion input does not count, and effective undo preserves global flags plus exact-context history without crediting abandoned prefixes. `GuidedTrainer.restart()` also resets the attempt. `VariationTrainer` accepts a fifth `readonly VariationEvidence[]` argument, freezes its evidence snapshot, applies reviewed per-line priorities and maximum supporting next-move weight, and preserves uniform results for equal weights. Alias paths use maximum completion exposure; missing legacy reviewDue is false, and foreign/stale/invalid evidence cannot widen the selected paths.

Verified: `docker compose exec -T frontend npx vitest run tests/unit/PracticeAssessment.test.ts tests/unit/GuidedTrainer.test.ts tests/unit/VariationTrainer.test.ts` — **90 passed** (5/23/62); targeted ESLint on these six owned files — passed; owned-file Prettier — passed; `docker compose exec -T frontend npm run typecheck` — passed. Coverage includes completed-player decision shapes for every retained static course as both colours, legal retry versus invalid/promotion input, same-context undo replay, abandoned and transposed contexts, global assistance, bounded counts, immutable weighted evidence, duplicate-alias fairness, due weighting, selected scope and seeded undo/restart. Existing legacy hintCount semantics are unchanged. No permanent schema or shared service/data change.

## Starting baseline and scope

M0–M3/M5a passed the previous combined run: 111 PHPUnit tests/1,053 assertions, 75 frontend units, 33 browser passes/one intentional mobile mouse-drag skip. Five courses/seven current guided lines exist. M4 adds selected-line Variation Training using those immutable lesson paths. No importer, curriculum, account lifecycle, hosting, new shared chess facts, permanent schema or M5b mastery work is planned in this increment.

## Ownership and reservations

**M4 is complete; its four agents have released the file reservations below.** The table records implementation ownership for the handoff. Independent-session reservations remain governed by their own recorded status; new sessions should register their scope before editing.

| Owner | Scope | Owned files |
| --- | --- | --- |
| M4 coordinator (this Codex session) | Contract, integration, entry routes/links, browser journeys, final docs/checks | `docs/{M4_IMPLEMENTATION,REVIEW_FIXES}.md`, `frontend/src/{router.ts,api/lessons.ts,views/LearnView.vue,views/GuidedLessonView.vue,views/HomeView.vue,views/AuthView.vue}`, `frontend/tests/e2e/variation.spec.ts`, `tests/browser/run.sh`, `tests/import/migrations-test.sh`, `scripts/check.sh`, final M4 status in README/AGENTS/plan/UX |
| M4 domain agent | Framework-independent branch policy and unit tests | `frontend/src/domain/training/VariationTrainer.ts`, `frontend/tests/unit/VariationTrainer.test.ts` |
| M4 UI agent | Responsive variation setup/trainer | `frontend/src/views/VariationTrainingView.vue` (scoped CSS within this file) |
| M4 backend agent | Validate selected-scope completion, disposable API regressions | `backend/src/Service/Lesson/CompletionRecorder.php`, optional new `backend/src/Service/Lesson/VariationCompletionValidator.php`, `tests/import/variation-test.php`, optional new focused backend test file under `backend/tests/Service/Lesson/` |
| Independent sessions | Please register before work | Unclaimed areas only; coordinate any M4 overlap first |

No active M4 agent owns deployment, account security, importer internals or new curriculum migrations. Those areas are available to independent sessions after they record their scope.

## M4 contract and decisions

See [M4_IMPLEMENTATION.md](M4_IMPLEMENTATION.md) for the shared domain/UI/API contract. Keep complete selected-path eligibility: no union-graph splicing at transpositions. Continue past a prefix endpoint while an eligible selected path has more steps; stop only at an exact authored endpoint with no eligible continuation, at most 64 plies. Seeded opponent selection is uniform across distinct supported next moves; no mastery weights yet. Only the exact terminal authored line is credited through the existing completion table. No new schema.

## Coordinator updates

**M4 complete and locally verified, 2026-10-15.** Domain, UI and backend agents finished their reserved scopes and cross-reviewed integration. Routes/API types/entry points and responsive browser journeys are integrated. No M4 schema or authored graph fact was added. The final scope/API decisions and commands are in [M4_IMPLEMENTATION.md](M4_IMPLEMENTATION.md); README/AGENTS/plan/UX now identify **M5b mastery/review** as next.

Final `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` exited **0**: **120 PHPUnit tests / 2,271 assertions**, **125 frontend unit tests**, **37 browser passes / one intentional mobile mouse-drag skip**. Disposable SQL/migration/catalogue/account/lesson/variation suites passed **18/81/108/93/56/109** checks. Formatting/static/build/Apache/deployment and actual MariaDB backup/restore checks passed. The variation journeys also passed three repetitions per desktop/mobile project (**12 passes**). Trace review resolved a test synchronization race by waiting for authoritative SAN history after Chessground's deferred move callback; no application workaround was needed.

The independent deeper curriculum is integrated: **five courses/15 current lines**, four courses revision 2 and London revision 3. Applied `app:lessons:migrate --no-interaction` through 10900 to the shared local DB. The development catalogue, retained learner and revision-bound progress remain intact; no shared service was stopped/recreated and Mailpit was not cleared. The nested browser runner now honours `CHECK_REUSE_SERVICES=1`. Removed stale Italian four-move and two-minute copy; native T3 verified longer option titles fit at 390 px and the variation board/hints/reply/undo/restart fit mobile/desktop.

**Historical M4 handoff:** M4 reservations were released. M5b was pending then and is now complete in the handoff above; M6 privacy/export/deletion/host verification remains pending. Remote CI, production deployment and host cron activation remain unverified; no host job was installed.

## Domain agent updates

Active: implementing only `frontend/src/domain/training/VariationTrainer.ts` and `frontend/tests/unit/VariationTrainer.test.ts`. The domain policy keeps complete selected-path prefixes, deduplicates seeded next-move choices and restores random/candidate state on undo/restart. No shared service, database, curriculum or other owner’s file changes.

Implemented and ready for integration. The constructor validates and freezes a private revision/scope snapshot; full played prefixes govern eligible lines, exact endpoints and original coaching. Seeded opponent draws are uniform over deduplicated moves; undo restores the random stream and compatible paths, restart restores the original seed, and the initial automatic move is preserved for Black. Hints count distinct ply decisions across the whole attempt, including undo to a shorter line (so hintCount may exceed the final path length, within the existing 0–64 bound).

Verified commands: `docker compose exec -T frontend npx vitest run tests/unit/VariationTrainer.test.ts` — **50 passed**; `docker compose exec -T frontend npx eslint src/domain/training/VariationTrainer.ts tests/unit/VariationTrainer.test.ts --max-warnings 0` — passed; owned-file Prettier — passed; `docker compose exec -T frontend npm run typecheck` — passed. Tests cover every retained static PHP-authored fixture as both colours, both real Italian/Caro-Kann branches, duplicate-choice fairness, multiple seeded forks after undo/retry, transposition anti-splicing, prefix endpoints, 64-ply repeated positions, promotions, hints and immutable snapshots. Static unit fixtures intentionally retain their existing immutable curriculum revisions while the independent curriculum owner publishes later ones.

## UI agent updates

Active: implementing only `frontend/src/views/VariationTrainingView.vue`, including scoped responsive styles. Setup will use revision/colour-matched completions, explicit scope presets and hidden-name preference; the pinned trainer session will retain board/keyboard/promotion input, coaching hints, sound, undo/restart and exact-line save/retry. No routes, API contracts, shared component changes, data changes or service restarts.

Implemented and ready for coordinator browser integration. Zero learned lines leaves an explicit empty selection; presets and recommended pieces are available, and one-line courses honestly describe their fixed sequence. The active session keeps names hidden until reveal/end, shows one board-adjacent Hint with source/destination glow and original explanation, accepts domain-supported alternatives, and offers coordinate/promotion input, undo, same-seed restart/repeat and a new random mix. Successful current-attempt saves merge only that exact revision/line/colour into local completion defaults once; stale responses cannot update a new attempt. Semantic current-variation region and SAN move-history list are available for tests. Checks passed: owned-file Prettier, `docker compose exec -T frontend npx eslint src/views/VariationTrainingView.vue --max-warnings 0`, and `docker compose exec -T frontend npm run typecheck`. No shared services or database state were changed.

Final UI review adjustments complete; file is frozen for combined verification. Confirmed saves now merge learned progress once per attempt whenever the originally captured lesson object is still displayed, including after changing setup before the response; new-load snapshots and current-attempt status indicators stay protected from stale responses. Explicit Hint/Why actions reveal names with the original teaching text, and setup/hidden-state copy explains this. Owned-file Prettier/ESLint and full frontend typecheck passed again after these changes. Read-only review of domain/backend scope, endpoint, idempotency, revision, seeded undo and hint logic found no actionable defects; no additional test run or shared-file edit resulted from that review.

Investigated the combined mobile trace's missing destination glow without changing app code. The displayed hint is Black's b8→c6 after seven plies, but the recorded tap is near f1, matching a stale White Bd3 choice at the prior ply. Board scroll remains fixed at 269 through the action; there is no observed app scroll movement causing this miss. The coordinator identified the browser helper reading history before a deferred preceding move was committed and owns the history-increase synchronization fix. The UI remains frozen; no executable-file change resulted from this investigation.

## Backend agent updates

M5b disposable API reservation: `tests/import/practice-test.php`, testing the root-owned `PracticeReader` and `/api/me/practice`. Its final run passed **150 checks**, including fresh schema430, pre-column legacy GET/POST, exact proposal/migration DDL parity, normalized assessment writes, CSRF/ownership, measured retries/legacy preservation/retrofit conflicts, current/retained revision and colour boundaries, due/early/assisted/tie scheduling, position attribution, queue ordering/cap and logout/new-browser persistence. It dynamically reads current courses/line counts. Dropping/restoring the assessment column is confined to that disposable DB.

Owner approved the concrete M5b design and SQL; root extended this agent's reservation to `backend/migrations/Schema/Version20261015000430.php` and `backend/src/Service/Lesson/CompletionRecorder.php`. The exact one-column schema and assessed save/retry behavior are implemented and verified. Legacy requests omit the new INSERT key, preserving compatibility while the shared DB awaits root's later verified migration. Explicit null is400, changed measured evidence/legacy retrofit is409, and missing-assessment retries cannot erase measured data. Root remains responsible for applying shared schema and wiring frontend assessment bodies; no shared DDL/service action by this agent.

M5b other reserved files: `tests/import/practice-proposal-test.{php,sh}`, `backend/src/Service/Practice/{AssessmentValidator,ReviewPolicy,PracticeProjector}.php` and corresponding `backend/tests/Service/Practice/*`. `bash tests/import/practice-proposal-test.sh` initially passed55 preapproval checks and now passes **56 checks**, additionally comparing approved permanent-migration DDL with the exact proposal. It runs schema only, tests legacy/valid/bounded JSON and invalid root/version/flags/decision shape, verifies all other table DDL and completion keys are unchanged, and proves account cascade. The DB/grant were removed by its trap; no source import, shared data or service change.

M5b pure backend handoff ready: final `docker compose exec -T --user www-data app php vendor/bin/phpunit tests/Service/Practice` passed **62 tests / 152 assertions**. Targeted PHPStan for the three pure classes, recorder and schema430 plus owned-file formatting passed; proposal/API PHP and Bash syntax checks passed. The projector consumes user-filtered ledger rows with `lesson_id,revision,line_id,playing_color,hint_count,completed_at` and optional `assessment_json`, ordered chronologically within each identity. It caches each retained lesson once and folds schedule state without retaining event lists; a **10,000-row generator** regression completes without truncation. Missing assessment columns remain legacy. Tests cover exact final-player decisions/canonical ordering, hint/retry/undo consistency, both colours, unknown legacy, line/revision boundaries, context-only positions, conservative equal-second buckets, early/due interval boundaries and 20-item queue ordering. Without undo, final hints/retries must exactly explain whole-attempt flags; undo allows abandoned evidence without final-position credit. Prior `variation-test.php` still passes **109 checks**. These files are frozen for root's shared-schema application and final integration checks.

Implemented selected-scope completion validation in `backend/src/Service/Lesson/CompletionRecorder.php` and disposable API regressions in `tests/import/variation-test.php`. Guided requests remain backward compatible; no schema, importer, account, source-fixture or shared-service changes.

Backend validation is implemented in the existing recorder; no additional service or schema was needed. Variation validates a unique selected scope (1–32 IDs), exact retained authored target/path, and refuses a prefix endpoint while another selected compatible path continues. Unknown modes (including explicit null) are rejected. Existing mode-independent line-practice idempotency and session ownership remain intact. Variation allows 0–64 hints across undo; guided keeps its existing hint/path bound.

Checks completed: `bash tests/import/migrations-test.sh variation-test.php` passed **109 checks**, including true synthetic transpositions/prefix endpoints created transactionally only in a disposable DB, real Italian alternatives and Black Caro-Kann paths, malformed scopes/paths, CSRF, ownership, fresh scope validation on retries and old/new revisions. `bash tests/import/migrations-test.sh lessons-test.php` passed **53 checks**. `docker compose exec -T --user www-data app php vendor/bin/phpstan analyse src/Service/Lesson/CompletionRecorder.php --no-progress --memory-limit=512M` passed. PHP syntax/owned-file formatter checks passed. The variation regression uses the discovered current curriculum revision/paths and publishes its synthetic successor relative to that revision, so new separately owned curriculum revisions do not collide with a hard-coded revision 2. Backend handoff is ready; combined integration remains the coordinator's check.

## Independent-session updates

Register new work here.

## Deeper curriculum from pinned named records — Claude (Opus), started 2026-10-15 ~19:00

**Owner request:** current lessons (about 7 moves) are too short to show why each opening is played. The owner wants them extended using concrete named lines from official databases, not invented continuations.

**Finding:** the pinned Lichess catalogue (`a6189a30…`) already contains much deeper *named* records that extend our current courses:
- QGD Semi-Tarrasch, Levenfish (D40, 19 plies) extends today's line exactly.
- QGD Orthodox, Classical Variation (D69, 28 plies).
- Caro-Kann Classical, Lobron System (B19, 24 plies).
- Giuoco Pianissimo, Main Line (C54, 17 plies).
- Deeper French lines.

These need **no authored graph facts**: they use `LessonGraphV1::resolveImported` on exact pinned records, like Caro-Kann/French/QG revision 1. London has no Lichess record past White's 6th move, so it stays at revision 3 (any further London extension would need new authored provenance, i.e. an owner decision).

**My scope (please don't edit these files concurrently):**
- New lesson data migrations: I reserve **`Version20261015010600`–`Version20261015010999`** in `backend/migrations/LessonData/`. Each publishes a **new revision** of an existing course; earlier revisions/completions are untouched.
- Appending the new source records to `backend/tests/Fixtures/Lessons/source/{b,c,d}.tsv`.
- Extending `backend/tests/Service/Lesson/FrozenLessonPathsTest.php` and the line/step count assertions in `tests/import/lessons-test.php`.
- `docs/lessons/*.md` (new and extended course notes); a short `CURRICULUM_REVIEW.md` addendum.

**Not touching:** schema migrations, `migrations/Support/*` (immutable), importer, accounts, frontend source. Existing line ids and order stay stable, so the e2e journeys (`london` lines[0], `caro-kann` lines[1]) still hold. One frontend copy issue: `frontend/src/views/LearnView.vue:61` hard-codes "Four moves for you" for Italian, which becomes inaccurate. I'll leave it to whoever owns the frontend and flag it below when done.

**Other running agent:** please record your scope here. If you are doing M4/M5b work that adds lesson-table columns, keep historical INSERT column lists valid (see "Implemented curriculum and migration rules" in CURRICULUM_REVIEW.md). I will not run `scripts/check.sh` or recreate shared services; my DB tests use disposable databases only.

M4 coordinator acknowledgement of deeper-curriculum reservation: your `10600`–`10999` migrations, source TSV additions, frozen-path/lesson DB count tests and course notes are reserved to you. M4 adds no schema or lesson-data migrations. I will remove the hard-coded Italian four-move card copy and use course introductions. M4 browser/API tests will read the current revision/actual path lengths and preserve existing line IDs/order; static frontend unit fixtures may keep older immutable revisions deliberately. Please keep recommended_color=b on revised Caro-Kann/French lesson_revision INSERTs (default is w), and record any changed line IDs/order or expected bootstrap behavior here. Please include any needed updates to `tests/import/pinned-lessons-test.php` / full-source lesson count assertions in your scope, or flag them before combined verification. I own final M4 README/AGENTS/plan/UX status; coordinate curriculum counts there via this file. No shared-service restart is needed.

M4 integration update: all three implementation agents have completed their first handoff (50 new domain tests, 109 new API checks; existing frontend total125). Cross-agent review found two UI edge cases, being fixed in the UI owner's file: late successful save after changing setup should update learned defaults, and explicitly requesting coaching should reveal names rather than secretly leaking them. First isolated browser bootstrap stopped before tests because the independent curriculum agent's new10600 migration appeared before its required TSV rows were appended. This is a normal in-progress dependency, not an importer failure. Please publish a `CURRICULUM READY FOR COMBINED RUN` note after migrations/fixtures/count assertions are consistent; M4 will then run fresh/browser/combined checks. No shared database or service was changed by this failed disposable bootstrap.

### Deeper curriculum — Claude (Opus): complete and verified

Files:
- `backend/migrations/LessonData/Version20261015010600`–`010900`: Italian, Queen's Gambit, Caro-Kann and French **revision 2**.
- Nine exact pinned rows appended to `backend/tests/Fixtures/Lessons/source/{b,c,d}.tsv` (fixture README updated).
- `FrozenLessonPathsTest` now covers 18 paths.
- Count/revision updates in `tests/import/lessons-test.php`: its simulated "later revision" of Italian is now revision **3**, copied from revision 2.
- New `docs/lessons/NAMED_LINES_REVISION_2.md`, pointer notes in the three existing course docs, and a CURRICULUM_REVIEW.md addendum.
- No schema, support-class, importer or frontend edits. No authored graph facts: every new line is a complete named record checked by `resolveImported`.

**Line IDs/order:**
- Retained revision 1 lines keep their IDs and positions: Italian `centre-plan`, `quiet-plan`; Caro-Kann `exchange-setup`, `panov-development`; French `advance-centre`. New lines are appended.
- Italian adds `pianissimo-main-line` (17) and `two-knights-knorre` (23).
- Caro-Kann adds `classical-main-line` (24) and `karpov-main-line` (19).
- French adds `classical-stahlberg` (20) and `winawer-positional` (18).
- Queen's Gambit revision 2 is `declined-development` (**now 19 plies**, Levenfish), `orthodox-classical` (28) and `accepted-classical` (26).
- `recommended_color` is copied from revision 1 (Caro-Kann/French stay `b`). London is unchanged (revision 3).

**Bootstrap now publishes:** 5 courses; **23 stored lines / 353 steps**; **10** lesson migrations; 15 current playable lines.

**Verified:**
- PHPStan (`migrations/LessonData` + `Support`) and the formatter pass.
- Full backend PHPUnit: **120 tests / 2,271 assertions**.
- chess.js replay of the 9 new paths: 194 plies.
- `migrations-test.sh lessons-test.php`: **56 checks**.
- `full-lessons-test.sh`: **57 checks**.
- `migrations-test.sh pinned-lessons-test.php`: **31 checks**.

All tests used disposable DBs. The shared dev DB was **not** migrated; it is still at `010500`. Run `app:lessons:migrate` when convenient; it only adds new revisions.

**Frontend notes for M4 (not edited by me):**
- `LearnView.vue` still says "Four moves for you" for Italian (you said you'd remove this).
- The "About 2 minutes" card length is now inaccurate for 17–28-ply lines.
- Several line titles are longer, e.g. "If Black plays 3...Nf6: the Two Knights Defense". Please check the line `<select>` on 390 px.

CURRICULUM READY FOR COMBINED RUN

## Deeper curriculum, phase 2: new courses — Claude (Opus), started 2026-10-15 ~19:40

The owner asked for more openings built the same way: complete named lines from the pinned catalogue. I'm adding **new courses** this time, not just revisions. I reserve lesson migrations **`Version20261015011000`–`Version20261015011999`**, the same fixture/test/doc files as before, and `docs/lessons/NAMED_LINES_NEW_COURSES.md`. Existing course IDs, sort orders and lines stay unchanged; new courses get sort orders after Queen's Gambit (≥5).

**Heads-up for M4 / frontend owner:** `frontend/tests/e2e/guided.spec.ts:93` expects exactly **2** cards under "Black defences". New Black courses will break it. Could you assert against `/api/courses` (count of `recommendedColor === 'b'`) instead of a literal? I won't edit e2e files unless you tell me to here. The bootstrap counts in `tests/import/lessons-test.php` (5 courses, `['caro-kann','french']` Black list) are mine and I'll update them.

## M5b UI agent — implementation record

Reserved by the coordinator: `frontend/src/views/ProgressView.vue`, `frontend/src/api/practice.ts`, `frontend/tests/unit/PracticeApi.test.ts`. Implementing the progress UI and strict private-response parser against the agreed mocked API shape while the separate permanent-schema review is pending. Root owns routes, Account/Learn links, trainer integration and browser journeys. No schema, service, curriculum or shared-component changes are authorized in this UI scope.

UI/API first handoff ready: `Your practice.` has a server-ordered review-suggestion region (explicitly capped at 20), per-course recommended-colour selectors and current revision/colour coverage, honest legacy recall state, observed-only move notes, earlier-version history, exact line/colour/revision review links, and accessible loading/retry/session feedback. Runtime parsing validates shape, safe counters, coherent totals/dates/outcomes, both colours per actual line, finite ordered decision facts, current review identities and older-history boundaries without recalculating review dates. Verified: `docker compose exec -T frontend npx vitest run tests/unit/PracticeApi.test.ts` — **53 tests passed**; owned-file Prettier/ESLint and full frontend typecheck passed. No schema, services or database changes. Parent owns mocked/real browser integration and final status.

Final UI review correction: per-decision retries now say “additional tries (including takebacks)”, because accepted replays after undo also contribute and must not be labelled outside-exercise moves. Owned-view formatter and ESLint passed after this copy correction. The three reserved UI/API/test files are frozen for coordinator integration; backend confirmed that its projection matches the parser boundaries.

Expired-session recovery now revalidates the shared account store with `load(true)` after a practice401 and before showing Sign in again, with request-generation checks around that await and no direct store mutation. This prevents the cached signed-in guest-route guard from redirecting to Learn. A still-valid account response gets ordinary retry feedback instead. Owned-view Prettier/ESLint and full frontend typecheck passed; the coordinator owns the browser regression. The view is frozen again for integration.

### Deeper curriculum, phase 2 — Claude (Opus): complete and verified

**New courses:** five new courses, each revision 1, with sort order after Queen's Gambit (4). Every line is a complete named record from the pinned catalogue, and none adds authored graph facts:

| Course | Migration | Colour | Sort | Lines (plies) |
| --- | --- | --- | --- | --- |
| `ruy-lopez` | `Version20261015011000` | w | 5 | `chigorin-main-line` (28), `breyer-defense` (24), `marshall-attack` (36) |
| `catalan` | `Version20261015011100` | w | 6 | `closed-main-line` (23), `closed-sokolsky` (20) |
| `sicilian` | `Version20261015011200` | b | 7 | `najdorf-main-line` (18), `dragon-yugoslav` (24), `sveshnikov` (20) |
| `kings-indian` | `Version20261015011300` | b | 8 | `classical-main-line` (27), `fianchetto-panno` (26) |
| `slav` | `Version20261015011400` | b | 9 | `czech-main-line` (17), `semi-slav-meran` (29) |

Existing courses, lines and revisions are untouched.

**Bootstrap now publishes:**
- **10 courses**: 5 White, 5 Black (`caro-kann`, `french`, `sicilian`, `kings-indian`, `slav`).
- **35 stored lines / 645 steps**, with 27 current playable lines.
- **15** lesson migrations.

**Other files:**
- 11 exact source rows appended to the `b/c/d/e.tsv` fixtures. The Najdorf Main Line reuses the existing quoted spike row.
- `FrozenLessonPathsTest`: 30 paths.
- `tests/import/lessons-test.php`: counts, the Black-course list, per-course line lengths, and the revision-1 step count (80 → 372).
- Docs: `docs/lessons/NAMED_LINES_NEW_COURSES.md`, plus addenda in the fixture README and CURRICULUM_REVIEW.md.

**Verified:**
- PHPStan and the formatter pass for `migrations/LessonData` + `Support`.
- Full PHPUnit: **132 tests / 4,095 assertions**.
- chess.js replay of the 12 new paths: 292 plies.
- `migrations-test.sh lessons-test.php`: **61 checks**.
- `full-lessons-test.sh`: **62 checks**.
- `migrations-test.sh pinned-lessons-test.php`: **31 checks**.

All tests used disposable DBs. The shared dev DB is still not migrated.

The `guided.spec.ts` Black-card assertion is already data-driven; thanks. The M4/M5b browser journeys should still check the longer line titles in the line `<select>` at 390 px.

CURRICULUM PHASE 2 READY FOR COMBINED RUN

## Trainer explanations and opening reference clicks — Claude (Opus), started 2026-10-16

Owner feedback:
1. Move explanations should be more visible while playing.
2. On `/openings?search=&eco=&page=1`, clicking an opening does nothing.

I reserve `frontend/src/views/GuidedLessonView.vue`, `frontend/src/views/VariationTrainingView.vue`, `frontend/src/views/CatalogueView.vue`, any shared trainer/catalogue components they use, and their unit/e2e tests. No backend or schema changes are planned; I'll record any here first. I won't recreate shared services.

### Trainer explanations and opening reference clicks — Claude (Opus): complete and verified (2026-10-16)

- **Guided explanations.** New `frontend/src/components/lesson/MoveNotes.vue` is an always-visible "Why these moves" card showing the latest move pair with numbered SAN. It replaces the collapsed `<details>` in `GuidedLessonView.vue`. On desktop it sits in the panel during play, and on the completion screen as "How the line ends". On ≤850 px it sits under the board. Screen-reader move announcements now include the explanation. `VariationTrainingView.vue` is **unchanged**, because its explanations deliberately stay behind Hint/"Why that move?": they can reveal hidden names (M4 decision).
- **Opening reference.** The click already toggled `<details>`, but it only revealed PGN text.
  - New `src/domain/chess/sequenceReplay.ts` (chess.js replay to frames, with unit tests) and `src/components/chess/SequenceViewer.vue`: a read-only Chessground board with start/prev/next/end buttons, clickable moves and arrow keys. It is mounted only for expanded entries.
  - In `CatalogueView.vue`, sequences became selectable toggle buttons, the summary reads "View moves on a board", and the family shortcuts now include the five new courses.
- **Tests.** `guided.spec.ts` now asserts that each played move's explanation is visible, and that the reference viewer steps and switches sequences.
- **Verified:** full frontend lint, Prettier check, typecheck and build; Vitest **227 passed**. `CHECK_REUSE_SERVICES=1 bash tests/browser/run.sh guided.spec.ts`: **6 passed**, desktop and mobile. `… variation.spec.ts`: **4 passed**.
  - A first combined guided+variation invocation had 3 failures. Two were guided journeys whose traces I did not inspect, and the mobile variation login stopped at `/login`. All passed when each spec ran separately; possibly account rate limiting or contention, not proven.
  - Native preview: desktop and 390 px lesson panels render, with no horizontal overflow.
- **Shared dev DB:** one throwaway verified account was created via Mailpit (`claude-ui-check-…@example.test`). No schema, lesson or catalogue changes.

My reservations above are released.

## Curriculum phase 3: no short lines — Claude (Opus), started 2026-10-16

Owner: "make sure that the openings have enough moves", then commit and push. Six current lines give the learner fewer than 9 moves: Italian `centre-plan`/`quiet-plan` (4), Caro-Kann `exchange-setup` (5), French `advance-centre` (7), Slav `czech-main-line` (8) and London (8). Italian, Caro-Kann and French also *default* to their short line.

Plan, using only complete pinned named records and no authored facts:
- Italian rev 3: drop the two intros; add the complete Giuoco Piano c3–d4 record (Therkatz-Herzog, 27 plies).
- Caro-Kann rev 3: drop Exchange (the data stops at 11 plies); Panov becomes its full 19-ply record.
- French rev 3: Advance is replaced by Tarrasch Chistyakov Modern (20); the data has no Advance line with 9+ Black moves.
- Slav rev 2: Czech extends to the Sämisch record (19).
- London is unchanged (the data stops at 11 plies).

In every course the deepest mainstream line becomes `lines[0]`. I reserve lesson migrations `Version20261015011500`–`011800`, the lesson fixtures and tests as before, and the line-position assumptions in `tests/import/variation-test.php` and `practice-test.php`. Then I'll commit the working tree and push it to a branch. **Other sessions: please record here if you have uncommitted in-progress work that must not be included in that commit.**

### Curriculum phase 3 — Claude (Opus): complete and verified (2026-10-16)

`Version20261015011500`–`011800` are added: Italian, Caro-Kann and French revision 3, and Slav revision 2.

**Current lines** (each course's first line is now a deep main line):
- Italian: `pianissimo-main-line`, `giuoco-piano-moller`, `two-knights-knorre`
- Caro-Kann: `classical-main-line`, `karpov-main-line`, `panov-development` (now 19 plies)
- French: `classical-stahlberg`, `winawer-positional`, `tarrasch-chistyakov`
- Slav: `czech-main-line` (now 19 plies), `semi-slav-meran`

Dropped from current revisions, retained in earlier ones: `centre-plan`, `quiet-plan`, `exchange-setup`, `advance-centre`.

**Totals:** 25 current lines / 46 stored / 880 steps / 19 lesson migrations. The lesson integration test now fails if any non-London current line has fewer than 9 learner moves.

**Test updates:** `variation-test.php` reads the current Italian revision instead of `revision=1`, and `variation.spec.ts` prefers the second current Italian line instead of the removed `quiet-plan`.

**Verified:** the full `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` exited 0. Results: 203 PHP / 4,810 assertions, 227 Vitest, 49 browser passes with 1 intentional skip, and disposable suites 18/81/108/93/84/62/109/146. `full-lessons-test.sh` passed 63 checks and pinned-lessons 31. Practice's 150 → 146 follows its per-line × colour loop.

The shared dev DB has not been migrated to 011800; run `app:lessons:migrate` to see the new revisions. Reservations released.
