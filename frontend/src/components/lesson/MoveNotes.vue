<script setup lang="ts">
import { t, type Text } from '../../i18n'
export interface MoveNote {
  /** Who moved, for example "You played" or "Black replied". */
  who: Text
  /** Numbered SAN, for example "5. d3" or "5... d6". */
  label: string
  explanation: string
}

withDefaults(defineProps<{ notes: MoveNote[]; heading?: string | undefined }>(), {
  heading: 'Why these moves',
})
</script>

<template>
  <section v-if="notes.length" class="move-notes" :aria-label="t(heading)">
    <span class="eyebrow">{{ t(heading).toLocaleUpperCase() }}</span>
    <article
      v-for="(note, index) in notes"
      :key="note.label"
      :class="{ latest: index === notes.length - 1 }"
    >
      <p class="move-notes-move">
        {{ t(note.who) }} <strong>{{ note.label }}</strong>
      </p>
      <p class="move-notes-text">{{ t(note.explanation) }}</p>
    </article>
  </section>
</template>

<style scoped>
.move-notes {
  display: grid;
  gap: 10px;
  margin-block: 18px;
}
.move-notes article {
  padding: 12px 14px;
  border-left: 3px solid #c9cfb8;
  background: #f1f2e8;
  border-radius: 0 6px 6px 0;
}
.move-notes article.latest {
  border-left-color: var(--green, #4f6b45);
  background: #e9eddc;
}
.move-notes-move {
  margin: 0 0 4px;
  font-size: 12px;
  color: var(--muted);
}
.move-notes-move strong {
  color: var(--ink);
  font-family: ui-monospace, monospace;
  font-size: 13px;
}
.move-notes-text {
  margin: 0;
  font-size: 15px;
  line-height: 1.65;
  color: var(--ink);
}
</style>
