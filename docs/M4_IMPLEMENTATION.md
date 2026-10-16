# M4 Variation Training implementation contract

Status: **M4 implemented and locally verified** on 2026-10-15. The owner requested multiple agents and shared coordination in [REVIEW_COORDINATION.md](REVIEW_COORDINATION.md). Domain, UI and backend agents implemented separate scopes; the coordinator integrated routes, API types, entry points, browser journeys and documentation.

## Product behavior

`/train/:id` is authenticated and uses the existing revision-pinned `GET /api/lessons/:id` payload. Learners choose their pieces and one or more course lines. Default selection is lines previously completed for that same lesson revision/colour; when none are learned, show that clearly and let the learner explicitly select available lines. Offer “All learned”, “Main line only” and “Select all available” presets. A one-line selection is allowed and explicitly described as one fixed sequence. Course cards with multiple lines and guided completion screens link to practice. The route supports every current course; Italian/Caro-Kann are the real browser acceptance journeys.

The opponent chooses only a supported continuation among selected paths. Any taught player reply within that scope is accepted. A legal out-of-scope move stays on the current position, with neutral retry/hint feedback and no opponent response. Keep promotion choice, board/coordinate input, origin-then-destination glowing hints with original explanations, sound, undo/restart, responsive layout and polite announcements. Names can be hidden until reveal/end; explicitly requesting Hint or Why also reveals names, with this behavior disclosed beside the preference and hidden state. Opponent color and opening context remain understandable. At completion show the exact line actually practised, finite scope and save state, without mastery claims.

## Domain contract

New `frontend/src/domain/training/VariationTrainer.ts` exports `VariationTrainer` and a discriminated move result equivalent to GuidedResult (accepted/illegal/outside-line/wait/alternative-line/promotion-required as applicable).

Constructor: `new VariationTrainer(lesson: Lesson, lineIds: string[], playingColor: PlayingColor, seed: number)`.

Public members:

- readonly `lesson`, `playingColor`, `lineIds` (selected identities), `seed`.
- getters `ply`, `complete`, `playerTurn`, `hintCount`, `path` (copied UCI list), `canUndo`.
- `eligibleLines: LessonLine[]` (still compatible full selected paths, including any matching prefix endpoints).
- `continuations: LessonStep[]` (unique next UCI choices from compatible paths, deterministic lesson order).
- `expected: LessonStep | undefined` (first supported continuation, used for an optional hint, not an exclusive target).
- `previous: LessonStep | undefined` (last accepted move's original coaching).
- `completedLine: LessonLine | undefined` (only on complete; stable first exact terminal match).
- `snapshot(): ChessSnapshot`, `play(from,to,promotion?)`, `reply()`, `hint(): string`, `hintSquares(): [Square,Square] | undefined`, `undo(): void`, `restart(): void`.

Validate selected IDs (nonempty, unique, existing, maximum 32), seed (uint32), colour and all selected legal paths/canonical transitions at construction. Reuse BrowserChessGame. Track candidate path cursors/prefixes, not just outgoing canonical edges. Shared positions may be reused, but reaching a transposition never enables a suffix from an incompatible authored prefix or credits an unplayed line. If a selected path is a prefix of another, continue while any eligible candidate has a next move; only the terminal exact match is credited. Every selected line is finite2–64 plies; cap the session at 64. Deduplicate next moves before uniform seeded selection so duplicate records do not bias it. Undo restores candidate/endpoint/random state to the last player decision with its reply; preserve the automatic initial White move when Black has made no move. Restart restores original scope and seed. Hint count is per distinct decision on the current attempt (repeated requests at a decision count once).

## Completion API contract

Extend the existing `Completion` request with optional `mode: 'guided' | 'variation'` and `selectedLineIds: string[]` for variation mode. Missing mode preserves all existing guided requests. POST remains `/api/me/completions` with the existing CSRF/session safeguards and200 `{saved:true}` response.

For variation requests, validate unique nonempty selected IDs (max 32), each belongs to the exact retained lesson/revision, the credited `lineId` is selected, and the submitted UCI path is exactly a selected finite authored line. Reject a prefix completion when another selected compatible line has a continuation. Reject stitched/transposed paths that are not a complete authored selected path, upstream-only edges, unknown IDs/mode, malformed scope and >64 plies. Preserve idempotency/user isolation/revision binding. Existing private `user_line_completion` records remain line practice regardless of mode: do not add tables/columns, manufacture mastery or persist seeds/raw interaction logs.

Hint usage follows the guided trainer's per-ply decision markers across the whole attempt, including undo/retries, bounded 0–64. A variation attempt may explore a longer branch before undoing into a shorter terminal path, so variation hintCount may exceed its final path length; guided requests retain their existing stricter path-length bound. No accuracy/mastery percentage is derived from this count in M4.

## Ownership and validation

Domain/UI/backend agents own only the files listed in the coordination doc. The coordinator owns shared API types, routes, lesson entry links, browser integration, allowlist/check wiring and final docs. No global formatting or shared container restart.

Required checks: domain unit fixtures for both real Italian replies, seeded White choices while playing Black/Caro-Kann, transposition anti-splicing, prefix endpoints, repeated-position cap, promotion, hints, undo/restart; disposable backend scope/endpoint/retry/privacy regressions; desktop/mobile real browser setup, Black orientation, legal-outside rejection followed immediately by supported move, board hints/name reveal/save/reload. Preserve the full previous suite and pinned-source bootstrap.

## Verification record (2026-10-15)

| Exact command/check | Result |
| --- | --- |
| `docker compose exec -T frontend npx vitest run tests/unit/VariationTrainer.test.ts` | **50 passed**; supported replies, uniform distinct choices, immutable scopes, seeded undo/restart, Black start, transposition anti-splicing, prefix endpoints, promotion, hints and 64-ply cap |
| `bash tests/import/migrations-test.sh variation-test.php` | **109 checks**; exact scoped terminal paths, malformed scope/mode, guided compatibility, CSRF/ownership/retry and retained revisions |
| `CHECK_REUSE_SERVICES=1 bash tests/browser/run.sh variation.spec.ts` | **4 passed** on desktop/mobile; real account/API journeys |
| `CHECK_REUSE_SERVICES=1 bash tests/browser/run.sh variation.spec.ts --repeat-each=3` | **12 passed**; repeated journeys after synchronizing input with Chessground's deferred accepted-move callback |
| Final `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` | **Exit 0**; **120 PHPUnit tests / 2,271 assertions**, **125 Vitest tests**, **37 browser passes / one intentional mobile mouse-drag skip**; SQL/migration/catalogue/account/lesson/variation suites **18/81/108/93/56/109**; static/format/build/Apache/deployment and actual MariaDB backup/restore checks passed |
| `docker compose exec -T --user www-data app bin/console app:lessons:migrate --no-interaction` | Applied the four separately authored additive curriculum migrations, 10600–10900, locally; catalogue/account/progress retained |
| Native T3 preview | Variation board, glowing origin/destination, explanation, supported reply, undo/restart at 412 px and desktop; longer guided-line options fit at 390 px without horizontal overflow |

Browser cases verify Black orientation/automatic White start, explicit selection and learned defaults by revision/colour, neutral legal-outside-scope feedback followed by a supported move, alternate Italian replies, hidden/revealed names, exact-line completion, unchanged retry identity, reload and a delayed successful save after returning to setup. The completion API keeps the exact revision/path/selection checks even on retries. Cross-review corrected late-save learning defaults and explicitly disclosed coaching name reveals.

The independent curriculum session supplied **15 current lines across five courses**, with Italian/Caro-Kann/French/Queen's Gambit revision 2 and London revision 3. Its full pinned-source bootstrap passed 31 checks and deeper full-source lesson suite passed 57; see [the curriculum source/verification notes](lessons/NAMED_LINES_REVISION_2.md) and [coordination record](REVIEW_COORDINATION.md). Static frontend unit fixtures deliberately retain earlier immutable revisions as regression cases. M4 itself adds no schema, curriculum migration or shared chess fact.

All integration DBs/browser containers were disposable. Shared DB/Mailpit services were reused, the retained learner was preserved and no hosting task was installed. M5b mastery/review and M6 privacy/export/deletion/hosting verification remain pending; no remote CI or production deployment has been demonstrated.
