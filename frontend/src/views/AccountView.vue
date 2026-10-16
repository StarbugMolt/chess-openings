<script setup lang="ts">
import { t, message, type Text } from '../i18n'
import { nextTick, onBeforeUnmount, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAccountStore } from '../stores/account'
import { AccountError, exportAccountData } from '../api/account'
const account = useAccountStore()
const router = useRouter()
const error = ref<Text>('')
const busy = ref<'logout' | 'export' | 'delete'>()
const exportPassword = ref('')
const deletePassword = ref('')
const downloadError = ref<Text>('')
const deletionError = ref<Text>('')
const downloaded = ref(false)
const showDeletion = ref(false)
const deletionConfirmed = ref(false)
const deleted = ref(false)
const deleteInput = ref<HTMLInputElement>()
const deleteTrigger = ref<HTMLButtonElement>()
const deletedHeading = ref<HTMLHeadingElement>()
let active = true
onBeforeUnmount(() => {
  active = false
  exportPassword.value = ''
  deletePassword.value = ''
})

async function recoverSession(cause: unknown): Promise<void> {
  if (cause instanceof AccountError && cause.status === 401) await account.load(true)
}

function failure(cause: unknown, fallback: string): Text {
  if (cause instanceof AccountError) {
    if (cause.status === 401) return 'Your session has ended. Sign in again to continue.'
    if (cause.status === 429)
      return cause.retryAfter
        ? message('Too many confirmation attempts. Try again in {p0} seconds.', {
            p0: cause.retryAfter,
          })
        : 'Too many confirmation attempts. Please try again later.'
    return cause.message
  }
  return fallback
}

async function download(): Promise<void> {
  if (busy.value) return
  busy.value = 'export'
  downloadError.value = ''
  downloaded.value = false
  const password = exportPassword.value
  exportPassword.value = ''
  try {
    const blob = await exportAccountData(password)
    if (!active) return
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = 'chess-openings-data.json'
    document.body.append(link)
    link.click()
    link.remove()
    window.setTimeout(() => URL.revokeObjectURL(url), 1000)
    downloaded.value = true
  } catch (cause) {
    await recoverSession(cause)
    if (active)
      downloadError.value = failure(cause, 'Could not download your data. Please try again.')
  } finally {
    busy.value = undefined
  }
}

async function toggleDeletion(show: boolean): Promise<void> {
  showDeletion.value = show
  deletePassword.value = ''
  deletionConfirmed.value = false
  deletionError.value = ''
  await nextTick()
  if (show) deleteInput.value?.focus()
  else deleteTrigger.value?.focus()
}

async function removeAccount(): Promise<void> {
  if (busy.value || !deletionConfirmed.value) return
  busy.value = 'delete'
  deletionError.value = ''
  const password = deletePassword.value
  deletePassword.value = ''
  try {
    await account.deleteAccount(password)
    if (!active) return
    deleted.value = true
    await nextTick()
    deletedHeading.value?.focus()
  } catch (cause) {
    await recoverSession(cause)
    if (active)
      deletionError.value = failure(
        cause,
        'Could not confirm deletion. Check your connection and try again.',
      )
  } finally {
    busy.value = undefined
  }
}

async function logout(): Promise<void> {
  if (busy.value) return
  busy.value = 'logout'
  error.value = ''
  try {
    await account.logout()
    await router.replace('/')
  } catch {
    error.value = 'Could not sign out. Please try again.'
  } finally {
    busy.value = undefined
  }
}
</script>
<template>
  <main id="main-content" class="page-width account-page">
    <template v-if="deleted">
      <div class="account-intro deletion-result">
        <span class="eyebrow">{{ t('YOUR ACCOUNT') }}</span>
        <h1 ref="deletedHeading" tabindex="-1">{{ t('Account deleted.') }}</h1>
        <p>{{ t('Your account and saved practice have been removed. You are signed out.') }}</p>
        <RouterLink class="primary-link" to="/">{{ t('Back to home') }}</RouterLink>
      </div>
    </template>
    <template v-else>
      <div class="account-intro">
        <span class="eyebrow">{{ t('YOUR ACCOUNT') }}</span>
        <h1>{{ t('Your account.') }}</h1>
        <p v-if="account.user">
          {{
            t(
              'Your email is verified. Choose a guided lesson to practise an opening, with automatic replies and saved completion.',
            )
          }}
        </p>
      </div>
      <section class="account-card" :aria-label="t('Account details')">
        <template v-if="account.user"
          ><h2>{{ t('Signed in') }}</h2>
          <p class="account-email">{{ account.user.email }}</p>
          <p>{{ t('Only you can access your account details.') }}</p></template
        >
        <p v-else-if="account.unavailable" role="status">
          {{ t('We could not check your sign-in. Please try again.') }}
        </p>
        <p v-else>
          {{ t('Your session has ended.') }}
          <RouterLink to="/login?next=/account">{{ t('Sign in again') }}</RouterLink>
          {{ t('to continue.') }}
        </p>
        <p v-if="error" role="alert" class="account-error">{{ t(error) }}</p>
        <RouterLink class="primary-link" to="/learn">{{ t('Choose a lesson →') }}</RouterLink>
        <RouterLink class="primary-link" to="/progress">{{
          t('Your practice & reviews →')
        }}</RouterLink>
        <button v-if="account.user" class="account-signout" :disabled="!!busy" @click="logout">
          {{ busy === 'logout' ? t('Signing out…') : t('Sign out') }}
        </button>
        <div class="account-links">
          <RouterLink to="/forgot-password">{{ t('Reset password') }}</RouterLink
          ><RouterLink to="/privacy">{{ t('Privacy notice') }}</RouterLink>
        </div>
      </section>
      <div v-if="account.user" class="account-data">
        <section class="account-card" aria-labelledby="download-heading">
          <h2 id="download-heading">{{ t('Download your data') }}</h2>
          <p id="download-description">
            {{
              t(
                'Get a JSON file with your account details and all saved practice, including earlier course versions. Passwords and sign-in secrets are excluded.',
              )
            }}
          </p>
          <form :aria-busy="busy === 'export'" @submit.prevent="download">
            <label for="export-password">{{ t('Current password for download') }}</label>
            <input
              id="export-password"
              v-model="exportPassword"
              type="password"
              autocomplete="current-password"
              required
              maxlength="256"
              :disabled="!!busy"
              aria-describedby="download-description"
            />
            <p v-if="downloadError" role="alert" class="account-error">{{ t(downloadError) }}</p>
            <p v-if="downloaded" role="status" class="account-success">
              {{ t('Your download is ready. Keep the file somewhere private.') }}
            </p>
            <button class="primary-link" :disabled="!!busy">
              {{ busy === 'export' ? t('Preparing download…') : t('Download my data') }}
            </button>
          </form>
        </section>
        <section class="account-card deletion-card" aria-labelledby="delete-heading">
          <h2 id="delete-heading">{{ t('Delete your account') }}</h2>
          <p id="delete-description">
            {{
              t(
                'Permanently remove your account and all saved practice. This cannot be undone. You can download your data first.',
              )
            }}
          </p>
          <button
            v-if="!showDeletion"
            ref="deleteTrigger"
            type="button"
            class="danger-button"
            :disabled="!!busy"
            @click="toggleDeletion(true)"
          >
            {{ t('Delete my account…') }}
          </button>
          <form v-else :aria-busy="busy === 'delete'" @submit.prevent="removeAccount">
            <label for="delete-password">{{ t('Current password for deletion') }}</label>
            <input
              id="delete-password"
              ref="deleteInput"
              v-model="deletePassword"
              type="password"
              autocomplete="current-password"
              required
              maxlength="256"
              :disabled="!!busy"
              aria-describedby="delete-description"
            />
            <label class="remember-option deletion-confirmation">
              <input v-model="deletionConfirmed" type="checkbox" required :disabled="!!busy" />
              <span>{{
                t('I understand that my account and saved practice will be permanently deleted.')
              }}</span>
            </label>
            <p v-if="deletionError" role="alert" class="account-error">{{ t(deletionError) }}</p>
            <button class="danger-button" :disabled="!!busy || !deletionConfirmed">
              {{ busy === 'delete' ? t('Deleting account…') : t('Permanently delete my account') }}
            </button>
            <button
              type="button"
              class="cancel-button"
              :disabled="!!busy"
              @click="toggleDeletion(false)"
            >
              {{ t('Keep my account') }}
            </button>
          </form>
          <p class="account-disclosure">
            <RouterLink to="/privacy">{{
              t('Read how deletion affects backups and security records.')
            }}</RouterLink>
          </p>
        </section>
      </div>
    </template>
  </main>
</template>

<style scoped>
.account-data {
  grid-column: 1 / -1;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 24px;
}
.account-data h2 {
  margin-top: 0;
  font-size: 28px;
}
.account-data form {
  gap: 14px;
}
.account-data label,
.account-data button,
.deletion-card .account-disclosure {
  font-size: 14px;
}
.account-data button,
.account-data input[type='password'],
.deletion-confirmation {
  min-height: 44px;
}
.danger-button,
.cancel-button {
  padding: 12px 16px;
  border: 1px solid #943a2c;
  border-radius: 4px;
  background: #fff7f4;
  color: #7d3025;
  line-height: 1.5;
}
.cancel-button {
  border-color: #afbaa4;
  background: #f4f4e9;
  color: var(--ink);
}
.deletion-card .account-disclosure {
  margin-top: 22px;
}
.deletion-result {
  grid-column: 1 / -1;
}
@media (max-width: 680px) {
  .account-data {
    grid-template-columns: 1fr;
  }
}
</style>
