# Privacy and retention operations

M6b implements the public notice and bounded privacy maintenance without changing the schema. The owner confirmed these public details and retention choices on 2026-10-16:

- Publisher/data controller: **StarbugStone**.
- Privacy contact: **chessopenings@starbugstone.com**.
- Website: **https://chessopening.starbugstone.com/**.
- Hosting and verification/password-reset email delivery: **O2Switch**.
- Keep verified accounts until their owner deletes them; do not automatically purge unverified accounts.
- O2Switch recovery backups are retained for **30 days**, as confirmed by the owner.

The owner confirms production is deployed. This session also received `{"status":"ok"}` from the public HTTPS `/api/health` endpoint. That establishes current readiness, not deployment of this unpushed increment, scheduled maintenance, SMTP delivery, or successful recovery testing. Existing deployment/weekly-cron status must be checked independently; this session installed no hosting job.

## Public notice and configuration

`/privacy` gives the controller/contact, data and purposes, legal bases, retention, hosting/email recipients, essential cookies, account controls, rights and the CNIL complaint link. It distinguishes local Mailpit capture from production delivery. It makes no compliance certification claim. The public website URL is shown explicitly; production URL templates use the same origin.

Anonymous read-only `GET /api/privacy` exposes only whitelisted public notice fields and numeric policy values. It does not start a session or expose SMTP credentials, database settings, account records or private paths. Vue validates the response and requests it without cookies. `unverifiedAccountDays: 0` means no automatic age-based purge. Network failures offer retry; missing public fields are identified without inventing contact details. Real public defaults are in `backend/.env`, so the existing deployment can inherit them without rewriting its private SMTP configuration.

Optional overrides in the private production environment:

```dotenv
PRIVACY_PUBLISHER=StarbugStone
PRIVACY_CONTACT_EMAIL=chessopenings@starbugstone.com
PRIVACY_EMAIL_PROVIDER=O2Switch
PRIVACY_BACKUP_DIRECTORY=
```

These three identity fields are **public**. Never put a transport DSN, password or credential in them. Transport-like provider values are rejected. The backup directory is private and never returned by the API/report. Leave it empty for standard deployment: in production, the command resolves the release's `.env.local` symlink and uses its sibling `backups/` directory. A custom layout must set an absolute private backup path. Do not use a web-accessible directory. Shared session/log root symlinks are supported; child symlinks are skipped.

## Retention and deletion boundaries

| Data | Policy and enforcement |
| --- | --- |
| Verified accounts and private practice | Until owner deletion; no inactivity purge. Account metadata, including the single overwritten latest-sign-in time and any admin grant time (see [ADMIN_REVIEW.md](ADMIN_REVIEW.md)), is removed with the account; maintenance never changes those columns. Existing authenticated, password/CSRF-confirmed account deletion cascades to links and completions. |
| Unverified accounts | No automatic age-based deletion. The owner chose to retain them. A person can verify then use Account, or contact the publisher for assistance. |
| Email-token records | Verification expires at 24 hours, reset at one hour; existing token flows also consume/replace links. Expired rows are eligible immediately, pruned opportunistically and by daily maintenance. |
| Hashed request-limit buckets | Expire at the end of their existing 15-minute/hour/day windows. Expired rows are pruned opportunistically and daily. These hashes remain personal security data, not anonymous analytics. |
| PHP session files | Authentication expires after two hours idle/eight hours absolute. Known `sess_*` files become eligible after eight hours without activity. Busy files are skipped under advisory locks. Deleting an account immediately prevents other browsers authenticating, even before their unusable files are pruned. |
| Dated application logs | Only valid `prod-YYYY-MM-DD.log` and `openings-YYYY-MM-DD.log` files, after 14 days without modification. Monolog also rotates at 14/12 files respectively and now locks writers. Count rotation can delete earlier. |
| Application SQL snapshots | At most three successful managed deployment snapshots; eligible for removal after 30 days. The updated private preparation helper prunes after successful backup; daily cleanup enforces age expiry even without deployments. Failed backup preserves prior snapshots. |
| O2Switch recovery backups | Separate provider-managed 30-day backup cycle. Application cleanup cannot rewrite or verify those copies. |
| Hosting access/email logs and mail storage | Managed separately by O2Switch. Do not apply the application's 14-day log setting to provider access logs, SMTP queues, sent mail or support records. Their exact settings remain a hosting verification item. |
| Local Mailpit | Development messages are separate copies. Use test identities; inspect/delete only your own test messages, or explicitly agree a developer cleanup. The command never clears shared Mailpit. |

Daily cleanup uses bounded batches, so these periods mark **eligibility**, rather than an exact deletion timestamp. An outage, busy file or backlog can delay removal. Monitor failures and eligible counts and adjust the schedule/limit when needed. Unknown/manual exports, partial dumps, arbitrary logs and subdirectories are never silently removed. Operational deploy/import wrapper logs retain their existing size/release rotation; avoid putting personal records in their output. No new deletion-event ledger, account activity field or private aggregate table has been added.

A hosting backup can contain an application snapshot that was already almost 30 days old. Provider and application periods can therefore add together; do not promise that every recovery copy vanishes 30 days after account deletion. Restrict all backups to recovery and let their separate cycles expire.

## Daily maintenance

The Symfony command is read-only by default. `--check-config` checks only the public fields, without querying private records. `--apply` is the explicit mutation switch. `--limit` accepts 1–10000, default 1000, **per category**. Output contains UTC time and counts only. Failure is nonzero; do not treat partial progress as a successful full run. Each deletion is retryable/idempotent.

```bash
# Local, safe configuration check and dry run; no shared data is deleted.
docker compose exec -T --user www-data app bin/console app:privacy:cleanup --check-config
docker compose exec -T --user www-data app bin/console app:privacy:cleanup

# HOST examples, replace account/root/PHP version and verify timezone first.
/opt/alt/php83/usr/bin/php /home/ACCOUNT/apps/chess-openings/current/bin/console app:privacy:cleanup --check-config --env=prod --no-debug
/opt/alt/php83/usr/bin/php /home/ACCOUNT/apps/chess-openings/current/bin/console app:privacy:cleanup --env=prod --no-debug
# After reviewing the dry run and private paths:
/opt/alt/php83/usr/bin/php /home/ACCOUNT/apps/chess-openings/current/bin/console app:privacy:cleanup --apply --limit=1000 --env=prod --no-debug
```

After deploying this increment, verify the command, public notice and resolved private layout, inspect the dry run, run one checked application, then install [the daily 02:47 host-time cron example](../ops/cron/privacy-maintenance.crontab.example). Configure/test cron failure email and inspect eligible counts periodically. Deployment polling and weekly opening sync remain separate jobs. **The example is not installed by this change.** No host access was supplied in this session.

Standard releases automatically include the command and log-lock configuration. `control/production.php` is an installed copy: ordinary app deployments do not update it. Use the existing disable/wait/reinstall/check/enable procedure from [deployment operations](DEPLOYMENT_TEMPLATE.md) to update control hooks after reviewing the diff. Preserve host-only configuration and secrets. The daily command can prune the standard private snapshot directory even before that helper is updated; do not omit the daily job because the helper also rotates on deployment.

## Recovery gate

Restore privately with the public site and deploy/maintenance jobs stopped. Verify the snapshot and migration/application compatibility before exposing it. Identify account deletions made after the snapshot from trustworthy newer account records or an operator's existing deletion-request records. Reapply those deletions (and their ownership cascades) **before reopening** and test that old sessions, reset links and remembered sign-ins cannot revive them. Then verify account ownership, progress and readiness, and resume jobs deliberately.

This code does **not** record an automated durable deletion-suppression ledger. If the necessary newer evidence is unavailable, the operator cannot safely claim a raw snapshot preserves prior deletions: do not reopen it blindly. Plan recovery/reconciliation with the publisher first. Any future persistent deletion registry changes private-data architecture and requires separate review. Do not put passwords or full exports in deletion-request records. A code rollback never restores the database automatically.

## Purposes, rights and operating responsibilities

Email, password hash, verification and practice evidence are necessary to provide the requested account-based learning service; the notice uses contract performance as the basis. Network/email keyed hashes and short-lived operational records serve the publisher's security interest: prevent repeated credential/email abuse and diagnose failures, with bounded windows, minimal fields and no tracking/advertising use. This documents the application's purpose and safeguards; the publisher remains responsible for assessing necessity, balancing that interest and handling objections.

People can use Account for access/export/deletion and the public contact for correction, restriction, objection or assistance, as applicable. Verify identity proportionately without requesting a password by email. Handle requests normally within one month; exceptions need an explained response. Restrict recovery copies and provider access, verify processor terms/data locations, and review the notice when purposes, providers or retention change. No international-transfer safeguard is invented from an email domain or hosting brand. Additional publisher/legal-identification requirements outside this privacy notice remain an owner/legal review item; no private identity/address has been fabricated.

Primary references used for the notice: [CNIL transparency](https://www.cnil.fr/fr/conformite-rgpd-information-des-personnes-et-transparence), [retention](https://www.cnil.fr/fr/passer-laction/les-durees-de-conservation-des-donnees), [individual rights](https://www.cnil.fr/fr/passer-laction/les-droits-des-personnes-sur-leurs-donnees), [contract performance](https://www.cnil.fr/fr/les-bases-legales/contrat), [legitimate interest](https://cnil.fr/fr/les-bases-legales/interet-legitime), and [O2Switch infrastructure](https://www.o2switch.fr/infrastructures/). Provider backup duration comes from the owner's confirmation, not an inferred universal provider policy.

## Verification

Focused verification on 2026-10-16:

- `bash tests/import/migrations-test.sh privacy-test.php`: **43 checks**, using a fresh disposable database and private temporary files; expiry boundaries, dry run, bounded deletion, live-record survival, unchanged accounts/progress/curriculum/catalogue, public-only API and no unnecessary cookie.
- `docker compose exec -T --user www-data app php vendor/bin/phpunit tests/Service/Privacy`: **8 tests/51 assertions**, including lock contention, shared root and child symlinks, file allowlists, date validation, configuration-only checks and webroot rejection before mutation.
- `docker compose exec -T frontend npx vitest run tests/unit/PrivacyApi.test.ts`: **3 tests**; typed validation, missing-field coherence, cookie omission and outages.
- `docker compose exec -T app php /app/tests/deploy/production-test.php`: passed private backup/count/age/manual-file/failure checks.
- `docker compose exec -T --user www-data app bin/console app:privacy:cleanup --check-config` and the default dry run: passed; no shared records/files removed. Configuration-only check also passed with a deliberately unreachable dummy database URL.
- Native T3 inspection: correct publisher/contact/provider at 390 px and 1280 px, with no horizontal overflow.

Final `CHECK_REUSE_SERVICES=1 bash scripts/check.sh` exited **0** on the fixed M6b source snapshot: **211 PHP tests/4,861 assertions, 230 frontend tests and 53 desktop/mobile browser passes**, plus one intentional mobile mouse-drag skip. Disposable suites passed **18 constraint / 81 migration / 108 catalogue / 93 account / 84 account-data / 43 privacy / 62 lesson / 109 variation / 146 practice checks**. Static/format/type/build, Apache/deployment/controller and actual MariaDB backup/restore all passed.

The first shared-source combined run passed through backup/restore, then six registration browser journeys returned 500 while the independent admin entity/schema implementation changed after the browser DB had bootstrapped. Privacy cases passed there. To obtain a stable result without undoing other agents' work, the final run archived base **`5acdce9`** plus only M6b's owned runtime/tests/runner changes into **`/tmp/chess-m6b-verify-dvfba0yh`**. It used a separate Compose project, database, Mailpit and runtime volume, cached images and the existing locked dependency volumes. Parallel admin and phase-4 curriculum work were excluded; their integration needs its own combined check. The M6b snapshot used nine schema/19 lesson migrations and 25 current/46 retained lines/880 steps.

Commands actually verified in that snapshot:

```bash
docker compose up -d --wait db mailpit app
docker compose exec -T --user www-data app bin/console app:database:prepare --source-directory=tests/Fixtures/Lessons/source --no-interaction
CHECK_REUSE_SERVICES=1 bash scripts/check.sh
```

Local logs/traces are retained under ignored `artifacts/privacy-retention-20261016-dvfba0yh/`. All destructive verification used disposable databases/files. No shared account, progress, Mailpit message or hosting data was deleted. `docker compose down --volumes` then removed only that disposable project and its DB/runtime/cache volumes; shared external dependency volumes were preserved. The original development services remained running.

Final small hardening after the fixed-snapshot full run suppresses PHP’s raw `unlink` warning before the existing generic cleanup exception, so a permission failure does not print a session filename. The focused privacy suite was rerun: **eight tests/51 assertions passed**. A disposable read-only-directory check as `www-data` also confirmed a generic failure, no filesystem warning and preservation of the file; its temporary directory was then removed. No cleanup behaviour or retention period changed.

## Administrator access (2026-10-16)

The owner-approved admin area adds `user_account.last_login_at` (latest sign-in only) and `admin_granted_at`. Admins can see account emails, dates, practice counts and aggregate statistics, and can delete accounts. Deletions are written to the `admin` log channel (`var/log/admin-YYYY-MM-DD.log`, JSON, 0600, one file per day with entries, Monolog keeping at most the 14 most recent) with admin and account IDs only. This log is not yet in `app:privacy:cleanup`'s dated-log pattern, because Monolog's `max_files` already bounds it.

## Host steps for this release

1. Wait for this commit's **CI** workflow to succeed and the normal host poller to activate it. Confirm `https://chessopening.starbugstone.com/api/privacy` reports your public details, `configured: true` and `development: false`; open `/privacy` too.
2. In the host terminal, set these to the **existing** application root and PHP CLI from your deployment configuration. Keep the current private DB/SMTP configuration. The notice's public values already have committed defaults.

```bash
CHESS_ROOT="$HOME/apps/chess-openings" # adapt to your existing application root
CHESS_PHP=/opt/alt/php83/usr/bin/php    # use your configured PHP CLI
"$CHESS_PHP" "$CHESS_ROOT/current/bin/console" app:privacy:cleanup --check-config --env=prod --no-debug
"$CHESS_PHP" "$CHESS_ROOT/current/bin/console" app:privacy:cleanup --env=prod --no-debug
```

The first command should report `{"configured":true,"missing":[]}`. The second must report `dryRun: true` and `deploymentBackupsConfigured: true` for the standard shared layout. Review counts. With a custom layout, set `PRIVACY_BACKUP_DIRECTORY` in the existing private environment to the actual private SQL-backup directory, then repeat the dry run.

3. Update the installed `production.php` helper under the poll lock. This waits for a running cron deployment, disables polling during the update, lints the fetched helper, atomically replaces it and checks/re-enables polling. Run from one host terminal. The Git source is pinned to the **currently active release**. The controller already fetched that commit; this update reads its local Git object and needs no separate Git SSH authentication. Existing configuration and secrets are retained.

```bash
CHESS_REPO=$("$CHESS_PHP" -r '$c=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR); echo $c["repository_path"];' "$CHESS_ROOT/control/config.json")
CHESS_SHA=$(cut -c1-40 "$CHESS_ROOT/current/public/release.txt")
(
  set -eu
  umask 077
  flock -x 9
  "$CHESS_PHP" "$CHESS_ROOT/control/deploy.php" "$CHESS_ROOT/control/config.json" disable
  test ! -f "$CHESS_ROOT/state/enabled"
  git -C "$CHESS_REPO" show "$CHESS_SHA:scripts/deploy/production.php" > "$CHESS_ROOT/control/production.php.next"
  "$CHESS_PHP" -l "$CHESS_ROOT/control/production.php.next"
  chmod 700 "$CHESS_ROOT/control/production.php.next"
  mv "$CHESS_ROOT/control/production.php.next" "$CHESS_ROOT/control/production.php"
  "$CHESS_PHP" "$CHESS_ROOT/control/deploy.php" "$CHESS_ROOT/control/config.json" check
  "$CHESS_PHP" "$CHESS_ROOT/control/deploy.php" "$CHESS_ROOT/control/config.json" enable
) 9>"$CHESS_ROOT/poll.lock"
```

If this block fails after disabling polling, correct the reported issue before re-enabling it. No installer/configurator is needed for this single-helper update.

4. After the dry run, perform one checked cleanup:

```bash
"$CHESS_PHP" "$CHESS_ROOT/current/bin/console" app:privacy:cleanup --apply --limit=1000 --env=prod --no-debug --no-interaction
```

It should report `dryRun: false`; account removals stay zero. It removes only expired temporary records and known stale files. No manual schema migration is required by this privacy increment.

5. Run `date '+%Z %z'` to check the host timezone. In **cPanel → Cron Jobs**, add one daily job at minute **47**, hour **2**, all other date fields `*`. Use the actual absolute PHP/application paths:

```cron
47 2 * * * /opt/alt/php83/usr/bin/php /home/ACCOUNT/apps/chess-openings/current/bin/console app:privacy:cleanup --apply --limit=1000 --env=prod --no-debug --no-interaction > /dev/null
```

In cPanel's command field, paste only the command after the five schedule fields. Set/test a monitored cron-email recipient for failures. Keep deployment polling and weekly opening sync as separate existing jobs. After the first scheduled execution, inspect another dry run for a persistent backlog; successful stdout is discarded by the example, while failures remain on stderr for cron email.
