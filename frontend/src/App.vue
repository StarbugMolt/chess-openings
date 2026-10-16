<script setup lang="ts">
import { onMounted } from 'vue'
import { useAccountStore } from './stores/account'
const account = useAccountStore()
onMounted(() => {
  void account.load()
})
const isDevelopment = import.meta.env.DEV
</script>

<template>
  <a class="skip-link" href="#main-content">Skip to content</a>
  <header class="site-header page-width">
    <RouterLink class="wordmark" to="/" aria-label="Chess Openings home">
      <span class="brand-mark" aria-hidden="true" />
      <span class="wordmark-text">Chess Openings</span>
    </RouterLink>
    <nav aria-label="Main navigation">
      <RouterLink class="learn-link" to="/learn">Learn</RouterLink>
      <RouterLink class="catalogue-link" to="/openings">Openings</RouterLink>
      <RouterLink class="account-link" :to="account.user ? '/account' : '/login'">{{
        account.user ? 'Account' : 'Sign in'
      }}</RouterLink>
      <span class="preview-label">Early preview</span>
    </nav>
  </header>
  <p v-if="account.unavailable" class="session-status page-width" role="status">
    We could not check your sign-in. <button @click="account.load(true)">Try again</button>
  </p>
  <RouterView />
  <footer class="site-footer page-width">
    <span>A little theory. A lot more confidence.</span>
    <RouterLink to="/privacy">Privacy</RouterLink>
    <a href="/third-party-notices.txt">Dependency licences</a>
    <RouterLink v-if="isDevelopment" class="developer-link" to="/dev/board"
      >Developer board</RouterLink
    >
    <a href="https://github.com/Starbugstone/chess-openings">Open source under GPLv3</a>
  </footer>
</template>
