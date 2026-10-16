# French Defense beginner lesson

> **Revision 2 is current.** This note describes revision 1, which stays stored for pinned sessions and saved practice. Revision 2 adds complete named main lines from the pinned catalogue; see [NAMED_LINES_REVISION_2.md](NAMED_LINES_REVISION_2.md).

The French lesson teaches **seven Black moves**, with White's replies supplied automatically. Its default side is Black. It introduces one practical idea: when White advances to e5, challenge the supporting pawn on d4 with the c-pawn and develop pieces towards that target.

The taught line is:

```pgn
1. e4 e6 2. d4 d5 3. e5 c5 4. c3 Nc6 5. Nf3 Qb6
6. Bd3 cxd4 7. cxd4 Bd7
```

This is a finite, 14-ply introduction, not a complete French repertoire. White can choose other moves in a real game. The lesson stops after Black develops the c8 bishop; Black has not yet completed development or castled. Its hints explain the move on the board, and its original explanations cover both colours so the opposite side remains meaningful to practise.

The early queen move has a specific purpose: attack b2 now and prepare pressure against d4 after the c5-pawn is exchanged. Its explanation makes clear that the c5-pawn initially blocks that diagonal, without teaching early queen moves as a general beginner rule. Ending after `7...Bd7` demonstrates development alongside central pressure and avoids extending into the source line's later gambit decisions.

## Imported provenance

| Field | Value |
| --- | --- |
| Repository | [lichess-org/chess-openings](https://github.com/lichess-org/chess-openings) |
| Immutable SHA | `a6189a30dc273ccb21fc2536a9a2fefd5592a67a` |
| Source file | [`c.tsv`](https://github.com/lichess-org/chess-openings/blob/a6189a30dc273ccb21fc2536a9a2fefd5592a67a/c.tsv#L64) |
| Physical row, including header | `64` |
| ECO | `C02` |
| Exact name | French Defense: Advance Variation, Milner-Barry Gambit, Main Line |
| Exact source PGN | `1. e4 e6 2. d4 d5 3. e5 c5 4. c3 Nc6 5. Nf3 Qb6 6. Bd3 cxd4 7. cxd4 Bd7 8. O-O` |
| Taught prefix | First 14 of the source's 15 plies |
| Lesson identity | `french`, revision `1`, line `advance-centre` |
| Recommended colour / ordering | Black (`b`), course sort order `3` |
| Data migration | `backend/migrations/LessonData/Version20261015010400.php` |

The migration freezes UCI, SAN and both canonical position keys for every taught step. `LessonGraphV1::resolveImported()` verifies this prefix against the complete named path in the pinned imported batch before publishing the lesson. It creates no chess graph facts and adds no authored graph provenance. Lesson prose is original; it is not copied from a course or generated from an upstream refresh.

The lesson is published only after its revision, line and all steps have been queued for insertion. Existing lesson revisions and user completions remain untouched. Source-catalogue refreshes do not modify this curriculum.

## Verification

The migration was prepared using read-only queries against the existing pinned local catalogue. Verified locally on 2026-10-15:

- PHP lint passed for `Version20261015010400.php`.
- PHPStan level 8 passed for this migration and `migrations/Support/LessonGraphV1.php`.
- PHP CS Fixer dry-run reported no changes for this migration.
- A read-only PHP check resolved all 14 edges against the exact pinned named source. Legal PHP replay matched every frozen UCI, SAN and canonical position key.
- Calling the migration's `up()` method without executing its SQL queued 18 statements, including 14 steps. Every parameter count matched and the publication-pointer update was last.

The focused commands were:

```bash
docker compose exec -T app php -l migrations/LessonData/Version20261015010400.php
docker compose exec -T app vendor/bin/phpstan analyse --no-progress \
  migrations/LessonData/Version20261015010400.php migrations/Support/LessonGraphV1.php
docker compose exec -T app vendor/bin/php-cs-fixer fix \
  migrations/LessonData/Version20261015010400.php --dry-run --diff
```

No shared development-database writes were performed for these checks. Main-session integration checks cover fresh schema/import/lesson preparation, API defaults and frontend training; those results are recorded with the integrated lesson work rather than claimed here before they run.
