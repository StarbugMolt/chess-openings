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
    <p class="move-notes-heading">{{ t(heading) }}</p>
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
  gap: 14px;
  margin-block: 16px;
  padding-block: 14px;
  border-block: 1px solid var(--rule);
}
.move-notes-heading {
  margin: 0;
  color: var(--pencil);
  font-weight: 600;
}
.move-notes article {
  padding-left: 14px;
  border-left: 3px solid var(--rule);
}
.move-notes article.latest {
  border-left-color: var(--ink);
}
.move-notes-move {
  margin: 0 0 4px;
  color: var(--pencil);
  font-size: 15px;
}
.move-notes-move strong {
  padding: 1px 5px;
  color: var(--ink);
  font-size: 16px;
  font-weight: 750;
}
.latest .move-notes-move strong {
  background: var(--marker);
}
.move-notes-text {
  margin: 0;
  font-size: 16px;
  line-height: 1.55;
  color: var(--ink);
}
</style>
