# Practice, recall and reviews

**M5b is implemented and locally verified.** Its private storage/review design was [approved by the owner](M5B_REVIEW.md) on 2026-10-15; no importer or curriculum architecture changed.

## Try it

After local setup and `make catalogue`, sign in, finish a guided lesson or variation practice, then choose **See your practice & reviews**. The same page is available from Account or at [localhost:5173/progress](http://localhost:5173/progress). Practice with White and Black is separate. Current coverage says how many of that course's current taught lines you have finished with the displayed pieces; it is not a percentage of the opening reference or your chess strength.

The page shows suggested reviews, current course coverage, measured recall, moves to revisit and earlier-version history. Each course defaults to its recommended pieces. Review links select an exact current line, colour and revision in the existing guided trainer. An outdated link explains the course update and offers explicit current setup. Old practice remains in its original version and does not automatically complete a revised line.

## What a finished practice records

Both trainers collect a bounded summary while you play. A taught player decision counts its accepted move and any extra legal tries outside the exercise. A replay after a takeback is another try; illegal input and an unfinished promotion do not count. Hints are welcome. Legal alternatives outside an assigned exercise stay neutral and preserve the board.

A line is recalled without help when its completed attempt used no hint, outside-exercise retry or takeback. Decision-level recall means a first try without a hint in that exact authored line context. A takeback records the finished practice with help and conservatively excludes its decisions from independent recall. Restart starts a fresh attempt. Counters on the page describe finished, measured practices, not every unfinished session.

Hints/retries on abandoned branches remain whole-attempt assistance flags; those abandoned positions receive no progress. Reaching the same chess position in another authored line does not complete that line or inherit its decision credit. Older completions remain useful practice history with recall marked **unknown**, even if their old hint count was zero.

## Review timing and variation replies

The server schedules the first measured unassisted review after one day. Successful due reviews advance through 1, 3, 7, 14 and 30 days. Repeating before the due time records practice without advancing that interval. A practice with help schedules another look in ten minutes. Legacy-only current practice is available to review immediately with recall unknown. New lines are learning opportunities rather than overdue work.

Times and due status come from the server's UTC clock. Equal-second measured events are grouped conservatively: any assisted practice in that second determines that bucket's schedule. The page shows up to 20 suggestions; you can practise any line at any time.

Variation training favours supported lines with less practice or a due review. It freezes evidence for the selected lesson revision/colour when the session starts. Distinct next moves receive the maximum compatible line priority; duplicate aliases do not multiply probability. The seeded mix and undo/restart remain deterministic, and weighting never expands the chosen taught paths. Active lessons do not change when a catalogue or course is refreshed.

## Private API and storage

`GET /api/me/practice` requires the current account and returns `Cache-Control: private, no-store`:

- `generatedAt`: server UTC timestamp.
- `courses`: current metadata and both colour entries per authored line; each includes completion/assessment/recall counts, last practice/assessment/outcome, due time/status and contextual decision counters.
- `reviewQueue`: up to 20 ordered current lesson/revision/line/colour identities.
- `history`: retained non-current course versions, separate from current coverage.

Existing `/api/me/progress` and lesson responses remain compatible. No private progress is added to public `/api/courses` caching. The browser parser rejects malformed identities, counters and review references. A confirmed session expiry refreshes the account state before offering sign-in.

`POST /api/me/completions` adds optional `assessment`:

```json
{"version":1,"usedHint":false,"usedRetry":false,"usedUndo":false,"decisions":[{"ply":0,"attempts":1,"hinted":false}]}
```

Every final player decision appears once using its zero-based authored ply; no opponent decision is accepted. Attempts are bounded 1–64, decisions 1–32. The server validates attribution against the exact retained taught path, normalizes field/decision order and checks hint/retry consistency. Without takebacks, all hint/retry evidence must appear on the final decisions. A full maximum-size variation request with this summary remains within the existing 4 KiB request bound.

Approved schema migration `Version20261015000430` adds only nullable `assessment_json VARCHAR(4096)` to `user_line_completion`, with JSON/version/shape bounds. Existing user ownership, exact revision/line foreign keys and account-deletion cascade remain unchanged. No physical backfill or aggregate table is introduced. All counts and schedules derive from that immutable ledger and retained paths.

Retries of one user/attempt identity save one atomic row. A changed measured assessment or retrospective assessment on an already saved legacy attempt returns 409. A legacy retry cannot erase an existing measured summary. Assessment/progress carries no engine score, raw rejected move choices, seed or abandoned-path log. Account download/deletion now includes/removes this ledger in [M6a](ACCOUNT_DATA.md); public-launch retention/legal/hosting verification remains M6 work.

The private reader uses one repeatable-read view for publication pointers, lesson paths and account rows. It iterates all relevant ledger rows without a second retained event list and caches each immutable revision once; the DBAL/PDO driver may buffer results. Per-user read cost grows with private history. A 10,000-row generated regression proves no silent truncation; materialized aggregates remain a future decision if measured performance requires them.

## Verification

Verified on 2026-10-15:

| Command/check | Result |
| --- | --- |
| `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` | **Exit 0: 194 PHPUnit tests / 4,247 assertions, 219 Vitest tests and 45 desktop/mobile browser passes**, plus one intentional mobile mouse-drag skip; static/format/type/build, Apache/deployment and real private MariaDB backup/restore passed |
| Disposable suites included above | **18 SQL constraint, 81 migration, 108 catalogue, 93 account, 61 lesson, 109 variation and 150 practice checks** |
| `bash tests/import/practice-proposal-test.sh` | **56 checks**, including exact approved proposal/permanent-migration DDL parity |
| `docker compose exec -T --user www-data app php vendor/bin/phpunit tests/Service/Practice` | **62 tests / 152 assertions** |
| Focused frontend units | Training domain **90**, practice response parser **53**, review links **22** |
| `CHECK_REUSE_SERVICES=1 bash tests/browser/run.sh guided.spec.ts variation.spec.ts practice.spec.ts` | **18 desktop/mobile passes** |

The browser journeys include a separate cookie jar/session, measured hints, normalized save retries, exact colour/revision selection, stale links and expired-session recovery. Native T3 inspection verifies the signed-in ten-course dashboard at 390 px. Fresh bootstrap applies nine schema and 15 lesson migrations. Shared services, the pinned source catalogue and retained learner were preserved. Remote CI and production hosting remain unverified; M6 is next.
