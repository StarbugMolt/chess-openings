# M5b approved recall and review design

Status: **owner approved and implemented on 2026-10-15; final combined verification passed.** The owner approved this private progress design and exact SQL after 55 disposable checks. Permanent additive schema `Version20261015000430` is implemented and applied locally. Existing importer, curriculum and provenance approvals remain settled; do not ask for them or this approved M5b design again.

## Storage decision

Add one nullable `assessment_json VARCHAR(4096)` column to the existing private `user_line_completion` ledger. The existing user/attempt primary key, exact lesson/revision/line/colour foreign keys and account-delete cascade remain authoritative. Keep all old rows as NULL: a historical completion is exposure, and a historical zero hint count does not prove independent recall. No backfill, new aggregate table, raw wrong-move log, engine judgement, seed storage or abandoned-position history.

[M5B_SCHEMA_PROPOSAL.sql](M5B_SCHEMA_PROPOSAL.sql) is the approved exact additive SQL implemented by schema 430. The column is normalized ASCII JSON, bounded to 4096 bytes, with database checks for a version-1 object and 1–32 decision summaries. The application validates the complete nested shape before storing it. Existing ORM protection already includes this table. The disposable schema suite now passes 56 checks, including exact proposal/permanent-migration DDL parity.

The nullable column contains only:

```json
{"version":1,"usedHint":false,"usedRetry":false,"usedUndo":false,"decisions":[{"ply":0,"attempts":1,"hinted":false}]}
```

`ply` is zero-based in the final exact authored path. Include every player decision exactly once, in order, and no opponent decision. Each decision has 1–64 attempts and a boolean hint marker. Attempts count accepted taught moves (including replay of the same context after undo) plus legal moves outside the selected exercise; illegal input and an incomplete promotion do not count. Labels distinguish neutral moves outside the exercise and additional tries (including takebacks); they never call these a chess blunder. Counts saturate at 64. The whole-attempt flags survive undo/branch changes. Restart starts a new attempt. A hint on an abandoned branch can leave `usedHint=true` while no final decision is hinted; abandoned positions receive no credit. A replay after undo can have attempts>1 with usedRetry=false and usedUndo=true; the validator accepts this combination.

An undo invalidates independent recall for that finished attempt, including its position summaries, because the player has seen the replies. A line is recalled without help only when all three flags are false. A final position is recalled on first try when attempts=1, hinted=false and usedUndo=false. Hints/retries in abandoned branches conservatively prevent whole-line recall but are never attributed to a final position. These are self-reported practice summaries validated against the taught path, not a chess-strength rating or certified assessment.

Without undo, the final hinted-decision count must equal `hintCount`, and the presence of attempts above one must match `usedRetry`. With undo, abandoned hint/retry evidence can explain whole-attempt flags; final decisions never receive that abandoned credit.

The request adds optional `assessment` to the existing completion JSON. Missing assessment keeps old clients working. Explicit null/malformed assessment is rejected. Normalize field order and decision order before persistence/idempotency comparison. A changed measured summary on an existing measured attempt is 409. An existing legacy attempt cannot gain a retrospective assessment; retry remains safe and cannot erase measured data. The same user/attempt key still saves one atomic row, so no redundant counters can drift. A worst-case existing scope/path plus the proposed summary serializes to 3782 bytes, within the existing 4096-byte request bound.

## Position identity and revision boundaries

Derive progress from the immutable completion ledger and retained authored paths. A decision context is `(lesson, revision, line, playing colour, ply)`, with its canonical position/move obtained from that exact taught step. Two lines sharing a position do not inherit each other's completion or decision credit. This makes the denominator explicit: decisions in a specific taught line. All current-line totals/coverage use the current published revision and the selected colour. Earlier-version practice remains separately visible and never inflates current coverage. Source refreshes cannot rewrite summaries or active sessions.

Per-line facts include total/assessed/unassisted completions, last practice/assessment/outcome and next review. Per-decision facts include finished assessed practices, first-try recalls, hinted practices and retries. Legacy NULL rows count only as total practice. Read all relevant rows without silently truncating; iterate the ledger and load each immutable path once. DBAL/PDO may buffer results. This avoids materialized aggregate tables and repair/backfill races. The tradeoff is per-user work proportional to private history; a 10,000-row generated regression verifies completeness; revisit materialization only if measured performance requires it.

## Review policy

The server owns UTC timestamps and the review clock. A first measured unassisted completion schedules review after 1 day. A due unassisted review advances through 1, 3, 7, 14 and 30 days, capped at 30. Repeating before the due time records practice without advancing the stage or moving the due date. Assisted practice returns the stage to 0 and schedules a review in 10 minutes. Legacy-only practised lines are available to review immediately with recall marked unknown. New, unpractised lines are offered as guided learning rather than overdue work.

Completion timestamps currently have second precision. Group equal-second events for the same line/colour conservatively: if any measured attempt in that second was assisted, that bucket uses the assisted schedule/outcome. Never infer real chronology from random attempt IDs. Earlier and later measured events are processed by server timestamp. There is no mastery percentage; the UI shows concrete coverage such as “3 of 5 current lines practised as Black” and “Recalled without help”.

Variation training freezes weighting evidence at session start for the exact lesson revision/colour. Line priority is `1 + floor(8 / (1 + completions)) + (reviewDue ? 4 : 0)`. Missing/new evidence gives equal priorities. At an opponent fork, deduplicate supported moves and take the maximum compatible line priority per move. Identical full paths use their most-practised exposure so duplicate aliases do not bias selection. Seeded random draws and undo/restart retain deterministic behavior; selected whole-path eligibility and finite endpoints remain unchanged. Weighting never makes an unsupported reply playable.

## API and interface

Keep existing `/api/me/progress` and lesson payloads compatible. Add private authenticated `GET /api/me/practice` with `generatedAt`, current courses, line/colour facts, a server-ordered bounded review queue referencing those identities and separate earlier-version history. Return `private, no-store`. Never expose private progress through public `/api/courses` caching.

Add authenticated `/progress` (“Your practice”), linked from Account, Learn and completion screens. Show ready reviews, clear current course/colour coverage and collapsed earlier-version history. Review links reuse the existing guided mode: `/learn/:id?line=...&color=b&revision=N`. Validate every parameter; a stale revision explains the update and returns to explicit current setup. Mobile uses wrapping one-column cards and 44 px actions, with text dates/statuses and a keyboard-accessible retry.

## Acceptance verified

- Disposable SQL proposal checks: nullable legacy data, valid/bounded versioned JSON, constraints, account cascade and unchanged curriculum/source tables.
- Pure domain tests: final-path attribution after undo, whole flags, bounded retries, promotion, duplicate hints, restart and immutable weighted scopes.
- Server/API tests: exact final-player decisions, malformed/contradictory summaries, normalized retries, legacy compatibility, user isolation, old/current revisions, due boundaries and equal-second events.
- Progress UI and real desktop/mobile journeys: empty/legacy/measured progress, review links, stale revision notices, save/retry, logout/new session persistence and no overflow.
- Prior suites and source/bootstrap ordering passed. The approved permanent migration was applied locally after disposable verification; hosting and M6 remain separate work.

Final `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` exited 0: **194 PHP tests / 4,247 assertions, 219 frontend tests, 45 browser passes and one intentional mobile mouse-drag skip**. Disposable practice API verification passed 150 checks; SQL proposal/migration parity passed 56. Exact commands, policy details and limitations are in [PROGRESS.md](PROGRESS.md). Shared services, source catalogue, accounts and prior practice were preserved. This approval is settled and does not need to be requested again.
