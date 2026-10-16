<script setup lang="ts">
import { t, message as textMessage, type Text } from '../i18n'
import { computed, ref, shallowRef } from 'vue'
import ChessBoard from '../components/chess/ChessBoard.vue'
import { BrowserChessGame, type PromotionPiece } from '../domain/chess/BrowserChessGame'
import { usePreferencesStore } from '../stores/preferences'

const preferences = usePreferencesStore()
let game = new BrowserChessGame()
const position = shallowRef(game.snapshot())
const orientation = ref<'white' | 'black'>('white')
const from = ref('e2')
const to = ref('e4')
const message = ref<Text>(
  'Manual board: you move both colours here. There is no automatic opponent.',
)
const pending = ref<{ from: string; to: string; choices: PromotionPiece[] }>()
const noDestinations = new Map()
const turn = computed(() => (position.value.turn === 'w' ? 'white' : 'black'))
const promotionNames: Record<PromotionPiece, string> = {
  q: 'Queen',
  r: 'Rook',
  b: 'Bishop',
  n: 'Knight',
}

function play(origin: string, destination: string, promotion?: PromotionPiece) {
  if (pending.value && !promotion) return
  const result = game.play(origin, destination, promotion)
  if (result.kind === 'promotion-required') {
    pending.value = { from: origin, to: destination, choices: result.choices }
    message.value = 'Choose a piece for promotion.'
  } else {
    pending.value = undefined
    position.value = game.snapshot()
    if (result.kind === 'played') {
      preferences.moveSound()
      from.value = ''
      to.value = ''
    }
    message.value =
      result.kind === 'played'
        ? textMessage('Played {p0}.', { p0: result.move.san })
        : 'That move is not legal in this position.'
  }
}

function undo() {
  if (pending.value) {
    pending.value = undefined
    message.value = 'Promotion cancelled. Choose your move.'
    return
  }
  pending.value = undefined
  game.undo()
  position.value = game.snapshot()
  message.value = 'Previous position restored.'
}
function cancelPromotion() {
  pending.value = undefined
  message.value = 'Promotion cancelled. Choose your move.'
}
function restart() {
  pending.value = undefined
  game = new BrowserChessGame()
  position.value = game.snapshot()
  message.value = 'Position restarted.'
}
function promotionPosition() {
  game = new BrowserChessGame('7k/P7/8/8/8/8/8/7K w - - 0 1')
  pending.value = undefined
  position.value = game.snapshot()
  from.value = 'a7'
  to.value = 'a8'
  message.value = 'Promotion fixture loaded.'
}
</script>

<template>
  <main id="main-content" class="page-width lab-main">
    <div class="lab-heading">
      <div>
        <span class="eyebrow">{{ t('DEVELOPMENT ONLY') }}</span>
        <h1>{{ t('Board & rules lab') }}</h1>
        <p>
          {{ t('This is a manual developer tool. For automatic replies and hints,') }}
          <RouterLink to="/learn">{{ t('start a guided lesson') }}</RouterLink
          >.
        </p>
      </div>
      <RouterLink to="/">{{ t('← Home') }}</RouterLink>
    </div>
    <div class="lab-grid">
      <div class="lab-board">
        <ChessBoard
          :fen="position.fen"
          :orientation="orientation"
          :turn-color="turn"
          :destinations="pending ? noDestinations : position.destinations"
          :last-move="position.lastMove"
          :in-check="position.inCheck"
          :label="t('Chessboard, {p0} to move', { p0: turn === 'white' ? 'White' : 'Black' })"
          @move="play"
        />
      </div>
      <section class="lab-panel" :aria-label="t('Board controls')">
        <p class="turn-indicator">
          {{ t('{p0} to move', { p0: turn === 'white' ? t('White') : t('Black') })
          }}<span v-if="position.inCheck"> {{ t('· Check') }}</span
          ><span v-if="position.gameOver"> {{ t('· Game over') }}</span>
        </p>
        <p class="move-feedback" role="status">{{ t(message) }}</p>
        <div
          v-if="pending"
          class="promotion-picker"
          role="group"
          :aria-label="t('Choose promotion piece')"
        >
          <button
            v-for="choice in pending.choices"
            :key="choice"
            type="button"
            @click="play(pending.from, pending.to, choice)"
          >
            {{ t(promotionNames[choice]) }}</button
          ><button type="button" @click="cancelPromotion">{{ t('Cancel') }}</button>
        </div>
        <form class="coordinate-form" @submit.prevent="play(from, to)">
          <label
            >{{ t('From square')
            }}<input v-model="from" pattern="[a-h][1-8]" maxlength="2" required autocomplete="off"
          /></label>
          <label
            >{{ t('To square')
            }}<input v-model="to" pattern="[a-h][1-8]" maxlength="2" required autocomplete="off"
          /></label>
          <button type="submit" :disabled="!!pending">{{ t('Play move') }}</button>
        </form>
        <div class="lab-actions">
          <button
            type="button"
            :aria-label="t('Move sound')"
            :aria-pressed="preferences.soundEnabled"
            @click="preferences.toggleSound"
          >
            {{ t('Sound {p0}', { p0: preferences.soundEnabled ? t('on') : t('off') }) }}
          </button>
          <button type="button" :disabled="position.history.length === 0 && !pending" @click="undo">
            {{ t('Undo') }}</button
          ><button type="button" @click="restart">{{ t('Restart') }}</button
          ><button type="button" @click="orientation = orientation === 'white' ? 'black' : 'white'">
            {{ t('Flip board') }}
          </button>
        </div>
        <h2>{{ t('Move history') }}</h2>
        <ol class="move-history" :aria-label="t('Move history')">
          <li v-for="(move, index) in position.history" :key="index">{{ move }}</li>
        </ol>
        <p v-if="!position.history.length" class="muted">{{ t('No moves yet.') }}</p>
        <details>
          <summary>{{ t('Rules fixtures') }}</summary>
          <button type="button" @click="promotionPosition">
            {{ t('Load promotion fixture') }}
          </button>
        </details>
        <details>
          <summary>{{ t('Current FEN') }}</summary>
          <code data-testid="fen">{{ position.fen }}</code>
        </details>
      </section>
    </div>
  </main>
</template>

<style scoped>
.lab-main {
  padding-block: 36px 70px;
}
.lab-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 35px;
}
.lab-heading h1 {
  font-size: 40px;
  letter-spacing: -1px;
  margin-block: 14px 0;
}
.lab-heading a {
  font-size: 13px;
}
.lab-grid {
  display: grid;
  grid-template-columns: minmax(280px, 600px) minmax(250px, 1fr);
  gap: 50px;
}
.lab-board {
  margin: 12px 22px 20px 6px;
}
.lab-panel {
  padding: 24px;
  background: #eeefe5;
  border: 1px solid var(--line);
  border-radius: 6px;
}
.turn-indicator {
  font-weight: 650;
  text-transform: capitalize;
}
.move-feedback {
  font-size: 13px;
  line-height: 1.6;
  min-height: 42px;
}
.coordinate-form {
  display: flex;
  gap: 10px;
  align-items: end;
  flex-wrap: wrap;
}
.coordinate-form label {
  display: grid;
  gap: 7px;
  font-size: 12px;
}
.coordinate-form input {
  width: 78px;
  padding: 10px;
  border: 1px solid #b5bea9;
  border-radius: 4px;
  background: #fafbf5;
}
.lab-panel button {
  padding: 10px 14px;
  border: 1px solid #aab79f;
  border-radius: 4px;
  color: var(--ink);
  background: #fafbf5;
  font-size: 12px;
}
.lab-panel button:disabled {
  opacity: 0.45;
  cursor: default;
}
.lab-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-block: 20px;
}
.lab-panel h2 {
  font-size: 14px;
  margin-top: 30px;
}
.move-history {
  display: flex;
  flex-wrap: wrap;
  gap: 8px 16px;
  list-style: none;
  padding: 0;
  font-size: 14px;
  min-height: 20px;
}
.muted {
  font-size: 12px;
  color: var(--muted);
}
details {
  border-top: 1px solid var(--line);
  margin-top: 22px;
  padding-top: 16px;
  font-size: 12px;
}
summary {
  cursor: pointer;
  margin-bottom: 15px;
}
code {
  overflow-wrap: anywhere;
  font-size: 11px;
  line-height: 1.7;
}
.promotion-picker {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 15px;
}
@media (max-width: 800px) {
  .lab-grid {
    grid-template-columns: 1fr;
    gap: 25px;
  }
  .lab-board {
    max-width: 570px;
    width: calc(100% - 28px);
    margin-inline: auto;
  }
  .lab-heading h1 {
    font-size: 32px;
  }
  .lab-heading {
    align-items: end;
  }
}
</style>
