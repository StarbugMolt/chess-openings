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
    <header class="catalogue-header">
      <h1>{{ t('Look up an opening.') }}</h1>
      <p class="catalogue-intro">
        {{
          t(
            'Search names and compare recorded sequences. Some sequences stop earlier or reach a setup in a different order, so we group identical names together.',
          )
        }}
      </p>
    </header>
    <form class="catalogue-search" :aria-label="t('Search openings')" @submit.prevent="navigate()">
      <label class="search-field" for="opening-search">{{ t('Opening name') }}</label>
      <div class="search-row">
        <input
          id="opening-search"
          v-model="search"
          type="search"
          maxlength="100"
          placeholder="Italian Game"
        /><button type="submit" class="primary-link">{{ t('Search openings') }}</button>
      </div>
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
    </form>
    <nav class="family-links" :aria-label="t('Browse opening families')">
      <span>{{ t('Browse a family') }}</span>
      <button v-for="family in families" :key="family" type="button" @click="browse(family)">
        {{ t(family) }}
      </button>
    </nav>
    <p class="play-first notice">
      <strong>{{ t('Want to play?') }}</strong>
      <RouterLink to="/learn">{{ t('Start a beginner lesson') }}</RouterLink
      >. {{ t('We play the other side and guide your next move.') }}
    </p>
    <p class="result-count" role="status" aria-live="polite">
      {{
        catalogue.loading
          ? t('Loading openings…')
          : catalogue.page
            ? t('{p0} named variations found', { p0: formatNumber(catalogue.page.total) })
            : ''
      }}
    </p>
    <div v-if="catalogue.error" class="notice status-actions" role="alert">
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
                ><span class="result-meta"
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
              <div class="result-detail">
                <template v-if="opening.sequences?.length">
                  <SequenceViewer
                    v-if="expanded.has(opening.sourceId)"
                    :key="selectedPgn(opening)"
                    :pgn="selectedPgn(opening)"
                    :name="opening.name"
                  />
                  <p class="detail-note">
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
                <p v-else class="detail-note">
                  {{
                    t(
                      'Move sequences are unavailable in this cached result. Search again to refresh it.',
                    )
                  }}
                </p>
                <p v-if="(opening.sequenceCount ?? 1) > 3" class="detail-note">
                  {{ t('Showing the three shortest sequences.') }}
                </p>
              </div>
            </details>
            <RouterLink
              v-if="courseFor(opening)"
              class="lesson-entry"
              :to="`/learn/${courseFor(opening)?.id}`"
              >{{ t('{p0} beginner lesson →', { p0: courseFor(opening)?.title }) }}</RouterLink
            >
            <span v-else class="reference-label">{{ t('Guided lesson not available yet') }}</span>
          </li>
        </ul>
        <p v-if="!catalogue.page.items.length" class="notice">
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
  padding-block: 56px 24px;
}
.catalogue-header {
  max-width: 760px;
}
.catalogue-intro {
  color: var(--pencil);
  font-size: 17px;
}
.catalogue-search {
  margin-block: 28px 20px;
  max-width: 760px;
}
.search-field {
  display: block;
  margin-bottom: 8px;
  font-weight: 650;
}
.search-row {
  display: flex;
}
.search-row input {
  flex: 1 1 auto;
  min-width: 0;
  min-height: 52px;
  border: 2px solid var(--ink);
  border-right: 0;
  border-radius: var(--radius) 0 0 var(--radius);
  font-size: 18px;
}
.search-row .primary-link {
  min-height: 52px;
  border-radius: 0 var(--radius) var(--radius) 0;
  flex: 0 0 auto;
}
.advanced-search {
  margin-top: 6px;
}
.advanced-search summary {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  color: var(--pencil);
  font-size: 15px;
}
.advanced-search label {
  display: grid;
  gap: 6px;
  margin-block: 6px;
  font-size: 15px;
  font-weight: 600;
}
.advanced-search input {
  width: 110px;
}
.advanced-search small {
  display: block;
  color: var(--pencil);
  font-size: 14px;
}
.family-links {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
  margin-block: 8px 20px;
}
.family-links span {
  margin-right: 6px;
  font-weight: 650;
}
.family-links button {
  min-height: 40px;
  padding-inline: 14px;
  border-radius: 999px;
  font-size: 14px;
}
.play-first {
  max-width: 760px;
}
.play-first a {
  font-weight: 650;
}
.result-count {
  margin: 28px 0 10px;
  color: var(--pencil);
  font-weight: 600;
}
.opening-results {
  padding: 0;
  margin: 0;
  list-style: none;
  border-top: 2px solid var(--ink);
}
.opening-results > li {
  padding: 4px 4px 16px;
  border-bottom: 1px solid var(--rule);
}
.opening-results summary {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  gap: 4px 18px;
  min-height: 44px;
  padding-top: 12px;
  list-style: none;
  font-weight: 400;
}
.opening-results summary::-webkit-details-marker {
  display: none;
}
.opening-results summary strong {
  font-size: 18px;
  font-weight: 750;
  overflow-wrap: anywhere;
}
.opening-results summary:hover strong {
  text-decoration: underline;
  text-underline-offset: 4px;
}
.result-meta {
  color: var(--pencil);
  font-size: 15px;
}
.view-moves {
  color: var(--ink);
  font-weight: 600;
  text-decoration: underline;
  text-underline-offset: 3px;
}
details[open] .view-moves {
  background: var(--marker);
  text-decoration: none;
}
.result-detail {
  margin-top: 12px;
  padding: 20px;
  border: 1px solid var(--rule);
  border-radius: var(--radius);
  background: var(--sheet);
}
.detail-note {
  color: var(--pencil);
  font-size: 15px;
}
.reference-sequences {
  display: grid;
  gap: 6px;
  padding: 0;
  margin: 0;
  list-style: none;
}
.reference-sequences button {
  display: block;
  width: 100%;
  min-height: 44px;
  padding: 10px 14px;
  text-align: left;
  font-weight: 400;
}
.reference-sequences button[aria-pressed='true'] {
  border-color: var(--ink);
  box-shadow: inset 4px 0 0 var(--ink);
}
.reference-sequences code {
  overflow-wrap: anywhere;
  white-space: normal;
}
.reference-sequences small {
  display: block;
  margin-top: 2px;
  color: var(--pencil);
  font-size: 14px;
}
.lesson-entry {
  display: inline-flex;
  align-items: center;
  min-height: 40px;
  margin-top: 4px;
  font-weight: 650;
}
.reference-label {
  display: block;
  margin-top: 4px;
  color: var(--pencil);
  font-size: 15px;
}
.catalogue-pages {
  display: flex;
  gap: 16px;
  align-items: center;
  margin-block: 24px;
  font-weight: 600;
}
.catalogue-source {
  color: var(--pencil);
  font-size: 14px;
}
@media (max-width: 680px) {
  .catalogue-main {
    padding-top: 36px;
  }
  .search-row {
    flex-wrap: wrap;
    gap: 8px;
  }
  .search-row input {
    border-right: 2px solid var(--ink);
    border-radius: var(--radius);
    flex-basis: 100%;
  }
  .search-row .primary-link {
    border-radius: var(--radius);
    width: 100%;
  }
  .result-detail {
    padding: 14px;
  }
}
</style>
