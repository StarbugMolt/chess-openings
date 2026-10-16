# Caro-Kann beginner lesson

> **Revision 2 is current.** This note describes revision 1, which stays stored for pinned sessions and saved practice. Revision 2 adds complete named main lines from the pinned catalogue; see [NAMED_LINES_REVISION_2.md](NAMED_LINES_REVISION_2.md).

Lesson `caro-kann`, revision 1, is published by `Version20261015010300`. Its recommended playing colour is **Black**. White starts the game and supplies fixed automatic replies when the learner plays Black. The lesson selector still supports practising the other side.

The lesson teaches the purpose of `...c6` and `...d5`, a central pawn exchange, development and, in its longer line, king safety. It has two finite lines; it does not claim to cover every reply to the Caro-Kann or continue to the end of a game. Every move has an original coordinate hint and explanation, including the automatic side's moves.

| Line | Taught path | Black moves | Stop |
| --- | --- | --- | --- |
| `exchange-setup` — First steps: the Exchange setup | `1. e4 c6 2. d4 d5 3. exd5 cxd5 4. Bd3 Nc6 5. c3 Nf6` | 5 | Both black knights developed. The bishops and king still need attention. |
| `panov-development` — Next step: meet the Panov and castle | `1. e4 c6 2. d4 d5 3. exd5 cxd5 4. c4 Nc6 5. Nc3 Nf6 6. Nf3 e6 7. cxd5 exd5 8. Bb5 Bd6 9. Bg5 O-O` | 9 | Black has castled, developed both knights and one bishop. The bishop on c8 remains undeveloped. |

## Immutable source paths

Both lines are exact prefixes of named records in the existing pinned Lichess catalogue, commit `a6189a30dc273ccb21fc2536a9a2fefd5592a67a`. Source row numbers count the header as row 1. The complete records must be included in the offline lesson fixture; their final White moves are retained as source facts but are beyond these taught lines' stopping points.

| Source file and row | ECO | Exact source name | Exact complete source PGN |
| --- | --- | --- | --- |
| `b.tsv`, row 325 | B13 | Caro-Kann Defense: Exchange Variation, Rubinstein Variation | `1. e4 c6 2. d4 d5 3. exd5 cxd5 4. Bd3 Nc6 5. c3 Nf6 6. Bf4` |
| `b.tsv`, row 337 | B14 | Caro-Kann Defense: Panov Attack | `1. e4 c6 2. d4 d5 3. exd5 cxd5 4. c4 Nc6 5. Nc3 Nf6 6. Nf3 e6 7. cxd5 exd5 8. Bb5 Bd6 9. Bg5 O-O 10. O-O` |

Source: [pinned Lichess `b.tsv`](https://github.com/lichess-org/chess-openings/blob/a6189a30dc273ccb21fc2536a9a2fefd5592a67a/b.tsv). The move/name data is CC0; the instructional prose is original project content.

The migration freezes every taught UCI, SAN and canonical four-field position key. `LessonGraphV1::resolveImported` checks those facts against the ordered named source record in the pinned snapshot before any lesson rows are queued. It creates no chess positions, move edges or authored-provenance records. New lesson rows are inserted with explicit columns, and the current-revision pointer is published only after the revision, lines and steps have been inserted. This preserves the schema's publication foreign key.

## Editorial boundaries

The shorter Exchange line is first so a beginner can learn the initial pawn idea without first memorising a long variation. The longer Panov line introduces White's `c4` challenge, explains both subsequent pawn captures, distinguishes the bishop pin on c6 from the bishop pressure on f6, and explains castling as moving both king and rook. It acknowledges the remaining undeveloped bishop rather than calling this a completed setup.

Other legal moves remain outside the chosen finite line. They receive neutral feedback and cannot be treated as mistakes based on these source records. A later extension or correction must publish a new lesson revision.

## Verification

Verified on 2026-10-15:

```bash
docker compose exec -T --user www-data app php -l migrations/LessonData/Version20261015010300.php
docker compose exec -T --user www-data app vendor/bin/phpstan analyse migrations/LessonData/Version20261015010300.php migrations/Support --no-progress
docker compose exec -T --user www-data app vendor/bin/php-cs-fixer fix migrations/LessonData/Version20261015010300.php --dry-run --diff --using-cache=no
```

All three pass. An additional read-only PHP check resolves all 28 frozen taught plies against the two pinned named paths, independently replays every SAN with the PHP adapter, and confirms the migration queues 33 publication statements without executing them. A separate chess.js replay also matches every frozen UCI, SAN and canonical position key, including Black's castling move.

This focused authoring verification does not apply the migration to the shared development database. Main-session integration covers fresh bootstrap, browser play and completion persistence.
