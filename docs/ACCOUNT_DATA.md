# Account download and deletion

M6a implements the user account controls without a schema change. It uses existing account ownership, retained completions and deletion cascades. Public-launch publisher/contact details, retention rules and host verification remain separate M6 work.

## Use the controls

Sign in and open **Account**. **Download your data** asks for the current password and downloads `chess-openings-data.json`. The file includes account metadata, all saved practice with its original course revision/colour and retained labels, compact measured assessments (or NULL for older practice), and email-link creation/expiry metadata. No earlier version or colour is silently omitted. Password hashes, email-link hashes/secrets, cookies and abuse-limit keys are excluded.

**Delete my account…** reveals a separate confirmation form. Enter the current password, acknowledge permanent loss, then choose **Permanently delete my account**. **Keep my account** cancels and clears the form. Successful deletion shows a confirmation and signs out. The account, email links and every retained completion/assessment are removed together; derived progress/reviews then have no underlying account data. Another account, the opening catalogue and published lessons remain intact.

Both forms clear the password on submission and when leaving the page. Credentials are never put in URLs or browser storage. Controls have labelled fields, visible feedback and mobile touch targets; focus moves into the confirmation form, back to its trigger on cancellation, and to the success heading after deletion.

## API contract

Both endpoints require an authenticated session, JSON `{"password": "current password"}` and session-bound `X-CSRF-Token` obtained from `/api/auth/csrf`. The existing 4 KiB body/256-byte password bounds apply. Extra fields, including a client-selected user ID, are rejected.

| Method/path | Success |
| --- | --- |
| `POST /api/me/data-export` | JSON attachment, `formatVersion: 1`, `exportedAt`, `account`, `completions`, `emailLinks` |
| `DELETE /api/me` | `{"deleted": true}` after the deletion transaction and configured Symfony logout |

POST export replaces the plan's earlier GET proposal so each download can confirm the password in a request body without a reusable elevated session. GET cannot download data. Responses use `private, no-store, no-transform` and `no-referrer`; downloads additionally use JSON MIME type, `attachment` disposition and `nosniff`.

`account` contains `id`, `email`, `createdAt`, `verifiedAt`. Each completion contains `attemptId`, `lessonId`, `lessonTitle`, `revision`, `lineId`, `lineTitle`, `playingColor`, `hintCount`, `completedAt`, and nullable versioned `assessment`. Each email-link entry contains only `purpose`, `createdAt`, `expiresAt`. Dates are UTC ISO 8601. Current and earlier practice retain their original identity; legacy recall is not inferred. Derived review schedules are reproducible from the immutable practice ledger and do not need a second stored export dataset.

Anonymous/expired sessions return 401, invalid CSRF 403, malformed confirmation or wrong password 400, and throttling 429 with `Retry-After`. Wrong-password feedback leaves a valid session signed in. Five confirmations per account and 30 per client network are allowed per 15-minute window; both actions and all that account's browsers share the limit. Successful confirmations count too. IPv6 networks follow the existing /64 policy. On confirmed session expiry, UI recovery refreshes the shared account state before offering sign-in with `/account` preserved.

## Consistency and deletion boundary

The service locks the current account row and verifies its fresh database password, serializing with reset, token issuance and deletion rather than trusting the session's old hash. Export reads every owned completion and retained label under that transaction. JSON is built in request memory; DBAL/PDO may also buffer rows. Read/encoding cost grows with history, and a 10,000-row generator regression verifies that no history cap silently truncates a download. No permanent export file or new table is created.

Deletion commits existing `ON DELETE CASCADE` removal of private email tokens and completions, then uses the configured Symfony logout listeners to invalidate the current session and clear remember me. Other sessions and remembered browsers cannot reload the deleted user on their next request. Other browsers' private session files are not enumerated for immediate physical erasure; they become unusable and follow the existing session-handler expiry/cleanup. Recreating the same email/password does not revive their old credentials. A database failure rolls the transaction back and leaves the account signed in; it never reports success after partial removal.

Deletion acts on the live service. It does not rewrite existing backups, access logs, temporary HMAC abuse-limit records or local Mailpit messages. Their actual retention and recovery/restore procedure still need finalisation before public launch. There is no invented expiry promise, backup-erasure claim or compliance label.

## Verification

Focused checks passed on 2026-10-16:

| Command/check | Result |
| --- | --- |
| `bash tests/import/migrations-test.sh account-data-test.php` | **84 disposable checks**: password/CSRF, private attachment/secret exclusions, legacy/measured/retained ownership, rollback/cascades, other sessions/remember/reset links, recreation and persistent limits |
| `docker compose exec -T --user www-data app php vendor/bin/phpunit tests/Service/Account/AccountDataTest.php` | **5 tests / 29 assertions**, including fresh locked-password use and complete 10,000-row history |
| `docker compose exec -T frontend npx vitest run tests/unit/AccountStore.test.ts` | **11 tests**: request security, attachment/error handling and stale-read-safe deletion state |
| `CHECK_REUSE_SERVICES=1 bash tests/browser/run.sh account-data.spec.ts` | **4 desktop/mobile passes**: real private JSON download, cancellation, wrong passwords, confirmation, cookie/session removal and expiry recovery |
| `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` | **Exit 0: 199 PHP tests / 4,276 assertions, 227 frontend tests, 49 desktop/mobile browser passes**, one intentional mobile mouse-drag skip; static/type/format/build, Apache/deploy and actual private MariaDB backup/restore passed |
| Disposable suites in that combined run | **18 constraints / 81 migration / 108 catalogue / 93 account / 84 account-data / 61 lesson / 109 variation / 150 practice checks** |

The full run also covers the independent guided move-explanation and catalogue reference-board changes. All destructive checks use disposable databases/accounts. No schema change was needed; fresh bootstrap remains nine schema/15 lesson migrations. Shared services, the retained learner and the other agent's browser account were preserved. Native T3 inspection at 390 px confirms the account cards fit without horizontal scrolling; no destructive action was submitted there.

The first targeted browser run lost a trace file when another run cleared shared output. Each isolated runner now uses ignored `artifacts/browser/<run>` output outside frontend source discovery. An older intermediate `.test-artifacts` directory is also ignored by Git/formatting/lint so another session's retained artifacts need not be deleted. The corrected targeted and final combined runs both pass. [GitHub CI](https://github.com/Starbugstone/chess-openings/actions/runs/38861773058) passed for `4e484ef` on 2026-10-16. Host activation and remaining M6 public privacy/retention work remain unverified.
