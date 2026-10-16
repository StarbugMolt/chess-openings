# Account foundation

M5a and M5b are implemented and locally verified. Registration, email verification and resend, session login/logout, optional remember me, password recovery, `/api/me`, account forms and the owner-configured privacy notice are available. Private practice, recall and review are available at `/progress`; see [current behavior and verification](PROGRESS.md). M6a account download/deletion is implemented; see [ACCOUNT_DATA.md](ACCOUNT_DATA.md). M6b implements privacy/retention maintenance; see [PRIVACY_RETENTION.md](PRIVACY_RETENTION.md) for chosen values and remaining host checks.

## Try it locally

```bash
bash scripts/local-up.sh
# Open http://localhost:5173/register and use an @example.test address.
# Open http://localhost:8025 to follow the captured verification email.
# Confirm your email, then sign in with the password you chose during registration.
```

Refreshes retain the session. Checking **Remember me for 30 days** also restores sign-in after the session/browser closes. It is off by default. Sign out on shared devices. Mailpit captures all local mail; nothing is sent to the test address externally.

After completing a guided lesson or variation practice, choose **See your practice & reviews**, or open **Your practice** from Account. Saved practice is account-owned and survives logout and another browser session. Current coverage is separate for White/Black and each course revision. Earlier-version practice remains visible without automatically completing revised content; legacy completions have unknown recall rather than inferred perfect scores.

## API contract

All account responses, including errors, use `Cache-Control: private, no-store`. Requests use the same origin with cookies. Acquire a session-bound token from `GET /api/auth/csrf` (`{csrfToken}`) before a mutation, then send it as `X-CSRF-Token` alongside `Content-Type: application/json`. Login rotates the session ID and CSRF token. Never save credentials, email-link tokens or CSRF values in browser local storage.

| Method / path | JSON body / response |
| --- | --- |
| `GET /api/auth/csrf` | `{csrfToken: string}`; starts an anonymous session when needed |
| `POST /api/auth/register` | `{email, password}`; generic 202 |
| `POST /api/auth/request-verification` | `{email}`; generic 202, eligible unverified accounts only |
| `POST /api/auth/verify-email` | `{token}`; activates the password bound to this registration link; 200 or generic invalid/expired 400 |
| `POST /api/auth/login` | `{email, password, rememberMe?: boolean}`; `{user}` or generic 401 |
| `POST /api/auth/logout` | `{}`; clears session and remember cookie from this browser |
| `POST /api/auth/request-reset` | `{email}`; generic 202, verified accounts only |
| `POST /api/auth/reset-password` | `{token, password}`; 200 or generic invalid/expired 400 |
| `GET /api/me` | `{user: {id: string, email: string, verified: boolean, createdAt: string}}`; anonymous 401 |
| `POST /api/me/data-export` | `{password}` plus CSRF; private versioned JSON attachment with owned account/practice/link metadata |
| `DELETE /api/me` | `{password}` plus CSRF; `{deleted: true}` after account/token/progress cascade and sign-out |
| `GET /api/me/progress` | Existing per-line/revision/colour completion summary; anonymous 401 |
| `GET /api/me/practice` | `{generatedAt, courses, reviewQueue, history}`; private current recall/review facts and separate earlier versions; anonymous 401 |
| `POST /api/me/completions` | Exact lesson/revision/line/colour path and attempt identity, with optional compact `assessment`; `{saved: true}` |

Malformed input is 400, missing/invalid CSRF is 403, throttled requests are 429 with `Retry-After`. Email addresses are trimmed/lowercased, validated and capped at 254 bytes. New passwords accept 12–256 characters within a 256-byte cap; passwords are never trimmed. JSON bodies are capped at 4 KiB, with a 64 KiB Apache body limit for the application. There is no account endpoint selecting another user's ID. `/api/me`, `/api/lessons`, `/api/training` and `/api/progress` prefixes require `ROLE_USER`; lesson APIs enforce ownership when recording progress. `/account` and `/progress` use router authentication guards. The catalogue stays public and independent of account state; public `/api/courses` never contains private progress.

The practice page distinguishes service failure from session expiry. On a confirmed 401 from the practice endpoint it refreshes the shared account cache before offering sign-in with `/progress` preserved as the destination, so a stale signed-in guest guard cannot redirect the user away from login. Current line/colour coverage, suggested dates and earlier history use the private no-store endpoint. Its response includes both colours per current line and up to 20 server-ordered review suggestions; the responsive page exposes labelled controls and retry feedback on desktop and mobile.

Registration and email requests return the same public message for known/ineligible/unknown addresses. Register with email and password, confirm the email link, then sign in with that same password; verification never asks for another password or computes a new hash. Each registration link binds its own password hash. Re-registering an unverified address issues another independently bound link without deleting earlier links or changing the account password. Confirming a link installs its bound hash atomically with activation, then deletes all that account's verification links. The owner's own registration link therefore keeps the owner's password whether another registration came before or after it. Registering an already verified address sends an account-exists notice without changing its password.

**Accepted owner decision (2026-10-15):** confirming an unsolicited link still activates the password from the registration that issued it. Resends use the most recent pending registration's hash. Mailbox possession alone cannot identify which registration was intended; the owner accepted this residual risk for the standard registration flow. The verification page and email say to ignore a registration the user did not create. This supersedes the interim password-at-verification contract.

SMTP delivery runs on `kernel.terminate`, after sending the response. Accepted responses carry their exact byte `Content-Length`, and private account responses use `no-transform`, so clients can finish the fixed response before SMTP on runtimes without a finish-request function. Delivery is best effort in the PHP process, without a durable queue or automatic retry. Transport failures log a generic message without email/password/token or SMTP exception context; request another email after fixing the transport. A lazy termination subscriber resolves account mail services only for account email requests, leaving public catalogue reads independent of mail configuration. Verify response flushing, proxy compression and any `fastcgi_finish_request`/`litespeed_finish_request` support on the deployed host before launch. Database operations still vary by account eligibility, so this does not promise mathematically identical timing.

## Storage and lifecycle

`Version20261015000200` adds three private tables, independent of the imported catalogue. Additive schema `Version20261015000420` extends verification tokens with password bindings and deterministic issuance ordering:

| Table | Ownership / constraints |
| --- | --- |
| `user_account` | Doctrine `User` entity; integer internal ID, unique normalised email, Argon2id password hash, UTC creation/verification times; no display name or other profile fields |
| `account_email_token` | DBAL-managed; SHA-256 token hash primary key, account FK with delete cascade, checked `verify`/`reset` purpose, creation/expiry, nullable pending password hash, unique auto-increment issuance sequence and latest-issuance/account-purpose/expiry indexes |
| `account_request_limit` | DBAL-managed; HMAC-SHA-256 bucket key, attempt count and expiry index; raw email/IP are not stored here |

The schema migration is nontransactional because MariaDB DDL commits implicitly; its down operation refuses destructive removal. ORM diffs include the account entity while excluding DBAL token/limit tables, imported tables and migration histories. Fresh migration and ORM-diff tests verify this separation.

The separately owner-approved M5b schema `Version20261015000430` adds nullable `assessment_json VARCHAR(4096)` to the existing private completion ledger. It preserves the same user/attempt identity, exact revision/line foreign keys and account-delete cascade, with no new aggregate table or historical backfill. Existing rows remain NULL with unknown recall. An optional version-1 assessment records bounded final player-decision attempts/hint markers and whole-attempt hint/retry/takeback flags; no passwords, engine scores, raw rejected choices, seeds or abandoned-path logs are added. The server validates the summary against the retained authored path and saves it atomically with the completion. Identical retries stay idempotent; changed measured evidence or retrospective assessment of a saved legacy attempt returns 409. An older-client retry cannot erase a measured summary.

Recall and review are derived for the exact course/revision/line/colour/ply context. The first measured unassisted review is due after one day; due unassisted reviews advance through 1, 3, 7, 14 and 30 days, while practice with help schedules ten minutes. Early repetition does not advance the interval. Legacy-only current practice is available to review immediately, with recall unknown. Variation training freezes private review/exposure evidence at session start to favour less-practised or due supported replies; it cannot extend the selected taught scope. See [PROGRESS.md](PROGRESS.md) for the full assessment and scheduling contract.

Email tokens use 32 random bytes and store only hashes. Verification expires in 24 hours; reset expires in one hour. Multiple unexpired verification links remain valid until one is consumed, which invalidates all the others. Reset issuance still replaces the previous reset link. A user-row lock serialises issue/consume, and consumption rechecks expiry under lock before verification/password change. A resend copies the most recently issued pending hash, ordered by the database's auto-increment `issued_sequence` rather than second-resolution timestamps; equal timestamps and clock changes cannot choose an older registration. Existing links remain valid after migration: legacy null bindings preserve the locked account's current hash, and a legacy resend explicitly binds that hash.

Reset checks token validity before invoking Argon2id. Verification only installs the existing bound hash and never invokes the hasher. Opening an email link does not consume it: its secret is a URL fragment, Vue removes that fragment and requires a CSRF-protected confirm button. Links use configured `DEFAULT_URI`, never the request Host header. Verification does not automatically sign in.

Symfony hashes passwords with its sodium Argon2id hasher; `ext-sodium` is required. Unknown login addresses still undergo a dummy hash check and are rejected by the verified-user checker. Unverified accounts cannot authenticate. Kernel boot pins PHP to UTC so Doctrine hydrates timezone-free account timestamps consistently. Sessions contain a password checksum rather than its hash; Symfony reloads the account and invalidates old sessions after password changes. Sessions expire after two idle hours or eight hours from login. Native session storage has an eight-hour garbage-collection lifetime and uses the existing private shared deployment directory. `AccountRoutes` defines the protected API prefixes for access control, account-session expiry and private response headers together.

Remember me uses Symfony's signed, HttpOnly `CHESS_REMEMBER` cookie, with a rolling 30-day expiry when it restores sign-in. Its signature includes the current password hash, so resetting the password invalidates existing remember cookies on every device. A restoration starts a fresh bounded session. Logout clears this browser's cookie; this is Symfony's signed-cookie mechanism, not a server-maintained device/session registry. Both session and remember cookies use SameSite=Lax; production forces Secure, while local HTTP remains usable. Existing sessions survive a page refresh without opting into remember me. Changing `APP_SECRET` invalidates signed cookies, so keep the host secret stable across releases.

Limits are shared through MariaDB across PHP processes and code releases: login 10 attempts per email/client network/15 minutes and 60 per network/15 minutes. A successful login clears its email/network bucket; the shared network ceiling still counts requests to limit distributed username guessing. Registration/verification-mail/reset-mail share 5 requests per email/hour, 10 per email/day and 20 per network/hour. Verification consumption and password-reset consumption each allow 30 per network/15 minutes. IPv6 privacy addresses are grouped by /64; IPv4-mapped IPv6 addresses share the canonical IPv4 bucket. Raw addresses/emails are HMACed before storage. Expired windows reset atomically even when the stale row is outside the bounded cleanup batch.

Expired request-limit records and email tokens are pruned in bounded batches during account operations and by the daily `app:privacy:cleanup --apply` command. Its default is dry run. Accounts remain until owner deletion; the owner explicitly chose no automatic unverified-account purge. Application logs expire after 14 days without modification; managed SQL snapshots after 30 days, with at most three retained on deployment. Provider backups use a separate owner-confirmed 30-day cycle. See [PRIVACY_RETENTION.md](PRIVACY_RETENTION.md) for daily setup, delays and recovery boundaries. Production logs exclude the security channel from the error buffer and do not flush it for expected 400/401/403/404/405/429 responses; production application logs rotate with 14 retained files.

## Configuration and verification

Compose already supplies `MAILER_DSN=smtp://mailpit:1025` and `DEFAULT_URI=http://localhost:5173`; `.env` supplies a development-only `MAIL_FROM`. Hosting must configure an HTTPS canonical URL, a verified real sender/SMTP transport, a strong stable `APP_SECRET`, private shared sessions and HTTPS. The owner confirms O2Switch production hosting and account-email delivery. Public `/privacy` identifies StarbugStone and chessopenings@starbugstone.com, documents legal bases, account controls and retention, and shows Mailpit guidance only outside production. This session verified public HTTPS health; detailed SMTP and scheduled-job verification still needs host evidence. The daily privacy cron example is not installed by this increment.

Actually verified on 2026-10-15:

```bash
bash tests/import/migrations-test.sh accounts-test.php
docker compose run --rm browser npm run test:e2e -- accounts.spec.ts
bash scripts/check.sh
```

The disposable MariaDB account suite exercises real Symfony kernels and Mailpit: registration, normalisation/Argon2id, verification/resend, replacement/expiry/single use, account isolation, session/CSRF rotation, logout, idle/absolute expiry, persistent rate limits, remembered browser restoration/tampering, reset invalidation across sessions/remember cookies, and local HTTP versus production HTTPS cookie flags. Browser form tests use deterministic API contracts on desktop and mobile; they separately verify reload, account guarding, fragment removal, explicit confirmation, remember-me submission, password clearing and privacy navigation. Test databases/grants are removed afterward. Full suite counts are recorded in the README and implementation plan.

The owner-approved review-fix account suite passed **93 integration checks**. Cases cover both registration orders, the owner's earlier and later links retaining their passwords, deletion of competing verification links after consumption, deterministic latest-hash resends with equal timestamps, legacy links/resends and the accepted unsolicited-link behavior. Other coverage includes network-isolated login throttles, success reset, IPv6 /64 sharing, daily mail limits, expired buckets beyond one cleanup batch and UTC hydration. It also starts an isolated HTTP application against a deliberately stalled SMTP server and verifies that the generic response completes while SMTP still waits for its four-second greeting, with exact `Content-Length` and `no-transform` (0.040 seconds in the last run). This verifies the local PHP HTTP runtime; actual Apache/LiteSpeed/proxy response framing remains a host gate. No shared mailbox is cleared or modified by the timing test.

The focused account PHPUnit command below passed **16 tests / 41 assertions**, including invalid/expired reset tokens never reaching the password hasher, valid and legacy verification never hashing, deferred delivery, private failure logging and public reads never resolving mail configuration. The separate migration suite passed **81 checks** with `.00420` present:

```bash
docker compose exec -T --user www-data app sh -c 'cd /app/backend && php vendor/bin/phpunit tests/Security tests/Service/Account tests/EventSubscriber/AccountEmailSubscriberTest.php tests/Controller/AccountVerificationTest.php'
bash tests/import/migrations-test.sh accounts-test.php
bash tests/import/migrations-test.sh
```

The accepted registration password is active only after its corresponding email is confirmed; the user enters it once during registration.

## M5b account and practice verification (2026-10-15)

The final `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` passed **194 PHP tests / 4,247 assertions**, **219 frontend unit tests** and **45 desktop/mobile browser cases**, plus one intentional mobile mouse-drag skip. The disposable account suite remained at **93 checks** and the new practice API suite passed **150 checks**, including owner isolation, legacy/measured retry semantics, revision boundaries and review timing. Browser journeys cover session recovery, saved evidence in another cookie jar/session, explicit review-link selection, stale revisions and mobile layouts. These results preserve the earlier M5a checkpoints above and are recorded with current limitations in [PROGRESS.md](PROGRESS.md). No host deployment or cron installation is implied.

## Account download and deletion (M6a, 2026-10-16)

Account now offers password-confirmed **Download my data** and a separate, cancellable permanent-deletion form. Both use CSRF and a persistent shared confirmation limit (five per user and 30 per network per 15 minutes). The JSON download includes all earlier/current line revisions and measured or legacy summaries, never password/token hashes or cookie secrets. Deletion atomically removes the account, its email tokens and all completions through existing cascades, then signs out. Other sessions and remember cookies cannot restore it. Another account and the public chess/curriculum layers remain unchanged.

No schema migration or new private storage was needed. The current password is checked against a locked fresh database row, including after password changes. Wrong passwords, outages and rate limits are actionable; confirmed expiry refreshes client account state. Existing backups/logs/temporary abuse limits and local captured email are outside live-account deletion; their chosen retention and notice are in [PRIVACY_RETENTION.md](PRIVACY_RETENTION.md). [ACCOUNT_DATA.md](ACCOUNT_DATA.md) records the exact API, boundaries and current verification.

M6a final verification: `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` exited 0 with **199 PHP tests / 4,276 assertions, 227 frontend tests and 49 desktop/mobile browser passes**, plus one intentional mobile mouse-drag skip. The new disposable account-data suite passed **84 checks**, existing account lifecycle stayed at **93**, and the focused account-data browser subset passed **4** cases. No schema change or shared user/service replacement was needed. [ACCOUNT_DATA.md](ACCOUNT_DATA.md) records exact commands and live-deletion boundaries.

[GitHub CI](https://github.com/Starbugstone/chess-openings/actions/runs/38861773058) passed for the published application commit `4e484ef` on 2026-10-16. Subsequent commits receive a new CI run; this does not verify O2Switch deployment or activate either host job.
