# Admin area — design for owner review

**Status: owner approved this design and its exact SQL on 2026-10-16 ("approve as proposed": last sign-in updates on both password sign-in and remember-me restore); do not request this approval again. Backend, UI, privacy wording and verification are implemented. See §9 and §10.**

On 2026-10-16 the owner asked for an admin backend to administer users and view usage statistics, and chose:

- **Statistics:** existing data, plus recording each account's **last sign-in**.
- **User actions:** list/search users and delete an account.
- **Granting admin:** a server console command only; there is no web route that grants admin.
- **Interface:** Vue `/admin` pages and admin-only JSON endpoints in the existing app.

Recording last sign-in reverses a documented privacy rule: the plan says "Minimal identity; no … last-login tracking", and the privacy page says "We do not collect … last-login history". Those texts change with this increment.

## 1. Schema (exact SQL: [ADMIN_SCHEMA_PROPOSAL.sql](ADMIN_SCHEMA_PROPOSAL.sql))

One additive schema migration, `Version20261016000440`:

```sql
ALTER TABLE user_account
  ADD COLUMN admin_granted_at DATETIME DEFAULT NULL,
  ADD COLUMN last_login_at DATETIME DEFAULT NULL;
```

- Both columns are mapped on the Doctrine `User` entity, so `doctrine:schema:validate` and the empty-ORM-diff checks still pass. There is no new table, so the table protection list is unchanged.
- **`admin_granted_at`**: non-NULL means admin; the value records when admin was granted. `getRoles()` returns `ROLE_USER` plus `ROLE_ADMIN` when it is set. Symfony reloads the user on every request, so a revoke applies to existing sessions on their next request.
- **`last_login_at`**: overwritten on every successful sign-in, both password sign-in and remember-me restore. It is one value, not a history, and second precision UTC. No IP address, browser, device or location is stored.
- Existing accounts start with `NULL` for both ("never recorded"). There is no backfill, and `down()` is irreversible, like the other account migrations.

## 2. Granting admin

- `bin/console app:admin:grant <email>` sets `admin_granted_at`. It is refused for unknown or unverified accounts and is idempotent.
- `app:admin:revoke <email>` clears it. `app:admin:list` lists admin emails and grant times.
- No HTTP endpoint can create, grant or revoke admins, so a stolen admin session cannot create more admins.

## 3. Admin API (`/api/admin/*`, requires `ROLE_ADMIN`)

All admin responses are private: `no-store`, `no-referrer`, and the same session-expiry rules as other account routes. Mutations need the session CSRF token.

| Endpoint | Purpose |
| --- | --- |
| `GET /api/admin/users?search=&page=` | 50 per page, sorted newest first; case-insensitive email substring search with literal wildcards. Each row: `id`, `email`, `createdAt`, `verifiedAt`, `lastSignInAt`, `admin`, `completions`, `lastPracticeAt`. |
| `DELETE /api/admin/users/{id}` | Body `{password}`: the **admin's own** current password, re-checked against a fresh locked row. It uses the existing deletion cascade (account, email links, completions), so the deleted user's other sessions stop working on their next request. It refuses to delete the caller (use Account) or another admin (revoke first with the CLI). It shares the persistent confirmation limits (5 per admin and 30 per network per 15 minutes). A log line records the admin ID, the deleted account ID and the time. The email is **not** logged, so it is not kept after deletion. |
| `GET /api/admin/stats` | Aggregates only (see §4). |

`/api/me` gains `admin: boolean`, so the app can show an Admin link. Non-admins get 403 from `/api/admin/*`, and the router redirects them away from `/admin`.

## 4. Statistics (all derived on request; nothing new is stored except `last_login_at`)

- **Accounts:** total, verified, unverified and admins; sign-ups and verifications per week for the last 12 weeks.
- **Sign-in activity:** accounts signed in within the last 1, 7 and 30 days, and accounts never recorded (those that predate this change).
- **Practice**, from `user_line_completion`:
  - completions in total, in the last 7 days and in the last 30 days, plus completions per day for the last 30 days;
  - distinct practising accounts in the last 7 and 30 days;
  - completions and distinct learners per course, and the top lines;
  - the White/Black split;
  - share of assessed completions with no hint, no retry and no takeback.
- **Not available:** guided-vs-variation mode (deliberately not stored), page views, visit counts and time-on-page. Nothing goes to third parties. Only the users list shows individual accounts; there is no per-user activity history beyond the two timestamps and the practice count.

## 5. Interface

`/admin` (sign-in required, admin only) has two sections:

- **Usage**: numeric tables and simple bars, with no chart library and no external requests.
- **Users**: search, paging and a delete flow, using the same password and confirmation-checkbox pattern as Account.

An **Admin** link appears in the header only for admins. It works on mobile.

## 6. Privacy and documentation changes

- **Privacy page** (owned by the active M6b session): replace "We do not collect … last-login history" with a plain statement. The service records the time of your most recent sign-in, so the operator can see whether accounts are in use; it keeps one timestamp, not a history; it is included in your data download and deleted with your account. Also state that administrators can see account emails and dates and can delete accounts.
- **Account data export**: the `account` block gains `lastSignInAt`. `ACCOUNT_DATA.md`, `ACCOUNTS.md` and the plan's `user_account` row are updated.

## 7. Verification plan

- **Disposable DB tests:**
  - parity between the proposal and the migration;
  - the schema-validate and empty-diff checks;
  - an `admin-test.php` covering 401/403 for anonymous users and non-admins, CSRF, search escaping and paging, stats against seeded data, deletion rules (refuses self and admins; cascades; ends the target's other sessions), wrong passwords, confirmation limits, and last-login recording for password and remember-me sign-ins;
  - a test that a CLI revoke takes effect on the next request.
- **Unit tests:** console commands, role mapping and the frontend parsers.
- **Browser tests:** the admin journey on desktop and mobile, and the Admin link hidden from non-admins.
- Finally the full `CHECK_REUSE_SERVICES=1 bash scripts/check.sh`.

## 8. JSON contract (frozen for parallel backend/frontend work)

All errors are `{"error": string}`. Statuses: 400 malformed or wrong password, 401 not signed in, 403 not an admin or bad CSRF, 404 unknown user, 409 refused target (self or another admin), 429 with `Retry-After`, 503 database unavailable. Dates are ISO 8601 UTC strings (`DATE_ATOM`) or `null`. IDs are decimal strings.

`GET /api/me` → `{"user": {"id", "email", "verified", "createdAt", "admin": boolean}}`

`GET /api/admin/users?search=<≤254 chars>&page=<1..9999>` → page is 1-based, 50 per page, newest accounts first:
```json
{"items": [{"id": "12", "email": "a@b.c", "createdAt": "…", "verifiedAt": "…|null", "lastSignInAt": "…|null",
            "admin": false, "completions": 3, "lastPracticeAt": "…|null"}],
 "page": 1, "limit": 50, "total": 1, "search": ""}
```

`DELETE /api/admin/users/{id}` (CSRF header, body exactly `{"password": "<admin's own password>"}`) → `{"deleted": true}`.

`GET /api/admin/stats` (weeks start on Monday UTC; series are oldest → newest and include zero entries):
```json
{"generatedAt": "…",
 "accounts": {"total": 0, "verified": 0, "unverified": 0, "admins": 0,
              "weekly": [{"weekStart": "2026-07-27", "signups": 0, "verifications": 0}]},
 "signIns": {"last1Day": 0, "last7Days": 0, "last30Days": 0, "neverRecorded": 0},
 "practice": {"total": 0, "last7Days": 0, "last30Days": 0, "learners7Days": 0, "learners30Days": 0,
              "daily": [{"date": "2026-09-17", "completions": 0}],
              "byCourse": [{"lessonId": "italian", "title": "Italian Game", "completions": 0, "learners": 0}],
              "topLines": [{"lessonId": "italian", "lineId": "…", "courseTitle": "…", "lineTitle": "…", "completions": 0}],
              "byColor": {"w": 0, "b": 0}, "assessed": 0, "cleanAssessed": 0}}
```
`accounts.weekly` has 12 entries and `practice.daily` 30, both ending at the current week or day. `byCourse` lists every course in sort order, including ones with zero completions. `topLines` has at most 10 entries, ordered by completions descending. `cleanAssessed` counts assessed completions with `usedHint`, `usedRetry` and `usedUndo` all false.

## 9. Implemented: backend (2026-10-16)

The backend half is implemented and verified on disposable databases. The Vue `/admin` UI, the privacy-page wording and the runner/check wiring are separate work.

**Files**
- Migration: `backend/migrations/Schema/Version20261016000440.php`, with the exact proposal SQL.
- Entity: `Entity/User.php` maps both columns, adds `isAdmin()`, adds `ROLE_ADMIN` in `getRoles()` and adds `admin` to `toArray()`.
- Last sign-in: `EventSubscriber/AccountLoginSubscriber.php`.
- Access: `Security/AccountRoutes.php` and `config/packages/security.yaml`.
- API: `Controller/AdminController.php` and `Service/Admin/{AdminUsers,AdminStats,AdminGrants}.php`.
- Commands: `Command/Admin{Grant,Revoke,List}Command.php`.
- Export: `Service/Account/AccountData.php` adds `account.lastSignInAt`.

**Decisions within the frozen contract**
- **Query validation.** `search` is trimmed and lowercased, and the normalised value is echoed. `search` must be valid UTF-8 of at most 254 bytes. `page` must match `[1-9][0-9]{0,3}`. Arrays, `01`, `0`, `10000` and non-digits return 400. A page past the end returns 200 with empty `items`. The search escapes `%`, `_` and the escape character `!` (`LIKE … ESCAPE '!'`), and the column's `utf8mb4_unicode_ci` collation makes it case-insensitive. Ordering is `created_at DESC, id DESC`.
- **Delete order.** Checks run in this order:
  1. Body exactly `{password}` (400), then CSRF (403).
  2. The shared `account-data-network` (30/900) and `account-data-user` (5/900, keyed by the admin's ID) limits, shared with Account download and deletion (429).
  3. One transaction that locks the admin and target rows with `… WHERE id IN (?, ?) ORDER BY id FOR UPDATE`.
  4. The admin is still admin (else 403), then the admin's fresh password (400, `Check your current password and try again.`).
  5. The target: unknown (404), or self or another admin (409).

  Self, admin and unknown refusals therefore require the correct password. Any DBAL error returns 503, and the whole cascade rolls back.
- **Deletion log.** It writes `info` "Administrator deleted an account." with `adminId`, `deletedAccountId` and `deletedAt` to the default application logger, with no email. In `prod`, the default handler is `fingers_crossed` at `error` level. The line is written in development and test, but a successful request does not persist it in production. A persistent audit line needs a dedicated info-level handler in `monolog.yaml`, owned by M6b.
- **Grant/revoke and sessions.** Symfony deauthenticates a session whose roles changed, and its remember-me listener clears the cookie. A grant or revoke therefore returns **401 on the account's next request in every browser**, including remembered ones, rather than 403. After signing in again, the new role applies (non-admins get 403). The UI should send a 401 from `/api/admin/*` to sign-in.
- **`topLines`.** Entries are grouped by lesson and line ID, so a line kept across revisions appears once. Titles come from the newest revision that has completions for it.
- **Time windows.** `last7Days` and the other day windows are rolling windows from the request time (UTC, second precision). `daily` uses UTC dates and `weekly` uses Monday-start UTC weeks. `byCourse` joins each lesson's current revision.

**Verified commands** (all `docker compose exec` calls ran as `--user www-data`):

| Command | Result |
| --- | --- |
| `docker compose exec -T --user www-data app composer analyse` | No errors |
| `docker compose exec -T --user www-data app vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php --dry-run <18 admin-owned files>` | 0 fixable |
| `docker compose exec -T --user www-data app composer test` | **235 tests / 5,002 assertions** |
| `docker compose exec -T --user www-data app php vendor/bin/phpunit tests/Service/Admin` | 17 tests / 120 assertions |
| `bash /tmp/admin-runner.sh admin-test.php`: a copy of `migrations-test.sh` with the repo-root `cd` and `admin-test.php` in its allowlist | **161 checks** |
| `bash tests/import/migrations-test.sh migrations-test.php` | 81 checks |
| `bash tests/import/migrations-test.sh accounts-test.php` | 93 checks |
| `bash tests/import/migrations-test.sh account-data-test.php` | 85 checks (one new last-sign-in check) |
| `bash tests/import/migrations-test.sh practice-test.php` | 146 checks |
| `bash tests/import/migrations-test.sh privacy-test.php` | 43 checks |

The shared development database was **not** migrated. Until `app:schema:migrate` is applied to it, the running dev app cannot hydrate `User`.

## 10. Implemented: UI, integration and live verification (2026-10-16)

**Frontend**
- `src/api/admin.ts`: strict parsers and an `AdminError {status, message, retryAfter}`.
- `src/api/account.ts`: `admin` flag.
- `src/router.ts`: `/admin`; non-admins are sent to `/learn`.
- `src/App.vue`: an Admin link, shown to admins only.
- `src/views/AdminView.vue`.
- `src/views/AuthView.vue`: `/admin` (with optional query) is now an accepted after-sign-in destination.

**Integration**
- **Audit log:** the deletion record goes to a dedicated `admin` Monolog channel (`var/log/admin-YYYY-MM-DD.log`, JSON, 0600, at most 14 daily files). This means it is persisted in production; the default `fingers_crossed` handler would drop info lines.
- **Privacy page:** it now states the single latest-sign-in time, what administrators can see and do, and that the download includes the latest sign-in. `PRIVACY_RETENTION.md` records the columns and the admin log.
- **Test wiring:** `admin-test.php` is in the `migrations-test.sh` allowlist and in `scripts/check.sh`.
- **Browser runner:** `tests/browser/run.sh` seeds one verified admin per run, with a random password, through `app:admin:grant`.
- **Live journey:** `frontend/tests/e2e/admin-live.spec.ts`:
  - a real learner is refused with 403 from `/admin` and sent to `/learn`;
  - the admin signs in through `/login?next=/admin`;
  - the admin sees real aggregates, searches for the learner and deletes the account;
  - the learner's existing session then gets 401.

**Verified**
- `bash tests/import/migrations-test.sh admin-test.php`: 161 checks.
- `CHECK_REUSE_SERVICES=1 bash tests/browser/run.sh admin-live.spec.ts admin.spec.ts`: 14 passed on desktop and mobile. The first live run failed because sign-in sent admins to `/learn`. The fix is the `AuthView` destination allowlist, and the test's URL assertions are now anchored.

**Applied to the shared dev DB:** schema `Version20261016000440` (additive), so dev sign-in matches the new mapping.

**Full combined check (2026-10-16).** `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` exited 0 against `8671711` (M6b published) plus this admin work, after installing the committed lockfiles (Vite 8.3.4, Vitest 5.0.4, Vue Router 5, Pinia 4, Composer update).
- **PHP:** 235 PHPUnit tests / 5,002 assertions; PHPStan, style, container and YAML lints all pass.
- **Disposable DB suites:** 18 constraint, 81 migration, 108 catalogue, 93 account, 85 account-data, 43 privacy, 62 lesson, 109 variation, 146 practice and 161 admin checks.
- **Frontend:** 237 Vitest tests, lint, format, typecheck and build.
- **Other suites:** smoke, Apache, health, deployment, preparation and build-hook checks.
- **Browser:** 67 desktop/mobile passes, one intentional mobile mouse-drag skip. These include the mocked admin spec and the live admin journey on both projects.

The `CHECK_REUSE_SERVICES` guard in `check.sh` was fixed during this run. It had failed while other sessions ran one-off app containers.
