<script setup lang="ts">
import { t, formatNumber } from '../i18n'
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useCatalogueStore } from '../stores/catalogue'
import { getCourses, type Course } from '../api/lessons'
import type { OpeningSummary } from '../api/catalogue'
import { catalogueQuery } from '../domain/catalogueQuery'
import SequenceViewer from '../components/chess/SequenceViewer.vue'

const route = useRoute()
const router = useRouter()
const catalogue = useCatalogueStore()
const search = ref('')
const eco = ref('')
const courses = ref<Course[]>([])
const families = [
  'Italian Game',
  'London System',
  'Caro-Kann',
  'French Defense',
  'Sicilian Defense',
  "Queen's Gambit",
  'Ruy Lopez',
  'Catalan Opening',
  "King's Indian Defense",
  'Slav Defense',
]
/** Only expanded entries mount a board; each remembers which recorded sequence it shows. */
const expanded = reactive(new Map<string, number>())
function selectedPgn(opening: OpeningSummary): string {
  return opening.sequences?.[expanded.get(opening.sourceId) ?? 0]?.pgn ?? ''
}
function toggle(opening: OpeningSummary, event: Event) {
  if ((event.target as HTMLDetailsElement).open)
    expanded.set(opening.sourceId, expanded.get(opening.sourceId) ?? 0)
  else expanded.delete(opening.sourceId)
}
onMounted(() => {
  void getCourses()
    .then((result) => {
      courses.value = result
    })
    .catch(() => {})
})
function courseFor(opening: OpeningSummary) {
  return courses.value.find((course) => opening.name.includes(course.title))
}
const query = computed(() => catalogueQuery(route.query))
const currentPage = computed(() => query.value.page)

watch(
  [() => route.query.search, () => route.query.eco, () => route.query.page],
  () => {
    if (route.name !== 'catalogue') return
    const normalized = query.value
    const canonical = {
      search: normalized.search,
      eco: normalized.eco,
      page: String(normalized.page),
    }
    search.value = normalized.search
    eco.value = normalized.eco
    if (JSON.stringify(route.query) !== JSON.stringify(canonical)) {
      void router.replace({ name: 'catalogue', query: canonical, hash: route.hash })
      return
    }
    void catalogue.open(normalized)
  },
  { immediate: true },
)

function navigate(page = 1) {
  const query = { search: search.value.trim(), eco: eco.value.trim().toUpperCase(), page }
  const target = {
    name: 'catalogue',
    query: { ...query, page: String(page) },
  }
  if (router.resolve(target).fullPath === route.fullPath) void catalogue.open(query)
  else void router.push(target)
}
function browse(name: string) {
  search.value = name
  eco.value = ''
  navigate()
}
</script>

<template>
  <main id="main-content" class="page-width catalogue-main">
    <span class="eyebrow">{{ t('OPENING REFERENCE') }}</span>
    <h1>{{ t('Look up an opening.') }}</h1>
    <p class="catalogue-intro">
      {{
        t(
          'Search names and compare recorded sequences. Some sequences stop earlier or reach a setup in a different order, so we group identical names together.',
        )
      }}
    </p>
    <nav class="family-links" :aria-label="t('Browse opening families')">
      <span>{{ t('Browse a family:') }}</span
      ><button v-for="family in families" :key="family" @click="browse(family)">
        {{ t(family) }}
      </button>
    </nav>
    <p class="play-first">
      <strong>{{ t('Want to play?') }}</strong>
      <RouterLink to="/learn">{{ t('Start a beginner lesson →') }}</RouterLink>
      {{ t('We play the other side and guide your next move.') }}
    </p>
    <form class="catalogue-search" :aria-label="t('Search openings')" @submit.prevent="navigate()">
      <label
        >{{ t('Opening name')
        }}<input v-model="search" type="search" maxlength="100" placeholder="Italian Game"
      /></label>
      <details class="advanced-search">
        <summary>{{ t('Optional search filters') }}</summary>
        <label
          >{{ t('ECO code')
          }}<input
            v-model="eco"
            maxlength="3"
            pattern="[A-Ea-e][0-9]{0,2}"
            placeholder="C50" /></label
        ><small>{{ t('This is a reference code. You do not need it to learn an opening.') }}</small>
      </details>
      <button type="submit">{{ t('Search openings') }}</button>
    </form>
    <p role="status" aria-live="polite">
      {{
        catalogue.loading
          ? t('Loading openings…')
          : catalogue.page
            ? t('{p0} named variations found', { p0: formatNumber(catalogue.page.total) })
            : ''
      }}
    </p>
    <div v-if="catalogue.error" role="alert">
      <p>{{ t(catalogue.error) }}</p>
      <button type="button" @click="catalogue.open(query)">{{ t('Try again') }}</button>
    </div>
    <section
      v-if="catalogue.page"
      :aria-busy="catalogue.loading"
      :aria-label="t('Opening search results')"
    >
      <p v-if="!catalogue.status?.sourceSha" role="status">
        {{ t('The opening library is being prepared.') }}
      </p>
      <template v-else>
        <ul class="opening-results" :aria-label="t('Named openings')">
          <li v-for="opening in catalogue.page.items" :key="opening.sourceId">
            <details @toggle="toggle(opening, $event)">
              <summary>
                <strong>{{ opening.name }}</strong
                ><span
                  >{{
                    t(
                      (opening.sequenceCount ?? 1) === 1
                        ? '{p0} recorded sequence ·'
                        : '{p0} recorded sequences ·',
                      { p0: opening.sequenceCount ?? 1 },
                    )
                  }}
                  <span class="view-moves">{{ t('View moves on a board') }}</span></span
                >
              </summary>
              <template v-if="opening.sequences?.length">
                <SequenceViewer
                  v-if="expanded.has(opening.sourceId)"
                  :key="selectedPgn(opening)"
                  :pgn="selectedPgn(opening)"
                  :name="opening.name"
                />
                <p>
                  {{
                    t(
                      'These are reference sequences, not separate lessons. Longer sequences can continue a shorter one.',
                    )
                  }}
                </p>
                <ol class="reference-sequences">
                  <li v-for="(sequence, position) in opening.sequences" :key="sequence.pgn">
                    <button
                      type="button"
                      :aria-pressed="(expanded.get(opening.sourceId) ?? 0) === position"
                      @click="expanded.set(opening.sourceId, position)"
                    >
                      <code>{{ sequence.pgn }}</code
                      ><small>{{
                        t('Reference code {p0} · Show on the board', { p0: sequence.eco })
                      }}</small>
                    </button>
                  </li>
                </ol>
              </template>
              <p v-else>
                {{
                  t(
                    'Move sequences are unavailable in this cached result. Search again to refresh it.',
                  )
                }}
              </p>
              <p v-if="(opening.sequenceCount ?? 1) > 3">
                {{ t('Showing the three shortest sequences.') }}
              </p>
            </details>
            <RouterLink
              v-if="courseFor(opening)"
              class="lesson-entry"
              :to="`/learn/${courseFor(opening)?.id}`"
              >{{ t('{p0} beginner lesson →', { p0: t(courseFor(opening)?.title) }) }}</RouterLink
            >
            <span v-else class="reference-label">{{
              t('Reference entry · Guided lesson not available yet')
            }}</span>
          </li>
        </ul>
        <p v-if="!catalogue.page.items.length">
          {{ t('No matching openings. Try a shorter name or another ECO code.') }}
        </p>
        <nav class="catalogue-pages" :aria-label="t('Catalogue pages')">
          <button
            type="button"
            :aria-disabled="currentPage <= 1 || catalogue.loading"
            @click="currentPage > 1 && !catalogue.loading && navigate(currentPage - 1)"
          >
            {{ t('Previous') }}
          </button>
          <span>{{ t('Page {p0}', { p0: currentPage }) }}</span>
          <button
            type="button"
            :aria-disabled="
              currentPage * catalogue.page.limit >= catalogue.page.total || catalogue.loading
            "
            @click="
              currentPage * catalogue.page.limit < catalogue.page.total &&
              !catalogue.loading &&
              navigate(currentPage + 1)
            "
          >
            {{ t('Next') }}
          </button>
        </nav>
        <p class="catalogue-source">
          {{
            t('Opening names from the Lichess CC0 catalogue. Course content is curated separately.')
          }}
        </p>
      </template>
    </section>
  </main>
</template>

<style scoped>
.catalogue-main {
  padding-block: 52px 80px;
  min-height: 70vh;
}
h1 {
  font-size: clamp(34px, 5vw, 54px);
  letter-spacing: -1.4px;
  margin-block: 18px;
}
.catalogue-intro {
  max-width: 640px;
  line-height: 1.8;
  color: var(--muted);
}
.catalogue-search {
  display: flex;
  flex-wrap: wrap;
  align-items: end;
  gap: 16px;
  margin-block: 32px;
}
label {
  display: grid;
  gap: 8px;
  font-size: 13px;
}
input {
  width: min(280px, 70vw);
  padding: 12px;
  border: 1px solid var(--line);
  border-radius: 4px;
  background: #fafbf5;
}
label:nth-child(2) input {
  width: 90px;
}
button {
  padding: 12px 18px;
  border: 1px solid #aab79f;
  border-radius: 4px;
  background: #eeefe5;
  color: var(--ink);
  font-size: 13px;
}
button:disabled,
button[aria-disabled='true'] {
  opacity: 0.45;
  cursor: default;
}
.family-links {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-block: 24px;
  font-size: 13px;
}
.family-links button {
  padding: 10px 12px;
  min-height: 44px;
}
.catalogue-count,
.catalogue-source {
  font-size: 12px;
  color: var(--muted);
}
.opening-results {
  padding: 0;
  list-style: none;
  border-top: 1px solid var(--line);
}
.opening-results li {
  display: block;
  padding: 20px 8px;
  border-bottom: 1px solid var(--line);
  line-height: 1.6;
}
.opening-results summary {
  cursor: pointer;
  display: flex;
  flex-wrap: wrap;
  gap: 8px 20px;
  align-items: baseline;
  list-style: none;
}
.opening-results summary strong {
  font-weight: 550;
  overflow-wrap: anywhere;
}
.opening-results summary > span,
.reference-label {
  color: var(--muted);
  font-size: 12px;
}
.opening-results details p {
  font-size: 13px;
  color: var(--muted);
}
.lesson-entry {
  display: inline-block;
  margin-top: 12px;
  font-size: 13px;
  text-decoration: underline;
  text-underline-offset: 4px;
}
.reference-label {
  display: block;
  margin-top: 10px;
}
.reference-sequences {
  padding-left: 20px;
}
.reference-sequences li {
  padding: 4px 0;
  border: 0;
}
.reference-sequences button {
  display: block;
  width: 100%;
  text-align: left;
  padding: 10px 12px;
  background: transparent;
  border: 1px solid transparent;
}
.reference-sequences button[aria-pressed='true'] {
  border-color: #aab79f;
  background: #eef0e5;
}
.view-moves {
  color: var(--ink);
  text-decoration: underline;
  text-underline-offset: 3px;
}
.opening-results summary:hover strong {
  text-decoration: underline;
}
.reference-sequences code {
  font-size: 13px;
  overflow-wrap: anywhere;
  white-space: normal;
}
.reference-sequences small {
  display: block;
  font-size: 11px;
  color: var(--muted);
}
.play-first {
  padding: 18px 20px;
  border: 1px solid var(--line);
  border-radius: 6px;
  line-height: 1.8;
  font-size: 14px;
  background: #eef0e5;
}
.play-first a {
  text-decoration: underline;
  margin-inline: 7px;
}
.advanced-search summary {
  cursor: pointer;
  font-size: 12px;
  padding-bottom: 8px;
}
.advanced-search label {
  margin-block: 10px;
}
.advanced-search small {
  display: block;
  max-width: 240px;
  font-size: 11px;
  color: var(--muted);
  line-height: 1.6;
}
.eco-code {
  flex-shrink: 0;
  color: #506949;
  font-size: 12px;
  font-weight: 650;
}
.opening-results li > span:last-child {
  min-width: 0;
  overflow-wrap: anywhere;
}
.catalogue-pages {
  display: flex;
  gap: 20px;
  align-items: center;
  margin-block: 28px;
  font-size: 13px;
}
</style>
