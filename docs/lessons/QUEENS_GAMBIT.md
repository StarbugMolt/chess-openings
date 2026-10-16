# Queen's Gambit beginner lesson

> **Revision 2 is current.** This note describes revision 1, which stays stored for pinned sessions and saved practice. Revision 2 adds complete named main lines from the pinned catalogue; see [NAMED_LINES_REVISION_2.md](NAMED_LINES_REVISION_2.md).

This hand-authored lesson introduces the Queen's Gambit as White through seven moves and both sides castling. Its stable lesson ID is `queens-gambit`, revision `1`, recommended colour `w`, sort order `4`; its one line is `declined-development` (14 plies).

The exact path is:

```pgn
1. d4 d5 2. c4 e6 3. Nc3 Nf6 4. Nf3 c5
5. e3 Nc6 6. Bd3 Bd6 7. O-O O-O
```

The lesson explains the offer on c4, Black declining it with `...e6`, development towards the centre and castling. After `...c5`, opposing pawns can capture each other: this is central tension. Neither side is obliged to capture immediately. This particular line keeps the tension while the players develop their pieces.

The stopping point is deliberate: both kings have castled, both knights have developed and each side has brought out its king's bishop. The queen's bishops remain at home. There are further development and central pawn decisions to make; this is an opening foundation, not a complete game or a repertoire against every Black reply. The trainer supplies the other side's fixed moves automatically. Hints name the piece and its two squares, and the original explanations cover both colours.

## Immutable source provenance

All 14 chess edges are the complete path of one existing named Lichess record. No positions or moves are added to the graph by this migration.

| Field | Value |
| --- | --- |
| Dataset | `lichess-org/chess-openings` |
| Pinned SHA | `a6189a30dc273ccb21fc2536a9a2fefd5592a67a` |
| File | `d.tsv` |
| Physical TSV row (header is row 1) | `405` |
| ECO | `D40` |
| Full source name | `Queen's Gambit Declined: Semi-Tarrasch Defense, Symmetrical Variation` |
| Exact original PGN | `1. d4 d5 2. c4 e6 3. Nc3 Nf6 4. Nf3 c5 5. e3 Nc6 6. Bd3 Bd6 7. O-O O-O` |
| Lesson data migration | `backend/migrations/LessonData/Version20261015010500.php` |

The [pinned TSV](https://github.com/lichess-org/chess-openings/blob/a6189a30dc273ccb21fc2536a9a2fefd5592a67a/d.tsv#L405) supplies the chess path, not the teaching prose. The migration freezes UCI, SAN and canonical four-field before/after position keys for every step. `LessonGraphV1::resolveImported()` verifies these facts against the ordered steps of that exact named path in the pinned imported snapshot, then returns its existing graph IDs. The migration uses no live application chess adapter or source-name inference.

Schema preparation and the pinned catalogue import must precede lesson publication. The lesson is inserted with an unpublished `NULL` pointer, followed by its immutable revision, line and steps; only then is revision `1` published in the same transaction. Future lesson edits require a new migration and revision; weekly source refreshes do not rewrite this lesson or its completion history.

## Focused verification

Verified locally on 2026-10-15:

```sh
docker compose exec -T --user www-data app php -l migrations/LessonData/Version20261015010500.php
docker compose exec -T --user www-data app vendor/bin/phpstan analyse --no-progress migrations/LessonData/Version20261015010500.php migrations/Support/LessonGraphV1.php
docker compose exec -T --user www-data app vendor/bin/php-cs-fixer fix migrations/LessonData/Version20261015010500.php --dry-run --diff
```

All passed. A separate read-only PHP check independently replayed the frozen SAN with the PHP chess adapter, compared all 14 UCI/canonical position transitions, and called the migration's `up()` to verify the pinned named-path lookup and its 18 queued curriculum statements. It did not execute those statements or modify the shared development database. Fresh-database publication and full trainer tests belong to the combined curriculum integration check.
