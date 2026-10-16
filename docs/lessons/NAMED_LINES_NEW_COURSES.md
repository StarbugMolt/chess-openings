# New courses from complete named lines

After revision 2 deepened the first four courses, the owner asked for more openings built the same way. Each new course teaches only **complete named records** from the pinned Lichess catalogue. Every lesson ends where its named line ends, at 17 to 36 plies.

As in revision 2, every frozen UCI, SAN and canonical position key is checked against the exact pinned record by `LessonGraphV1::resolveImported()` before lesson rows are queued. **No chess positions, moves or authored-provenance rows are added.** Hints are generated from the position: piece, squares, capture, check and castling rook. Explanations, titles and introductions are original project prose.

## Courses

| Course | ID | Colour | Sort | Migration | Lines |
| --- | --- | --- | --- | --- | --- |
| Ruy Lopez | `ruy-lopez` | White | 5 | `Version20261015011000` | 3 |
| Catalan Opening | `catalan` | White | 6 | `Version20261015011100` | 2 |
| Sicilian Defense | `sicilian` | Black | 7 | `Version20261015011200` | 3 |
| King's Indian Defense | `kings-indian` | Black | 8 | `Version20261015011300` | 2 |
| Slav Defense | `slav` | Black | 9 | `Version20261015011400` | 2 |

Each migration refuses to run if its course ID already exists. It inserts the course with an unpublished pointer, then revision 1, lines and steps. It publishes the pointer last, in one transaction, the same pattern as the Caro-Kann and French revision 1 migrations.

## Source records

Pinned dataset: [`lichess-org/chess-openings`](https://github.com/lichess-org/chess-openings) at `a6189a30dc273ccb21fc2536a9a2fefd5592a67a` (CC0). Row numbers count the header as row 1. The exact PGNs are in each migration's `*_SOURCE` constants.

| Line | File, row | ECO | Exact name | Plies |
| --- | --- | --- | --- | --- |
| `ruy-lopez/1/chigorin-main-line` | `c.tsv` 1248 | C97 | Ruy Lopez: Closed, Chigorin, Yugoslav System | 28 |
| `ruy-lopez/1/breyer-defense` | `c.tsv` 1237 | C95 | Ruy Lopez: Closed, Breyer Defense | 24 |
| `ruy-lopez/1/marshall-attack` | `c.tsv` 1214 | C89 | Ruy Lopez: Marshall Attack, Main Line, Spassky Variation | 36 |
| `catalan/1/closed-main-line` | `e.tsv` 35 | E09 | Catalan Opening: Closed, Main Line | 23 |
| `catalan/1/closed-sokolsky` | `e.tsv` 36 | E09 | Catalan Opening: Closed, Sokolsky Variation | 20 |
| `sicilian/1/najdorf-main-line` | `b.tsv` 782 | B99 | Sicilian Defense: Najdorf Variation, Main Line | 18 |
| `sicilian/1/dragon-yugoslav` | `b.tsv` 722 | B79 | Sicilian Defense: Dragon Variation, Yugoslav Attack, Soltis Variation | 24 |
| `sicilian/1/sveshnikov` | `b.tsv` 561 | B33 | Sicilian Defense: Lasker-Pelikan Variation, Sveshnikov Variation | 20 |
| `kings-indian/1/classical-main-line` | `e.tsv` 365 | E98 | King's Indian Defense: Orthodox Variation, Classical System, Kozul Gambit | 27 |
| `kings-indian/1/fianchetto-panno` | `e.tsv` 271 | E63 | King's Indian Defense: Fianchetto Variation, Panno Variation, Donner Line | 26 |
| `slav/1/czech-main-line` | `d.tsv` 226 | D19 | Slav Defense: Czech Variation, Classical System, Main Line | 17 |
| `slav/1/semi-slav-meran` | `d.tsv` 472 | D49 | Semi-Slav Defense: Meran Variation, Rellstab Attack | 29 |

## Editorial choices

- Lines were chosen among the deepest named records of popular openings. Mainstream theory was preferred over traps and obscure gambits; for example, the Scotch Game's only deep records are a trap (Sea-Cadet Mate) and the Gottschall Variation, so no Scotch course was added.
- Two chosen records carry gambit names but are main theory. The Marshall Attack is Black's pawn sacrifice met by White's main line. The record named "Kozul Gambit" is the Classical King's Indian race in which White's `14. c5` queenside break meets Black's kingside pawn storm. The notes present both as plans, not tricks.
- The Slav course includes the Semi-Slav Meran as a second line, because both start with `...c6` against the Queen's Gambit.
- Several lines end on the automatic side's move, for example Czech Slav `9. Qe2` and Meran `15. Bg5`. The final explanation names the end of the line and the next middlegame task.
- Opening Explorer statistics, engine evaluations and claims about move popularity are deliberately absent.

## Verification

See the phase-2 entries in [REVIEW_COORDINATION.md](../REVIEW_COORDINATION.md) for the commands run and results. The nine revision-2 records and these new records (except the Najdorf Main Line, which was already present as a quoted importer-spike row) are in `backend/tests/Fixtures/Lessons/source/*.tsv`.
