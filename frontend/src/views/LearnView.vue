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
    <span class="eyebrow">{{ t('START HERE · GUIDED LESSONS') }}</span>
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
    <p v-if="account.user" class="learn-intro">
      <RouterLink class="practice-entry" to="/progress">{{
        t('Your practice & reviews →')
      }}</RouterLink>
    </p>
    <p v-if="loading" role="status">{{ t('Loading lessons…') }}</p>
    <div v-else-if="error" role="alert">
      <p>{{ t(error) }}</p>
      <button @click="load">{{ t('Try again') }}</button>
    </div>
    <fieldset class="lesson-side-filter">
      <legend>{{ t('Which pieces would you like to learn?') }}</legend>
      <label
        v-for="option in [
          { value: 'all', label: 'Both sides' },
          { value: 'w', label: 'White openings' },
          { value: 'b', label: 'Black defences' },
        ]"
        :key="option.value"
        ><input v-model="side" type="radio" name="lesson-side" :value="option.value" />{{
          t(option.label)
        }}</label
      >
    </fieldset>
    <div v-if="!loading && !error" class="lesson-cards">
      <article v-for="course in visibleCourses" :key="course.id" class="lesson-card">
        <span class="eyebrow">{{
          course.recommendedColor === 'b' ? t('YOU PLAY BLACK') : t('YOU PLAY WHITE')
        }}</span>
        <h2>{{ t(course.title) }}</h2>
        <p>{{ t(course.introduction) }}</p>
        <span class="lesson-length">{{ t('At your own pace · Hints included') }}</span>
        <RouterLink class="primary-link" :to="`/learn/${course.id}`"
          >{{ t('Learn the {p0}', { p0: t(course.title) }) }}
          <span aria-hidden="true">→</span></RouterLink
        >
        <RouterLink
          v-if="course.lineCount > 1"
          class="variation-entry"
          :to="`/train/${course.id}`"
          >{{ t('Practise variations in {p0} →', { p0: t(course.title) }) }}</RouterLink
        >
      </article>
      <p v-if="!visibleCourses.length">
        {{ t('The first guided lessons are being prepared. Please check back shortly.') }}
      </p>
    </div>
    <aside class="reference-note">
      <strong>{{ t('Looking for another opening?') }}</strong>
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
  padding-block: 52px 80px;
  min-height: 75vh;
}
h1 {
  font-size: clamp(38px, 5vw, 58px);
  letter-spacing: -1.8px;
}
.learn-intro {
  max-width: 650px;
  color: var(--muted);
  line-height: 1.8;
}
.practice-entry {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
}
.lesson-cards {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 24px;
  margin-block: 36px;
}
.lesson-side-filter {
  border: 0;
  padding: 0;
  margin-top: 28px;
  display: flex;
  flex-wrap: wrap;
  gap: 12px 24px;
}
.lesson-side-filter legend {
  margin-bottom: 12px;
  font-size: 14px;
}
.lesson-side-filter label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 44px;
  font-size: 14px;
  cursor: pointer;
}
.lesson-side-filter input {
  accent-color: var(--ink);
  width: 18px;
  height: 18px;
}
.lesson-card {
  padding: 30px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #faf9f4;
  display: flex;
  flex-direction: column;
  align-items: start;
}
.lesson-card h2 {
  font:
    32px Georgia,
    serif;
  margin-bottom: 8px;
}
.lesson-card p,
.reference-note p {
  font-size: 14px;
  line-height: 1.8;
  color: var(--muted);
}
.lesson-length {
  font-size: 12px;
  color: var(--muted);
  margin-block: auto 22px;
  padding-top: 15px;
}
.variation-entry {
  margin-top: 18px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  font-size: 13px;
  line-height: 1.6;
}
.reference-note {
  max-width: 750px;
  border-top: 1px solid var(--line);
  padding-top: 25px;
}
.reference-note a {
  text-decoration: underline;
}
@media (max-width: 700px) {
  .lesson-cards {
    grid-template-columns: 1fr;
  }
  .lesson-card {
    padding: 24px;
  }
}
</style>
