# Revision 2: complete named lines

The owner found the first lessons, at about seven moves each, too short to show why each opening is played. They asked for longer lessons built from concrete lines in official opening data rather than invented continuations. The pinned Lichess catalogue already contains much deeper **named** records for four of the five courses. Revision 2 of those courses teaches the complete records, up to 28 plies.

Every new line is an exact, complete named record at the pinned SHA. `LessonGraphV1::resolveImported()` checks every frozen UCI, SAN and canonical position key against that record before any lesson row is queued. **No chess positions, moves or authored-provenance rows are added.** All teaching prose is original.

## Published revisions

| Course | Migration | Revision 2 lines (plies) | Retained unchanged from revision 1 |
| --- | --- | --- | --- |
| Italian Game (`w`) | `Version20261015010600` | `pianissimo-main-line` (17), `two-knights-knorre` (23) | `centre-plan` (8), `quiet-plan` (8) |
| Queen's Gambit (`w`) | `Version20261015010700` | `declined-development` (19, extended from 14), `orthodox-classical` (28), `accepted-classical` (26) | — |
| Caro-Kann Defense (`b`) | `Version20261015010800` | `classical-main-line` (24), `karpov-main-line` (19) | `exchange-setup` (10), `panov-development` (18) |
| French Defense (`b`) | `Version20261015010900` | `classical-stahlberg` (20), `winawer-positional` (18) | `advance-centre` (14) |

Retained lines are copied from revision 1 with `INSERT … SELECT`, so their paths, hints and explanations are identical. Existing line IDs keep their order. New lines are appended after them. The revision row copies the start position and `recommended_color` from revision 1 and gets a new introduction. Each migration refuses to run unless revision 1 is still current and the retained lines have their expected lengths.

Revision 1 rows stay in the database. Sessions pinned to revision 1 and completions recorded against it stay valid. A new navigation or session sees revision 2. The Queen's Gambit `declined-development` ID now refers to the 19-ply Levenfish path in revision 2. Completions remain revision-bound, so revision 1 practice is not credited to the longer line.

London is unchanged at revision 3. The pinned catalogue has no London record past White's sixth move. The existing eight-move line already extends beyond it with owner-approved authored facts. Going further would need another authored-provenance decision or a different source; both are outside this change.

## Source records

Pinned dataset: [`lichess-org/chess-openings`](https://github.com/lichess-org/chess-openings) at `a6189a30dc273ccb21fc2536a9a2fefd5592a67a` (CC0). Row numbers count the header as row 1.

| Line | File, row | ECO | Exact name | Exact PGN |
| --- | --- | --- | --- | --- |
| `italian/2/pianissimo-main-line` | `c.tsv` 931 | C54 | Italian Game: Classical Variation, Giuoco Pianissimo, Main Line | `1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. c3 Nf6 5. d3 d6 6. O-O O-O 7. Re1 a6 8. Bb3 Ba7 9. h3` |
| `italian/2/two-knights-knorre` | `c.tsv` 1013 | C59 | Italian Game: Two Knights Defense, Knorre Variation | `1. e4 e5 2. Nf3 Nc6 3. Bc4 Nf6 4. Ng5 d5 5. exd5 Na5 6. Bb5+ c6 7. dxc6 bxc6 8. Be2 h6 9. Nf3 e4 10. Ne5 Bd6 11. d4 Qc7 12. Bd2` |
| `queens-gambit/2/declined-development` | `d.tsv` 403 | D40 | Queen's Gambit Declined: Semi-Tarrasch Defense, Levenfish Variation | `1. d4 d5 2. c4 e6 3. Nc3 Nf6 4. Nf3 c5 5. e3 Nc6 6. Bd3 Bd6 7. O-O O-O 8. Qe2 Qe7 9. dxc5 Bxc5 10. e4` |
| `queens-gambit/2/orthodox-classical` | `d.tsv` 563 | D69 | Queen's Gambit Declined: Orthodox Defense, Classical Variation | `1. d4 d5 2. c4 e6 3. Nc3 Nf6 4. Bg5 Be7 5. e3 O-O 6. Nf3 Nbd7 7. Rc1 c6 8. Bd3 dxc4 9. Bxc4 Nd5 10. Bxe7 Qxe7 11. O-O Nxc3 12. Rxc3 e5 13. dxe5 Nxe5 14. Nxe5 Qxe5` |
| `queens-gambit/2/accepted-classical` | `d.tsv` 288 | D28 | Queen's Gambit Accepted: Classical, Flohr Variation | `1. d4 d5 2. c4 dxc4 3. Nf3 Nf6 4. e3 e6 5. Bxc4 c5 6. O-O a6 7. Qe2 b5 8. Bb3 Nc6 9. Rd1 c4 10. Bc2 Nb4 11. Nc3 Nxc2 12. Qxc2 Bb7 13. d5 Qc7` |
| `caro-kann/2/classical-main-line` | `b.tsv` 371 | B19 | Caro-Kann Defense: Classical Variation, Lobron System | `1. e4 c6 2. d4 d5 3. Nd2 dxe4 4. Nxe4 Bf5 5. Ng3 Bg6 6. h4 h6 7. Nf3 Nd7 8. h5 Bh7 9. Bd3 Bxd3 10. Qxd3 e6 11. Bd2 Ngf6 12. O-O-O Be7` |
| `caro-kann/2/karpov-main-line` | `b.tsv` 358 | B17 | Caro-Kann Defense: Karpov Variation, Modern Main Line | `1. e4 c6 2. d4 d5 3. Nd2 dxe4 4. Nxe4 Nd7 5. Ng5 Ngf6 6. Bd3 e6 7. N1f3 Bd6 8. Qe2 h6 9. Ne4 Nxe4 10. Qxe4` |
| `french/2/classical-stahlberg` | `c.tsv` 157 | C14 | French Defense: Classical Variation, Stahlberg Variation | `1. e4 e6 2. d4 d5 3. Nc3 Nf6 4. Bg5 Be7 5. e5 Nfd7 6. Bxe7 Qxe7 7. f4 O-O 8. Nf3 c5 9. Qd2 Nc6 10. O-O-O c4` |
| `french/2/winawer-positional` | `c.tsv` 207 | C19 | French Defense: Winawer Variation, Positional Variation | `1. e4 e6 2. d4 d5 3. Nc3 Bb4 4. e5 c5 5. a3 Bxc3+ 6. bxc3 Ne7 7. a4 Nbc6 8. Nf3 Qa5 9. Bd2 Bd7` |

## Editorial choices

The lines were chosen among the deepest named records that extend each course. Mainstream main lines were preferred over sharp gambits and traps: for example, the Italian Möller/Therkatz-Herzog (27 plies), Evans Gambit and Giuoco Piano Krause records were not chosen. Each lesson stops where its named record stops, so the end of a line is a defined reference point rather than an editorial cut. Final explanations say where the named line ends and what the next middlegame task is. Some lines end on the automatic side's move, for example Karpov `10. Qxe4`; the trainer already supports this, as Queen's Gambit revision 1 did.

Every explanation describes one taught line. Other legal moves stay neutral, and the notes avoid engine evaluations and claims about move frequency.

## Verification (2026-10-15)

```bash
docker compose exec -T app vendor/bin/phpstan analyse --no-progress migrations/LessonData migrations/Support
docker compose exec -T app vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php migrations/LessonData --dry-run --using-cache=no
docker compose exec -T app vendor/bin/phpunit tests/Service/Lesson/FrozenLessonPathsTest.php
bash tests/import/migrations-test.sh lessons-test.php
bash tests/import/full-lessons-test.sh
```

- PHPStan and the formatter passed.
- `FrozenLessonPathsTest` replays all 18 frozen paths with the PHP adapter (20 tests, 1,887 assertions).
- A separate chess.js replay matched every UCI, SAN and canonical key for the 9 new paths (194 plies).
- The fixture lesson integration test passed 56 checks: 5 courses, 23 stored lines, 353 steps and 10 lesson migrations. It also checks the revision-2 line IDs and lengths and confirms that authored provenance still belongs only to the London migration.
- The full pinned-source result is recorded in [REVIEW_COORDINATION.md](../REVIEW_COORDINATION.md).

Offline fixtures in `backend/tests/Fixtures/Lessons/source/{b,c,d}.tsv` include the nine exact records above.
