<script setup lang="ts">
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
  index.value === 0 ? 'Starting position' : `Position after ${frame.value?.label}`,
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
    :aria-label="`${name} on a board`"
  >
    <div class="sequence-board">
      <ChessBoard
        :fen="frame.fen"
        :last-move="lastMove"
        :in-check="frame.check"
        :turn-color="frame.fen.split(' ')[1] === 'w' ? 'white' : 'black'"
        read-only
        :label="`${name}: ${status}`"
      />
    </div>
    <div class="sequence-side">
      <p class="sequence-status" aria-live="polite">{{ status }}</p>
      <div class="sequence-controls" @keydown="key">
        <button type="button" :disabled="index === 0" aria-label="Starting position" @click="go(0)">
          ⏮
        </button>
        <button
          type="button"
          :disabled="index === 0"
          aria-label="Previous move"
          @click="go(index - 1)"
        >
          ◀
        </button>
        <button
          type="button"
          :disabled="index === last"
          aria-label="Next move"
          @click="go(index + 1)"
        >
          ▶
        </button>
        <button
          type="button"
          :disabled="index === last"
          aria-label="Final position"
          @click="go(last)"
        >
          ⏭
        </button>
      </div>
      <ol class="sequence-moves" aria-label="Moves in this sequence" @keydown="key">
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
        Step through with the buttons, click any move, or use the arrow keys.
      </p>
    </div>
  </div>
  <p v-else class="sequence-unavailable">This sequence cannot be shown on a board.</p>
</template>

<style scoped>
.sequence-viewer {
  display: grid;
  grid-template-columns: minmax(220px, 340px) minmax(200px, 1fr);
  gap: 24px;
  margin-block: 16px;
}
.sequence-board {
  width: 100%;
}
.sequence-status {
  font-weight: 650;
  font-size: 14px;
  margin: 0 0 10px;
  color: var(--ink);
}
.sequence-controls {
  display: flex;
  gap: 6px;
}
.sequence-controls button {
  min-width: 44px;
  min-height: 44px;
  padding: 8px;
  font-size: 15px;
}
.sequence-moves {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 0;
  margin: 14px 0 0;
  list-style: none;
}
.sequence-moves li {
  padding: 0;
  border: 0;
}
.sequence-moves button {
  padding: 6px 8px;
  min-height: 36px;
  font-size: 13px;
  font-family: ui-monospace, monospace;
  background: transparent;
  border: 1px solid transparent;
}
.sequence-moves button[aria-current='step'] {
  background: var(--ink);
  border-color: var(--ink);
  color: white;
}
.sequence-tip,
.sequence-unavailable {
  font-size: 12px;
  color: var(--muted);
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
