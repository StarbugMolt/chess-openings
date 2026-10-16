<script setup lang="ts">
import { t } from './i18n'
import LanguageSelector from './components/LanguageSelector.vue'
import { onMounted } from 'vue'
import { useAccountStore } from './stores/account'
const account = useAccountStore()
onMounted(() => {
  void account.load()
})
const isDevelopment = import.meta.env.DEV
</script>

<template>
  <a class="skip-link" href="#main-content">{{ t('Skip to content') }}</a>
  <header class="site-header page-width">
    <RouterLink class="wordmark" to="/" :aria-label="t('Chess Openings home')">
      <span class="brand-mark" aria-hidden="true" />
      <span class="wordmark-text">{{ t('Chess Openings') }}</span>
    </RouterLink>
    <nav :aria-label="t('Main navigation')">
      <RouterLink class="learn-link" to="/learn">{{ t('Learn') }}</RouterLink>
      <RouterLink class="catalogue-link" to="/openings">{{ t('Openings') }}</RouterLink>
      <RouterLink v-if="account.user?.admin" class="admin-link" to="/admin">{{
        t('Admin')
      }}</RouterLink>
      <RouterLink class="account-link" :to="account.user ? '/account' : '/login'">{{
        account.user ? t('Account') : t('Sign in')
      }}</RouterLink>
      <LanguageSelector />
      <span class="preview-label">{{ t('Early preview') }}</span>
    </nav>
  </header>
  <p v-if="account.unavailable" class="session-status page-width" role="status">
    {{ t('We could not check your sign-in.') }}
    <button @click="account.load(true)">{{ t('Try again') }}</button>
  </p>
  <RouterView />
  <footer class="site-footer page-width">
    <span>{{ t('A little theory. A lot more confidence.') }}</span>
    <RouterLink to="/privacy">{{ t('Privacy') }}</RouterLink>
    <a href="/third-party-notices.txt">{{ t('Dependency licences') }}</a>
    <RouterLink v-if="isDevelopment" class="developer-link" to="/dev/board">{{
      t('Developer board')
    }}</RouterLink>
    <a href="https://github.com/Starbugstone/chess-openings">{{ t('Open source under GPLv3') }}</a>
  </footer>
</template>

<style scoped>
/* Global mobile rules hide unlisted header links; the admin link stays reachable. */
@media (max-width: 680px) {
  .site-header nav > a.admin-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 44px;
    min-height: 44px;
  }
}
</style>
