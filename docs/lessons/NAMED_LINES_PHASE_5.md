# Phase 5: nine more courses from complete named lines

The owner asked for more training courses after the deployed `27dfeda` release. Like the [earlier new courses](NAMED_LINES_NEW_COURSES.md), each course teaches only **complete named records** from the pinned Lichess catalogue, and every line gives the learner at least nine moves. Lines run from 17 to 26 plies.

Every frozen UCI, SAN and canonical position key is checked against the exact pinned record by `LessonGraphV1::resolveImported()` before lesson rows are queued. **No chess positions, moves or authored-provenance rows are added.** Hints are generated from the position: piece, squares, capture, check and castling rook. Explanations, titles and introductions are original project prose with French and Spanish translations in `frontend/src/i18n/lessons.json`.

## Courses

| Course | ID | Colour | Sort | Migration | Lines |
| --- | --- | --- | --- | --- | --- |
| Scotch Game | `scotch` | White | 10 | `Version20261016020000` | 2 |
| Four Knights Game | `four-knights` | White | 11 | `Version20261016020100` | 3 |
| Evans Gambit | `evans-gambit` | White | 12 | `Version20261016020200` | 2 |
| Petrov's Defense | `petrov` | Black | 13 | `Version20261016020300` | 2 |
| Alekhine Defense | `alekhine` | Black | 14 | `Version20261016020400` | 2 |
| Nimzo-Indian Defense | `nimzo-indian` | Black | 15 | `Version20261016020500` | 2 |
| Grünfeld Defense | `grunfeld` | Black | 16 | `Version20261016020600` | 2 |
| Benoni Defense | `benoni` | Black | 17 | `Version20261016020700` | 2 |
| Tarrasch Defense | `tarrasch` | Black | 18 | `Version20261016020800` | 2 |

Each migration refuses to run if its course ID already exists. It inserts the course with an unpublished pointer, then revision 1, lines and steps, and publishes the pointer last in one transaction, the same pattern as the earlier new courses. The migration range `Version20261016020000`–`029999` deliberately avoids the earlier, unpublished phase-4 drafting range.

## Source records

Pinned dataset: [`lichess-org/chess-openings`](https://github.com/lichess-org/chess-openings) at `a6189a30dc273ccb21fc2536a9a2fefd5592a67a` (CC0). Row numbers count the header as row 1. The exact PGNs are in each migration's `*_SOURCE` constants, and the rows are appended unchanged to `backend/tests/Fixtures/Lessons/source/*.tsv`.

| Line | File, row | ECO | Exact name | Plies | Learner moves |
| --- | --- | --- | --- | --- | --- |
| `scotch/1/classical-gottschall` | `c.tsv` 758 | C45 | Scotch Game: Gottschall Variation | 24 | 12 |
| `scotch/1/scotch-gambit-max-lange` | `c.tsv` 971 | C56 | Italian Game: Scotch Gambit, Max Lange Attack, Long Variation | 21 | 11 |
| `four-knights/1/scotch-four-knights` | `c.tsv` 794 | C47 | Four Knights Game: Scotch Variation Accepted, Main Line | 17 | 9 |
| `four-knights/1/spanish-symmetrical` | `c.tsv` 823 | C49 | Four Knights Game: Spanish Variation, Symmetrical Variation | 20 | 10 |
| `four-knights/1/rubinstein-maroczy` | `c.tsv` 812 | C48 | Four Knights Game: Spanish Variation, Rubinstein Variation, Maróczy Variation | 18 | 9 |
| `evans-gambit/1/accepted-steinitz` | `c.tsv` 877 | C51 | Italian Game: Evans Gambit, Steinitz Variation | 21 | 11 |
| `evans-gambit/1/accepted-levenfish` | `c.tsv` 892 | C52 | Italian Game: Evans Gambit, Levenfish Variation | 21 | 11 |
| `petrov/1/classical-maroczy` | `c.tsv` 658 | C42 | Petrov's Defense: Classical Attack, Maróczy Variation | 20 | 10 |
| `petrov/1/classical-marshall` | `c.tsv` 657 | C42 | Petrov's Defense: Classical Attack, Marshall Variation, Chinese Gambit | 18 | 9 |
| `alekhine/1/four-pawns-tartakower` | `b.tsv` 171 | B03 | Alekhine Defense: Four Pawns Attack, Tartakower Variation | 22 | 11 |
| `alekhine/1/exchange-karpov` | `b.tsv` 163 | B03 | Alekhine Defense: Exchange Variation, Karpov Variation | 21 | 10 |
| `nimzo-indian/1/rubinstein-bernstein` | `e.tsv` 241 | E59 | Nimzo-Indian Defense: Normal Variation, Bernstein Defense | 21 | 10 |
| `nimzo-indian/1/samisch-capablanca` | `e.tsv` 164 | E29 | Nimzo-Indian Defense: Sämisch Variation, Capablanca Variation | 18 | 9 |
| `grunfeld/1/exchange-spassky` | `d.tsv` 619 | D89 | Grünfeld Defense: Exchange Variation, Spassky Variation | 26 | 13 |
| `grunfeld/1/russian-keres` | `d.tsv` 642 | D98 | Grünfeld Defense: Russian Variation, Keres Variation | 21 | 10 |
| `benoni/1/classical-tal-line` | `a.tsv` 751 | A77 | Benoni Defense: Classical Variation, Czerniak Defense, Tal Line | 20 | 10 |
| `benoni/1/four-pawns-main-line` | `a.tsv` 738 | A69 | Benoni Defense: Four Pawns Attack, Main Line | 20 | 10 |
| `tarrasch/1/classical-main-line` | `d.tsv` 354 | D34 | Tarrasch Defense: Classical Variation, Main Line | 22 | 11 |
| `tarrasch/1/classical-advance` | `d.tsv` 348 | D34 | Tarrasch Defense: Classical Variation, Advance Variation | 18 | 9 |

## Choice of lines

Lines were chosen from records long enough for at least nine learner moves (17 plies for White, 18 for Black), preferring main or classical systems over traps and named mates. Shared opening moves inside a course use identical explanations, and the move where a line leaves its sibling says so. Where an English explanation already existed in an earlier course (for example 1. e4 or 1. d4), it is reused verbatim with its existing translations. French and Spanish descriptions keep the English record name after "Source"; older courses translate it.

## Verification

See the phase-5 section of [REVIEW_COORDINATION.md](../REVIEW_COORDINATION.md) for the exact commands and results.
