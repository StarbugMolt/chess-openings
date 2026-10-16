# Replay fixtures

`source/a.tsv` through `e.tsv` contain **two unchanged parsed records per upstream file** from [lichess-org/chess-openings at a6189a30dc273ccb21fc2536a9a2fefd5592a67a](https://github.com/lichess-org/chess-openings/tree/a6189a30dc273ccb21fc2536a9a2fefd5592a67a). TSV quoting was re-encoded with PHP `fputcsv`; ECO, name and PGN values are unchanged. The dataset is CC0-1.0. `provenance.json` records physical upstream row numbers, full upstream file hashes and local fixture hashes. Tests verify local hashes so fixture changes require an intentional provenance update.

`golden.json` contains these ten source lines plus fourteen authored minimal rules cases (GPL-3.0-only). Expected UCI, canonical SAN, full FEN and four-field keys were calculated with chess.js 1.4.0, saved once and checked against the separate PHP p-chess/chess 1.2.0 adapter. Both test suites consume the saved expected states; neither suite regenerates its expectations. Source-fixture golden cases retain their original file/row/SHA. Authored cases cover transpositions, both castling sides, file/rank disambiguation, underpromotion, promotion with check, checkmate, legal/phantom/pinned en-passant, side to move, castling rights and move clocks.

The en-passant field uses the **legal capture** convention: `e3` after the initial `e4` is removed, `d6` after `e4 a6 e5 d5` is kept, and `d6` in `k3r3/8/8/3pP3/8/8/8/4K3 w - d6 0 1` is removed because `exd6` exposes the king. Full FEN retains the two clocks; graph identity excludes them. The two d-pawn move orders converge on the same key while retaining distinct paths. No fixture is a curated lesson.

Run both suites through `bash scripts/check.sh`, or individually:

```bash
docker compose exec -T --user www-data app vendor/bin/phpunit tests/Service/OpeningImport
docker compose run --rm --no-deps frontend npm test
docker compose exec -T --user www-data app bin/console app:openings:validate --source-directory=tests/Fixtures/Openings/source
```

These checks are offline. The separate full-dataset measurement in [the review package](../../../../docs/IMPORTER_REVIEW.md) downloads using Symfony HttpClient and replays using PHP only.
