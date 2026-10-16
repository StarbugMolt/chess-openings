<script setup lang="ts">
import { t, message, formatDate, type Text } from '../i18n'
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
const error = ref<Text>('')
const sessionEnded = ref(false)
const pieces = ref<Record<string, PlayingColor>>({})
const showAllReviews = ref(false)
const announcement = ref<Text>('')
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
  announcement.value = message('Showing {p0} progress for {p1}.', {
    p0: course.title,
    p1: colorName(color),
  })
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
      ? message('Showing {p0} suggested reviews.', { p0: result.reviewQueue.length })
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
    <p class="sr-only" aria-live="polite" aria-atomic="true">{{ t(announcement) }}</p>
    <header class="progress-heading">
      <h1>{{ t('Your practice.') }}</h1>
      <RouterLink class="choose-lessons" to="/learn">{{ t('Choose a lesson') }}</RouterLink>
    </header>
    <p class="progress-intro">
      {{
        t(
          'See what you have practised and revisit a line when you are ready. Hints are always welcome.',
        )
      }}
    </p>
    <p v-if="loading" role="status">{{ t('Loading your practice…') }}</p>
    <div v-else-if="error" class="notice status-actions progress-error" role="alert">
      <p>{{ t(error) }}</p>
      <RouterLink v-if="sessionEnded" class="primary-link" to="/login?next=/progress">{{
        t('Sign in again')
      }}</RouterLink>
      <button v-else @click="load">{{ t('Try again') }}</button>
    </div>
    <template v-else-if="overview">
      <section v-if="!hasPractice" class="first-practice" :aria-label="t('Start practising')">
        <h2>{{ t('Your first line starts here.') }}</h2>
        <p>
          {{
            t(
              'Finish a guided line to save your practice. Then come back to see what feels familiar and what to revisit.',
            )
          }}
        </p>
        <RouterLink class="primary-link" to="/learn">{{ t('Start a guided lesson') }}</RouterLink>
      </section>
      <section class="review-section" :aria-label="t('Ready to review')">
        <h2 id="review-heading">{{ t('Ready to review') }}</h2>
        <p v-if="reviews.length" class="muted">
          {{
            t(
              'Up to 20 suggested reviews from your current course versions are shown here. A review is a suggestion; you can practise any time.',
            )
          }}
        </p>
        <p v-else-if="hasPractice" class="muted">
          {{
            t(
              'Nothing is ready for review yet. You can repeat a favourite line or learn another below.',
            )
          }}
        </p>
        <p v-else class="muted">
          {{ t('Your reviews will appear here after you start practising.') }}
        </p>
        <ul v-if="reviews.length" class="review-list" :aria-label="t('Lines ready to review')">
          <li
            v-for="review in visibleReviews"
            :key="`${review.reference.lessonId}/${review.reference.revision}/${review.reference.lineId}/${review.reference.playingColor}`"
          >
            <div>
              <span class="side-token" :class="{ 'is-black': review.line.playingColor === 'b' }">{{
                t('You play {p0}', { p0: colorName(review.line.playingColor) })
              }}</span>
              <h3>{{ t(review.course.title) }}</h3>
              <p class="review-line">{{ t(review.line.title) }}</p>
              <p v-if="review.line.lastOutcome === 'unknown'" class="muted">
                {{ t('Earlier practice recorded · recall not tracked yet') }}
              </p>
              <p v-else-if="review.line.nextReviewAt" class="muted">
                {{ t('Suggested review:') }}
                <time :datetime="review.line.nextReviewAt">{{
                  formatDate(review.line.nextReviewAt)
                }}</time>
              </p>
            </div>
            <RouterLink class="primary-link" :to="reviewLink(review.reference)">{{
              t('Review this line')
            }}</RouterLink>
          </li>
        </ul>
        <button
          v-if="reviews.length > 5"
          class="review-more"
          :aria-expanded="showAllReviews"
          @click="showAllReviews = !showAllReviews"
        >
          {{ showAllReviews ? t('Show fewer reviews') : t('Show more suggestions') }}
        </button>
      </section>
      <section class="course-section" aria-labelledby="course-progress-heading">
        <h2 id="course-progress-heading">{{ t('Your course progress') }}</h2>
        <p class="muted">
          {{
            t(
              'Counts cover the current course version and the pieces shown. Practice with White and Black is recorded separately. Each course starts with its recommended pieces; switch pieces to see your other practice.',
            )
          }}
        </p>
        <div class="course-progress-grid">
          <article
            v-for="course in overview.courses"
            :key="course.id"
            class="course-progress-card"
            role="region"
            :aria-label="t('{p0} practice', { p0: t(course.title) })"
          >
            <div class="card-meta">
              <span class="side-token" :class="{ 'is-black': courseColor(course) === 'b' }">{{
                t('You play {p0}', { p0: colorName(courseColor(course)) })
              }}</span>
              <span class="version-tag">{{ t('Version {p0}', { p0: course.revision }) }}</span>
            </div>
            <h3>{{ t(course.title) }}</h3>
            <p class="coverage-count">
              {{
                t(
                  course.lineCount === 1
                    ? '{p0} of {p1} current line practised as {p2}'
                    : '{p0} of {p1} current lines practised as {p2}',
                  {
                    p0: practisedCount(course),
                    p1: course.lineCount,
                    p2: colorName(courseColor(course)),
                  },
                )
              }}
            </p>
            <progress
              :value="practisedCount(course)"
              :max="course.lineCount"
              :style="{ '--segments': course.lineCount }"
              :aria-label="
                t('{p0}: current lines practised as {p1}', {
                  p0: course.title,
                  p1: colorName(courseColor(course)),
                })
              "
            />
            <label class="pieces-label"
              >{{ t('Your pieces')
              }}<select
                :value="courseColor(course)"
                :aria-label="t('Pieces for {p0}', { p0: t(course.title) })"
                @change="changePieces(course, $event)"
              >
                <option value="w">
                  {{ t('White') }}{{ course.recommendedColor === 'w' ? t(' (recommended)') : '' }}
                </option>
                <option value="b">
                  {{ t('Black') }}{{ course.recommendedColor === 'b' ? t(' (recommended)') : '' }}
                </option>
              </select></label
            >
            <details class="course-lines">
              <summary>
                {{
                  t('View {p0} {p1}', {
                    p0: course.lineCount,
                    p1: course.lineCount === 1 ? t('line') : t('lines'),
                  })
                }}
              </summary>
              <ul
                :aria-label="
                  t('{p0} lines as {p1}', {
                    p0: t(course.title),
                    p1: colorName(courseColor(course)),
                  })
                "
              >
                <li
                  v-for="line in courseLines(course)"
                  :key="`${line.lineId}/${line.playingColor}`"
                >
                  <h4>{{ t(line.title) }}</h4>
                  <p class="line-status">{{ t(lineStatus(line)) }}</p>
                  <p v-if="line.completions">
                    {{ line.completions }}
                    {{ line.completions === 1 ? t('saved practice') : t('saved practices')
                    }}<template v-if="line.lastPractisedAt">
                      {{ t('· Last practised') }}
                      <time :datetime="line.lastPractisedAt">{{
                        formatDate(line.lastPractisedAt)
                      }}</time></template
                    >
                  </p>
                  <p v-if="line.completions && !line.assessedCompletions" class="muted">
                    {{
                      t(
                        'Earlier practice was saved before recall tracking. Review to see what you remember.',
                      )
                    }}
                  </p>
                  <template v-else-if="line.assessedCompletions">
                    <p>
                      {{
                        t(
                          'Recall tracked in {p0} of {p1} saved practices. {p2} finished without help.',
                          {
                            p0: line.assessedCompletions,
                            p1: line.completions,
                            p2: line.unassistedCompletions,
                          },
                        )
                      }}
                    </p>
                    <p v-if="line.lastOutcome === 'recalled'">
                      {{
                        t('Latest tracked practice: recalled without hints, retries or takebacks.')
                      }}
                    </p>
                    <p v-else>
                      {{
                        t(
                          'Latest tracked practice: used a hint, retry or takeback. Repetition helps these moves feel familiar.',
                        )
                      }}
                    </p>
                  </template>
                  <p v-if="line.nextReviewAt">
                    {{ t('Suggested review:') }}
                    <time :datetime="line.nextReviewAt">{{ formatDate(line.nextReviewAt) }}</time>
                  </p>
                  <details v-if="focusPositions(line).length" class="decision-notes">
                    <summary>{{ t('Moves to revisit') }}</summary>
                    <ul>
                      <li v-for="position in focusPositions(line)" :key="position.ply">
                        <p>{{ t(position.hint) }}</p>
                        <p class="muted">
                          {{
                            t(
                              'Recalled on first try without a hint in {p0} of {p1} tracked practices.',
                              { p0: position.recalled, p1: position.practices },
                            )
                          }}<template v-if="position.hints">
                            {{ t('Hints used in {p0}.', { p0: position.hints }) }}</template
                          ><template v-if="position.retries">
                            {{
                              t('{p0} additional {p1} (including takebacks).', {
                                p0: position.retries,
                                p1: position.retries === 1 ? t('try') : t('tries'),
                              })
                            }}</template
                          >
                        </p>
                      </li>
                    </ul>
                  </details>
                  <RouterLink
                    class="line-action"
                    :to="lineLink(course, line)"
                    :aria-label="
                      t('{p0} {p1}: {p2} as {p3}', {
                        p0: line.completions ? 'Review' : 'Learn',
                        p1: course.title,
                        p2: line.title,
                        p3: colorName(line.playingColor),
                      })
                    "
                    >{{ t(line.completions ? 'Review this line' : 'Learn this line') }}</RouterLink
                  >
                </li>
              </ul>
            </details>
            <RouterLink
              v-if="!practisedCount(course)"
              class="line-action"
              :to="startLink(course)"
              >{{ t('Start with guidance') }}</RouterLink
            >
          </article>
        </div>
        <p v-if="!overview.courses.length">
          {{ t('Courses are being prepared.') }}
          <RouterLink to="/learn">{{ t('Browse lessons') }}</RouterLink>
          {{ t('for the latest additions.') }}
        </p>
      </section>
      <details v-if="overview.history.length" class="earlier-practice">
        <summary>{{ t('Earlier course versions') }}</summary>
        <p class="muted">
          {{
            t(
              'Your earlier practice is kept here. Course updates start separate progress, so these records do not add to current line coverage.',
            )
          }}
        </p>
        <ul :aria-label="t('Earlier course practice')">
          <li
            v-for="entry in overview.history"
            :key="`${entry.lessonId}/${entry.revision}/${entry.lineId}/${entry.playingColor}`"
          >
            <h3>{{ t('{p0} · Version {p1}', { p0: t(entry.title), p1: entry.revision }) }}</h3>
            <p>
              {{
                t('{p0} · You played {p1}', {
                  p0: entry.lineTitle,
                  p1: colorName(entry.playingColor),
                })
              }}
            </p>
            <p>
              {{
                t('{p0} {p1} · Last practised', {
                  p0: entry.completions,
                  p1: entry.completions === 1 ? t('saved practice') : t('saved practices'),
                })
              }}
              <time :datetime="entry.lastPractisedAt">{{ formatDate(entry.lastPractisedAt) }}</time>
            </p>
            <p v-if="entry.assessedCompletions" class="muted">
              {{
                t('{p0} of {p1} tracked practices finished without help.', {
                  p0: entry.unassistedCompletions,
                  p1: entry.assessedCompletions,
                })
              }}
            </p>
            <p v-else class="muted">{{ t('Recall was not tracked for this practice.') }}</p>
          </li>
        </ul>
      </details>
      <p class="progress-note">
        {{
          t(
            'These records describe practice of the taught lines, not your overall chess strength. Hints and retries help you learn; a legal move outside an exercise is not automatically a chess mistake.',
          )
        }}
      </p>
    </template>
  </main>
</template>

<style scoped>
.progress-main {
  padding-block: 56px 24px;
}
.progress-heading {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px 24px;
}
.progress-heading h1 {
  margin: 0;
}
.progress-intro,
.progress-note,
.muted {
  color: var(--pencil);
}
.progress-intro {
  margin-block: 12px 32px;
  font-size: 17px;
}
.choose-lessons,
.line-action {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  font-weight: 650;
}
.first-practice {
  margin-block: 0 32px;
  padding: 28px;
  border: 2px solid var(--ink);
  border-radius: var(--radius);
  background: var(--sheet);
}
.first-practice h2 {
  font-size: 24px;
}
.review-section {
  padding-bottom: 36px;
  margin-bottom: 36px;
  border-bottom: 1px solid var(--rule);
}
.review-section h2,
.course-section h2 {
  font-size: 26px;
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
  margin-block: 20px;
  border-top: 2px solid var(--ink);
}
.review-list > li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
  padding: 18px 4px;
  border-bottom: 1px solid var(--rule);
}
.review-list > li > div {
  min-width: 0;
}
.review-list h3 {
  margin: 8px 0 2px;
}
.review-list p {
  margin-block: 2px;
}
.review-line {
  font-weight: 600;
}
.review-list .primary-link {
  flex-shrink: 0;
}
.card-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}
.version-tag {
  color: var(--pencil);
  font-size: 14px;
  font-weight: 600;
}
.pieces-label {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
  margin-block: 16px 4px;
  font-size: 15px;
  font-weight: 600;
}
.pieces-label select {
  min-height: 44px;
  padding-block: 8px;
}
.course-progress-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
  gap: 20px;
  margin-top: 20px;
}
.course-progress-card {
  min-width: 0;
  padding: 22px 24px;
  border: 1px solid var(--rule);
  border-radius: var(--radius);
  background: var(--sheet);
}
.course-progress-card h3 {
  margin: 12px 0 6px;
  overflow-wrap: anywhere;
}
.coverage-count {
  margin-bottom: 10px;
  font-weight: 600;
}
summary {
  display: flex;
  align-items: center;
  min-height: 44px;
}
.course-lines {
  margin-top: 8px;
  border-top: 1px solid var(--rule);
}
.course-lines > ul > li {
  padding-block: 16px;
  border-top: 1px solid var(--rule);
}
.course-lines li p {
  margin-block: 6px;
  font-size: 15px;
}
.line-status {
  font-weight: 700;
}
.decision-notes ul {
  padding-left: 14px;
  border-left: 3px solid var(--marker);
}
.decision-notes li + li {
  margin-top: 14px;
}
.earlier-practice {
  margin-top: 36px;
  padding-top: 8px;
  border-top: 1px solid var(--rule);
}
.earlier-practice li {
  padding-block: 14px;
  border-top: 1px solid var(--rule);
}
.earlier-practice h3 {
  font-size: 19px;
}
.progress-note {
  margin-top: 36px;
  font-size: 15px;
}
@media (max-width: 760px) {
  .progress-main {
    padding-top: 36px;
  }
  .review-list > li {
    flex-direction: column;
    align-items: stretch;
    gap: 12px;
  }
  .review-list .primary-link {
    align-self: start;
  }
  .course-progress-card {
    padding: 20px;
  }
}
</style>
