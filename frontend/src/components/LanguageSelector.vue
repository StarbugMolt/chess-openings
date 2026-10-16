<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { languages, locale, setLocale, t } from '../i18n'

const open = ref(false)
const root = ref<HTMLElement>()
const trigger = ref<HTMLButtonElement>()
const current = computed(() => languages.find((language) => language.code === locale.value)!)

async function toggle() {
  open.value = !open.value
  if (open.value) {
    await nextTick()
    root.value?.querySelector<HTMLButtonElement>('[aria-pressed="true"]')?.focus()
  }
}
function close() {
  open.value = false
  trigger.value?.focus()
}
function choose(value: string) {
  setLocale(value)
  close()
}
function outside(event: PointerEvent) {
  if (event.target instanceof Node && !root.value?.contains(event.target)) open.value = false
}
onMounted(() => document.addEventListener('pointerdown', outside))
onBeforeUnmount(() => document.removeEventListener('pointerdown', outside))
</script>

<template>
  <div ref="root" class="language-selector" @keydown.esc.stop.prevent="close">
    <button
      ref="trigger"
      type="button"
      class="language-trigger"
      :aria-label="t('Language: {language}', { language: current.name })"
      :aria-expanded="open"
      aria-controls="language-options"
      @click="toggle"
    >
      <span aria-hidden="true">◎</span> {{ current.name }} <span aria-hidden="true">▾</span>
    </button>
    <div
      v-if="open"
      id="language-options"
      class="language-options"
      role="group"
      :aria-label="t('Choose language')"
    >
      <button
        v-for="language in languages"
        :key="language.code"
        type="button"
        :lang="language.code"
        :aria-pressed="locale === language.code"
        @click="choose(language.code)"
      >
        {{ language.name }} <span v-if="locale === language.code" aria-hidden="true">✓</span>
      </button>
    </div>
  </div>
</template>

<style scoped>
.language-selector {
  position: relative;
  font-size: 12px;
}
.language-selector button {
  min-height: 44px;
  padding: 8px 10px;
  border: 1px solid var(--line);
  border-radius: 4px;
  background: var(--paper, #faf9f4);
  color: var(--ink);
  font: inherit;
}
.language-trigger {
  display: flex;
  gap: 8px;
  align-items: center;
}
.language-options {
  position: absolute;
  z-index: 20;
  right: 0;
  top: calc(100% + 6px);
  min-width: 160px;
  padding: 5px;
  border: 1px solid var(--line);
  border-radius: 6px;
  background: #faf9f4;
  box-shadow: 0 6px 18px #253e3520;
  display: grid;
  gap: 4px;
}
.language-options button {
  display: flex;
  justify-content: space-between;
  text-align: left;
  border-color: transparent;
}
.language-options button[aria-pressed='true'] {
  background: #e9eddc;
}
@media (max-width: 680px) {
  .language-selector {
    position: static;
  }
}
</style>
