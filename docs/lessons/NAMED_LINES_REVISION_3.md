# Revision 3: no short lines

The owner asked that the openings have enough moves. After revision 2 and the new courses, six current lines still gave the learner fewer than nine moves. Italian, Caro-Kann and French also opened on one of those short introductions by default.

| Course / line | Learner moves | Deepest named record that continues it |
| --- | --- | --- |
| Italian `centre-plan` | 4 | Giuoco Piano, Therkatz-Herzog Variation (27 plies) |
| Italian `quiet-plan` | 4 | none past 9 plies; the Pianissimo main line already teaches the quiet plan |
| Caro-Kann `exchange-setup` | 5 | none past 11 plies |
| French `advance-centre` | 7 | none past 17 plies (8 Black moves) |
| Slav `czech-main-line` | 8 | Czech Variation, Dutch Variation, Sämisch Variation (19 plies) |
| London `first-setup` | 8 | none past 11 plies; the lesson already extends the data with owner-approved authored facts |

## Published revisions

Every current line except London is now a complete named record at the pinned SHA, with at least nine learner moves. The first line of each course is a deep main line. No authored facts are added; `LessonGraphV1::resolveImported()` validates every new path as before.

| Course | Migration | Current lines in order (plies / learner moves) |
| --- | --- | --- |
| Italian rev 3 | `Version20261015011500` | `pianissimo-main-line` (17/9), **`giuoco-piano-moller`** (27/14), `two-knights-knorre` (23/12) |
| Caro-Kann rev 3 | `Version20261015011600` | `classical-main-line` (24/12), `karpov-main-line` (19/9), **`panov-development`** (19/9, now the full record) |
| French rev 3 | `Version20261015011700` | `classical-stahlberg` (20/10), `winawer-positional` (18/9), **`tarrasch-chistyakov`** (20/10) |
| Slav rev 2 | `Version20261015011800` | **`czech-main-line`** (19/9, extended to the Sämisch record), `semi-slav-meran` (29/14) |

Bold lines are new or extended. Retained lines are copied from the previous revision with `INSERT … SELECT`, so their paths and prose are identical; only their sort order changes. The extended Panov and Czech lines reuse their published explanations for every unchanged move. Their former final notes said the lesson ends there, so those moves get new explanations, as do the added moves. Each migration refuses to run unless the previous revision is still current and the retained lines have their expected lengths.

The dropped introductions stay in their earlier revisions. Sessions and completions pinned to them remain valid, and saved practice is not transferred to the new lines. Line IDs are revision-scoped. `panov-development` and `czech-main-line` keep their IDs but are longer in the new revision. The French Advance line is no longer current: no named Advance record gives Black nine moves.

London remains at eight White moves. Going further would need either another owner-approved authored extension or a different source. Both are outside "complete named records from the pinned catalogue".

## Source records

Pinned dataset: [`lichess-org/chess-openings`](https://github.com/lichess-org/chess-openings) at `a6189a30dc273ccb21fc2536a9a2fefd5592a67a` (CC0). Row numbers count the header as row 1.

| Line | File, row | ECO | Exact name | Exact PGN |
| --- | --- | --- | --- | --- |
| `italian/3/giuoco-piano-moller` | `c.tsv` 955 | C54 | Italian Game: Giuoco Piano, Therkatz-Herzog Variation | `1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. c3 Nf6 5. d4 exd4 6. cxd4 Bb4+ 7. Nc3 Nxe4 8. O-O Bxc3 9. d5 Bf6 10. Re1 Ne7 11. Rxe4 d6 12. Bg5 Bxg5 13. Nxg5 O-O 14. Nxh7` |
| `caro-kann/3/panov-development` | `b.tsv` 337 | B14 | Caro-Kann Defense: Panov Attack | `1. e4 c6 2. d4 d5 3. exd5 cxd5 4. c4 Nc6 5. Nc3 Nf6 6. Nf3 e6 7. cxd5 exd5 8. Bb5 Bd6 9. Bg5 O-O 10. O-O` |
| `french/3/tarrasch-chistyakov` | `c.tsv` 87 | C07 | French Defense: Tarrasch Variation, Chistyakov Defense, Modern Line | `1. e4 e6 2. d4 d5 3. Nd2 c5 4. exd5 Qxd5 5. Ngf3 cxd4 6. Bc4 Qd6 7. O-O Nf6 8. Nb3 Nc6 9. Nbxd4 Nxd4 10. Nxd4 a6` |
| `slav/2/czech-main-line` | `d.tsv` 228 | D19 | Slav Defense: Czech Variation, Dutch Variation, Sämisch Variation | `1. d4 d5 2. c4 c6 3. Nf3 Nf6 4. Nc3 dxc4 5. a4 Bf5 6. e3 e6 7. Bxc4 Bb4 8. O-O O-O 9. Qe2 Ne4 10. g4` |

The Giuoco Piano record is the Möller Attack. White gives up the e4 pawn for development, then wins material back with `9. d5`, `10. Re1` and `14. Nxh7`. The notes present it as the classical c3–d4 plan and state the material balance at each stage. Every concrete claim was checked against the positions: pins, attacked pieces and material.

## Verification (2026-10-16)

- PHPStan and the formatter passed for `migrations/LessonData` and `Support`.
- `FrozenLessonPathsTest`: 36 tests / 4,245 assertions. A separate chess.js replay of the four new paths covered 85 plies.
- `migrations-test.sh lessons-test.php`: 62 checks. It now asserts that every current line except London has at least nine learner moves.
- `full-lessons-test.sh`: 63 checks; `migrations-test.sh pinned-lessons-test.php`: 31 checks.
- `migrations-test.sh variation-test.php`: 109 checks, and `practice-test.php`: 146. Practice's per-line × colour loop has four fewer lines × colours, so the count dropped from 150.
- Backend PHPUnit: 203 tests / 4,810 assertions.
- Browser: `guided.spec.ts` 6 passed, `practice.spec.ts` 8 passed, `variation.spec.ts` 4 passed. The variation spec now prefers the second current Italian line instead of the removed `quiet-plan`.

Fixtures `backend/tests/Fixtures/Lessons/source/c.tsv` and `d.tsv` include the three new records. The Panov record was already present.

The final combined `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` exited 0 on 2026-10-16.
- Backend: 203 PHPUnit tests / 4,810 assertions, 227 Vitest tests, and 49 desktop/mobile browser passes with one intentional mobile mouse-drag skip.
- Disposable suites: 18 constraint, 81 migration, 108 catalogue, 93 account, 84 account-data, 62 lesson, 109 variation and 146 practice checks.
