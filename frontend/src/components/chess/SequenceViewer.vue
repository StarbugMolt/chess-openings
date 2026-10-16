<script setup lang="ts">
import { t, message } from '../../i18n'
import { computed, ref, watch } from 'vue'
import type { Key } from '@lichess-org/chessground/types'
import ChessBoard from './ChessBoard.vue'
import { replaySequence } from '../../domain/chess/sequenceReplay'

const props = defineProps<{ pgn: string; name: string }>()
const frames = computed(() => replaySequence(props.pgn))
const index = ref(0)
watch(
  frames,
  (value) => {
    index.value = value ? value.length - 1 : 0
  },
  { immediate: true },
)
const last = computed(() => (frames.value?.length ?? 1) - 1)
const frame = computed(() => frames.value?.[index.value])
const lastMove = computed(() => frame.value?.lastMove as [Key, Key] | undefined)
const status = computed(() =>
  index.value === 0
    ? 'Starting position'
    : message('Position after {p0}', { p0: frame.value?.label }),
)
function go(target: number) {
  index.value = Math.max(0, Math.min(last.value, target))
}
function key(event: KeyboardEvent) {
  const moves: Record<string, number> = {
    ArrowLeft: index.value - 1,
    ArrowRight: index.value + 1,
    Home: 0,
    End: last.value,
  }
  const target = moves[event.key]
  if (target === undefined) return
  event.preventDefault()
  go(target)
}
</script>

<template>
  <div
    v-if="frames && frame"
    class="sequence-viewer"
    role="group"
    :aria-label="t('{p0} on a board', { p0: name })"
  >
    <div class="sequence-board">
      <ChessBoard
        :fen="frame.fen"
        :last-move="lastMove"
        :in-check="frame.check"
        :turn-color="frame.fen.split(' ')[1] === 'w' ? 'white' : 'black'"
        read-only
        :label="t('{name}: {status}', { name, status })"
      />
    </div>
    <div class="sequence-side">
      <p class="sequence-status" aria-live="polite">{{ t(status) }}</p>
      <div class="sequence-controls" @keydown="key">
        <button
          type="button"
          :disabled="index === 0"
          :aria-label="t('Starting position')"
          @click="go(0)"
        >
          <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 2h2v12H3zM14 2v12L6 8z" /></svg>
        </button>
        <button
          type="button"
          :disabled="index === 0"
          :aria-label="t('Previous move')"
          @click="go(index - 1)"
        >
          <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M12 2v12L4 8z" /></svg>
        </button>
        <button
          type="button"
          :disabled="index === last"
          :aria-label="t('Next move')"
          @click="go(index + 1)"
        >
          <svg viewBox="0 0 16 16" aria-hidden="true"><path d="M4 2v12l8-6z" /></svg>
        </button>
        <button
          type="button"
          :disabled="index === last"
          :aria-label="t('Final position')"
          @click="go(last)"
        >
          <svg viewBox="0 0 16 16" aria-hidden="true">
            <path d="M11 2h2v12h-2zM2 2v12l8-6z" />
          </svg>
        </button>
      </div>
      <ol class="sequence-moves" :aria-label="t('Moves in this sequence')" @keydown="key">
        <li v-for="(item, position) in frames.slice(1)" :key="position">
          <button
            type="button"
            :aria-current="index === position + 1 ? 'step' : undefined"
            @click="go(position + 1)"
          >
            {{ item.label }}
          </button>
        </li>
      </ol>
      <p class="sequence-tip">
        {{ t('Step through with the buttons, click any move, or use the arrow keys.') }}
      </p>
    </div>
  </div>
  <p v-else class="sequence-unavailable">{{ t('This sequence cannot be shown on a board.') }}</p>
</template>

<style scoped>
.sequence-viewer {
  display: grid;
  grid-template-columns: minmax(220px, 340px) minmax(200px, 1fr);
  gap: 28px;
  margin-bottom: 16px;
}
.sequence-board {
  width: 100%;
  padding: 3px;
}
.sequence-status {
  margin: 0 0 10px;
  font-size: 17px;
  font-weight: 750;
}
.sequence-controls {
  display: flex;
  gap: 6px;
}
.sequence-controls button {
  display: grid;
  place-items: center;
  min-width: 48px;
  padding: 0;
}
.sequence-controls svg {
  width: 14px;
  height: 14px;
  fill: currentColor;
}
.sequence-moves {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 0;
  margin: 14px 0 0;
  list-style: none;
}
.sequence-moves button {
  min-height: 36px;
  padding: 4px 8px;
  border-color: transparent;
  background: transparent;
  font-size: 15px;
  font-weight: 650;
}
.sequence-moves button:hover {
  border-color: var(--rule-strong);
}
.sequence-moves button[aria-current='step'] {
  border-color: var(--ink);
  background: var(--marker);
}
.sequence-tip,
.sequence-unavailable {
  color: var(--pencil);
  font-size: 14px;
}
@media (max-width: 640px) {
  .sequence-viewer {
    grid-template-columns: 1fr;
  }
  .sequence-board {
    max-width: 340px;
  }
}
</style>
