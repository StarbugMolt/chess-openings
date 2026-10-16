<script setup lang="ts">
import { t, type Text } from '../i18n'
import { computed, onMounted, ref } from 'vue'
import { getCourses, type Course } from '../api/lessons'
import { useAccountStore } from '../stores/account'
const account = useAccountStore()
const courses = ref<Course[]>([])
const loading = ref(true)
const error = ref<Text>('')
const side = ref<'all' | 'w' | 'b'>('all')
const visibleCourses = computed(() =>
  courses.value.filter((course) => side.value === 'all' || course.recommendedColor === side.value),
)
async function load() {
  loading.value = true
  error.value = ''
  try {
    courses.value = await getCourses()
  } catch {
    error.value = 'Lessons could not be loaded. Please try again.'
  } finally {
    loading.value = false
  }
}
onMounted(() => {
  void load()
})
</script>
<template>
  <main id="main-content" class="page-width learn-main">
    <header class="learn-header">
      <h1>{{ t('Learn an opening.') }}</h1>
      <p class="learn-intro">
        {{
          t(
            'Pick an opening below. You move your pieces, we play the other side. A hint is always one click away. No chess notation needed.',
          )
        }}
      </p>
      <p id="variation-training" class="learn-intro">
        {{ t('Learn one fixed line first. Then use') }}
        <strong>{{ t('Practise variations') }}</strong>
        {{ t('to mix the taught replies from lines you choose.') }}
      </p>
      <RouterLink v-if="account.user" class="practice-entry" to="/progress">{{
        t('Your practice and reviews')
      }}</RouterLink>
    </header>
    <fieldset class="lesson-side-filter">
      <legend>{{ t('Which pieces would you like to learn?') }}</legend>
      <div class="segments">
        <label
          v-for="option in [
            { value: 'all', label: 'Both sides' },
            { value: 'w', label: 'White openings' },
            { value: 'b', label: 'Black defences' },
          ]"
          :key="option.value"
          ><input v-model="side" type="radio" name="lesson-side" :value="option.value" />{{
            option.label
          }}</label
        >
      </div>
    </fieldset>
    <p v-if="loading" role="status">{{ t('Loading lessons…') }}</p>
    <div v-else-if="error" class="notice status-actions" role="alert">
      <p>{{ t(error) }}</p>
      <button @click="load">{{ t('Try again') }}</button>
    </div>
    <div v-if="!loading && !error" class="lesson-cards">
      <article v-for="course in visibleCourses" :key="course.id" class="lesson-card">
        <span class="side-token" :class="{ 'is-black': course.recommendedColor === 'b' }">{{
          t(course.recommendedColor === 'b' ? 'You play Black' : 'You play White')
        }}</span>
        <h2>{{ t(course.title) }}</h2>
        <p>{{ t(course.introduction) }}</p>
        <div class="lesson-actions">
          <RouterLink class="primary-link" :to="`/learn/${course.id}`">{{
            t('Learn the {p0}', { p0: course.title })
          }}</RouterLink>
          <RouterLink
            v-if="course.lineCount > 1"
            class="variation-entry"
            :to="`/train/${course.id}`"
            >{{ t('Practise variations')
            }}<span class="sr-only"> {{ t('in') }} {{ t(course.title) }}</span></RouterLink
          >
        </div>
      </article>
      <p v-if="!visibleCourses.length">
        {{ t('The first guided lessons are being prepared. Please check back shortly.') }}
      </p>
    </div>
    <aside class="reference-note">
      <h2>{{ t('Looking for another opening?') }}</h2>
      <p>
        {{ t('The') }} <RouterLink to="/openings">{{ t('opening reference') }}</RouterLink>
        {{
          t(
            'lists names and recorded move sequences. The lessons above are ready to practise; reference entries are not automatically playable courses.',
          )
        }}
      </p>
    </aside>
  </main>
</template>
<style scoped>
.learn-main {
  padding-block: 56px 24px;
}
.learn-header {
  max-width: 760px;
}
.learn-intro {
  color: var(--pencil);
  font-size: 17px;
}
.practice-entry {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  font-weight: 650;
}
.lesson-side-filter {
  border: 0;
  padding: 0;
  margin: 32px 0 0;
  min-width: 0;
}
.lesson-side-filter legend {
  padding: 0;
  margin-bottom: 12px;
  font-weight: 650;
}
/* Native radios stay in the accessibility tree and under the pointer; the label draws the segment. */
.segments {
  display: inline-flex;
  flex-wrap: wrap;
  border: 1.5px solid var(--ink);
  border-radius: var(--radius);
  overflow: hidden;
  background: var(--sheet);
}
.segments label {
  position: relative;
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  padding: 0 18px;
  font-weight: 600;
  cursor: pointer;
}
.segments label + label {
  border-left: 1.5px solid var(--ink);
}
.segments input {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  margin: 0;
  opacity: 0;
  cursor: pointer;
}
.segments label:has(input:checked) {
  background: var(--ink);
  color: #fff;
}
.segments label:has(input:focus-visible) {
  outline: 3px solid var(--marker);
  outline-offset: -5px;
}
.lesson-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
  gap: 20px;
  margin-block: 28px 56px;
}
.lesson-card {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 24px 24px 22px;
  border: 1px solid var(--rule);
  border-radius: var(--radius);
  background: var(--sheet);
}
.lesson-card h2 {
  font-size: 24px;
  font-stretch: var(--semi-wide);
  margin: 14px 0 8px;
}
.lesson-card p {
  color: var(--pencil);
  font-size: 15px;
  line-height: 1.55;
  margin-bottom: 20px;
}
.lesson-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 20px;
  margin-top: auto;
}
.variation-entry {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  font-weight: 650;
}
.reference-note {
  max-width: 760px;
  padding-top: 28px;
  border-top: 1px solid var(--rule);
}
.reference-note h2 {
  font-size: 20px;
  font-stretch: var(--semi-wide);
}
.reference-note p {
  color: var(--pencil);
}
@media (max-width: 680px) {
  .learn-main {
    padding-top: 36px;
  }
  .segments {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .segments label {
    justify-content: center;
    padding: 6px 8px;
    font-size: 15px;
    line-height: 1.2;
    text-align: center;
  }
}
</style>
