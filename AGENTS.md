# Agent Instructions

Read [README.md](README.md) for the product overview and **[docs/IMPLEMENTATION_PLAN.md](docs/IMPLEMENTATION_PLAN.md)** for the authoritative technical specification, proposed schema, importer design, milestones and acceptance tests.

## Work rules

1. This repository started as a **documentation-only/empty repository**. Do not assume Vue, Symfony, database migrations, tests or deployment already exist; inspect the current tree before editing.
2. Follow the accepted stack: **Vue 3 + TypeScript + Vite + Pinia + Vue Router + Chessground + chess.js** (frontend), **Symfony 7.4 LTS + Doctrine + MariaDB** (backend), and a **PHP** opening importer (preferred tested candidate `p-chess/chess`). The intended project licence is **GPLv3**.
3. Build only the two committed modes: **strict guided opening lines** and **variation training with supported theoretical branches**. Do not add Stockfish, arbitrary sandbox/off-book AI, ads, payment, third-party tracking or social features without an explicit design change.
4. Chessground is the UI, chess.js is the move validator, and a framework-agnostic trainer module governs allowed lesson moves. Treat legal-but-not-taught moves neutrally.
5. Import **named Lichess opening data locally** from an immutable pinned SHA, using **Symfony HttpClient + a PHP PGN/SAN chess-replay adapter + DBAL**. Maintain the canonical position/move graph and named source paths separately from curated curriculum and user progress. **No Node/TypeScript import script**, no dependency on Lichess's Opening Explorer API at runtime. Validate PHP canonical state against chess.js fixtures.
6. **Approved architecture:** three independent layers — imported Lichess chess graph/named opening paths, **Doctrine-migration-managed curated lessons**, and private account/progress data. No YAML lesson source, special YAML synchroniser or auto-generated lesson content. Validate authored line steps against the imported graph; increment lesson revisions intentionally. Set up and test **schema migrations -> first catalogue import -> lesson data migrations** on a fresh DB.
7. Before locking permanent importer/database migrations, review the **remaining implementation details** (canonical position key, concrete tables/indexes, import/update lifecycle) in sections 6–7 with the repository owner. The three-layer architecture and Doctrine migration-based lesson authoring are *already accepted*; a fixture-based importer spike is welcome first.
8. Use strict TypeScript, typed request/response shapes, explicit relational constraints, deterministic imports and automated tests. Changes should be small, runnable and documented.
9. **The backend owns the entire weekly Lichess refresh.** Implement Symfony Console `app:openings:sync` with PHP HttpClient download of five TSVs from one immutable SHA, PHP legal move replay, DBAL staged import, locking, safe activation/rollback and status logging. Run it directly via **weekly O2Switch/cPanel PHP CLI cron** (Monday 05:17 host timezone after verification). No GitHub Actions scheduled importer or Node runtime for importing. Vue/Pinia/API respond to `catalogueRevision` at next navigation/new session, never mid-lesson. Weekly import changes source catalogue only, not lessons or progress. See [docs/OPENING_UPDATES.md](docs/OPENING_UPDATES.md) and [ops/cron/opening-catalogue.crontab.example](ops/cron/opening-catalogue.crontab.example).
10. Build privacy/security into accounts: minimal user data, session-cookie security, CSRF, authenticated progress endpoints, export/deletion, privacy notices. Do not ship an invented “GDPR compliant” label.
11. Never claim a milestone is implemented just because it is documented. When completing a phase, update the README setup instructions and record exact commands actually verified.

## First useful increment

Implement **M0** (workspace, application scaffolds, local MariaDB, CI, real development commands) then **M1** (Chessground + chess.js working board and rule tests). Next create an **M2 Symfony/PHP importer spike** with small, reproducible TSV/PGN fixtures, testing the PHP chess replayer and transpositions against frontend chess.js. Build and validate the full `app:openings:sync` before registering the actual cPanel weekly cron; **do not install a scheduled task whose backend command does not exist yet**.

See the detailed Definition of Done and test matrix in the plan.
