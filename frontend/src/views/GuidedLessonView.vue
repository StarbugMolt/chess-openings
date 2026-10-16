<script setup lang="ts">
import { t, message, type Text } from '../i18n'
import { computed, nextTick, onBeforeUnmount, ref, shallowRef, watch, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import ChessBoard from '../components/chess/ChessBoard.vue'
import MoveNotes, { type MoveNote } from '../components/lesson/MoveNotes.vue'
import {
  getLesson,
  saveCompletion,
  type Completion,
  type LessonPayload,
  type PlayingColor,
} from '../api/lessons'
import { GuidedTrainer } from '../domain/training/GuidedTrainer'
import { selectReview } from '../domain/training/ReviewLink'
import type { ChessSnapshot, PromotionPiece } from '../domain/chess/BrowserChessGame'
import { usePreferencesStore } from '../stores/preferences'
const route = useRoute()
const preferences = usePreferencesStore()
const payload = shallowRef<LessonPayload>()
watchEffect(() => {
  if (payload.value)
    document.title = t('{p0} lesson · Chess Openings', { p0: payload.value.lesson.title })
})
const position = shallowRef<ChessSnapshot>()
const trainer = shallowRef<GuidedTrainer>()
const lineId = ref('')
const color = ref<PlayingColor>('w')
const started = ref(false)
const ply = ref(0)
const complete = ref(false)
const assessment = computed(() => (complete.value ? trainer.value?.assessment : undefined))
const recalledDecisions = computed(() =>
  assessment.value?.usedUndo
    ? 0
    : (assessment.value?.decisions.filter((decision) => decision.attempts === 1 && !decision.hinted)
        .length ?? 0),
)
const waiting = ref(false)
const hint = ref<Text>('')
const selectedSquare = ref<string>()
const hintPieceSelected = computed(
  () => !!hintMove.value && selectedSquare.value === hintMove.value[0],
)
const hintMove = computed(() =>
  hint.value && playerTurn.value ? trainer.value?.hintSquares() : undefined,
)
const hintExplanation = computed(() => line.value?.steps[ply.value]?.explanation ?? '')
const feedback = ref<Text>('')
const announcement = ref<Text>('')
const fromSquare = ref('')
const toSquare = ref('')
const pendingPromotion = ref<{ from: string; to: string; choices: PromotionPiece[] }>()
const promotionNames: Record<PromotionPiece, string> = {
  q: 'Queen',
  r: 'Rook',
  b: 'Bishop',
  n: 'Knight',
}
const error = ref<Text>('')
const reviewNotice = ref<Text>('')
const loading = ref(false)
const saving = ref(false)
const saved = ref(false)
const saveError = ref<Text>('')
const attemptId = ref('')
const line = computed(() => payload.value?.lesson.lines.find((line) => line.id === lineId.value))
const ownMoves = computed(
  () => line.value?.steps.filter((_, i) => i % 2 === (color.value === 'w' ? 0 : 1)).length ?? 0,
)
const ownPlayed = computed(() =>
  Math.min(ownMoves.value, Math.floor((ply.value + (color.value === 'w' ? 1 : 0)) / 2)),
)
const playerTurn = computed(
  () => started.value && !complete.value && !waiting.value && position.value?.turn === color.value,
)
const destinations = computed(() =>
  playerTurn.value ? (position.value?.destinations ?? new Map()) : new Map(),
)
/** The latest move pair with its teaching note, always visible while playing. */
const recentNotes = computed<MoveNote[]>(() => {
  const steps = line.value?.steps ?? []
  const first = Math.max(0, ply.value - 2)
  return steps.slice(first, ply.value).map((step, offset) => {
    const index = first + offset
    const white = index % 2 === 0
    const number = Math.floor(index / 2) + 1
    return {
      who:
        white === (color.value === 'w')
          ? 'You played'
          : message('{p0} played', { p0: white ? 'White' : 'Black' }),
      label: `${number}${white ? '.' : '...'} ${step.san}`,
      explanation: step.explanation,
    }
  })
})
const latestExplanation = () => line.value?.steps[ply.value - 1]?.explanation ?? ''
const previouslyCompleted = computed(
  () =>
    payload.value?.progress.some(
      (progress) =>
        progress.lessonId === payload.value?.lesson.id &&
        progress.revision === payload.value?.lesson.revision &&
        progress.lineId === lineId.value &&
        progress.playingColor === color.value,
    ) ?? false,
)
let replyTimer: ReturnType<typeof setTimeout> | undefined
let controller: AbortController | undefined
let loadGeneration = 0

function cancelReply() {
  if (replyTimer) clearTimeout(replyTimer)
  replyTimer = undefined
  waiting.value = false
}
function sync() {
  if (!trainer.value) return
  position.value = trainer.value.snapshot()
  ply.value = trainer.value.ply
  complete.value = trainer.value.complete
}
function newAttemptId(): string {
  const bytes = crypto.getRandomValues(new Uint8Array(16))
  bytes[6] = ((bytes[6] ?? 0) & 15) | 64
  bytes[8] = ((bytes[8] ?? 0) & 63) | 128
  const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('')
  return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`
}
function prepare() {
  pendingPromotion.value = undefined
  fromSquare.value = ''
  toSquare.value = ''
  selectedSquare.value = undefined
  cancelReply()
  started.value = false
  hint.value = ''
  feedback.value = ''
  saveError.value = ''
  saved.value = false
  saving.value = false
  const lesson = payload.value?.lesson
  if (!lesson || !lineId.value) return
  trainer.value = new GuidedTrainer(lesson, lineId.value, color.value)
  attemptId.value = newAttemptId()
  sync()
}
async function load() {
  cancelReply()
  controller?.abort()
  controller = new AbortController()
  const generation = ++loadGeneration
  loading.value = true
  error.value = ''
  reviewNotice.value = ''
  started.value = false
  payload.value = undefined
  position.value = undefined
  trainer.value = undefined
  try {
    const result = await getLesson(String(route.params.id), controller.signal)
    if (generation !== loadGeneration) return
    payload.value = result
    const selection = selectReview(result.lesson, route.query)
    lineId.value = selection.lineId
    color.value = selection.playingColor
    reviewNotice.value = selection.notice
    prepare()
  } catch {
    if (generation === loadGeneration)
      error.value =
        'This lesson could not be loaded. Check your connection and sign-in, then try again.'
  } finally {
    if (generation === loadGeneration) loading.value = false
  }
}
async function save() {
  const current = trainer.value
  const summary = current?.assessment
  if (!current?.complete || !summary || saving.value || saved.value || !payload.value) return
  const attempt = attemptId.value
  const completion: Completion = {
    attemptId: attempt,
    lessonId: current.lesson.id,
    revision: current.lesson.revision,
    lineId: current.lineId,
    playingColor: current.playingColor,
    hintCount: current.hintCount,
    path: current.path,
    assessment: summary,
  }
  saving.value = true
  saveError.value = ''
  try {
    await saveCompletion(completion)
    if (attemptId.value === attempt) saved.value = true
  } catch {
    if (attemptId.value === attempt)
      saveError.value =
        'You finished the line, but we could not save it. Keep this page open and try again.'
  } finally {
    if (attemptId.value === attempt) saving.value = false
  }
}
function next() {
  if (!trainer.value || !started.value) return
  if (trainer.value.complete) {
    void save()
    return
  }
  if (!trainer.value.playerTurn) {
    waiting.value = true
    replyTimer = setTimeout(() => {
      const result = trainer.value?.reply()
      if (result?.kind === 'accepted') {
        preferences.moveSound()
        feedback.value = ''
        announcement.value = message('{p0} played {p1}.', {
          p0: color.value === 'w' ? 'Black' : 'White',
          p1: result.move.san,
        })
      }
      waiting.value = false
      sync()
      if (result?.kind === 'accepted')
        announcement.value = message('{reply} {explanation} {status}', {
          reply: announcement.value,
          explanation: latestExplanation(),
          status: trainer.value?.complete ? 'Line complete.' : 'Your turn.',
        })
      next()
    }, 500)
  }
}
function start() {
  prepare()
  started.value = true
  next()
  announcement.value = message('Lesson started. You play {p0}. {p1}', {
    p0: color.value === 'w' ? 'White' : 'Black',
    p1: waiting.value ? 'Watch the first White move.' : 'Your turn.',
  })
  if (window.matchMedia('(max-width: 850px)').matches) void nextTick(scrollToBoard)
}
function scrollToBoard() {
  document.querySelector('.guided-board')?.scrollIntoView({
    block: 'start',
    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
  })
}
function play(from: string, to: string, promotion?: PromotionPiece) {
  if (!playerTurn.value || !trainer.value) return
  if (pendingPromotion.value && !promotion) return
  const result = trainer.value.play(from, to, promotion)
  if (result.kind === 'promotion-required') {
    pendingPromotion.value = { from, to, choices: result.choices }
    announcement.value = 'Choose a piece for promotion.'
    return
  }
  pendingPromotion.value = undefined
  if (result.kind === 'accepted') {
    hint.value = ''
    selectedSquare.value = undefined
    feedback.value = ''
    fromSquare.value = ''
    toSquare.value = ''
    preferences.moveSound()
    sync()
    announcement.value = message('You played {p0}. {p1} {p2}', {
      p0: result.move.san,
      p1: latestExplanation(),
      p2: trainer.value.complete ? 'Line complete.' : 'Watch the reply.',
    })
    next()
  } else {
    feedback.value =
      result.kind === 'alternative-line'
        ? message(
            'That move belongs to “{p0}”, another taught line. This exercise follows the selected line, so the board stays here. Try again or use a hint.',
            { p0: result.title },
          )
        : result.kind === 'outside-line'
          ? 'That move is legal, but outside this lesson. The board stays here. Try again or use a hint.'
          : 'That piece cannot move there in this position. Try a highlighted square.'
    announcement.value = feedback.value
    sync()
  }
}
function showHint() {
  hint.value = trainer.value?.hint() ?? ''
  announcement.value = message('{p0} Why: {p1}', { p0: hint.value, p1: hintExplanation.value })
  if (window.matchMedia('(max-width: 850px)').matches) scrollToBoard()
}
function cancelPromotion() {
  pendingPromotion.value = undefined
  announcement.value = 'Promotion cancelled. Choose your move.'
}
function undo() {
  cancelReply()
  pendingPromotion.value = undefined
  trainer.value?.undo()
  hint.value = ''
  feedback.value = ''
  complete.value = false
  sync()
  announcement.value = 'Your last move and the reply were taken back. Your turn.'
  next()
}
watch(
  () => [route.params.id, route.query.line, route.query.color, route.query.revision],
  () => {
    void load()
  },
  { immediate: true },
)
onBeforeUnmount(() => {
  loadGeneration++
  cancelReply()
  controller?.abort()
})
</script>
<template>
  <main id="main-content" class="page-width guided-main">
    <p class="sr-only" aria-live="polite" aria-atomic="true">{{ t(announcement) }}</p>
    <RouterLink class="back-link" to="/learn">{{ t('← All lessons') }}</RouterLink>
    <p v-if="loading" role="status">{{ t('Preparing your lesson…') }}</p>
    <div v-else-if="error" class="notice status-actions" role="alert">
      <p>{{ t(error) }}</p>
      <button @click="load">{{ t('Try again') }}</button
      ><RouterLink to="/login">{{ t('Sign in') }}</RouterLink>
    </div>
    <template v-else-if="payload && position && line">
      <p v-if="reviewNotice" role="status" class="notice">{{ t(reviewNotice) }}</p>
      <div class="lesson-heading">
        <h1>{{ t(payload.lesson.title) }}</h1>
        <span class="side-token" :class="{ 'is-black': color === 'b' }">{{
          t('You play {p0}', { p0: color === 'w' ? 'White' : 'Black' })
        }}</span>
      </div>
      <div class="guided-grid" :class="{ 'is-setting-up': !started }">
        <div class="guided-board">
          <div v-if="started && !complete" class="board-toolbar">
            <span
              >{{ t(waiting ? 'Opponent replying…' : 'Your turn') }}
              <span class="move-count"
                >{{ Math.min(ownPlayed + 1, ownMoves) }}/{{ ownMoves }}</span
              ></span
            ><button class="marker-button" :disabled="!playerTurn" @click="showHint">
              {{ t('Hint') }}
            </button>
          </div>
          <ChessBoard
            :fen="position.fen"
            :orientation="color === 'w' ? 'white' : 'black'"
            :turn-color="position.turn === 'w' ? 'white' : 'black'"
            :destinations="destinations"
            :last-move="position.lastMove"
            :in-check="position.inCheck"
            :interaction-disabled="!playerTurn || !!pendingPromotion"
            :hint-move="hintMove"
            :hint-piece-selected="hintPieceSelected"
            :label="
              t('{p0} lesson board, you play {p1}', {
                p0: t(payload.lesson.title),
                p1: color === 'w' ? t('White') : t('Black'),
              })
            "
            @select="selectedSquare = $event"
            @move="play"
          />
          <div v-if="hint && !complete" class="mobile-board-hint hint">
            <strong>{{
              hintPieceSelected
                ? t('Tap the glowing destination.')
                : t('Pick up the glowing piece.')
            }}</strong>
            <p>{{ t(hint) }}</p>
            <p>
              <strong>{{ t('Why:') }}</strong> {{ t(hintExplanation) }}
            </p>
          </div>
          <p v-if="feedback" class="mobile-feedback notice">{{ t(feedback) }}</p>
          <MoveNotes
            v-if="started"
            class="mobile-move-notes"
            :notes="recentNotes"
            :heading="complete ? t('How the line ends') : undefined"
          />
        </div>
        <section class="guided-panel" :aria-label="t('Lesson instructions')">
          <template v-if="!started">
            <p class="panel-kicker">{{ t('Your line') }}</p>
            <h2>{{ t(line.title) }}</h2>
            <p>{{ t(payload.lesson.introduction) }}</p>
            <p class="how-to-play">
              {{
                t(
                  'Tap a piece, then a highlighted square. You can also drag it. We reply automatically. Select',
                )
              }}
              <strong>{{ t('Hint') }}</strong> {{ t('whenever you need help.') }}
            </p>
            <p v-if="previouslyCompleted" class="notice">
              {{ t('You have practised this line before. Repetition helps it feel familiar.') }}
            </p>
            <details class="lesson-options">
              <summary>{{ t('Other lesson options') }}</summary>
              <label
                >{{ t('Line')
                }}<select v-model="lineId" :aria-label="t('Line')" @change="prepare">
                  <option
                    v-for="choice in payload.lesson.lines"
                    :key="choice.id"
                    :value="choice.id"
                  >
                    {{ t(choice.title) }}
                  </option>
                </select></label
              >
              <label
                >{{ t('Your pieces')
                }}<select v-model="color" :aria-label="t('Your pieces')" @change="prepare">
                  <option value="w">
                    {{ t('White')
                    }}{{ payload.lesson.recommendedColor === 'w' ? t(' (recommended)') : '' }}
                  </option>
                  <option value="b">
                    {{ t('Black')
                    }}{{ payload.lesson.recommendedColor === 'b' ? t(' (recommended)') : '' }}
                  </option>
                </select></label
              >
            </details>
            <button class="primary-link start-button" @click="start">
              {{ t('Start lesson') }}
            </button>
          </template>
          <template v-else-if="complete">
            <p class="panel-kicker">{{ t('Nice work') }}</p>
            <h2>{{ t('Line complete.') }}</h2>
            <p>
              {{
                t(
                  'You played all {p0} of your moves in “{p1}”. You can repeat it or try another lesson.',
                  { p0: ownMoves, p1: t(line.title) },
                )
              }}
            </p>
            <p v-if="assessment?.usedUndo" class="recall-summary">
              {{
                t(
                  'Takebacks used. This practice is recorded with help; try a fresh attempt to check your recall.',
                )
              }}
            </p>
            <p v-else-if="assessment" class="recall-summary">
              <strong class="recall-count">{{
                t('{p0} of {p1}', { p0: recalledDecisions, p1: assessment.decisions.length })
              }}</strong>
              {{ t('taught decisions played on the first try without a hint.') }}
            </p>
            <MoveNotes class="panel-move-notes" heading="How the line ends" :notes="recentNotes" />
            <p v-if="saving" role="status">{{ t('Saving your practice…') }}</p>
            <p v-else-if="saved" role="status" class="saved-notice">
              {{ t('Practice saved to your account.') }}
            </p>
            <div v-if="saveError" class="notice status-actions" role="alert">
              <p>{{ t(saveError) }}</p>
              <button @click="save">{{ t('Try saving again') }}</button
              ><RouterLink to="/login" target="_blank">{{
                t('Sign in in another tab')
              }}</RouterLink>
            </div>
            <button class="primary-link start-button" @click="start">
              {{ t('Practise again') }}
            </button>
            <nav class="next-steps" :aria-label="t('After this lesson')">
              <RouterLink
                v-if="payload.lesson.lines.length > 1"
                :to="`/train/${payload.lesson.id}`"
                >{{ t('Practise variations in this opening') }}</RouterLink
              >
              <RouterLink to="/learn">{{ t('Choose another lesson') }}</RouterLink>
              <RouterLink to="/progress">{{ t('See your practice and reviews') }}</RouterLink>
            </nav>
          </template>
          <template v-else>
            <div class="turn-strip">
              <p class="lesson-phase" role="status">
                {{
                  waiting
                    ? t('{p0} is replying…', { p0: color === 'w' ? 'Black' : 'White' })
                    : t('Your turn')
                }}
              </p>
              <span class="move-count">{{
                t('Move {p0} of {p1} for you', {
                  p0: Math.min(ownPlayed + 1, ownMoves),
                  p1: ownMoves,
                })
              }}</span>
            </div>
            <progress
              :value="ownPlayed"
              :max="ownMoves"
              :style="{ '--segments': ownMoves }"
              :aria-label="t('Lesson progress')"
            />
            <h2>{{ t(line.title) }}</h2>
            <MoveNotes class="panel-move-notes" :notes="recentNotes" />
            <p class="how-to-play">
              {{
                waiting
                  ? t('Watch the reply, then it is your turn again.')
                  : t('Choose a piece and move it to a highlighted square. Need a nudge? Use Hint.')
              }}
            </p>
            <p v-if="feedback" role="alert" class="lesson-feedback notice">{{ t(feedback) }}</p>
            <div v-if="hint" class="hint" role="note" :aria-label="t('Move hint')">
              <strong>{{
                hintPieceSelected
                  ? t('Now tap the glowing destination.')
                  : t('Pick up the glowing piece.')
              }}</strong>
              <p>{{ t(hint) }}</p>
              <p>
                <strong>{{ t('Why:') }}</strong> {{ t(hintExplanation) }}
              </p>
            </div>
            <button class="hint-button marker-button" :disabled="!playerTurn" @click="showHint">
              {{ t('Hint') }}
            </button>
          </template>
          <div v-if="started" class="lesson-controls">
            <button :disabled="ply <= (color === 'w' ? 0 : 1) || complete" @click="undo">
              {{ t('Take back my move') }}</button
            ><button @click="start">{{ t('Start over') }}</button>
          </div>
          <div
            v-if="pendingPromotion"
            class="promotion-picker"
            role="group"
            :aria-label="t('Choose promotion piece')"
          >
            <button
              v-for="choice in pendingPromotion.choices"
              :key="choice"
              @click="play(pendingPromotion.from, pendingPromotion.to, choice)"
            >
              {{ t(promotionNames[choice]) }}
            </button>
            <button @click="cancelPromotion">{{ t('Cancel promotion') }}</button>
          </div>
          <div class="panel-footer">
            <button
              class="sound-button"
              :aria-label="t('Move sound')"
              :aria-pressed="preferences.soundEnabled"
              @click="preferences.toggleSound"
            >
              {{ t('Sound {p0}', { p0: preferences.soundEnabled ? 'on' : 'off' }) }}
            </button>
            <details v-if="started && !complete" class="keyboard-moves">
              <summary>{{ t('Enter a move with the keyboard') }}</summary>
              <p>
                {{
                  t(
                    'Use the square labels on the board, for example from e2 to e4. For castling, enter the king’s starting and destination squares.',
                  )
                }}
              </p>
              <form
                :aria-label="t('Enter a lesson move')"
                @submit.prevent="play(fromSquare, toSquare)"
              >
                <label
                  >{{ t('From square')
                  }}<input
                    v-model="fromSquare"
                    pattern="[a-h][1-8]"
                    maxlength="2"
                    autocomplete="off"
                    required
                /></label>
                <label
                  >{{ t('To square')
                  }}<input
                    v-model="toSquare"
                    pattern="[a-h][1-8]"
                    maxlength="2"
                    autocomplete="off"
                    required
                /></label>
                <button :disabled="!playerTurn || !!pendingPromotion">{{ t('Play move') }}</button>
              </form>
            </details>
          </div>
        </section>
      </div>
      <details v-if="started" class="lesson-notation">
        <summary>{{ t('Moves played (chess notation)') }}</summary>
        <ol :aria-label="t('Lesson move history')">
          <li v-for="(move, index) in position.history" :key="index">{{ move }}</li>
        </ol>
      </details>
    </template>
  </main>
</template>
<style scoped>
.guided-main {
  padding-block: 16px 8px;
}
.back-link {
  display: inline-flex;
  align-items: center;
  min-height: 40px;
  color: var(--pencil);
  font-weight: 600;
  text-decoration: none;
}
.back-link:hover {
  color: var(--ink);
  text-decoration: underline;
}
.lesson-heading {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 4px 20px;
  margin: 2px 0 18px;
}
.lesson-heading h1 {
  font-size: clamp(28px, 3vw, 38px);
  margin: 0;
}
/* The board takes the height the viewport allows, so the whole lesson fits on a laptop screen. */
.guided-grid {
  --board: clamp(300px, calc(100vh - var(--header-height) - 150px), 700px);
  display: grid;
  grid-template-columns: var(--board) minmax(300px, 440px);
  gap: 40px;
  align-items: start;
}
.guided-board {
  min-width: 0;
  padding: 3px;
}
.board-toolbar,
.mobile-board-hint,
.mobile-feedback,
.mobile-move-notes {
  display: none;
}
.guided-panel {
  min-width: 0;
  padding: 24px;
  border: 1px solid var(--rule);
  border-radius: var(--radius);
  background: var(--sheet);
}
.guided-panel h2 {
  font-size: 22px;
  font-stretch: var(--semi-wide);
  margin: 6px 0 12px;
}
.panel-kicker {
  margin: 0;
  color: var(--pencil);
  font-weight: 600;
}
.how-to-play {
  color: var(--pencil);
}
.turn-strip {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-between;
  align-items: baseline;
  gap: 4px 12px;
  margin-bottom: 10px;
}
.lesson-phase {
  margin: 0;
  font-size: 19px;
  font-weight: 800;
  font-stretch: var(--semi-wide);
}
.move-count {
  color: var(--pencil);
  font-size: 15px;
  font-weight: 600;
}
.guided-panel progress {
  margin-bottom: 18px;
}
.start-button {
  width: 100%;
  margin-top: 8px;
}
.hint-button {
  width: 100%;
  min-height: 48px;
  font-size: 16px;
}
.hint {
  margin-block: 16px;
  padding: 14px 16px;
  background: var(--marker-soft);
  border-left: 3px solid var(--ink);
}
.hint p {
  margin: 6px 0 0;
}
.recall-summary {
  padding: 14px 16px;
  background: var(--paper);
}
.recall-count {
  display: block;
  font-size: 28px;
  font-weight: 850;
  font-stretch: var(--wide);
  line-height: 1.1;
}
.saved-notice {
  font-weight: 600;
}
.next-steps {
  display: grid;
  margin-top: 12px;
}
.next-steps a {
  display: flex;
  align-items: center;
  min-height: 44px;
  border-bottom: 1px solid var(--rule);
  font-weight: 600;
}
.lesson-controls {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin-top: 16px;
}
.lesson-controls button {
  flex: 1 1 auto;
}
.promotion-picker {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 16px;
  padding: 12px;
  border: 2px solid var(--ink);
  border-radius: var(--radius);
}
.panel-footer {
  margin-top: 18px;
  padding-top: 8px;
  border-top: 1px solid var(--rule);
}
.sound-button {
  padding-inline: 0;
  border: 0;
  background: none;
  color: var(--pencil);
}
/* A small switch drawn beside the label; aria-pressed carries the state. */
.sound-button::before {
  content: '';
  display: inline-block;
  width: 28px;
  height: 16px;
  margin-right: 10px;
  vertical-align: -3px;
  border-radius: 999px;
  background: radial-gradient(circle at 8px 50%, #fff 0 5px, transparent 5.5px) var(--rule-strong);
}
.sound-button[aria-pressed='true']::before {
  background: radial-gradient(circle at 20px 50%, #fff 0 5px, transparent 5.5px) var(--ink);
}
.sound-button:hover {
  color: var(--ink);
}
.keyboard-moves summary {
  display: flex;
  align-items: center;
  min-height: 44px;
  color: var(--pencil);
}
.keyboard-moves p {
  color: var(--pencil);
  font-size: 15px;
}
.keyboard-moves form {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: end;
}
.keyboard-moves label {
  display: grid;
  gap: 6px;
  font-size: 15px;
  font-weight: 600;
}
.keyboard-moves input {
  width: 84px;
}
.lesson-options {
  margin-block: 16px;
  padding-top: 8px;
  border-top: 1px solid var(--rule);
}
.lesson-options summary {
  display: flex;
  align-items: center;
  min-height: 44px;
}
.lesson-options label {
  display: grid;
  gap: 6px;
  margin-top: 12px;
  font-size: 15px;
  font-weight: 600;
}
.lesson-options select {
  width: 100%;
}
.lesson-notation {
  margin-top: 20px;
}
.lesson-notation summary {
  display: flex;
  align-items: center;
  min-height: 44px;
  color: var(--pencil);
}
.lesson-notation ol {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 4px;
  padding: 0;
  margin: 8px 0 0;
  list-style: none;
  counter-reset: move;
  font-weight: 650;
}
.lesson-notation li {
  padding: 2px 6px;
}
.lesson-notation li:nth-child(odd)::before {
  content: counter(move) '. ';
  counter-increment: move;
  color: var(--pencil);
  font-weight: 500;
}
.lesson-notation li:last-child {
  background: var(--marker);
}
@media (max-width: 900px) {
  .guided-grid {
    grid-template-columns: minmax(0, 1fr);
    gap: 20px;
  }
  .is-setting-up .guided-panel {
    grid-row: 1;
  }
  .guided-board {
    width: 100%;
    max-width: 560px;
    margin-inline: auto;
  }
  .board-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
    font-weight: 700;
  }
  .board-toolbar .move-count {
    margin-left: 6px;
  }
  .board-toolbar button {
    min-width: 96px;
  }
  .mobile-board-hint {
    display: block;
    font-size: 15px;
  }
  .mobile-feedback {
    display: block;
  }
  .guided-panel .hint-button,
  .guided-panel .hint,
  .guided-panel .panel-move-notes {
    display: none;
  }
  .mobile-move-notes {
    display: grid;
  }
  .guided-panel {
    padding: 20px;
  }
}
</style>
