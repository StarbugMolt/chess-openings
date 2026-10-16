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
/*
 * Diagram board: plain light squares, hatched dark squares and an ink frame, as in a printed
 * opening book. Highlighter yellow marks the last move, the picked-up piece and hints.
 */
.chess-board {
  width: 100%;
  aspect-ratio: 1;
}
.chess-board cg-board {
  background-color: #f9fafc;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 80 80'%3E%3Cdefs%3E%3Cpattern id='h' width='3.5355' height='3.5355' patternUnits='userSpaceOnUse' patternTransform='rotate(45)'%3E%3Crect width='3.5355' height='3.5355' fill='%23d9dfea'/%3E%3Crect width='1.15' height='3.5355' fill='%23394a72'/%3E%3C/pattern%3E%3C/defs%3E%3Crect x='40' width='40' height='40' fill='url(%23h)'/%3E%3Crect y='40' width='40' height='40' fill='url(%23h)'/%3E%3C/svg%3E");
  background-size: 25% 25%;
  box-shadow: 0 0 0 3px var(--ink, #182544);
}
.chess-board square.last-move {
  background-color: #ffd93dc0;
}
.chess-board square.selected {
  background-color: #ffd21fe0;
  box-shadow: inset 0 0 0 3px var(--ink, #182544);
}
.chess-board square.move-dest {
  background: radial-gradient(circle, var(--ink, #182544) 0 15%, #fff 16% 22%, transparent 23%);
}
.chess-board square.move-dest:hover {
  background-color: #ffe9809c;
}
.chess-board square.oc.move-dest {
  background: radial-gradient(
    circle,
    transparent 0 64%,
    #fff 65% 68%,
    var(--ink, #182544) 69% 78%,
    transparent 79%
  );
}
.chess-board cg-board square.check {
  background: radial-gradient(circle, #d8433a 0 22%, #d8433a00 72%);
}
.chess-board square.hint-origin,
.chess-board square.hint-destination {
  background-color: #ffd93de6;
}
.chess-board square.hint-origin::after,
.chess-board square.hint-destination::after {
  content: '';
  position: absolute;
  inset: 7%;
  border: 3px solid var(--ink, #182544);
  border-radius: 50%;
  animation: lesson-hint-pulse 1.4s ease-in-out infinite;
}
.chess-board square.hint-destination::after {
  inset: 12%;
  border-radius: 4px;
  border-style: dashed;
}
@keyframes lesson-hint-pulse {
  50% {
    opacity: 0.35;
    transform: scale(0.9);
  }
}
.cg-wrap.chess-board coords {
  opacity: 1;
  font-family: inherit;
  font-size: 11px;
  font-weight: 700;
}
/* Files read as in notation (a–h, lowercase), tucked inside the bottom-right of each square. */
.cg-wrap.chess-board coords.files {
  left: 0;
  bottom: 1px;
  text-transform: none;
}
.cg-wrap.chess-board coords.files coord {
  padding-right: 4px;
  text-align: right;
}
.cg-wrap.chess-board coords coord {
  color: var(--ink, #182544);
  text-shadow:
    0 0 2px #fff,
    0 0 2px #fff,
    0 0 3px #fff;
}
@media (prefers-reduced-motion: reduce) {
  .chess-board square.hint-origin::after,
  .chess-board square.hint-destination::after {
    animation: none;
  }
}
</style>
