<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { Chessground } from '@lichess-org/chessground'
import type { Api } from '@lichess-org/chessground/api'
import type { Config } from '@lichess-org/chessground/config'
import type { Color, Key } from '@lichess-org/chessground/types'
import '@lichess-org/chessground/assets/chessground.base.css'
import '@lichess-org/chessground/assets/chessground.cburnett.css'

const props = withDefaults(
  defineProps<{
    fen: string
    orientation?: Color
    turnColor?: Color
    destinations?: Map<Key, Key[]>
    lastMove?: [Key, Key] | undefined
    inCheck?: boolean
    readOnly?: boolean
    coordinates?: boolean
    interactionDisabled?: boolean
    hintMove?: [Key, Key] | undefined
    hintPieceSelected?: boolean
    label?: string
  }>(),
  {
    orientation: 'white',
    turnColor: 'white',
    readOnly: false,
    coordinates: true,
    interactionDisabled: false,
    hintMove: undefined,
    hintPieceSelected: false,
    label: 'Chessboard',
    destinations: () => new Map(),
    lastMove: undefined,
    inCheck: false,
  },
)
const emit = defineEmits<{ move: [from: Key, to: Key]; select: [square: Key] }>()
const element = ref<HTMLElement>()
let board: Api | undefined
const reducedMotion = ref(false)
let motionPreference: MediaQueryList | undefined
function updateMotion() {
  reducedMotion.value = motionPreference?.matches ?? false
}

function configuration(): Config {
  return {
    fen: props.fen,
    orientation: props.orientation,
    turnColor: props.turnColor,
    lastMove: props.lastMove ?? [],
    check: props.inCheck,
    coordinates: props.coordinates && !props.readOnly,
    viewOnly: props.readOnly,
    disableContextMenu: true,
    highlight: {
      custom: new Map(
        props.hintMove
          ? [
              [props.hintMove[0], 'hint-origin'],
              ...(props.hintPieceSelected
                ? [[props.hintMove[1], 'hint-destination'] as [Key, string]]
                : []),
            ]
          : [],
      ),
    },
    events: { select: (square) => emit('select', square) },
    animation: { enabled: !reducedMotion.value, duration: 180 },
    movable: {
      free: false,
      color: props.turnColor,
      dests: props.interactionDisabled ? new Map() : (props.destinations ?? new Map()),
      rookCastle: false,
      events: {
        after: async (from, to) => {
          const previousFen = props.fen
          emit('move', from, to)
          await nextTick()
          // Accepted moves already updated through the watcher. Restore rejected moves/promotion
          // with destinations and turn, even when the authoritative FEN is unchanged.
          if (props.fen === previousFen) board?.set(configuration())
        },
      },
    },
    premovable: { enabled: false },
    drawable: { enabled: false, visible: false },
    selectable: { enabled: !props.readOnly && !props.interactionDisabled },
    draggable: { enabled: !props.readOnly && !props.interactionDisabled },
  }
}

onMounted(() => {
  motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)')
  updateMotion()
  motionPreference.addEventListener('change', updateMotion)
  if (element.value) board = Chessground(element.value, configuration())
})
watch(
  () => [
    props.fen,
    props.orientation,
    props.turnColor,
    props.destinations,
    props.lastMove,
    props.readOnly,
    props.coordinates,
    props.interactionDisabled,
    props.hintMove,
    props.hintPieceSelected,
    props.inCheck,
    reducedMotion.value,
  ],
  () => board?.set(configuration()),
)
onBeforeUnmount(() => {
  motionPreference?.removeEventListener('change', updateMotion)
  board?.destroy()
  board = undefined
})
</script>

<template>
  <div
    ref="element"
    class="cg-wrap chess-board"
    :class="{ 'is-read-only': readOnly }"
    :role="readOnly ? 'img' : 'group'"
    :aria-label="label"
  />
</template>

<style>
.chess-board {
  width: 100%;
  aspect-ratio: 1;
}
.chess-board cg-board {
  background-color: #e7e6d3;
  background-image: conic-gradient(#83986e 25%, #e7e6d3 0 50%, #83986e 0 75%, #e7e6d3 0);
  background-size: 25% 25%;
  border-radius: 2px;
}
.chess-board square.last-move {
  background: #e5c36c80;
}
.chess-board square.selected {
  background: #f2cf5590;
}
.chess-board square.move-dest {
  background: radial-gradient(#253e3545 22%, transparent 24%);
}
.chess-board square.oc.move-dest {
  background: radial-gradient(transparent 75%, #253e3560 76%);
}
.chess-board cg-board square.check {
  background: radial-gradient(#ba493f 10%, #ba493f00 70%);
}
.chess-board square.hint-origin {
  background: #eac76570;
}
.chess-board square.hint-origin::after,
.chess-board square.hint-destination::after {
  content: '';
  position: absolute;
  inset: 9%;
  border-radius: 50%;
  box-shadow:
    0 0 0 4px #fff6bd,
    0 0 18px 6px #c69125d0;
  animation: lesson-hint-pulse 1.5s ease-in-out infinite;
}
.chess-board square.hint-destination {
  background: #e9d266a0;
}
.chess-board square.hint-destination::after {
  inset: 15%;
  border-radius: 12%;
  box-shadow:
    0 0 0 4px #fff6bd,
    0 0 18px 6px #c69125d0;
}
@keyframes lesson-hint-pulse {
  50% {
    opacity: 0.55;
  }
}
@media (prefers-reduced-motion: reduce) {
  .chess-board square.hint-origin::after,
  .chess-board square.hint-destination::after {
    animation: none;
  }
}
</style>
