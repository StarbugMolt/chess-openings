<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue'
import {
  getPractice,
  PracticeError,
  type PracticeCourse,
  type PracticeLine,
  type ReviewIdentity,
} from '../api/practice'
import type { PlayingColor } from '../api/lessons'
import type { PracticeOverview } from '../api/practice'
import { useAccountStore } from '../stores/account'

const account = useAccountStore()
const overview = shallowRef<PracticeOverview>()
const loading = ref(true)
const error = ref('')
const sessionEnded = ref(false)
const pieces = ref<Record<string, PlayingColor>>({})
const showAllReviews = ref(false)
const announcement = ref('')
let controller: AbortController | undefined
let generation = 0
const reviews = computed(() =>
  (overview.value?.reviewQueue ?? []).flatMap((reference) => {
    const course = overview.value?.courses.find(
      (course) => course.id === reference.lessonId && course.revision === reference.revision,
    )
    const line = course?.lines.find(
      (line) => line.lineId === reference.lineId && line.playingColor === reference.playingColor,
    )
    return course && line ? [{ reference, course, line }] : []
  }),
)
const visibleReviews = computed(() =>
  showAllReviews.value ? reviews.value : reviews.value.slice(0, 5),
)
const hasPractice = computed(
  () =>
    overview.value?.courses.some((course) => course.lines.some((line) => line.completions)) ||
    !!overview.value?.history.length,
)
function colorName(color: PlayingColor): string {
  return color === 'w' ? 'White' : 'Black'
}
function courseColor(course: PracticeCourse): PlayingColor {
  return pieces.value[course.id] ?? course.recommendedColor
}
function courseLines(course: PracticeCourse): PracticeLine[] {
  return course.lines.filter((line) => line.playingColor === courseColor(course))
}
function practisedCount(course: PracticeCourse): number {
  return courseLines(course).filter((line) => line.completions > 0).length
}
function reviewLink(reference: ReviewIdentity) {
  return {
    path: `/learn/${encodeURIComponent(reference.lessonId)}`,
    query: {
      line: reference.lineId,
      color: reference.playingColor,
      revision: String(reference.revision),
    },
  }
}
function lineLink(course: PracticeCourse, line: PracticeLine) {
  return reviewLink({
    lessonId: course.id,
    revision: course.revision,
    lineId: line.lineId,
    playingColor: line.playingColor,
  })
}
function startLink(course: PracticeCourse) {
  const firstLine = courseLines(course)[0]
  return firstLine ? lineLink(course, firstLine) : `/learn/${encodeURIComponent(course.id)}`
}
function lineStatus(line: PracticeLine): string {
  if (!line.completions) return 'New line'
  if (line.reviewDue) return 'Ready to review'
  if (line.lastOutcome === 'unknown') return 'Practice recorded'
  return line.lastOutcome === 'recalled' ? 'Recalled without help' : 'Practised with help'
}
function formatDate(value: string): string {
  return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(
    new Date(value),
  )
}
function focusPositions(line: PracticeLine) {
  return line.positions.filter(
    (position) =>
      position.practices > 0 &&
      (position.recalled < position.practices || position.hints > 0 || position.retries > 0),
  )
}
function changePieces(course: PracticeCourse, event: Event) {
  const color = (event.target as HTMLSelectElement).value
  if (color !== 'w' && color !== 'b') return
  pieces.value = { ...pieces.value, [course.id]: color }
  announcement.value = `Showing ${course.title} progress for ${colorName(color)}.`
}
async function load() {
  controller?.abort()
  controller = new AbortController()
  const requestGeneration = ++generation
  loading.value = true
  error.value = ''
  sessionEnded.value = false
  try {
    const result = await getPractice(controller.signal)
    if (requestGeneration !== generation) return
    overview.value = result
    announcement.value = result.reviewQueue.length
      ? `Showing ${result.reviewQueue.length} suggested reviews.`
      : 'Your practice has loaded. No review suggestions are ready yet.'
  } catch (cause) {
    if (requestGeneration !== generation) return
    const expired = cause instanceof PracticeError && cause.status === 401
    if (expired) {
      // Refresh the shared auth cache before exposing /login: its guest guard uses it.
      await account.load(true)
      if (requestGeneration !== generation) return
      overview.value = undefined
    }
    sessionEnded.value = expired && !account.user
    error.value = sessionEnded.value
      ? 'Your session has ended. Sign in to see your private practice.'
      : 'Your practice could not be loaded. Check your connection and try again.'
    announcement.value = error.value
  } finally {
    if (requestGeneration === generation) loading.value = false
  }
}
onMounted(() => {
  void load()
})
onBeforeUnmount(() => {
  generation++
  controller?.abort()
})
</script>

<template>
  <main id="main-content" class="page-width progress-main">
    <p class="sr-only" aria-live="polite" aria-atomic="true">{{ announcement }}</p>
    <header class="progress-heading">
      <div>
        <span class="eyebrow">A LITTLE PRACTICE, OFTEN</span>
        <h1>Your practice.</h1>
      </div>
      <RouterLink class="choose-lessons" to="/learn">Choose a lesson →</RouterLink>
    </header>
    <p class="progress-intro">
      See what you have practised and revisit a line when you are ready. Hints are always welcome.
    </p>
    <p v-if="loading" role="status">Loading your practice…</p>
    <div v-else-if="error" class="progress-error" role="alert">
      <p>{{ error }}</p>
      <RouterLink v-if="sessionEnded" class="primary-link" to="/login?next=/progress"
        >Sign in again</RouterLink
      >
      <button v-else @click="load">Try again</button>
    </div>
    <template v-else-if="overview">
      <section v-if="!hasPractice" class="first-practice" aria-label="Start practising">
        <h2>Your first line starts here.</h2>
        <p>
          Finish a guided line to save your practice. Then come back to see what feels familiar and
          what to revisit.
        </p>
        <RouterLink class="primary-link" to="/learn">Start a guided lesson →</RouterLink>
      </section>
      <section class="review-section" aria-label="Ready to review">
        <h2 id="review-heading">Ready to review</h2>
        <p v-if="reviews.length">
          Up to 20 suggested reviews from your current course versions are shown here. A review is a
          suggestion; you can practise any time.
        </p>
        <p v-else-if="hasPractice">
          Nothing is ready for review yet. You can repeat a favourite line or learn another below.
        </p>
        <p v-else>Your reviews will appear here after you start practising.</p>
        <ul v-if="reviews.length" class="review-list" aria-label="Lines ready to review">
          <li
            v-for="review in visibleReviews"
            :key="`${review.reference.lessonId}/${review.reference.revision}/${review.reference.lineId}/${review.reference.playingColor}`"
          >
            <div>
              <span class="pieces-badge">You play {{ colorName(review.line.playingColor) }}</span>
              <h3>{{ review.course.title }}</h3>
              <p>{{ review.line.title }}</p>
              <p v-if="review.line.lastOutcome === 'unknown'" class="muted">
                Earlier practice recorded · recall not tracked yet
              </p>
              <p v-else-if="review.line.nextReviewAt" class="muted">
                Suggested review:
                <time :datetime="review.line.nextReviewAt">{{
                  formatDate(review.line.nextReviewAt)
                }}</time>
              </p>
            </div>
            <RouterLink class="primary-link" :to="reviewLink(review.reference)"
              >Review this line<span aria-hidden="true">→</span></RouterLink
            >
          </li>
        </ul>
        <button
          v-if="reviews.length > 5"
          class="review-more"
          :aria-expanded="showAllReviews"
          @click="showAllReviews = !showAllReviews"
        >
          {{ showAllReviews ? 'Show fewer reviews' : 'Show more suggestions' }}
        </button>
      </section>
      <section aria-labelledby="course-progress-heading">
        <h2 id="course-progress-heading">Your course progress</h2>
        <p class="muted">
          Counts cover the current course version and the pieces shown. Practice with White and
          Black is recorded separately.
        </p>
        <p class="muted">
          Each course starts with its recommended pieces. Switch pieces to see your other practice.
        </p>
        <div class="course-progress-grid">
          <article
            v-for="course in overview.courses"
            :key="course.id"
            class="course-progress-card"
            role="region"
            :aria-label="`${course.title} practice`"
          >
            <span class="pieces-badge"
              >You play {{ colorName(courseColor(course)) }} · Version {{ course.revision }}</span
            >
            <h3>{{ course.title }}</h3>
            <label class="pieces-label"
              >Your pieces<select
                :value="courseColor(course)"
                :aria-label="`Pieces for ${course.title}`"
                @change="changePieces(course, $event)"
              >
                <option value="w">
                  White{{ course.recommendedColor === 'w' ? ' (recommended)' : '' }}
                </option>
                <option value="b">
                  Black{{ course.recommendedColor === 'b' ? ' (recommended)' : '' }}
                </option>
              </select></label
            >
            <p class="coverage-count">
              {{ practisedCount(course) }} of {{ course.lineCount }} current
              {{ course.lineCount === 1 ? 'line' : 'lines' }} practised as
              {{ colorName(courseColor(course)) }}
            </p>
            <progress
              :value="practisedCount(course)"
              :max="course.lineCount"
              :aria-label="`${course.title}: current lines practised as ${colorName(courseColor(course))}`"
            />
            <details class="course-lines">
              <summary>
                View {{ course.lineCount }} {{ course.lineCount === 1 ? 'line' : 'lines' }}
              </summary>
              <ul :aria-label="`${course.title} lines as ${colorName(courseColor(course))}`">
                <li
                  v-for="line in courseLines(course)"
                  :key="`${line.lineId}/${line.playingColor}`"
                >
                  <h4>{{ line.title }}</h4>
                  <p class="line-status">{{ lineStatus(line) }}</p>
                  <p v-if="line.completions">
                    {{ line.completions }}
                    {{ line.completions === 1 ? 'saved practice' : 'saved practices'
                    }}<template v-if="line.lastPractisedAt">
                      · Last practised
                      <time :datetime="line.lastPractisedAt">{{
                        formatDate(line.lastPractisedAt)
                      }}</time></template
                    >
                  </p>
                  <p v-if="line.completions && !line.assessedCompletions" class="muted">
                    Earlier practice was saved before recall tracking. Review to see what you
                    remember.
                  </p>
                  <template v-else-if="line.assessedCompletions">
                    <p>
                      Recall tracked in {{ line.assessedCompletions }} of
                      {{ line.completions }} saved practices.
                      {{ line.unassistedCompletions }} finished without help.
                    </p>
                    <p v-if="line.lastOutcome === 'recalled'">
                      Latest tracked practice: recalled without hints, retries or takebacks.
                    </p>
                    <p v-else>
                      Latest tracked practice: used a hint, retry or takeback. Repetition helps
                      these moves feel familiar.
                    </p>
                  </template>
                  <p v-if="line.nextReviewAt">
                    Suggested review:
                    <time :datetime="line.nextReviewAt">{{ formatDate(line.nextReviewAt) }}</time>
                  </p>
                  <details v-if="focusPositions(line).length" class="decision-notes">
                    <summary>Moves to revisit</summary>
                    <ul>
                      <li v-for="position in focusPositions(line)" :key="position.ply">
                        <p>{{ position.hint }}</p>
                        <p class="muted">
                          Recalled on first try without a hint in {{ position.recalled }} of
                          {{ position.practices }} tracked practices.<template
                            v-if="position.hints"
                          >
                            Hints used in {{ position.hints }}.</template
                          ><template v-if="position.retries">
                            {{ position.retries }}
                            additional {{ position.retries === 1 ? 'try' : 'tries' }} (including
                            takebacks).</template
                          >
                        </p>
                      </li>
                    </ul>
                  </details>
                  <RouterLink
                    class="line-action"
                    :to="lineLink(course, line)"
                    :aria-label="`${line.completions ? 'Review' : 'Learn'} ${course.title}: ${line.title} as ${colorName(line.playingColor)}`"
                    >{{ line.completions ? 'Review this line' : 'Learn this line' }} →</RouterLink
                  >
                </li>
              </ul>
            </details>
            <RouterLink v-if="!practisedCount(course)" class="line-action" :to="startLink(course)"
              >Start with guidance →</RouterLink
            >
          </article>
        </div>
        <p v-if="!overview.courses.length">
          Courses are being prepared. <RouterLink to="/learn">Browse lessons</RouterLink> for the
          latest additions.
        </p>
      </section>
      <details v-if="overview.history.length" class="earlier-practice">
        <summary>Earlier course versions</summary>
        <p>
          Your earlier practice is kept here. Course updates start separate progress, so these
          records do not add to current line coverage.
        </p>
        <ul aria-label="Earlier course practice">
          <li
            v-for="entry in overview.history"
            :key="`${entry.lessonId}/${entry.revision}/${entry.lineId}/${entry.playingColor}`"
          >
            <h3>{{ entry.title }} · Version {{ entry.revision }}</h3>
            <p>{{ entry.lineTitle }} · You played {{ colorName(entry.playingColor) }}</p>
            <p>
              {{ entry.completions }}
              {{ entry.completions === 1 ? 'saved practice' : 'saved practices' }} · Last practised
              <time :datetime="entry.lastPractisedAt">{{ formatDate(entry.lastPractisedAt) }}</time>
            </p>
            <p v-if="entry.assessedCompletions" class="muted">
              {{ entry.unassistedCompletions }} of {{ entry.assessedCompletions }} tracked practices
              finished without help.
            </p>
            <p v-else class="muted">Recall was not tracked for this practice.</p>
          </li>
        </ul>
      </details>
      <p class="progress-note">
        These records describe practice of the taught lines, not your overall chess strength. Hints
        and retries help you learn; a legal move outside an exercise is not automatically a chess
        mistake.
      </p>
    </template>
  </main>
</template>

<style scoped>
.progress-main {
  padding-block: 42px 70px;
  min-height: 75vh;
}
.progress-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}
h1 {
  font-size: clamp(38px, 5vw, 58px);
  letter-spacing: -1.8px;
  margin-block: 20px;
}
h2 {
  font:
    30px Georgia,
    serif;
  margin: 0 0 16px;
}
h3 {
  font:
    26px Georgia,
    serif;
  margin: 12px 0;
  overflow-wrap: anywhere;
}
h4 {
  font-size: 15px;
  line-height: 1.6;
  margin: 0 0 8px;
}
p {
  font-size: 14px;
  line-height: 1.8;
}
.progress-intro,
.progress-note,
.muted {
  color: var(--muted);
}
.progress-intro {
  max-width: 680px;
  margin-bottom: 30px;
}
.choose-lessons,
.line-action {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  font-size: 14px;
}
.first-practice,
.progress-error {
  border: 1px solid var(--line);
  background: #edf0e4;
  padding: 25px;
  border-radius: 7px;
  margin-block: 28px;
}
.review-section {
  padding-block: 25px 32px;
  margin-bottom: 30px;
  border-bottom: 1px solid var(--line);
}
.review-list,
.course-lines ul,
.earlier-practice ul {
  list-style: none;
  margin: 0;
  padding: 0;
}
.review-list {
  display: grid;
  gap: 14px;
  margin-block: 22px;
}
.review-list > li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 22px;
  border: 1px solid var(--line);
  border-radius: 7px;
  background: #faf9f4;
}
.review-list > li > div {
  min-width: 0;
}
.review-list p {
  margin-block: 6px;
}
.review-list .primary-link {
  flex-shrink: 0;
}
.pieces-badge {
  font-size: 12px;
  color: var(--muted);
  line-height: 1.8;
}
.pieces-label {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  font-size: 13px;
  margin-block: 18px;
}
.pieces-label select {
  min-height: 44px;
  padding: 8px;
  border: 1px solid #aeb9a4;
  border-radius: 4px;
  background: #faf9f4;
  color: var(--ink);
}
.course-progress-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 22px;
}
.course-progress-card {
  min-width: 0;
  padding: 25px;
  border: 1px solid var(--line);
  border-radius: 7px;
  background: #faf9f4;
}
.coverage-count {
  margin-bottom: 10px;
}
progress {
  width: 100%;
  height: 7px;
  accent-color: var(--green);
}
summary {
  min-height: 44px;
  padding-block: 12px;
  line-height: 1.6;
  font-size: 14px;
  cursor: pointer;
}
.course-lines {
  margin-top: 14px;
}
.course-lines > ul > li {
  border-top: 1px solid var(--line);
  padding-block: 18px;
}
.course-lines li p {
  margin-block: 7px;
  font-size: 13px;
}
.line-status {
  font-weight: 600;
}
.decision-notes ul {
  padding-left: 15px;
  border-left: 2px solid var(--line);
}
.decision-notes li + li {
  margin-top: 16px;
}
.earlier-practice {
  margin-top: 30px;
  border-top: 1px solid var(--line);
  padding-top: 10px;
}
.earlier-practice li {
  padding-block: 15px;
  border-top: 1px solid var(--line);
}
.earlier-practice h3 {
  font-size: 22px;
}
.progress-note {
  max-width: 800px;
  margin-top: 30px;
  font-size: 13px;
}
button {
  min-height: 44px;
  padding: 10px 15px;
  border: 1px solid #aeb9a4;
  border-radius: 4px;
  background: #faf9f4;
  color: var(--ink);
}
.primary-link {
  min-height: 44px;
}
@media (max-width: 760px) {
  .progress-main {
    padding-inline: 20px;
    padding-top: 30px;
  }
  .progress-heading {
    display: block;
  }
  .course-progress-grid {
    grid-template-columns: 1fr;
  }
  .review-list > li {
    flex-direction: column;
    align-items: stretch;
    padding: 20px;
    gap: 12px;
  }
  .review-list .primary-link {
    align-self: start;
  }
  .course-progress-card {
    padding: 22px;
  }
}
</style>
