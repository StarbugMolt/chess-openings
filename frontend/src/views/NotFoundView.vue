<script setup lang="ts">
import { t } from '../i18n'
</script>

<template>
  <main id="main-content" class="page-width empty-page">
    <p class="eyebrow">{{ t('Page not found') }}</p>
    <h1>{{ t('Back to the opening.') }}</h1>
    <p>{{ t('This page is not available.') }}</p>
    <div class="status-actions">
      <RouterLink class="primary-link" to="/">{{ t('Return home') }}</RouterLink>
      <RouterLink to="/learn">{{ t('Browse lessons') }}</RouterLink>
    </div>
  </main>
</template>
