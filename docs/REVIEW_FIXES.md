# Review resolution

This records the M3 implementation following [review.md](../review.md), completed on 2026-10-15. Its counts and remaining-milestone statements are historical. The original coordination, including superseded account-flow proposals, is preserved in [the M3 archive](archive/REVIEW_COORDINATION_M3_2026-10-15.md). Current work is coordinated in [REVIEW_COORDINATION.md](REVIEW_COORDINATION.md); see [M4_IMPLEMENTATION.md](M4_IMPLEMENTATION.md) and the README for later variation-training status and verification.

## Implemented fixes

| Review area | Result |
| --- | --- |
| Catalogue/navigation, U1–U9, U11–U17 | Name substring search; grouped named variations with recorded moves and lesson entry points; family shortcuts; direct home/learning links; descriptive titles, route focus/scroll, current-page states, normalised queries and retained results during loading. |
| Trainer/frontend, U10, U18–U23, F1–F7 | Readable contrast/text, keyboard controls/live feedback, reduced motion, persisted sound, board resync, promotion/alternative-line states, Black undo/restart, validated lesson/progress responses, separate lesson errors, race guards and LRU access. Password visibility/Retry-After are supported; duplicate password entry is deliberately omitted. Production notices derive from rendered dependencies. Remaining type-aware tooling is listed below. |
| Accounts, S1–S9, S11–S12 | Token-bound registration passwords, deferred/framed mail responses, email/network login limits, IPv6 /64 grouping, atomic expired-window reset, daily mail cap, private rotating logs, reset-token checks before hashing, UTC and central private-route matching. The account contract follows the owner decision recorded in the coordination file. |
| Importer, I1–I8 | Durable rollback hold with distinct successful `held` status, explicit reviewed resume, pre-lock source validation, bounded preparation waits, checked lock ownership, safe release, UTF-8/redacted diagnostics, per-file rollback validation and failed-check cleanup. |
| Migrations, M1–M8, M10–M11 | Frozen/versioned lesson facts and explicit column lists; exact pinned named-path resolution; complete-source bootstrap check; pending-schema guards; protected DBAL tables; nontransactional DDL; guarded migration entry points; current-revision FK; fewer isolation statements; direct DBAL dependency and lesson-support static analysis. |
| Operations, O1–O14 | Bounded DB waits, retained prior assets/cache headers, explicit production configuration and local-mail opt-in, dependency-install script/plugin restrictions, private rotated backups and restore test, activation time reserve, custom preflight, bounded independent logs/cache, security headers and CI/audit updates. Hosting-specific details remain unverified. |
| Specification/documentation | Route map and interaction/accessibility contract in [UX.md](UX.md); actual account/schema/API descriptions and current-vs-planned status corrected. |

## Deliberate remaining work

- A separate opening-detail route with board replay and move search remains an enhancement. Catalogue entries currently reveal recorded sequences inline and offer authored courses separately.
- Retention/purge policy, public privacy/legal information, export/deletion and device-wide remembered-login revocation remain owner/M6 decisions. Existing signed remember cookies are invalidated by password changes and cleared in the current browser on logout.
- Confirming an unsolicited registration email can activate the password attached to that unsolicited registration. The owner chose the standard token-only confirmation flow; the page/email tell recipients to ignore registrations they did not request. Re-registering cannot overwrite the password attached to another outstanding registration link.
- O2Switch runtime/SQL mode/timeouts, SMTP/local transport, HTTPS/proxy/compression framing, backup recovery/capacity, cron recipient/token-expiry monitoring and release activation still need deployed verification. Neither host job is installed.
- Importer optimisations and extra Git-blob proof, image digest pins, checkout-action upgrades, complete type-aware frontend linting and cross-ecosystem automated update grouping are not correctness prerequisites for these fixes. They remain explicit follow-ups.
- Variation Training and durable mastery/review remain M4/M5b. A saved finite-line completion does not imply mastery of every branch.

## Combined verification

`CHECK_REUSE_SERVICES=1 bash scripts/check.sh` exited **0** after the final account/frontend changes. This mode requires healthy running services and leaves their containers running; the normal command retains startup/provisioning. The browser runner creates its own application/frontend containers and disposable database.

| Check | Final result |
| --- | --- |
| PHP unit/controller tests | **111 tests / 1,053 assertions** |
| Frontend unit tests | **75 passed** |
| Desktop/mobile browser tests | **33 passed**, one intentional mobile mouse-drag skip |
| Disposable SQL/migration/catalogue/account/lesson suites | **18 / 81 / 108 / 93 / 53 checks** |
| Static analysis, formatting, lint and production build | Passed, including PHPStan level 8 over immutable lesson support |
| Apache/readiness/cache/routing, deploy/controller/install/prepare/build/configuration checks | Passed |
| Actual MariaDB backup and restore | Passed in an isolated disposable DB |
| `bash tests/import/migrations-test.sh pinned-lessons-test.php` | **31 checks**; eight schema migrations, complete pinned 3,865-record catalogue, six lesson migrations, idempotent repeat bootstrap and coherent grouped source identities |
| M3's `bash tests/import/full-lessons-test.sh` | **54 checks** against the complete pinned source, including authored provenance and revision preservation |
| Composer/npm audits | No advisories reported |
| Native T3 mobile preview | Descriptive titles/focus/scroll, normalised catalogue URL, five course cards, token-only verification and no horizontal overflow at 390 px |

The final guided curriculum has **five courses and seven current lines**: Italian Game, London System revision 3 through castling, Caro-Kann Defense, French Defense and Queen's Gambit. White/Black recommendations are authored metadata. Guided sessions pin lesson revisions and save idempotent owner-scoped completion; variation mode and mastery/review remain separate milestones.

The full source checksum is `32b6eadeecd0a9620100f19613b55595e96e5cb72d1addd8cbd49fa9f7b009e3` at immutable SHA `a6189a30dc273ccb21fc2536a9a2fefd5592a67a`. These are local results; remote CI and host deployment remain unverified.
