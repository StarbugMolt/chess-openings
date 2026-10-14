# Agent Instructions

Read [README.md](README.md) for the product overview and **[docs/IMPLEMENTATION_PLAN.md](docs/IMPLEMENTATION_PLAN.md)** for the authoritative technical specification, proposed schema, importer design, milestones and acceptance tests.

## Work rules

1. This repository started as a **documentation-only/empty repository**. Do not assume Vue, Symfony, database migrations, tests or deployment already exist; inspect the current tree before editing.
2. Follow the accepted stack: **Vue 3 + TypeScript + Vite + Pinia + Vue Router + Chessground + chess.js**, **Symfony 7.4 LTS + Doctrine + MariaDB**. The intended code licence is **GPLv3**.
3. Build only the two committed modes: **strict guided opening lines** and **variation training with supported theoretical branches**. Do not add Stockfish, arbitrary sandbox/off-book AI, ads, payment, third-party tracking or social features without an explicit design change.
4. Chessground is the UI, chess.js is the move validator, and a framework-agnostic trainer module governs allowed lesson moves. Treat legal-but-not-taught moves neutrally.
5. Import **named Lichess opening data locally**, from a pinned source version. Keep the canonical position/move graph separate from source opening names, authored curriculum and user progress. Never depend on the Lichess API at runtime.
6. **Approved architecture:** imported position/move graph plus preserved named opening paths, **curated lesson layer**, and **private user/progress layer** must remain separate. Author lesson definitions as **version-controlled YAML under `lessons/`** (the canonical source), validate and sync them to MariaDB with a separate idempotent CLI loader. Never treat Lichess names as complete lessons or make database-only authoring the default.
7. Before locking permanent importer/database migrations, review the **remaining implementation details** (canonical position key, concrete tables/indexes, import/update lifecycle) in sections 6–7 with the repository owner. The three-layer architecture and YAML lesson source are *already accepted*; a fixture-based importer spike is welcome first.
8. Use strict TypeScript, typed request/response shapes, explicit relational constraints, deterministic imports and automated tests. Changes should be small, runnable and documented.
9. Plan for PHP-only production hosting: compile the Vue frontend and run offline import tooling in local development/CI.
10. Build privacy/security into accounts: minimal user data, session-cookie security, CSRF, authenticated progress endpoints, export/deletion, privacy notices. Do not ship an invented “GDPR compliant” label.
11. Never claim a milestone is implemented just because it is documented. When completing a phase, update the README setup instructions and record exact commands actually verified.

## First useful increment

Implement **M0** (workspace, application scaffolds, local MariaDB, CI, real development commands) then **M1** (Chessground + chess.js working board and rule tests). Next create an **M2 importer spike** with small, reproducible TSV fixtures to validate deduplication and transpositions before the full database migration.

See the detailed Definition of Done and test matrix in the plan.
