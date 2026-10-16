<script setup lang="ts">
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
const error = ref('')
const message = ref('')
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
  () => route.name,
  () => {
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
        /^(?:\/(?:learn|train)(?:\/|$)|\/(?:account|progress)$|\/admin(?:\?|$))/.test(next) &&
          !next.includes('\\')
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
        ? failure.message +
          (failure.retryAfter
            ? ` Try again in about ${Math.ceil(failure.retryAfter / 60)} minute${failure.retryAfter > 60 ? 's' : ''}.`
            : '')
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
      <span class="eyebrow">YOUR CHESS OPENINGS ACCOUNT</span>
      <h1>{{ titles[mode] }}</h1>
      <p v-if="mode === 'register'">Save your lesson practice. Just your email and a password.</p>
      <p v-else-if="mode === 'login'">Sign in with your verified email address.</p>
      <p v-else-if="mode === 'verify-email'">
        Confirm your email below. If you didn’t create this account, you can ignore this.
      </p>
      <p v-else-if="mode === 'reset-password'">
        Use at least 12 characters. Saving your password signs out existing sessions.
      </p>
      <p v-else>We’ll email a link if this address is eligible. Check your spam folder too.</p>
    </div>
    <section class="account-card" :aria-label="titles[mode]">
      <p ref="statusElement" role="status" tabindex="-1" class="account-success">{{ message }}</p>
      <p v-if="mode === 'login' && route.query.session === 'unavailable'" role="alert">
        We could not check your sign-in. The account service may be unavailable. Please try again.
      </p>
      <p v-if="mode === 'login' && route.query.session === 'cookies'" role="alert">
        Sign-in succeeded, but your browser did not keep the session. Allow cookies for this site
        and try again.
      </p>
      <form v-if="!message" @submit.prevent="submit">
        <p v-if="error" role="alert" class="account-error">{{ error }}</p>
        <label v-if="emailRequired" for="account-email"
          >Email address
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
          ><span id="password-label">{{ newPassword ? 'New password' : 'Password' }}</span>
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
              ? '12–256 characters, up to 256 bytes. Spaces and password managers welcome.'
              : 'Use the password you chose when you registered.'
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
          {{ showPassword ? 'Hide password' : 'Show password' }}
        </button>
        <label v-if="mode === 'login'" class="remember-option">
          <input v-model="rememberMe" type="checkbox" :disabled="busy" />
          <span
            >Remember me for 30 days<small
              >Use on your own device. Sign out on shared computers.</small
            ></span
          >
        </label>
        <p v-if="mode === 'register'" class="account-disclosure">
          By creating an account, you acknowledge the
          <RouterLink to="/privacy">account privacy notice</RouterLink>. Email verification is
          required before sign-in.
        </p>
        <p v-if="!emailRequired && !token" class="account-error">
          Open the complete link from your email, or request a new one below.
        </p>
        <button class="primary-link" type="submit" :disabled="busy || (!emailRequired && !token)">
          {{ busy ? 'Please wait…' : buttonLabel }} <span aria-hidden="true">→</span>
        </button>
      </form>
      <div class="account-links">
        <RouterLink v-if="mode !== 'login'" to="/login">Sign in</RouterLink>
        <RouterLink v-if="mode === 'login'" to="/register">Create an account</RouterLink>
        <RouterLink v-if="mode !== 'forgot-password' && mode !== 'register'" to="/forgot-password"
          >Reset password</RouterLink
        >
        <RouterLink
          v-if="mode === 'login' || mode === 'verify-email' || mode === 'register'"
          to="/resend-verification"
          >Resend verification email</RouterLink
        >
      </div>
    </section>
  </main>
</template>
