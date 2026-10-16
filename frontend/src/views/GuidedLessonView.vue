<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, ref, shallowRef, watch } from 'vue'
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
const hint = ref('')
const selectedSquare = ref<string>()
const hintPieceSelected = computed(
  () => !!hintMove.value && selectedSquare.value === hintMove.value[0],
)
const hintMove = computed(() =>
  hint.value && playerTurn.value ? trainer.value?.hintSquares() : undefined,
)
const hintExplanation = computed(() => line.value?.steps[ply.value]?.explanation ?? '')
const feedback = ref('')
const announcement = ref('')
const fromSquare = ref('')
const toSquare = ref('')
const pendingPromotion = ref<{ from: string; to: string; choices: PromotionPiece[] }>()
const promotionNames: Record<PromotionPiece, string> = {
  q: 'Queen',
  r: 'Rook',
  b: 'Bishop',
  n: 'Knight',
}
const error = ref('')
const reviewNotice = ref('')
const loading = ref(false)
const saving = ref(false)
const saved = ref(false)
const saveError = ref('')
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
      who: white === (color.value === 'w') ? 'You played' : `${white ? 'White' : 'Black'} played`,
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
    document.title = `${result.lesson.title} lesson · Chess Openings`
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
        announcement.value = `${color.value === 'w' ? 'Black' : 'White'} played ${result.move.san}.`
      }
      waiting.value = false
      sync()
      if (result?.kind === 'accepted')
        announcement.value += ` ${latestExplanation()} ${trainer.value?.complete ? 'Line complete.' : 'Your turn.'}`
      next()
    }, 500)
  }
}
function start() {
  prepare()
  started.value = true
  next()
  announcement.value = `Lesson started. You play ${color.value === 'w' ? 'White' : 'Black'}. ${waiting.value ? 'Watch the first White move.' : 'Your turn.'}`
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
    announcement.value = `You played ${result.move.san}. ${latestExplanation()} ${trainer.value.complete ? 'Line complete.' : 'Watch the reply.'}`
    next()
  } else {
    feedback.value =
      result.kind === 'alternative-line'
        ? `That move belongs to “${result.title}”, another taught line. This exercise follows the selected line, so the board stays here. Try again or use a hint.`
        : result.kind === 'outside-line'
          ? 'That move is legal, but outside this lesson. The board stays here. Try again or use a hint.'
          : 'That piece cannot move there in this position. Try a highlighted square.'
    announcement.value = feedback.value
    sync()
  }
}
function showHint() {
  hint.value = trainer.value?.hint() ?? ''
  announcement.value = `${hint.value} Why: ${hintExplanation.value}`
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
    <p class="sr-only" aria-live="polite" aria-atomic="true">{{ announcement }}</p>
    <RouterLink class="back-link" to="/learn">← All lessons</RouterLink>
    <p v-if="loading" role="status">Preparing your lesson…</p>
    <div v-else-if="error" role="alert">
      <p>{{ error }}</p>
      <button @click="load">Try again</button><RouterLink to="/login">Sign in</RouterLink>
    </div>
    <template v-else-if="payload && position && line">
      <p v-if="reviewNotice" role="status" class="saved-notice">{{ reviewNotice }}</p>
      <div class="lesson-heading">
        <div>
          <span class="eyebrow">GUIDED LESSON · BEGINNER</span>
          <h1>{{ payload.lesson.title }}</h1>
        </div>
        <span class="lesson-role"
          >You play {{ color === 'w' ? 'White' : 'Black' }} · We play
          {{ color === 'w' ? 'Black' : 'White' }}</span
        >
      </div>
      <div class="guided-grid" :class="{ 'is-setting-up': !started }">
        <div class="guided-board">
          <div v-if="started && !complete" class="board-toolbar">
            <span
              >{{ waiting ? 'Opponent replying…' : 'Your turn' }} ·
              {{ Math.min(ownPlayed + 1, ownMoves) }}/{{ ownMoves }}</span
            ><button :disabled="!playerTurn" @click="showHint">Hint</button>
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
            :label="`${payload.lesson.title} lesson board, you play ${color === 'w' ? 'White' : 'Black'}`"
            @select="selectedSquare = $event"
            @move="play"
          />
          <div v-if="hint && !complete" class="mobile-board-hint">
            <strong>{{
              hintPieceSelected ? 'Tap the glowing destination.' : 'Pick up the glowing piece.'
            }}</strong>
            <p>{{ hint }}</p>
            <p><strong>Why:</strong> {{ hintExplanation }}</p>
          </div>
          <p v-if="feedback" class="mobile-feedback">{{ feedback }}</p>
          <MoveNotes
            v-if="started"
            class="mobile-move-notes"
            :notes="recentNotes"
            :heading="complete ? 'How the line ends' : undefined"
          />
        </div>
        <section class="guided-panel" aria-label="Lesson instructions">
          <template v-if="!started">
            <span class="eyebrow">YOUR FIRST STEPS</span>
            <h2>{{ line.title }}</h2>
            <p>{{ payload.lesson.introduction }}</p>
            <p class="how-to-play">
              Tap a piece, then a highlighted square. You can also drag it. We reply automatically.
              Select <strong>Hint</strong> whenever you need help.
            </p>
            <p v-if="previouslyCompleted" class="saved-notice">
              You have practised this line before. Repetition helps it feel familiar.
            </p>
            <details class="lesson-options">
              <summary>Other lesson options</summary>
              <label
                >Line<select v-model="lineId" aria-label="Line" @change="prepare">
                  <option
                    v-for="choice in payload.lesson.lines"
                    :key="choice.id"
                    :value="choice.id"
                  >
                    {{ choice.title }}
                  </option>
                </select></label
              >
              <label
                >Your pieces<select v-model="color" aria-label="Your pieces" @change="prepare">
                  <option value="w">
                    White{{ payload.lesson.recommendedColor === 'w' ? ' (recommended)' : '' }}
                  </option>
                  <option value="b">
                    Black{{ payload.lesson.recommendedColor === 'b' ? ' (recommended)' : '' }}
                  </option>
                </select></label
              >
            </details>
            <button class="primary-link" @click="start">
              Start lesson <span aria-hidden="true">→</span>
            </button>
          </template>
          <template v-else-if="complete">
            <span class="eyebrow">NICE WORK</span>
            <h2>Line complete.</h2>
            <p>
              You played all {{ ownMoves }} of your moves in “{{ line.title }}”. You can repeat it
              or try another lesson.
            </p>
            <MoveNotes class="panel-move-notes" heading="How the line ends" :notes="recentNotes" />
            <p v-if="assessment?.usedUndo">
              Takebacks used. This practice is recorded with help; try a fresh attempt to check your
              recall.
            </p>
            <p v-else-if="assessment">
              {{ recalledDecisions }} of {{ assessment.decisions.length }} taught decisions played
              on the first try without a hint.
            </p>
            <p v-if="saving" role="status">Saving your practice…</p>
            <p v-else-if="saved" role="status" class="saved-notice">
              Practice saved to your account.
            </p>
            <div v-if="saveError" role="alert">
              <p>{{ saveError }}</p>
              <button @click="save">Try saving again</button
              ><RouterLink to="/login" target="_blank">Sign in in another tab</RouterLink>
            </div>
            <button class="primary-link" @click="start">Practise again</button
            ><RouterLink
              v-if="payload.lesson.lines.length > 1"
              class="secondary-link"
              :to="`/train/${payload.lesson.id}`"
              >Practise variations in this opening →</RouterLink
            >
            <RouterLink class="secondary-link" to="/learn">Choose another lesson →</RouterLink>
            <RouterLink class="secondary-link" to="/progress"
              >See your practice &amp; reviews →</RouterLink
            >
          </template>
          <template v-else>
            <p class="lesson-phase" role="status">
              {{ waiting ? `${color === 'w' ? 'Black' : 'White'} is replying…` : 'Your turn' }}
            </p>
            <h2>{{ line.title }}</h2>
            <p>Move {{ Math.min(ownPlayed + 1, ownMoves) }} of {{ ownMoves }} for you</p>
            <progress :value="ownPlayed" :max="ownMoves" aria-label="Lesson progress" />
            <MoveNotes class="panel-move-notes" :notes="recentNotes" />
            <p class="how-to-play">
              {{
                waiting
                  ? 'Watch the reply, then it is your turn again.'
                  : 'Choose a piece and move it to a highlighted square. Need a nudge? Use Hint.'
              }}
            </p>
            <p v-if="feedback" role="alert" class="lesson-feedback">{{ feedback }}</p>
            <div v-if="hint" class="hint" role="note" aria-label="Move hint">
              <strong>{{
                hintPieceSelected
                  ? 'Now tap the glowing destination.'
                  : 'Pick up the glowing piece.'
              }}</strong>
              <p>{{ hint }}</p>
              <p><strong>Why:</strong> {{ hintExplanation }}</p>
            </div>
            <button class="hint-button" :disabled="!playerTurn" @click="showHint">Hint</button>
          </template>
          <div v-if="started" class="lesson-controls">
            <button :disabled="ply <= (color === 'w' ? 0 : 1) || complete" @click="undo">
              Take back my move</button
            ><button @click="start">Start over</button>
          </div>
          <button
            class="sound-button"
            aria-label="Move sound"
            :aria-pressed="preferences.soundEnabled"
            @click="preferences.toggleSound"
          >
            Sound {{ preferences.soundEnabled ? 'on' : 'off' }}
          </button>
          <div
            v-if="pendingPromotion"
            class="promotion-picker"
            role="group"
            aria-label="Choose promotion piece"
          >
            <button
              v-for="choice in pendingPromotion.choices"
              :key="choice"
              @click="play(pendingPromotion.from, pendingPromotion.to, choice)"
            >
              {{ promotionNames[choice] }}
            </button>
            <button @click="cancelPromotion">Cancel promotion</button>
          </div>
          <details v-if="started && !complete" class="keyboard-moves">
            <summary>Enter a move with the keyboard</summary>
            <p>
              Use the square labels on the board, for example from e2 to e4. For castling, enter the
              king’s starting and destination squares.
            </p>
            <form aria-label="Enter a lesson move" @submit.prevent="play(fromSquare, toSquare)">
              <label
                >From square<input
                  v-model="fromSquare"
                  pattern="[a-h][1-8]"
                  maxlength="2"
                  autocomplete="off"
                  required
              /></label>
              <label
                >To square<input
                  v-model="toSquare"
                  pattern="[a-h][1-8]"
                  maxlength="2"
                  autocomplete="off"
                  required
              /></label>
              <button :disabled="!playerTurn || !!pendingPromotion">Play move</button>
            </form>
          </details>
        </section>
      </div>
      <details v-if="started" class="lesson-notation">
        <summary>Moves played (chess notation)</summary>
        <ol aria-label="Lesson move history">
          <li v-for="(move, index) in position.history" :key="index">{{ move }}</li>
        </ol>
      </details>
    </template>
  </main>
</template>
<style scoped>
.guided-main {
  padding-block: 32px 65px;
  min-height: 75vh;
}
.back-link {
  font-size: 13px;
  color: var(--muted);
}
.lesson-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-block: 28px;
}
.lesson-heading h1 {
  font-size: 42px;
  letter-spacing: -1.5px;
  margin: 12px 0;
}
.lesson-role {
  font-size: 13px;
  color: var(--muted);
}
.guided-grid {
  display: grid;
  grid-template-columns: minmax(260px, 600px) minmax(250px, 1fr);
  gap: 48px;
}
.guided-board {
  margin: 8px 20px 20px 6px;
}
.board-toolbar,
.mobile-board-hint,
.mobile-feedback,
.mobile-move-notes {
  display: none;
}
.guided-panel {
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 28px;
  background: #faf9f4;
  align-self: start;
}
.guided-panel h2 {
  font:
    28px Georgia,
    serif;
  margin: 14px 0;
}
.guided-panel p {
  font-size: 14px;
  line-height: 1.8;
}
.how-to-play {
  color: var(--muted);
}
.guided-panel button {
  min-height: 44px;
  border: 1px solid #afbaa4;
  border-radius: 5px;
  background: #f4f4e9;
  color: var(--ink);
  padding: 12px 16px;
  font-size: 13px;
}
.guided-panel button.primary-link {
  background: var(--ink);
  color: white;
  width: 100%;
  margin-block: 16px;
}
.guided-panel button:disabled {
  opacity: 0.5;
  cursor: default;
}
.hint {
  padding: 14px;
  border-left: 3px solid #8b9c66;
  background: #eef0df;
}
.lesson-feedback {
  color: #76592d;
}
.lesson-phase {
  font-weight: 650;
}
progress {
  accent-color: var(--green);
  width: 100%;
  height: 8px;
}
.lesson-controls {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 20px;
}
.guided-panel button.sound-button {
  background: transparent;
  border: 0;
  padding-left: 0;
  margin-top: 20px;
  color: var(--muted);
}
.keyboard-moves {
  margin-top: 20px;
  border-top: 1px solid var(--line);
  padding-top: 16px;
}
.keyboard-moves form {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  align-items: end;
}
.keyboard-moves label {
  display: grid;
  gap: 8px;
  font-size: 13px;
}
.keyboard-moves input {
  width: 78px;
  min-height: 44px;
  padding: 10px;
  border: 1px solid #aab79f;
  border-radius: 4px;
}
.promotion-picker {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-block: 16px;
}
.lesson-options {
  margin-block: 18px;
  border-top: 1px solid var(--line);
  padding-top: 16px;
}
summary {
  cursor: pointer;
  font-size: 13px;
}
.lesson-options label {
  display: grid;
  gap: 8px;
  font-size: 13px;
  margin-top: 16px;
}
select {
  min-height: 44px;
  max-width: 100%;
  padding: 10px;
  background: white;
  border: 1px solid var(--line);
  border-radius: 4px;
}
.saved-notice {
  color: #376047;
}
.secondary-link {
  display: block;
  min-height: 44px;
  padding-block: 10px;
  text-decoration: underline;
  font-size: 13px;
  margin-block: 15px;
}
.lesson-notation {
  margin-top: 20px;
  font-size: 12px;
}
.lesson-notation ol {
  display: flex;
  gap: 18px;
  flex-wrap: wrap;
  padding-left: 20px;
}
@media (max-width: 850px) {
  .is-setting-up .guided-panel {
    grid-row: 1;
  }
  .board-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    margin-bottom: 12px;
  }
  .board-toolbar button {
    padding: 11px 18px;
    border: 1px solid #afbaa4;
    background: #eef0e5;
    color: var(--ink);
    border-radius: 5px;
    min-height: 44px;
  }
  .mobile-board-hint {
    display: block;
    padding: 16px;
    margin-top: 15px;
    background: #eef0df;
    border-left: 3px solid #8b9c66;
    font-size: 13px;
    line-height: 1.7;
  }
  .mobile-board-hint p {
    margin-block: 8px;
  }
  .mobile-feedback {
    display: block;
    color: #76592d;
    font-size: 13px;
    line-height: 1.7;
  }
  .guided-panel .hint-button,
  .guided-panel .hint,
  .guided-panel .panel-move-notes {
    display: none;
  }
  .mobile-move-notes {
    display: grid;
  }
  .guided-grid {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  .guided-board {
    width: calc(100% - 30px);
    max-width: 540px;
    margin-inline: auto;
  }
  .lesson-heading {
    display: block;
  }
  .lesson-heading h1 {
    font-size: 36px;
  }
  .guided-panel {
    padding: 22px;
  }
}
</style>
