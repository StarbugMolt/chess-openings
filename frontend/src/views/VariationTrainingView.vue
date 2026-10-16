<script setup lang="ts">
import { t, message, joinText, type Text } from '../i18n'
import { computed, nextTick, onBeforeUnmount, ref, shallowRef, watch, watchEffect } from 'vue'
import { useRoute } from 'vue-router'
import ChessBoard from '../components/chess/ChessBoard.vue'
import {
  getLesson,
  saveCompletion,
  type Completion,
  type LessonPayload,
  type PlayingColor,
} from '../api/lessons'
import {
  BrowserChessGame,
  type ChessSnapshot,
  type PromotionPiece,
} from '../domain/chess/BrowserChessGame'
import { VariationTrainer } from '../domain/training/VariationTrainer'
import { usePreferencesStore } from '../stores/preferences'
import { getPractice, type PracticeOverview } from '../api/practice'

const route = useRoute()
const preferences = usePreferencesStore()
const payload = shallowRef<LessonPayload>()
watchEffect(() => {
  if (payload.value)
    document.title = t('{p0} variation training · Chess Openings', {
      p0: payload.value.lesson.title,
    })
})
const practice = shallowRef<PracticeOverview>()
const trainer = shallowRef<VariationTrainer>()
const position = shallowRef<ChessSnapshot>()
const boardRegion = ref<HTMLElement>()
const color = ref<PlayingColor>('w')
const selectedLineIds = ref<string[]>([])
const hideNames = ref(true)
const namesRevealed = ref(false)
const started = ref(false)
const waiting = ref(false)
const ply = ref(0)
const complete = ref(false)
const assessment = computed(() => (complete.value ? trainer.value?.assessment : undefined))
const recalledDecisions = computed(() =>
  assessment.value?.usedUndo
    ? 0
    : (assessment.value?.decisions.filter((decision) => decision.attempts === 1 && !decision.hinted)
        .length ?? 0),
)
const hint = ref<Text>('')
const selectedSquare = ref<string>()
const feedback = ref<Text>('')
const announcement = ref<Text>('')
const loading = ref(false)
const error = ref<Text>('')
const setupError = ref<Text>('')
const fromSquare = ref('')
const toSquare = ref('')
const pendingPromotion = ref<{ from: string; to: string; choices: PromotionPiece[] }>()
const saving = ref(false)
const saved = ref(false)
const saveError = ref<Text>('')
const attemptId = ref('')
const seed = ref(0)
const promotionNames: Record<PromotionPiece, string> = {
  q: 'Queen',
  r: 'Rook',
  b: 'Bishop',
  n: 'Knight',
}
const learnedLineIds = computed(() => {
  const current = payload.value
  if (!current) return []
  return current.lesson.lines
    .filter((line) =>
      current.progress.some(
        (progress) =>
          progress.lessonId === current.lesson.id &&
          progress.revision === current.lesson.revision &&
          progress.playingColor === color.value &&
          progress.lineId === line.id &&
          progress.completions > 0,
      ),
    )
    .map((line) => line.id)
})
const selectedLines = computed(
  () => payload.value?.lesson.lines.filter((line) => selectedLineIds.value.includes(line.id)) ?? [],
)
const validSelection = computed(
  () => selectedLines.value.length > 0 && selectedLines.value.length <= 32,
)
const namesVisible = computed(() => !hideNames.value || namesRevealed.value || complete.value)
const playerTurn = computed(
  () => started.value && !complete.value && !waiting.value && position.value?.turn === color.value,
)
const destinations = computed(() =>
  playerTurn.value ? (position.value?.destinations ?? new Map()) : new Map(),
)
const expected = computed(() => {
  void ply.value
  return trainer.value?.expected
})
const previous = computed(() => {
  void ply.value
  return trainer.value?.previous
})
const eligibleLines = computed(() => {
  void ply.value
  return trainer.value?.eligibleLines ?? []
})
const choiceCount = computed(() => {
  void ply.value
  return trainer.value?.continuations.length ?? 0
})
const completedLine = computed(() => (complete.value ? trainer.value?.completedLine : undefined))
const canUndo = computed(() => {
  void ply.value
  return !complete.value && (trainer.value?.canUndo ?? false)
})
const hintMove = computed(() =>
  hint.value && playerTurn.value ? trainer.value?.hintSquares() : undefined,
)
const hintPieceSelected = computed(
  () => !!hintMove.value && selectedSquare.value === hintMove.value[0],
)
const maximumPlies = computed(() =>
  Math.max(0, ...selectedLines.value.map((line) => line.steps.length)),
)
const ownPlayed = computed(() => {
  const firstTurn = payload.value?.lesson.startFen.split(' ')[1]
  return Math.floor((ply.value + (firstTurn === color.value ? 1 : 0)) / 2)
})
let controller: AbortController | undefined
let loadGeneration = 0
let replyTimer: ReturnType<typeof setTimeout> | undefined
const mergedAttempts = new Set<string>()

function randomSeed(): number {
  return crypto.getRandomValues(new Uint32Array(1))[0] ?? 0
}
function newAttemptId(): string {
  const bytes = crypto.getRandomValues(new Uint8Array(16))
  bytes[6] = ((bytes[6] ?? 0) & 15) | 64
  bytes[8] = ((bytes[8] ?? 0) & 63) | 128
  const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('')
  return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`
}
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
function clearAttempt() {
  cancelReply()
  attemptId.value = ''
  trainer.value = undefined
  started.value = false
  complete.value = false
  ply.value = 0
  hint.value = ''
  selectedSquare.value = undefined
  feedback.value = ''
  pendingPromotion.value = undefined
  fromSquare.value = ''
  toSquare.value = ''
  saving.value = false
  saved.value = false
  saveError.value = ''
  setupError.value = ''
}
function allLearned() {
  selectedLineIds.value = learnedLineIds.value.slice(0, 32)
}
function mainLineOnly() {
  selectedLineIds.value = payload.value?.lesson.lines[0] ? [payload.value.lesson.lines[0].id] : []
}
function selectAllAvailable() {
  selectedLineIds.value = payload.value?.lesson.lines.slice(0, 32).map((line) => line.id) ?? []
}
function changeColor() {
  allLearned()
  setupError.value = ''
}
async function load() {
  controller?.abort()
  controller = new AbortController()
  const generation = ++loadGeneration
  clearAttempt()
  loading.value = true
  error.value = ''
  payload.value = undefined
  practice.value = undefined
  position.value = undefined
  selectedLineIds.value = []
  try {
    const [lessonResult, practiceResult] = await Promise.allSettled([
      getLesson(String(route.params.id), controller.signal),
      getPractice(controller.signal),
    ])
    if (lessonResult.status === 'rejected') throw lessonResult.reason
    const result = lessonResult.value
    if (generation !== loadGeneration) return
    payload.value = result
    practice.value = practiceResult.status === 'fulfilled' ? practiceResult.value : undefined
    color.value = result.lesson.recommendedColor
    allLearned()
    seed.value = randomSeed()
    namesRevealed.value = false
    position.value = new BrowserChessGame(result.lesson.startFen).snapshot()
  } catch {
    if (generation === loadGeneration)
      error.value =
        'This course could not be loaded. Check your connection and sign-in, then try again.'
  } finally {
    if (generation === loadGeneration) loading.value = false
  }
}
async function save() {
  const current = trainer.value
  const line = current?.completedLine
  const summary = current?.assessment
  const lessonSnapshot = payload.value?.lesson
  if (!current?.complete || !line || !summary || !lessonSnapshot || saving.value || saved.value)
    return
  const attempt = attemptId.value
  const completion: Completion = {
    attemptId: attempt,
    lessonId: current.lesson.id,
    revision: current.lesson.revision,
    lineId: line.id,
    playingColor: current.playingColor,
    hintCount: current.hintCount,
    path: current.path,
    mode: 'variation',
    selectedLineIds: [...current.lineIds],
    assessment: summary,
  }
  saving.value = true
  saveError.value = ''
  try {
    await saveCompletion(completion)
    if (attemptId.value === attempt) saved.value = true
    if (payload.value?.lesson === lessonSnapshot && !mergedAttempts.has(attempt)) {
      mergedAttempts.add(attempt)
      const matching = payload.value.progress.find(
        (progress) =>
          progress.lessonId === completion.lessonId &&
          progress.revision === completion.revision &&
          progress.lineId === completion.lineId &&
          progress.playingColor === completion.playingColor,
      )
      payload.value = {
        ...payload.value,
        progress: matching
          ? payload.value.progress.map((progress) =>
              progress === matching
                ? { ...progress, completions: progress.completions + 1 }
                : progress,
            )
          : [
              ...payload.value.progress,
              {
                lessonId: completion.lessonId,
                revision: completion.revision,
                lineId: completion.lineId,
                playingColor: completion.playingColor,
                completions: 1,
              },
            ],
      }
    }
  } catch {
    if (attemptId.value === attempt)
      saveError.value =
        'You finished the line, but we could not save it. Keep this page open and try again.'
  } finally {
    if (attemptId.value === attempt) saving.value = false
  }
}
function next() {
  const current = trainer.value
  if (!current || !started.value) return
  if (current.complete) {
    namesRevealed.value = true
    announcement.value = message('Practice complete. You played {p0}.', {
      p0: current.completedLine?.title ?? 'the selected line',
    })
    void save()
    return
  }
  if (current.playerTurn) return
  waiting.value = true
  const attempt = attemptId.value
  replyTimer = setTimeout(() => {
    replyTimer = undefined
    if (trainer.value !== current || attemptId.value !== attempt || !started.value) return
    const result = current.reply()
    if (result.kind === 'accepted') {
      preferences.moveSound()
      announcement.value = message('{p0} played {p1}. {p2}', {
        p0: color.value === 'w' ? 'Black' : 'White',
        p1: result.move.san,
        p2: current.complete ? 'Practice complete.' : 'Your turn.',
      })
    }
    waiting.value = false
    sync()
    next()
  }, 500)
}
function scrollToBoard() {
  boardRegion.value?.scrollIntoView({
    block: 'start',
    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
  })
}
function start() {
  const lesson = payload.value?.lesson
  if (!lesson || !validSelection.value) return
  clearAttempt()
  try {
    trainer.value = new VariationTrainer(
      lesson,
      [...selectedLineIds.value],
      color.value,
      seed.value,
      lesson.lines.flatMap((line) =>
        (['w', 'b'] as const).map((playingColor) => ({
          lessonId: lesson.id,
          revision: lesson.revision,
          lineId: line.id,
          playingColor,
          completions:
            payload.value?.progress.find(
              (entry) =>
                entry.lessonId === lesson.id &&
                entry.revision === lesson.revision &&
                entry.lineId === line.id &&
                entry.playingColor === playingColor,
            )?.completions ?? 0,
          reviewDue:
            practice.value?.courses
              .find((course) => course.id === lesson.id && course.revision === lesson.revision)
              ?.lines.find(
                (entry) => entry.lineId === line.id && entry.playingColor === playingColor,
              )?.reviewDue ?? false,
        })),
      ),
    )
    attemptId.value = newAttemptId()
    namesRevealed.value = false
    started.value = true
    sync()
    next()
    announcement.value = message('Training started. You play {p0}. {p1}', {
      p0: color.value === 'w' ? 'White' : 'Black',
      p1: waiting.value ? 'Watch the first reply.' : 'Your turn.',
    })
    if (window.matchMedia('(max-width: 850px)').matches) void nextTick(scrollToBoard)
  } catch {
    clearAttempt()
    setupError.value =
      'These training lines could not be prepared. Reload the course and try again.'
  }
}
function newMix() {
  seed.value = randomSeed()
  start()
}
function changeSetup() {
  clearAttempt()
  const lesson = payload.value?.lesson
  if (lesson) position.value = new BrowserChessGame(lesson.startFen).snapshot()
  announcement.value = 'Choose your training lines and pieces.'
}
function play(from: string, to: string, promotion?: PromotionPiece) {
  const current = trainer.value
  if (!playerTurn.value || !current || (pendingPromotion.value && !promotion)) return
  const result = current.play(from, to, promotion)
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
    announcement.value = message('You played {p0}. {p1}', {
      p0: result.move.san,
      p1: current.complete ? 'Practice complete.' : 'Watch the reply.',
    })
    next()
  } else {
    feedback.value =
      result.kind === 'outside-line' || result.kind === 'alternative-line'
        ? 'That move is legal, but outside the selected lines at this point. The board stays here. Try another move or use a hint.'
        : 'That piece cannot move there in this position. Try a highlighted square.'
    announcement.value = feedback.value
    sync()
  }
}
function showHint() {
  if (!playerTurn.value) return
  const revealsNames = !namesVisible.value
  namesRevealed.value = true
  hint.value = trainer.value?.hint() ?? ''
  announcement.value = message('{p0}{p1} Why: {p2}', {
    p0: revealsNames ? 'Variation names revealed. ' : '',
    p1: hint.value,
    p2: expected.value?.explanation ?? '',
  })
  if (window.matchMedia('(max-width: 850px)').matches) scrollToBoard()
}
function cancelPromotion() {
  pendingPromotion.value = undefined
  announcement.value = 'Promotion cancelled. Choose your move.'
}
function undo() {
  if (!canUndo.value) return
  cancelReply()
  pendingPromotion.value = undefined
  trainer.value?.undo()
  hint.value = ''
  selectedSquare.value = undefined
  feedback.value = ''
  sync()
  announcement.value = 'Your last move and its reply were taken back. Your turn.'
  next()
}
function revealNames() {
  namesRevealed.value = true
  announcement.value = message('Possible lines: {p0}.', {
    p0: joinText(
      eligibleLines.value.map((line) => line.title),
      ', ',
    ),
  })
}
function onExplanationToggle(event: Event) {
  if ((event.currentTarget as HTMLDetailsElement).open && started.value && previous.value)
    revealNames()
}
watch(
  () => route.params.id,
  () => {
    void load()
  },
  { immediate: true },
)
onBeforeUnmount(() => {
  loadGeneration++
  attemptId.value = ''
  controller?.abort()
  cancelReply()
})
</script>

<template>
  <main id="main-content" class="page-width variation-main">
    <p class="sr-only" aria-live="polite" aria-atomic="true">{{ t(announcement) }}</p>
    <RouterLink class="back-link" to="/learn">{{ t('← All lessons') }}</RouterLink>
    <p v-if="loading" role="status">{{ t('Preparing your training…') }}</p>
    <div v-else-if="error" role="alert">
      <p>{{ t(error) }}</p>
      <button @click="load">{{ t('Try again') }}</button>
      <RouterLink to="/login">{{ t('Sign in') }}</RouterLink>
    </div>
    <template v-else-if="payload && position">
      <header class="training-heading">
        <div>
          <span class="eyebrow">{{ t('VARIATION TRAINING') }}</span>
          <h1>{{ t(payload.lesson.title) }}</h1>
        </div>
        <p>
          {{
            t('You play {p0} · We play {p1}', {
              p0: color === 'w' ? t('White') : t('Black'),
              p1: color === 'w' ? t('Black') : t('White'),
            })
          }}
        </p>
      </header>
      <div class="training-grid" :class="{ 'is-setting-up': !started }">
        <div ref="boardRegion" class="training-board">
          <div v-if="started && !complete" class="board-toolbar">
            <span>{{
              t('{p0} · Move {p1} for you', {
                p0: waiting ? t('Opponent replying…') : t('Your turn'),
                p1: ownPlayed + 1,
              })
            }}</span>
            <button :disabled="!playerTurn || !!pendingPromotion" @click="showHint">
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
              t('{p0} training board, you play {p1}', {
                p0: t(payload.lesson.title),
                p1: color === 'w' ? t('White') : t('Black'),
              })
            "
            @select="selectedSquare = $event"
            @move="play"
          />
          <div
            v-if="pendingPromotion"
            class="promotion-picker"
            role="group"
            :aria-label="t('Choose promotion piece')"
          >
            <p>{{ t('Choose your promotion piece.') }}</p>
            <button
              v-for="choice in pendingPromotion.choices"
              :key="choice"
              @click="play(pendingPromotion.from, pendingPromotion.to, choice)"
            >
              {{ t(promotionNames[choice]) }}
            </button>
            <button @click="cancelPromotion">{{ t('Cancel promotion') }}</button>
          </div>
          <div v-if="hint && !complete" class="board-hint" role="note" :aria-label="t('Move hint')">
            <strong>{{
              hintPieceSelected
                ? t('Tap the glowing destination.')
                : t('Pick up the glowing piece.')
            }}</strong>
            <p>{{ t(hint) }}</p>
            <p>
              <strong>{{ t('Why:') }}</strong> {{ t(expected?.explanation) }}
            </p>
            <p v-if="choiceCount > 1">
              {{ t('One supported move is shown. You can choose another taught reply too.') }}
            </p>
          </div>
          <p v-if="feedback" class="training-feedback" role="status">{{ t(feedback) }}</p>
        </div>
        <section class="training-panel" :aria-label="t('Variation training instructions')">
          <template v-if="!started">
            <h2>{{ t('Choose your practice') }}</h2>
            <p v-if="payload.lesson.lines.length > 1">
              {{
                t(
                  'Mix the lines you have learned. We choose from their taught replies, and you can play any supported response.',
                )
              }}
            </p>
            <p v-if="payload.lesson.lines.length > 1" class="scope-notice">
              {{
                t(
                  'Supported replies favour lines with less practice or a due review. Your selected lines set the scope.',
                )
              }}
            </p>
            <p v-else>
              {{
                t(
                  'This course currently has one taught line. Practise its fixed sequence without move-by-move guidance; hints are here when you need them.',
                )
              }}
            </p>
            <label class="pieces-label"
              >{{ t('Your pieces') }}
              <select v-model="color" :aria-label="t('Your pieces')" @change="changeColor">
                <option value="w">
                  {{ t('White')
                  }}{{ payload.lesson.recommendedColor === 'w' ? t(' (recommended)') : '' }}
                </option>
                <option value="b">
                  {{ t('Black')
                  }}{{ payload.lesson.recommendedColor === 'b' ? t(' (recommended)') : '' }}
                </option>
              </select>
            </label>
            <p v-if="!learnedLineIds.length" class="scope-notice">
              {{
                t(
                  'You have not completed a line in this course revision with these pieces yet. Try a guided lesson first, or select the lines you want to practise below.',
                )
              }}
            </p>
            <p v-else class="scope-notice">
              {{
                t(
                  learnedLineIds.length === 1
                    ? '{count} line previously completed with these pieces. Use All learned to select it.'
                    : '{count} lines previously completed with these pieces. Use All learned to select them.',
                  { count: learnedLineIds.length },
                )
              }}
            </p>
            <RouterLink :to="`/learn/${payload.lesson.id}`">{{
              t('Learn these moves with guidance →')
            }}</RouterLink>
            <fieldset class="line-choices">
              <legend>{{ t('Training lines') }}</legend>
              <div class="scope-presets">
                <button :disabled="!learnedLineIds.length" @click="allLearned">
                  {{ t('All learned') }}
                </button>
                <button @click="mainLineOnly">{{ t('Main line only') }}</button>
                <button @click="selectAllAvailable">{{ t('Select all available') }}</button>
              </div>
              <label v-for="line in payload.lesson.lines" :key="line.id" class="line-choice">
                <input
                  v-model="selectedLineIds"
                  type="checkbox"
                  :value="line.id"
                  :aria-label="t(line.title)"
                  :disabled="!selectedLineIds.includes(line.id) && selectedLineIds.length >= 32"
                />
                <span
                  ><strong>{{ t(line.title) }}</strong
                  ><small>{{ t(line.description) }}</small
                  ><small v-if="learnedLineIds.includes(line.id)">{{
                    t('Previously completed with {p0}', {
                      p0: color === 'w' ? t('White') : t('Black'),
                    })
                  }}</small></span
                >
              </label>
            </fieldset>
            <p v-if="selectedLines.length === 1" class="scope-notice">
              {{ t('One line is selected: this is a fixed sequence.') }}
              <template v-if="payload.lesson.lines.length > 1">{{
                t('Select more lines to mix supported choices.')
              }}</template>
            </p>
            <p v-else-if="selectedLines.length > 1" class="scope-notice">
              {{
                t(
                  '{p0} lines selected. Shared opening moves may stay the same before a supported choice appears.',
                  { p0: selectedLines.length },
                )
              }}
            </p>
            <p v-else class="scope-notice">
              {{ t('Select at least one training line to start.') }}
            </p>
            <p v-if="payload.lesson.lines.length > 32">
              {{ t('Select up to 32 lines for one session.') }}
            </p>
            <label class="checkbox-label"
              ><input v-model="hideNames" type="checkbox" />{{
                t('Hide variation names during practice')
              }}</label
            >
            <p class="muted">
              {{
                t(
                  'You can reveal names at any time. Hint and Why that move? reveal names alongside the original explanations. Names are also shown when the line ends.',
                )
              }}
            </p>
            <p v-if="setupError" role="alert">{{ t(setupError) }}</p>
            <button class="primary-link" :disabled="!validSelection" @click="start">
              {{ t('Start training') }} <span aria-hidden="true">→</span>
            </button>
          </template>
          <template v-else-if="complete && completedLine">
            <span class="eyebrow">{{ t('NICE WORK') }}</span>
            <h2>{{ t('Practice complete.') }}</h2>
            <div role="region" :aria-label="t('Current variation')">
              <p>
                {{ t('You played {p0} of your moves in', { p0: ownPlayed }) }}
                <strong>{{ t(completedLine.title) }}</strong
                >.
              </p>
            </div>
            <p>{{ t(completedLine.description) }}</p>
            <p v-if="assessment?.usedUndo">
              {{
                t(
                  'Takebacks used. This practice is recorded with help; try a fresh attempt to check your recall.',
                )
              }}
            </p>
            <p v-else-if="assessment">
              {{
                t('{p0} of {p1} taught decisions played on the first try without a hint.', {
                  p0: recalledDecisions,
                  p1: assessment.decisions.length,
                })
              }}
            </p>
            <p>
              {{
                t(
                  'This is the end of the taught line. Saved practice records this line and revision; it does not mean you know the whole opening.',
                )
              }}
            </p>
            <p v-if="saving" role="status">{{ t('Saving your practice…') }}</p>
            <p v-else-if="saved" role="status" class="scope-notice">
              {{ t('Practice saved to your account.') }}
            </p>
            <div v-if="saveError" role="alert">
              <p>{{ t(saveError) }}</p>
              <button @click="save">{{ t('Try saving again') }}</button>
              <RouterLink to="/login" target="_blank">{{ t('Sign in in another tab') }}</RouterLink>
            </div>
            <button class="primary-link" @click="newMix">{{ t('Try a new mix') }}</button>
            <button @click="start">{{ t('Repeat this mix') }}</button>
            <RouterLink class="practice-link" to="/progress">{{
              t('See your practice & reviews →')
            }}</RouterLink>
          </template>
          <template v-else>
            <h2>{{ t('Variation practice') }}</h2>
            <p role="status">
              {{
                waiting
                  ? t('{p0} is replying…', { p0: color === 'w' ? t('Black') : t('White') })
                  : t('Your turn')
              }}
            </p>
            <progress :value="ply" :max="maximumPlies" :aria-label="t('Training progress')" />
            <p v-if="selectedLines.length === 1">
              {{ t('One selected line · a fixed sequence.') }}
            </p>
            <p v-else>
              {{
                t('{p0} selected lines · replies stay within these taught paths.', {
                  p0: selectedLines.length,
                })
              }}
            </p>
            <p>
              {{
                t(
                  'Tap a piece, then a highlighted square, or drag it. Legal moves outside your selected lines stay on this position so you can retry.',
                )
              }}
            </p>
            <div
              v-if="namesVisible"
              class="variation-names"
              role="region"
              :aria-label="t('Current variation')"
            >
              <strong>{{
                t('Possible {p0}:', { p0: eligibleLines.length === 1 ? t('line') : t('lines') })
              }}</strong>
              {{ eligibleLines.map((line) => t(line.title)).join(' · ') }}
            </div>
            <div v-else class="name-controls">
              <p>{{ t('Variation names are hidden.') }}</p>
              <p>{{ t('Hints and explanations reveal variation names.') }}</p>
              <button @click="revealNames">{{ t('Reveal variation names') }}</button>
            </div>
          </template>
          <details
            v-if="started && previous"
            class="move-explanation"
            @toggle="onExplanationToggle"
          >
            <summary>{{ t('Why that move?') }}</summary>
            <p>{{ t(previous.explanation) }}</p>
          </details>
          <div v-if="started" class="training-controls">
            <template v-if="!complete">
              <button :disabled="!canUndo" @click="undo">{{ t('Take back my move') }}</button>
              <button @click="start">{{ t('Start over') }}</button>
            </template>
            <button @click="changeSetup">{{ t('Change training lines') }}</button>
          </div>
          <button
            class="sound-button"
            :aria-label="t('Move sound')"
            :aria-pressed="preferences.soundEnabled"
            @click="preferences.toggleSound"
          >
            {{ t('Sound {p0}', { p0: preferences.soundEnabled ? t('on') : t('off') }) }}
          </button>
          <details v-if="started && !complete" class="keyboard-moves">
            <summary>{{ t('Enter a move with the keyboard') }}</summary>
            <p>
              {{
                t(
                  'Use square labels, for example from e2 to e4. For castling, enter the king’s starting and destination squares.',
                )
              }}
            </p>
            <form
              :aria-label="t('Enter a training move')"
              @submit.prevent="play(fromSquare, toSquare)"
            >
              <label
                >{{ t('From square')
                }}<input
                  v-model="fromSquare"
                  pattern="[a-h][1-8]"
                  maxlength="2"
                  autocomplete="off"
                  autocapitalize="none"
                  required
              /></label>
              <label
                >{{ t('To square')
                }}<input
                  v-model="toSquare"
                  pattern="[a-h][1-8]"
                  maxlength="2"
                  autocomplete="off"
                  autocapitalize="none"
                  required
              /></label>
              <button :disabled="!playerTurn || !!pendingPromotion">{{ t('Play move') }}</button>
            </form>
          </details>
        </section>
      </div>
      <details v-if="started" class="training-notation">
        <summary>{{ t('Moves played (chess notation)') }}</summary>
        <ol :aria-label="t('Training move history')">
          <li v-for="(move, index) in position.history" :key="index">{{ move }}</li>
        </ol>
      </details>
    </template>
  </main>
</template>

<style scoped>
.practice-link {
  display: block;
  min-height: 44px;
  padding-block: 12px;
  margin-block: 12px;
  text-decoration: underline;
}
.variation-main {
  padding-block: 32px 65px;
  min-height: 75vh;
}
.back-link,
.muted {
  color: var(--muted);
  font-size: 13px;
}
.training-heading {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-block: 28px;
}
.training-heading h1 {
  font-size: 42px;
  letter-spacing: -1.5px;
  margin: 12px 0;
}
.training-heading > p {
  color: var(--muted);
  font-size: 13px;
}
.training-grid {
  display: grid;
  grid-template-columns: minmax(260px, 600px) minmax(270px, 1fr);
  gap: 40px;
  align-items: start;
}
.training-board {
  min-width: 0;
  margin: 8px 20px 20px 6px;
  scroll-margin-block: 12px;
}
.training-panel {
  min-width: 0;
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 24px;
  background: #faf9f4;
}
h2 {
  font:
    28px Georgia,
    serif;
  margin: 0 0 16px;
}
p {
  font-size: 14px;
  line-height: 1.7;
}
button,
select {
  min-height: 44px;
  border: 1px solid #aeb9a4;
  border-radius: 4px;
  padding: 10px 14px;
  background: #fbfaf5;
  color: var(--ink);
}
button:disabled {
  opacity: 0.55;
  cursor: default;
}
button.primary-link {
  background: var(--ink);
  color: #fff;
  margin-block: 14px;
}
.pieces-label {
  display: grid;
  gap: 7px;
  font-size: 14px;
  margin-block: 18px;
}
.scope-notice,
.board-hint,
.training-feedback {
  padding: 12px 14px;
  background: #edf0e4;
  border-radius: 4px;
}
.line-choices {
  min-width: 0;
  margin: 22px 0 14px;
  border: 1px solid var(--line);
  padding: 12px;
  border-radius: 5px;
}
.line-choices legend {
  padding-inline: 6px;
  font-size: 15px;
  font-weight: 600;
}
.scope-presets,
.training-controls,
.promotion-picker {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.scope-presets button {
  flex: 1 1 auto;
  font-size: 12px;
  padding-inline: 8px;
}
.line-choice,
.checkbox-label {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  padding-block: 12px;
  font-size: 14px;
  cursor: pointer;
}
.line-choice + .line-choice {
  border-top: 1px solid var(--line);
}
.line-choice input,
.checkbox-label input {
  width: 20px;
  height: 20px;
  margin: 0;
  flex: 0 0 auto;
  accent-color: var(--green);
}
.line-choice small {
  display: block;
  line-height: 1.6;
  font-size: 12px;
  color: var(--muted);
  margin-top: 5px;
}
.board-toolbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  min-height: 58px;
  font-size: 13px;
  margin-top: -8px;
}
.board-toolbar button {
  flex: 0 0 auto;
}
.board-hint {
  margin-top: 18px;
}
.board-hint p {
  margin-block: 8px;
}
.training-feedback {
  margin-block: 14px 0;
}
.name-controls,
.training-controls,
.move-explanation,
.keyboard-moves {
  margin-top: 20px;
}
.training-controls {
  border-top: 1px solid var(--line);
  padding-top: 18px;
}
summary {
  cursor: pointer;
  min-height: 44px;
  padding-block: 12px;
  font-size: 13px;
}
.sound-button {
  margin-top: 18px;
  border-color: transparent;
  background: transparent;
}
.promotion-picker {
  margin-top: 18px;
  border: 2px solid var(--green);
  padding: 12px;
  border-radius: 5px;
}
.promotion-picker p {
  width: 100%;
  margin: 0;
}
.keyboard-moves form {
  display: flex;
  align-items: end;
  flex-wrap: wrap;
  gap: 10px;
}
.keyboard-moves label {
  display: grid;
  gap: 6px;
  font-size: 13px;
}
.keyboard-moves input {
  width: 85px;
  min-height: 44px;
  border: 1px solid #aeb9a4;
  border-radius: 4px;
  background: #fff;
  padding: 8px;
}
progress {
  width: 100%;
  height: 5px;
  accent-color: var(--green);
}
.training-notation {
  margin-top: 22px;
}
.training-notation ol {
  display: flex;
  flex-wrap: wrap;
  gap: 12px 25px;
  font-size: 13px;
}
.variation-names {
  overflow-wrap: anywhere;
}
@media (max-width: 850px) {
  .variation-main {
    padding-inline: 20px;
    padding-block: 22px 45px;
  }
  .training-heading {
    display: block;
    margin-block: 22px;
  }
  .training-heading h1 {
    font-size: 35px;
  }
  .training-grid {
    display: flex;
    flex-direction: column;
    gap: 18px;
  }
  .training-board {
    width: calc(100% - 12px);
    margin: 6px 6px 16px;
  }
  .training-panel {
    width: 100%;
    padding: 20px;
  }
  .is-setting-up .training-panel {
    order: -1;
  }
  .training-heading > p {
    margin-block: 8px;
  }
}
</style>
