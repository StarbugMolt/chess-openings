<script setup lang="ts">
import { t } from '../i18n'
</script>

<template>
  <main id="main-content" class="page-width empty-page">
    <span class="eyebrow">{{ t('PAGE NOT FOUND') }}</span>
    <h1>{{ t('Back to the opening.') }}</h1>
    <p>{{ t('This page is not available.') }}</p>
    <RouterLink class="primary-link" to="/"
      >{{ t('Return home') }} <span aria-hidden="true">↗</span></RouterLink
    >
  </main>
</template>
