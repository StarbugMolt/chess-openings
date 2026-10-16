<script setup lang="ts">
import { t } from '../i18n'
</script>

<template>
  <main id="main-content" class="page-width privacy-page">
    <span class="eyebrow">{{ t('LOCAL DEVELOPMENT PREVIEW') }}</span>
    <h1>{{ t('Account privacy.') }}</h1>
    <p>
      {{
        t(
          'This preview stores your email address, a password hash, an internal account ID, creation time and email-verification time. It uses these details to let you sign in and recover your account. Completed practices save the lesson, line and revision, your chosen colour, completion time, hint count and a random attempt identifier to prevent duplicate saves. New practices also keep a compact summary of tries and hints at the taught decisions, together with whether you used a hint, retry or takeback. These summaries power your private progress and suggested reviews. Earlier practice stays recorded with recall marked unknown.',
        )
      }}
    </p>
    <h2>{{ t('Sessions and email') }}</h2>
    <p>
      {{
        t(
          'A first-party session cookie is necessary for sign-in and request security. It is HttpOnly and SameSite=Lax, with Secure required in production. Sessions expire after two hours of inactivity or eight hours from sign-in. Authentication tokens stay out of browser local storage. The separate sound and language preferences are stored only in your browser.',
        )
      }}
    </p>
    <p>
      {{
        t(
          '“Remember me” is optional. It adds a signed HttpOnly cookie, valid for 30 days and renewed when it restores sign-in. It uses the same SameSite and production Secure protections. Signing out clears it from this browser; changing your password invalidates existing remember-me cookies on every device.',
        )
      }}
    </p>
    <p>
      {{
        t(
          'Verification links expire after 24 hours; password-reset links after one hour. The database stores their hashes. Links work once. Confirming your email invalidates your other verification links; requesting a new password reset replaces the earlier reset link. Local development email is captured by Mailpit and is visible to the developer.',
        )
      }}
    </p>
    <h2>{{ t('Request limits and logs') }}</h2>
    <p>
      {{
        t(
          'Abuse limits use keyed hashes of email and IP addresses, with windows of 15 minutes or one hour or a day. Expired limit and email-token records are removed in bounded batches during account requests. Server access logs may contain IP addresses and request paths. Email-link secrets are kept in URL fragments rather than server request URLs. There are no advertising or analytics trackers.',
        )
      }}
    </p>
    <h2>{{ t('Your data and account deletion') }}</h2>
    <p>
      {{
        t(
          'In Account, you can download your account details, all saved practice and email-link metadata as a JSON file. Password hashes, email-link secrets and session cookies are excluded. You can also permanently delete your account, its email links and all saved practice. Both actions require your current password. Deletion signs you out; other browsers can no longer sign in to the deleted account.',
        )
      }}
    </p>
    <p>
      {{
        t(
          'Deletion removes your data from the live service. It does not rewrite existing backups, server logs or temporary abuse-prevention records. Their retention rules must be finalised before public registration. Local captured email may still be visible in Mailpit.',
        )
      }}
    </p>
    <RouterLink class="primary-link" to="/account">{{ t('Manage your account') }}</RouterLink>
    <h2>{{ t('Before public registration') }}</h2>
    <p>
      {{
        t(
          'This is a development preview. Use test details. Publisher and privacy contact details, legal bases, hosting and email providers, account and backup retention, and the rights and complaints process must be completed and reviewed before this service opens to the public.',
        )
      }}
    </p>
    <RouterLink class="primary-link" to="/">{{ t('Back to home') }}</RouterLink>
  </main>
</template>
