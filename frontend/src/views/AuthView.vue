<script setup lang="ts">
import { t, message as textMessage, setLocale, supportedLocale, type Text } from '../i18n'
import { computed, nextTick, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { accountAction, AccountError, type AccountAction } from '../api/account'
import { useAccountStore } from '../stores/account'

const route = useRoute()

const router = useRouter()
const account = useAccountStore()
const email = ref('')
const password = ref('')
const showPassword = ref(false)
const statusElement = ref<HTMLElement>()
const rememberMe = ref(false)
const token = ref('')
const busy = ref(false)
const error = ref<Text>('')
const message = ref<Text>('')
const mode = computed(() => String(route.name))
const titles: Record<string, string> = {
  login: 'Sign in.',
  register: 'Create an account.',
  'forgot-password': 'Reset your password.',
  'verify-email': 'Verify your email.',
  'reset-password': 'Choose a new password.',
  'resend-verification': 'Resend verification email.',
}
const emailRequired = computed(() =>
  ['login', 'register', 'forgot-password', 'resend-verification'].includes(mode.value),
)
const passwordRequired = computed(() =>
  ['login', 'register', 'reset-password'].includes(mode.value),
)
const newPassword = computed(() => mode.value !== 'login')
const buttonLabel = computed(
  () =>
    ({
      login: 'Sign in',
      register: 'Create account',
      'forgot-password': 'Send reset link',
      'verify-email': 'Verify email',
      'reset-password': 'Save new password',
      'resend-verification': 'Resend verification',
    })[mode.value] ?? 'Continue',
)

watch(
  [() => route.name, () => route.query.lang],
  () => {
    if (typeof route.query.lang === 'string' && supportedLocale(route.query.lang))
      setLocale(route.query.lang)
    password.value = ''
    showPassword.value = false
    error.value = ''
    message.value = ''
    token.value = new URLSearchParams(route.hash.slice(1)).get('token') ?? ''
    if (route.hash) void router.replace({ path: route.path, query: route.query, hash: '' })
  },
  { immediate: true },
)

async function submit(): Promise<void> {
  busy.value = true
  error.value = ''
  message.value = ''
  try {
    if (mode.value === 'login') {
      await account.login(email.value, password.value, rememberMe.value)
      const next = typeof route.query.next === 'string' ? route.query.next : ''
      await router.replace(
        /^(?:\/(?:learn|train)(?:\/|$)|\/(?:account|progress)$)/.test(next) && !next.includes('\\')
          ? next
          : '/learn',
      )
    } else {
      const action = (
        {
          register: 'register',
          'forgot-password': 'request-reset',
          'resend-verification': 'request-verification',
          'verify-email': 'verify-email',
          'reset-password': 'reset-password',
        } as Record<string, AccountAction>
      )[mode.value]
      if (!action) throw new Error('This form is unavailable.')
      const body =
        action === 'verify-email'
          ? { token: token.value }
          : action === 'reset-password'
            ? { token: token.value, password: password.value }
            : action === 'register'
              ? { email: email.value, password: password.value }
              : { email: email.value }
      const result = await accountAction(action, body)
      message.value =
        action === 'verify-email'
          ? 'Email confirmed. You can now sign in.'
          : action === 'register'
            ? 'Check your email to confirm your address, then sign in with the password you just chose.'
            : typeof result.message === 'string'
              ? result.message
              : 'Done.'
      if (action === 'reset-password' || action === 'verify-email') token.value = ''
      await nextTick()
      statusElement.value?.focus()
    }
  } catch (failure) {
    error.value =
      failure instanceof AccountError
        ? failure.retryAfter
          ? textMessage('{error} {retry}', {
              error: failure.message,
              retry: textMessage(
                Math.ceil(failure.retryAfter / 60) === 1
                  ? 'Try again in about {minutes} minute.'
                  : 'Try again in about {minutes} minutes.',
                {
                  minutes: Math.ceil(failure.retryAfter / 60),
                },
              ),
            })
          : failure.message
        : 'The account service is unavailable. Please try again.'
  } finally {
    password.value = ''
    busy.value = false
  }
}
</script>

<template>
  <main id="main-content" class="page-width account-page">
    <div class="account-intro">
      <h1>{{ t(titles[mode]) }}</h1>
      <p v-if="mode === 'register'">
        {{ t('Save your lesson practice. Just your email and a password.') }}
      </p>
      <p v-else-if="mode === 'login'">{{ t('Sign in with your verified email address.') }}</p>
      <p v-else-if="mode === 'verify-email'">
        {{ t('Confirm your email below. If you didn’t create this account, you can ignore this.') }}
      </p>
      <p v-else-if="mode === 'reset-password'">
        {{ t('Use at least 12 characters. Saving your password signs out existing sessions.') }}
      </p>
      <p v-else>
        {{ t('We’ll email a link if this address is eligible. Check your spam folder too.') }}
      </p>
    </div>
    <section class="account-card" :aria-label="t(titles[mode])">
      <p ref="statusElement" role="status" tabindex="-1" class="account-success">
        {{ t(message) }}
      </p>
      <p v-if="mode === 'login' && route.query.session === 'unavailable'" role="alert">
        {{
          t(
            'We could not check your sign-in. The account service may be unavailable. Please try again.',
          )
        }}
      </p>
      <p v-if="mode === 'login' && route.query.session === 'cookies'" role="alert">
        {{
          t(
            'Sign-in succeeded, but your browser did not keep the session. Allow cookies for this site and try again.',
          )
        }}
      </p>
      <form v-if="!message" @submit.prevent="submit">
        <p v-if="error" role="alert" class="account-error">{{ t(error) }}</p>
        <label v-if="emailRequired" for="account-email"
          >{{ t('Email address') }}
          <input
            id="account-email"
            v-model="email"
            type="email"
            autocomplete="email"
            maxlength="254"
            required
            :disabled="busy"
          />
        </label>
        <label v-if="passwordRequired" for="account-password"
          ><span id="password-label">{{ newPassword ? t('New password') : t('Password') }}</span>
          <input
            id="account-password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            :autocomplete="newPassword ? 'new-password' : 'current-password'"
            :minlength="newPassword ? 12 : 1"
            maxlength="256"
            required
            :disabled="busy"
            aria-describedby="password-help"
            aria-labelledby="password-label"
          />
          <small id="password-help">{{
            newPassword
              ? t('12–256 characters, up to 256 bytes. Spaces and password managers welcome.')
              : t('Use the password you chose when you registered.')
          }}</small>
        </label>
        <button
          v-if="passwordRequired"
          type="button"
          class="password-visibility"
          :aria-pressed="showPassword"
          aria-controls="account-password"
          @click="showPassword = !showPassword"
        >
          {{ showPassword ? t('Hide password') : t('Show password') }}
        </button>
        <label v-if="mode === 'login'" class="remember-option">
          <input v-model="rememberMe" type="checkbox" :disabled="busy" />
          <span
            >{{ t('Remember me for 30 days')
            }}<small>{{ t('Use on your own device. Sign out on shared computers.') }}</small></span
          >
        </label>
        <p v-if="mode === 'register'" class="account-disclosure">
          {{ t('By creating an account, you acknowledge the') }}
          <RouterLink to="/privacy">{{ t('account privacy notice') }}</RouterLink
          >. {{ t('Email verification is required before sign-in.') }}
        </p>
        <p v-if="!emailRequired && !token" class="account-error">
          {{ t('Open the complete link from your email, or request a new one below.') }}
        </p>
        <button class="primary-link" type="submit" :disabled="busy || (!emailRequired && !token)">
          {{ busy ? t('Please wait…') : t(buttonLabel) }}
        </button>
      </form>
      <div class="account-links">
        <RouterLink v-if="mode !== 'login'" to="/login">{{ t('Sign in') }}</RouterLink>
        <RouterLink v-if="mode === 'login'" to="/register">{{ t('Create an account') }}</RouterLink>
        <RouterLink
          v-if="mode !== 'forgot-password' && mode !== 'register'"
          to="/forgot-password"
          >{{ t('Reset password') }}</RouterLink
        >
        <RouterLink
          v-if="mode === 'login' || mode === 'verify-email' || mode === 'register'"
          to="/resend-verification"
          >{{ t('Resend verification email') }}</RouterLink
        >
      </div>
    </section>
  </main>
</template>
