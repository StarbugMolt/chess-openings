-- Proposed permanent schema migration Version20261016000440 (owner review pending; see ADMIN_REVIEW.md).
-- Additive, nullable columns on the existing ORM-mapped account table; no new table and no backfill.
-- admin_granted_at: non-NULL means ROLE_ADMIN; set/cleared only by the app:admin:grant/revoke console commands.
-- last_login_at: UTC second-resolution time of the latest successful sign-in (password or remember-me restore).
ALTER TABLE user_account
  ADD COLUMN admin_granted_at DATETIME DEFAULT NULL,
  ADD COLUMN last_login_at DATETIME DEFAULT NULL;
