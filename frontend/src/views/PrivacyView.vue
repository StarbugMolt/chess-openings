<script setup lang="ts">
import { t } from '../i18n'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { getPrivacyNotice, type PrivacyNotice } from '../api/privacy'

const notice = ref<PrivacyNotice>()
const loading = ref(false)
const unavailable = ref(false)
let request: AbortController | undefined
const contactLink = computed(() =>
  notice.value?.contactEmail
    ? `mailto:${encodeURIComponent(notice.value.contactEmail).replace('%40', '@')}`
    : undefined,
)
async function load(): Promise<void> {
  request?.abort()
  const current = new AbortController()
  request = current
  loading.value = true
  unavailable.value = false
  try {
    const result = await getPrivacyNotice(current.signal)
    if (request === current) notice.value = result
  } catch {
    if (!current.signal.aborted && request === current) unavailable.value = true
  } finally {
    if (request === current) loading.value = false
  }
}
onMounted(load)
onBeforeUnmount(() => {
  request?.abort()
  request = undefined
})
</script>

<template>
  <main id="main-content" class="page-width privacy-page">
    <span class="eyebrow">{{ t('PRIVACY NOTICE · UPDATED 16 OCTOBER 2026') }}</span>
    <h1>{{ t('Account privacy.') }}</h1>
    <p>
      {{
        t(
          'Learn chess openings with a private account and saved practice. We use no advertising or analytics trackers and do not sell your personal data.',
        )
      }}
    </p>
    <section aria-labelledby="publisher-heading">
      <h2 id="publisher-heading">{{ t('Who runs this service') }}</h2>
      <p>
        {{ t('This notice covers') }}
        <a href="https://chessopening.starbugstone.com/">chessopening.starbugstone.com</a>.
      </p>
      <p v-if="loading" role="status">{{ t('Loading publisher and retention details…') }}</p>
      <div v-else-if="unavailable" role="alert">
        <p>
          {{
            t(
              'We could not load the publisher and retention details. The account information below remains available.',
            )
          }}
        </p>
        <button class="privacy-retry" @click="load">{{ t('Try again') }}</button>
      </div>
      <template v-else-if="notice">
        <p v-if="notice.publisher">
          {{ t('The publisher and data controller is {p0}.', { p0: notice.publisher }) }}
        </p>
        <p v-if="contactLink">
          {{ t('For privacy questions or requests, email') }}
          <a :href="contactLink">{{ notice.contactEmail }}</a
          >.
        </p>
        <p v-if="!notice.configured" role="status">
          {{
            t(
              'Some publisher, privacy-contact or email-provider details have not yet been configured. This notice will display them here when supplied.',
            )
          }}
        </p>
        <p v-if="notice.development">
          {{
            t(
              'You are using the local development version. Use test details; development email is captured by Mailpit and is visible to the developer.',
            )
          }}
        </p>
      </template>
    </section>
    <h2>{{ t('What we keep and why') }}</h2>
    <p>
      {{
        t(
          'Accounts store an email address, password hash, internal ID, creation time and email-verification time. We need these to provide sign-in, account recovery and the private learning service you request. Providing those account features is based on performing our agreement with you. The course list and opening reference can be browsed without an account.',
        )
      }}
    </p>
    <p>
      {{
        t(
          'Finished practices store the taught course, revision, line, colour, completion time, hint count and a random attempt identifier that prevents duplicate saves. New practices also record a compact summary of tries and hints at the taught decisions, and whether you used a hint, retry or takeback. These power your private practice history and review suggestions. Earlier completions have unknown recall; we do not invent a rating from them.',
        )
      }}
    </p>
    <p>
      {{
        t(
          'Security limits and operational logs help prevent abuse and diagnose failures, based on our legitimate interest in keeping the service secure. Limits store keyed hashes of email and client-network addresses. Hosting access logs may contain IP addresses and requested paths. Passwords and email-link secrets stay out of request URLs. We do not collect a display name, date of birth or last-login history.',
        )
      }}
    </p>
    <h2>{{ t('How long we keep data') }}</h2>
    <dl class="retention-list">
      <dt>{{ t('Accounts and practice') }}</dt>
      <dd>
        {{
          t(
            'Until you delete your account. There is no automatic expiry based on inactivity and no automatic age-based deletion of unverified accounts. If necessary, verify your email first, then sign in and delete the account.',
          )
        }}
      </dd>
      <dt>{{ t('Email links') }}</dt>
      <dd>
        {{
          t(
            'Verification links expire after 24 hours; password-reset links after one hour. Used or replaced links are removed according to the account flow. Expired records are removed during requests and daily maintenance.',
          )
        }}
      </dd>
      <dt>{{ t('Request limits') }}</dt>
      <dd>
        {{
          t(
            'Usable for their 15-minute, hourly or daily security window. Expired records are removed during account requests and daily maintenance.',
          )
        }}
      </dd>
      <dt>{{ t('Application logs') }}</dt>
      <dd v-if="notice">
        {{
          t(
            "Dated application log files become eligible for cleanup after {p0} days without new entries. Hosting access logs follow the hosting operator's separate log policy.",
            { p0: notice.applicationLogDays },
          )
        }}
      </dd>
      <dd v-else>
        {{
          t(
            "The configured application-log retention period is temporarily unavailable. Hosting access logs follow the hosting operator's separate log policy.",
          )
        }}
      </dd>
      <dt>{{ t('O2Switch recovery backups') }}</dt>
      <dd v-if="notice">
        {{
          t('Retained for {p0} days in the hosting backup cycle.', { p0: notice.hostingBackupDays })
        }}
      </dd>
      <dd v-else>
        {{ t('Retained for 30 days in the hosting backup cycle, as confirmed by the publisher.') }}
      </dd>
      <dt>{{ t('Application deployment snapshots') }}</dt>
      <dd v-if="notice">
        {{
          t(
            "Up to three successful database snapshots. They become eligible for cleanup after {p0} days, separately from O2Switch's backups.",
            { p0: notice.deploymentBackupDays },
          )
        }}
      </dd>
      <dd v-else>
        {{
          t(
            'Separate from hosting backups; their configured retention details are temporarily unavailable.',
          )
        }}
      </dd>
    </dl>
    <p>
      {{
        t(
          'Eligible records are cleaned in daily batches. An outage, a backlog or a busy file can delay removal. Busy files are retried on a later run.',
        )
      }}
    </p>
    <h2>{{ t('Cookies and browser preferences') }}</h2>
    <p>
      {{
        t(
          'A first-party session cookie is necessary for sign-in and request security. It is HttpOnly and SameSite=Lax, with Secure required in production. Sessions expire after two hours of inactivity or eight hours from sign-in. Stale session files become eligible for cleanup after eight hours without activity. Authentication tokens stay out of browser local storage. Sound and language preferences are stored only in your browser.',
        )
      }}
    </p>
    <p>
      {{
        t(
          "“Remember me” is optional. It adds a signed HttpOnly cookie valid for 30 days, renewed when it restores sign-in, with the same SameSite and production Secure protections. Signing out clears this browser's cookie. Changing your password invalidates existing remembered sign-ins on every device.",
        )
      }}
    </p>
    <h2>{{ t('Hosting, email and access') }}</h2>
    <p>
      {{ t('The application is hosted by') }}
      <a
        href="https://www.o2switch.fr/infrastructures/"
        target="_blank"
        rel="noopener noreferrer"
        >{{ t('o2switch in France') }}</a
      >{{
        t(
          '. The publisher and hosting operator can access data as needed to run and secure the service.',
        )
      }}
    </p>
    <p v-if="notice?.emailProvider">
      {{
        t(
          'Account email is delivered through {p0}. It receives the destination address and account-email content to deliver verification and recovery messages.',
          { p0: notice.emailProvider },
        )
      }}
    </p>
    <p v-else>
      {{
        t(
          'The account-email provider has not yet been supplied in the public configuration. Its name and any relevant international-transfer information must be confirmed by the publisher.',
        )
      }}
    </p>
    <p>
      {{
        t(
          'There are no social accounts, advertising partners or third-party analytics. Review suggestions use your recorded practice; they do not make decisions with legal or similarly significant effects.',
        )
      }}
    </p>
    <h2>{{ t('Your choices and rights') }}</h2>
    <p>
      {{
        t(
          'In Account, download your details, every saved practice and email-link metadata as JSON, or permanently delete the account, its email links and all practice. Both actions require your current password. Deletion signs you out; other browsers cannot restore the deleted account.',
        )
      }}
    </p>
    <RouterLink class="primary-link" to="/account">{{ t('Manage your account') }}</RouterLink>
    <p>
      {{
        t(
          'You can request access, correction, deletion or restriction of your data, and portability or object to processing where applicable. Use the privacy-contact address above; we normally respond within one month. You can also',
        )
      }}
      <a
        href="https://www.cnil.fr/fr/adresser-une-plainte"
        target="_blank"
        rel="noopener noreferrer"
        >{{ t('contact the CNIL') }}</a
      >
      {{ t('or your local data-protection authority.') }}
    </p>
    <h2>{{ t('Deletion and backups') }}</h2>
    <p>
      {{
        t(
          'Account deletion removes live account and practice records immediately. It does not rewrite existing backups, logs or temporary security records. Recovery copies stay restricted to recovery. Account deletions made after a snapshot must be reapplied before a restored service reopens.',
        )
      }}
    </p>
    <p>
      {{
        t(
          'A hosting backup can contain an older deployment snapshot. These retention periods can add together, so we do not promise that every recovery copy disappears 30 days after account deletion. Captured development email is also outside live-account deletion.',
        )
      }}
    </p>
    <RouterLink class="primary-link" to="/">{{ t('Back to home') }}</RouterLink>
  </main>
</template>

<style scoped>
.privacy-page a:not(.primary-link) {
  text-decoration: underline;
  text-underline-offset: 3px;
  overflow-wrap: anywhere;
}
.privacy-retry {
  min-height: 44px;
  padding: 10px 16px;
  border: 1px solid #afbaa4;
  border-radius: 4px;
  background: #f4f4e9;
  color: var(--ink);
}
.retention-list {
  display: grid;
  grid-template-columns: minmax(150px, 1fr) minmax(0, 2fr);
  gap: 14px 24px;
  font-size: 14px;
  line-height: 1.7;
}
.retention-list dt {
  font-weight: 600;
}
.retention-list dd {
  margin: 0;
  color: var(--muted);
}
@media (max-width: 680px) {
  .retention-list {
    grid-template-columns: minmax(0, 1fr);
    gap: 8px;
  }
  .retention-list dd {
    margin-bottom: 14px;
  }
}
</style>
