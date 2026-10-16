# Authored continuations beyond the naming catalogue

**Owner approved this design and both provenance tables and authorised the full London lesson on 2026-10-15. No further approval is required for this increment.**

The owner wants a beginner London lesson that develops a complete setup, rather than stopping at the short imported name record. The imported Lichess London records currently stop no later than White's sixth move. Existing lesson revision 1 was a four-move introduction against a kingside fianchetto; revision 2 changes the introductory response to ...d5/...e6/...Nf6 but is deliberately short. Neither should be presented as a complete London course.

## Concrete proposed next revision

Publish London revision 3, retaining revisions 1 and 2 for pinned sessions and saved progress:

`1. d4 d5 2. Nf3 Nf6 3. Bf4 e6 4. e3 c5 5. c3 Nc6 6. Nbd2 Be7 7. Bd3 O-O 8. O-O`

White learns the central pawn, knight development, bringing the dark-squared bishop out before e3, supporting the centre with e3/c3, developing the remaining knight/bishop and castling. Black's replies occupy/challenge the centre, develop pieces and castle. This is one deliberately chosen theoretical teaching line, not a universal response or an engine judgement. Other replies need separately authored lessons/branches. All explanations are original, and hints identify the piece/destination and explain the move. Castling is explained as moving the king while the rook follows automatically.

## Provenance proposal

The existing canonical keys and imported seven-table architecture remain unchanged. Add the two small provenance tables in [CURRICULUM_SCHEMA_PROPOSAL.sql](CURRICULUM_SCHEMA_PROPOSAL.sql), managed through a new schema migration only after owner review:

- `authored_chess_position`: IDs of shared position facts first introduced by an authored migration, with its immutable migration class reference and UTC timestamp.
- `authored_chess_move`: IDs of shared move facts first introduced by an authored migration, with the same provenance.

The entire authored path is legally checked with the PHP adapter and independently with chess.js before publication. The expected SAN, UCI and canonical positions are frozen in migration-owned `migrations/Support/Paths20261015.php`; historical migrations use immutable `LessonGraphV1` support instead of live application replay code. The explicit Doctrine lesson data migration resolves these frozen facts by canonical position/UCI, verifies existing destinations/SAN and inserts only missing facts, recording their authorship. It writes immutable lesson revision 3, finite steps/hints/explanations and deliberately switches the current lesson pointer. One transaction and the shared database operation lock cover the whole authored operation; any conflict or FK failure rolls everything back. No Node importer, YAML source, automatic course generation or runtime off-book opponent is introduced.

Later Lichess sync may reuse the same facts if upstream eventually contains them; it cannot change their destinations, erase authorship, overwrite lessons or change private progress. New fact provenance does not require retaining an entire source snapshot indefinitely. Append-only graph retention and explicit restricted FKs remain in force. ORM schema tools exclude the two new DBAL provenance tables, as they already do for imported facts and lesson tables.

## Revision and acceptance policy

Old sessions retain the exact graph/revision with which they began; old-revision completion is accepted against that retained immutable path. A new navigation/session sees revision 3. Existing practice is preserved under its original revision and does not automatically mark the changed line complete. Completion is a record of practising the selected finite line, not a claim of mastery over every London branch.

Required verification: PHP/chess.js agree on every new canonical state including castling; fresh schema -> pinned fixture source -> authored migration succeeds; replay/conflict failure leaves no partial authored facts; repeated bootstrap adds nothing; future source sync preserves provenance/content/progress; mobile tap/desktop drag complete the line with automatic replies; on-board hints guide source then destination; authenticated completion validates exact path/revision and retries are idempotent.

This is the review required by plan section 7.2: “Supporting independently validated authored extensions requires a reviewed way to add shared chess facts with separate provenance”. It is a new authored-provenance decision, not a reopening of the already approved importer keys/tables/update lifecycle.

## Implemented curriculum and migration rules

The owner subsequently requested more lessons, especially Black defences. There are now five courses with seven currently published lines: Italian Game (two short White lines), London System (eight White moves), Caro-Kann Defense (five- and nine-move Black lines), French Defense (seven Black moves), and Queen's Gambit (seven White moves). The new Caro-Kann, French and Queen's Gambit paths are frozen prefixes of exact named records at the pinned SHA; they add no authored graph facts. Their original teaching notes and precise source records are described in [lessons/CARO_KANN.md](lessons/CARO_KANN.md), [lessons/FRENCH.md](lessons/FRENCH.md), and [lessons/QUEENS_GAMBIT.md](lessons/QUEENS_GAMBIT.md). Counting both retained London revisions gives nine stored lines and 101 steps. Current courses have seven playable lines (two Italian, one London, two Caro-Kann, one French, one Queen's Gambit).

Every lesson revision records a recommended playing colour; the lesson list labels it and lets learners filter White openings or Black defences. Starting Caro-Kann/French selects Black, rotates the board and plays White's first move automatically. Either colour remains available in the lesson options.

Historical migration support and facts are immutable after publication; future changes add new versions. Every data INSERT names its columns. Lesson-table schema changes must preserve these historical column names and permit omitted new columns with a default or NULL; incompatible changes require an explicit bridge rather than breaking fresh preparation. Imported migrations require the exact pinned named-path prefix, including canonical transitions, instead of accepting arbitrary edges from the cumulative graph. Fresh preparation therefore retains the pinned bootstrap requirement. Schema `00401` permits a NULL unpublished pointer while the first revision is created, then a composite FK guarantees every published pointer targets a real revision. Only the completed migration transaction publishes it.

Public `/api/courses` supplies titles, introductions, recommended sides and line counts without creating a session. Authenticated `/api/lessons/:id` captures the full immutable revision and catalogue revision plus private progress; `/api/me/completions` validates the exact retained path with session ownership and CSRF. This is the implemented M3 contract. Completing a line records practice; variation training, mastery scoring, export/deletion and launch legal pages remain later milestones.

## Revision 2: complete named lines (2026-10-15)

The owner asked for longer lessons taken from concrete lines in official opening data. Italian, Queen's Gambit, Caro-Kann and French revision 2 (`Version20261015010600`–`010900`) add nine complete named Lichess records at the pinned SHA, from 17 to 28 plies. They use the same imported-prefix validation as revision 1 and add no authored graph facts. The courses now store 23 lines and 353 steps across all revisions; the current revisions offer 15 playable lines. London stays at revision 3 because the catalogue has no deeper London record. Details, sources and verification are in [lessons/NAMED_LINES_REVISION_2.md](lessons/NAMED_LINES_REVISION_2.md).

## New courses from complete named lines (2026-10-15)

Five new courses (`Version20261015011000`–`011400`) teach twelve complete named records at the pinned SHA, from 17 to 36 plies. Ruy Lopez and Catalan are White courses; Sicilian, King's Indian and Slav (with the Semi-Slav Meran) are Black defences. They use the same imported-path validation and add no authored graph facts. Bootstrap now publishes 10 courses, 35 stored lines and 645 steps; the current revisions offer 27 playable lines. Details are in [lessons/NAMED_LINES_NEW_COURSES.md](lessons/NAMED_LINES_NEW_COURSES.md).

## Revision 3: no short lines (2026-10-16)

The owner asked that the openings have enough moves. Six current lines gave the learner fewer than nine moves, and Italian, Caro-Kann and French opened on them by default. `Version20261015011500`–`011800` publish Italian, Caro-Kann and French revision 3 and Slav revision 2. Every current line except London is now a complete pinned named record with at least nine learner moves, and each course opens on a deep main line. The short introductions remain in retained earlier revisions with their saved practice. London stays at eight White moves because the pinned catalogue has no deeper London record; going further would need a new authored-provenance decision. The courses store 46 lines and 880 steps; 25 lines are current. The lesson integration test now fails if any non-London current line drops below nine learner moves. Details are in [lessons/NAMED_LINES_REVISION_3.md](lessons/NAMED_LINES_REVISION_3.md).

## Phase 5: nine more courses (2026-10-16)

The owner asked for more training courses. Nine new courses (`Version20261016020000`–`020800`) teach nineteen complete named records at the pinned SHA, from 17 to 26 plies, each giving the learner at least nine moves. Scotch Game, Four Knights Game and Evans Gambit are White courses; Petrov's Defense, Alekhine Defense, Nimzo-Indian Defense, Grünfeld Defense, Benoni Defense and Tarrasch Defense are Black defences. They use the same imported-path validation and add no authored graph facts. Bootstrap now publishes 19 courses, 65 stored lines and 1,269 steps; the current curriculum has 44 lines. See [NAMED_LINES_PHASE_5.md](lessons/NAMED_LINES_PHASE_5.md).

