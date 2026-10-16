<script setup lang="ts">
import { t, message, locale, formatNumber, formatDate, type Text } from '../i18n'
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAccountStore } from '../stores/account'
import {
  ADMIN_SEARCH_MAX,
  AdminError,
  adminQuery,
  deleteAdminUser,
  getAdminStats,
  getAdminUsers,
  type AdminStats,
  type AdminUser,
  type AdminUserPage,
  type AdminUserQuery,
} from '../api/admin'

const route = useRoute()
const router = useRouter()
const account = useAccountStore()
let active = true

const signInLink = computed(() => ({ name: 'login', query: { next: route.fullPath } }))
const signedOut = computed(() => account.loaded && !account.user && !account.unavailable)
const notAdmin = computed(() => !!account.user && !account.user.admin)

/** 401/403 can mean the session ended or admin was revoked; refresh before explaining. */
async function explain(cause: unknown, fallback: string): Promise<Text> {
  if (!(cause instanceof AdminError)) return fallback
  switch (cause.status) {
    case 401:
      await account.load(true)
      return 'Your session has ended. Sign in again to continue.'
    case 403:
      await account.load(true)
      return account.user && !account.user.admin
        ? 'Your account no longer has admin access.'
        : 'Admin access was refused. Refresh the page and try again.'
    case 429:
      return cause.retryAfter
        ? message('Too many confirmation attempts. Try again in {p0} seconds.', {
            p0: cause.retryAfter,
          })
        : 'Too many confirmation attempts. Please try again later.'
    case 400:
    case 0:
      return cause.message
    default:
      return fallback
  }
}

// Section rail: the last section whose top has passed the upper third of the viewport.
const sectionIds = ['usage', 'growth', 'courses', 'users'] as const
const currentSection = ref<(typeof sectionIds)[number]>('usage')
let scrollFrame = 0
function trackSection(): void {
  cancelAnimationFrame(scrollFrame)
  scrollFrame = requestAnimationFrame(() => {
    let current: (typeof sectionIds)[number] = 'usage'
    for (const id of sectionIds) {
      const top = document.getElementById(id)?.getBoundingClientRect().top
      if (top !== undefined && top <= innerHeight / 3) current = id
    }
    currentSection.value = current
  })
}
onMounted(() => {
  addEventListener('scroll', trackSection, { passive: true })
  trackSection()
})

// Usage statistics
const stats = ref<AdminStats>()
const statsLoading = ref(false)
const statsError = ref<Text>('')
let statsRequest = 0
async function loadStats(): Promise<void> {
  const request = ++statsRequest
  statsLoading.value = true
  statsError.value = ''
  try {
    const result = await getAdminStats()
    if (active && request === statsRequest) stats.value = result
  } catch (cause) {
    const message = await explain(cause, 'Usage statistics are unavailable. Please try again.')
    if (active && request === statsRequest) statsError.value = message
  } finally {
    if (request === statsRequest) statsLoading.value = false
  }
}
onMounted(() => void loadStats())

const number = formatNumber
const percent = (part: number, whole: number) =>
  whole ? `${Math.round((part / whole) * 100)}%` : '—'
const share = (part: number, whole: number) => (whole ? Math.min(100, (part / whole) * 100) : 0)
const day = (value: string, withYear = false) =>
  new Date(`${value}T00:00:00Z`).toLocaleDateString(locale.value, {
    timeZone: 'UTC',
    day: 'numeric',
    month: 'short',
    ...(withYear ? { year: 'numeric' } : {}),
  })
const dateTime = formatDate

/** A clean axis maximum (2, 4, 10, 20, 25, 50, …) so gridline labels are round numbers. */
function axisMaximum(value: number): number {
  if (value <= 2) return 2
  if (value <= 4) return 4
  const magnitude = 10 ** Math.floor(Math.log10(value))
  return ([1, 2, 2.5, 5, 10].find((step) => step * magnitude >= value) ?? 10) * magnitude
}
function axisTicks(maximum: number): number[] {
  return Number.isInteger(maximum / 2) ? [maximum, maximum / 2, 0] : [maximum, 0]
}
const column = (value: number, maximum: number) => ({ '--size': `${share(value, maximum)}%` })
/** Tooltips near either edge of a chart open inwards so they never leave the card. */
const tipSide = (index: number, count: number) =>
  index < count / 4 ? 'tip-start' : index >= count - count / 4 ? 'tip-end' : ''

const daily = computed(() => stats.value?.practice.daily ?? [])
const dailyAxis = computed(() =>
  axisMaximum(Math.max(0, ...daily.value.map((entry) => entry.completions))),
)
const peakDay = computed(() =>
  daily.value.reduce<(typeof daily.value)[number] | undefined>(
    (best, entry) => (entry.completions > (best?.completions ?? 0) ? entry : best),
    undefined,
  ),
)
const weekly = computed(() => stats.value?.accounts.weekly ?? [])
const weeklyAxis = computed(() =>
  axisMaximum(Math.max(0, ...weekly.value.flatMap((week) => [week.signups, week.verifications]))),
)
const colorTotal = computed(() =>
  stats.value ? stats.value.practice.byColor.w + stats.value.practice.byColor.b : 0,
)
const courses = computed(() =>
  [...(stats.value?.practice.byCourse ?? [])].sort((a, b) => b.completions - a.completions),
)
const courseMaximum = computed(() => Math.max(0, ...courses.value.map((item) => item.completions)))
const lineMaximum = computed(() => stats.value?.practice.topLines[0]?.completions ?? 0)

// One tab stop per chart; arrow keys move between its marks.
const focused = reactive({ day: 29, week: 11 })
function rove(event: KeyboardEvent, chart: keyof typeof focused, count: number): void {
  const target = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[event.key]
  const next =
    event.key === 'Home'
      ? 0
      : event.key === 'End'
        ? count - 1
        : target === undefined
          ? undefined
          : focused[chart] + target
  if (next === undefined) return
  event.preventDefault()
  const list = event.currentTarget as HTMLElement
  focused[chart] = Math.min(count - 1, Math.max(0, next))
  void nextTick(() => (list.children[focused[chart]] as HTMLElement | undefined)?.focus())
}

// Users
const users = ref<AdminUserPage>()
const usersLoading = ref(false)
const usersError = ref<Text>('')
const search = ref('')
const now = ref(Date.now())
const query = computed(() => adminQuery(route.query))
const pageCount = computed(() =>
  users.value ? Math.max(1, Math.ceil(users.value.total / users.value.limit)) : 1,
)
let usersRequest = 0

function canonical(value: AdminUserQuery): Record<string, string> {
  return {
    ...(value.search ? { search: value.search } : {}),
    ...(value.page > 1 ? { page: String(value.page) } : {}),
  }
}

async function loadUsers(): Promise<void> {
  const request = ++usersRequest
  const current = query.value
  usersLoading.value = true
  usersError.value = ''
  try {
    const result = await getAdminUsers(current)
    if (!active || request !== usersRequest) return
    users.value = result
    now.value = Date.now()
    // A deletion or stale link can leave the requested page past the end.
    if (!result.items.length && result.total && current.page > 1)
      void router.replace({
        name: 'admin',
        query: canonical({ ...current, page: Math.ceil(result.total / result.limit) }),
      })
  } catch (cause) {
    const message = await explain(cause, 'The account list is unavailable. Please try again.')
    if (active && request === usersRequest) usersError.value = message
  } finally {
    if (request === usersRequest) usersLoading.value = false
  }
}

watch(
  [() => route.query.search, () => route.query.page],
  () => {
    if (route.name !== 'admin') return
    const normalized = query.value
    search.value = normalized.search
    const target = canonical(normalized)
    if (JSON.stringify(route.query) !== JSON.stringify(target)) {
      void router.replace({ name: 'admin', query: target, hash: route.hash })
      return
    }
    void loadUsers()
  },
  { immediate: true },
)

function navigate(page = 1): void {
  const target = { name: 'admin', query: canonical({ search: search.value.trim(), page }) }
  if (router.resolve(target).fullPath === route.fullPath.replace(/#.*$/, '')) void loadUsers()
  else void router.push(target)
}

function refresh(): void {
  void loadStats()
  void loadUsers()
}

const relativeUnits: [Intl.RelativeTimeFormatUnit, number][] = [
  ['year', 31_536_000],
  ['month', 2_592_000],
  ['week', 604_800],
  ['day', 86_400],
  ['hour', 3_600],
  ['minute', 60],
]
/** "3 days ago" in the chosen language; the exact time stays in the element's title. */
function ago(value: string): string {
  const seconds = (Date.parse(value) - now.value) / 1000
  const format = new Intl.RelativeTimeFormat(locale.value, { numeric: 'auto' })
  for (const [unit, size] of relativeUnits)
    if (Math.abs(seconds) >= size) return format.format(Math.round(seconds / size), unit)
  return format.format(0, 'second')
}

const tableCaption = computed(() =>
  users.value
    ? t('Accounts, newest first, page {p0} of {p1}', { p0: users.value.page, p1: pageCount.value })
    : '',
)
const resultSummary = computed(() => {
  if (usersLoading.value) return t('Loading accounts…')
  if (!users.value) return ''
  const total = users.value.total
  const noun = total === 1 ? 'account' : 'accounts'
  return users.value.search
    ? t('{p0} {p1} with an email containing “{p2}”', {
        p0: total,
        p1: noun,
        p2: users.value.search,
      })
    : t('{p0} {p1} in total', { p0: total, p1: noun })
})

// Deletion
const openId = ref<string>()
const password = ref('')
const confirmed = ref(false)
const deleting = ref(false)
const rowErrors = reactive(new Map<string, Text>())
const refused = reactive(new Set<string>())
const announcement = ref<Text>('')
const usersHeading = ref<HTMLHeadingElement>()

onBeforeUnmount(() => {
  active = false
  password.value = ''
  removeEventListener('scroll', trackSection)
  cancelAnimationFrame(scrollFrame)
})

const isSelf = (row: AdminUser) => row.id === account.user?.id
const blocked = (row: AdminUser) => isSelf(row) || row.admin || refused.has(row.id)
function blockedReason(row: AdminUser): string {
  if (isSelf(row)) return 'This is your account. Delete it from Account instead.'
  if (row.admin)
    return 'Admin accounts cannot be deleted here. Revoke admin access on the server first.'
  return 'The server refused to delete this account.'
}

function focusById(id: string): void {
  document.getElementById(id)?.focus()
}

async function openDeletion(row: AdminUser): Promise<void> {
  if (deleting.value || blocked(row)) return
  openId.value = row.id
  password.value = ''
  confirmed.value = false
  rowErrors.delete(row.id)
  await nextTick()
  focusById('admin-delete-password')
}

async function cancelDeletion(row: AdminUser): Promise<void> {
  openId.value = undefined
  password.value = ''
  confirmed.value = false
  rowErrors.delete(row.id)
  await nextTick()
  focusById(`delete-trigger-${row.id}`)
}

async function finishRemoval(row: AdminUser, notice: Text): Promise<void> {
  openId.value = undefined
  confirmed.value = false
  if (users.value)
    users.value = {
      ...users.value,
      items: users.value.items.filter((item) => item.id !== row.id),
      total: Math.max(0, users.value.total - 1),
    }
  announcement.value = notice
  await nextTick()
  usersHeading.value?.focus()
  void loadUsers()
  void loadStats()
}

async function remove(row: AdminUser): Promise<void> {
  if (deleting.value || !confirmed.value || openId.value !== row.id || blocked(row)) return
  deleting.value = true
  announcement.value = ''
  rowErrors.delete(row.id)
  const secret = password.value
  password.value = ''
  try {
    await deleteAdminUser(row.id, secret)
    if (active) await finishRemoval(row, message('Deleted the account {p0}.', { p0: row.email }))
  } catch (cause) {
    if (!active) return
    if (cause instanceof AdminError && cause.status === 404) {
      await finishRemoval(
        row,
        message('The account {p0} had already been deleted.', { p0: row.email }),
      )
    } else if (cause instanceof AdminError && cause.status === 409) {
      refused.add(row.id)
      openId.value = undefined
      rowErrors.set(
        row.id,
        'The server refused this deletion. Admin accounts must have admin access revoked on the server first, and your own account is deleted from Account.',
      )
      await nextTick()
      focusById(`delete-error-${row.id}`)
      void loadUsers()
    } else {
      rowErrors.set(
        row.id,
        await explain(cause, 'Could not delete this account. Check your connection and try again.'),
      )
      // Re-enable the form before returning focus to the cleared password field.
      deleting.value = false
      await nextTick()
      if (openId.value === row.id) focusById('admin-delete-password')
    }
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <main id="main-content" class="page-width admin-page">
    <aside class="admin-rail">
      <nav class="rail-nav" :aria-label="t('Admin sections')">
        <a href="#usage" :aria-current="currentSection === 'usage' ? 'location' : undefined">{{
          t('Overview')
        }}</a>
        <a href="#growth" :aria-current="currentSection === 'growth' ? 'location' : undefined">{{
          t('Growth')
        }}</a>
        <a href="#courses" :aria-current="currentSection === 'courses' ? 'location' : undefined">{{
          t('Courses')
        }}</a>
        <a href="#users" :aria-current="currentSection === 'users' ? 'location' : undefined"
          >{{ t('Users')
          }}<span v-if="stats" class="rail-count">{{ number(stats.accounts.total) }}</span></a
        >
      </nav>
      <p v-if="account.user" class="rail-identity">
        {{ t('Signed in as') }} <strong>{{ account.user.email }}</strong>
      </p>
    </aside>

    <div class="admin-body">
      <header class="admin-top">
        <div>
          <h1>{{ t('Admin.') }}</h1>
          <p class="admin-intro">
            {{
              t(
                'Usage figures and account administration for this service. Only accounts with admin access can open this page.',
              )
            }}
          </p>
        </div>
        <div class="top-actions">
          <p v-if="stats" class="generated">
            {{ t('Calculated') }}
            <time :datetime="stats.generatedAt">{{ dateTime(stats.generatedAt) }}</time>
          </p>
          <button
            type="button"
            class="refresh-button"
            :disabled="statsLoading || usersLoading"
            @click="refresh"
          >
            <span class="refresh-icon" aria-hidden="true"></span>{{ t('Refresh') }}
          </button>
        </div>
      </header>

      <p v-if="signedOut" class="admin-notice" role="alert">
        {{ t('Your session has ended.') }}
        <RouterLink :to="signInLink">{{ t('Sign in again') }}</RouterLink> {{ t('to continue.') }}
      </p>
      <p v-else-if="notAdmin" class="admin-notice" role="alert">
        {{ t('Your account no longer has admin access.') }}
        <RouterLink to="/learn">{{ t('Choose a lesson') }}</RouterLink> {{ t('instead.') }}
      </p>

      <section id="usage" class="admin-section" aria-labelledby="usage-heading">
        <h2 id="usage-heading" class="sr-only">{{ t('Usage') }}</h2>
        <p role="status" class="admin-status">
          {{ statsLoading && !stats ? t('Loading usage statistics…') : '' }}
        </p>
        <div v-if="statsError" role="alert" class="admin-error">
          <p>{{ t(statsError) }}</p>
          <button type="button" :disabled="statsLoading" @click="loadStats">
            {{ t('Try again') }}
          </button>
        </div>

        <div v-if="stats" class="usage" :class="{ refreshing: statsLoading }">
          <!-- The one loud element: practice activity on ink, today in highlighter. -->
          <div class="hero-band">
            <section class="hero-figures" aria-labelledby="practice-heading">
              <h3 id="practice-heading">{{ t('Practice') }}</h3>
              <dl>
                <div class="hero-total">
                  <dt>{{ t('Completions') }}</dt>
                  <dd>{{ number(stats.practice.total) }}</dd>
                </div>
                <div>
                  <dt>{{ t('Completions, last 7 days') }}</dt>
                  <dd>{{ number(stats.practice.last7Days) }}</dd>
                </div>
                <div>
                  <dt>{{ t('Completions, last 30 days') }}</dt>
                  <dd>{{ number(stats.practice.last30Days) }}</dd>
                </div>
                <div>
                  <dt>{{ t('Learners, last 7 days') }}</dt>
                  <dd>{{ number(stats.practice.learners7Days) }}</dd>
                </div>
                <div>
                  <dt>{{ t('Learners, last 30 days') }}</dt>
                  <dd>{{ number(stats.practice.learners30Days) }}</dd>
                </div>
              </dl>
            </section>

            <section class="hero-chart" aria-labelledby="daily-heading">
              <div class="chart-head">
                <h3 id="daily-heading">{{ t('Completions by day') }}</h3>
                <p class="chart-note">
                  <span class="key key-today" aria-hidden="true"></span>{{ t('Today so far') }}
                  <template v-if="peakDay">
                    <span class="note-gap"></span
                    >{{
                      t('Busiest day: {p0} on {p1}', {
                        p0: peakDay.completions,
                        p1: day(peakDay.date),
                      })
                    }}
                  </template>
                </p>
              </div>
              <div class="plot plot-dark">
                <div class="gridlines" aria-hidden="true">
                  <span v-for="tick in axisTicks(dailyAxis)" :key="tick">{{ number(tick) }}</span>
                </div>
                <ol
                  class="columns"
                  :aria-label="t('Completions for each of the last 30 days')"
                  @keydown="rove($event, 'day', daily.length)"
                >
                  <li
                    v-for="(entry, index) in daily"
                    :key="entry.date"
                    :class="[
                      tipSide(index, daily.length),
                      { 'is-today': index === daily.length - 1 },
                    ]"
                    :tabindex="index === focused.day ? 0 : -1"
                    @focus="focused.day = index"
                  >
                    <span
                      class="column"
                      :class="{ 'is-empty': !entry.completions }"
                      :style="column(entry.completions, dailyAxis)"
                      aria-hidden="true"
                    ></span>
                    <span class="tip">
                      <time :datetime="entry.date">{{ day(entry.date, true) }}</time>
                      <strong
                        >{{ number(entry.completions) }}
                        {{ t(entry.completions === 1 ? 'completion' : 'completions') }}</strong
                      >
                    </span>
                  </li>
                </ol>
                <div class="axis-x" aria-hidden="true">
                  <span v-for="index in [0, 7, 14, 21]" :key="index">{{
                    daily[index] ? day(daily[index].date) : ''
                  }}</span>
                  <span>{{ t('Today') }}</span>
                </div>
              </div>
            </section>
          </div>

          <div class="tile-row">
            <section class="tile" aria-labelledby="accounts-heading">
              <h3 id="accounts-heading">{{ t('Accounts') }}</h3>
              <dl class="tile-total">
                <div>
                  <dt>{{ t('Total') }}</dt>
                  <dd>{{ number(stats.accounts.total) }}</dd>
                </div>
              </dl>
              <div class="split" aria-hidden="true">
                <span
                  class="split-verified"
                  :style="{ flexGrow: stats.accounts.verified || 0 }"
                ></span>
                <span
                  class="split-unverified"
                  :style="{ flexGrow: stats.accounts.unverified || 0 }"
                ></span>
              </div>
              <dl class="tile-breakdown">
                <div>
                  <dt>
                    <span class="key key-verified" aria-hidden="true"></span>{{ t('Verified') }}
                  </dt>
                  <dd>{{ number(stats.accounts.verified) }}</dd>
                </div>
                <div>
                  <dt>
                    <span class="key key-unverified" aria-hidden="true"></span>{{ t('Unverified') }}
                  </dt>
                  <dd>{{ number(stats.accounts.unverified) }}</dd>
                </div>
                <div>
                  <dt>{{ t('Admins') }}</dt>
                  <dd>{{ number(stats.accounts.admins) }}</dd>
                </div>
              </dl>
            </section>

            <section class="tile" aria-labelledby="signins-heading">
              <h3 id="signins-heading">{{ t('Accounts signed in') }}</h3>
              <dl class="recency">
                <div
                  :style="{ '--share': `${share(stats.signIns.last1Day, stats.accounts.total)}%` }"
                >
                  <dt>{{ t('Last 24 hours') }}</dt>
                  <dd>{{ number(stats.signIns.last1Day) }}</dd>
                </div>
                <div
                  :style="{ '--share': `${share(stats.signIns.last7Days, stats.accounts.total)}%` }"
                >
                  <dt>{{ t('Last 7 days') }}</dt>
                  <dd>{{ number(stats.signIns.last7Days) }}</dd>
                </div>
                <div
                  :style="{
                    '--share': `${share(stats.signIns.last30Days, stats.accounts.total)}%`,
                  }"
                >
                  <dt>{{ t('Last 30 days') }}</dt>
                  <dd>{{ number(stats.signIns.last30Days) }}</dd>
                </div>
                <div
                  class="is-never"
                  :style="{
                    '--share': `${share(stats.signIns.neverRecorded, stats.accounts.total)}%`,
                  }"
                >
                  <dt>{{ t('Never recorded') }}</dt>
                  <dd>{{ number(stats.signIns.neverRecorded) }}</dd>
                </div>
              </dl>
              <p class="tile-note">
                {{
                  t(
                    '“Never recorded” includes accounts that have not signed in since sign-in times started being kept.',
                  )
                }}
              </p>
            </section>

            <section class="tile" aria-labelledby="clean-heading">
              <h3 id="clean-heading">{{ t('Completed without help') }}</h3>
              <template v-if="stats.practice.assessed">
                <p class="clean-share">
                  <strong>{{
                    percent(stats.practice.cleanAssessed, stats.practice.assessed)
                  }}</strong>
                  <span class="meter" aria-hidden="true"
                    ><span
                      :style="{
                        width: `${share(stats.practice.cleanAssessed, stats.practice.assessed)}%`,
                      }"
                    ></span
                  ></span>
                  {{
                    t('{p0} of {p1} assessed completions used no hint, retry or takeback.', {
                      p0: stats.practice.cleanAssessed,
                      p1: stats.practice.assessed,
                    })
                  }}
                </p>
              </template>
              <p v-else class="tile-note">{{ t('No assessed completions yet.') }}</p>
              <p class="tile-note">
                {{
                  t('Older completions saved before assessments were recorded are not included.')
                }}
              </p>
            </section>
          </div>

          <div id="growth" class="panel-row panel-row-wide">
            <section class="panel" aria-labelledby="weekly-heading">
              <div class="chart-head">
                <h3 id="weekly-heading">{{ t('Sign-ups by week') }}</h3>
                <ul class="legend">
                  <li>
                    <span class="key key-signups" aria-hidden="true"></span>{{ t('Sign-ups') }}
                  </li>
                  <li>
                    <span class="key key-verifications" aria-hidden="true"></span
                    >{{ t('Verifications') }}
                  </li>
                </ul>
              </div>
              <div class="plot">
                <div class="gridlines" aria-hidden="true">
                  <span v-for="tick in axisTicks(weeklyAxis)" :key="tick">{{ number(tick) }}</span>
                </div>
                <ol
                  class="columns columns-paired"
                  :aria-label="t('Sign-ups and email verifications for the last 12 weeks')"
                  @keydown="rove($event, 'week', weekly.length)"
                >
                  <li
                    v-for="(week, index) in weekly"
                    :key="week.weekStart"
                    :class="tipSide(index, weekly.length)"
                    :tabindex="index === focused.week ? 0 : -1"
                    @focus="focused.week = index"
                  >
                    <span
                      class="column column-signups"
                      :class="{ 'is-empty': !week.signups }"
                      :style="column(week.signups, weeklyAxis)"
                      aria-hidden="true"
                    ></span>
                    <span
                      class="column column-verifications"
                      :class="{ 'is-empty': !week.verifications }"
                      :style="column(week.verifications, weeklyAxis)"
                      aria-hidden="true"
                    ></span>
                    <span class="tip">
                      <time :datetime="week.weekStart">{{
                        t('Week of {p0}', { p0: day(week.weekStart, true) })
                      }}</time>
                      <strong>{{
                        t('{p0} sign-ups, {p1} verifications', {
                          p0: week.signups,
                          p1: week.verifications,
                        })
                      }}</strong>
                    </span>
                  </li>
                </ol>
                <div class="axis-x" aria-hidden="true">
                  <span v-for="index in [0, 4, 8, 11]" :key="index">{{
                    weekly[index] ? day(weekly[index].weekStart) : ''
                  }}</span>
                </div>
              </div>
              <details class="table-toggle">
                <summary>{{ t('Show the figures as a table') }}</summary>
                <div class="table-scroll">
                  <table class="data-table">
                    <caption class="sr-only">
                      {{
                        t('Sign-ups and email verifications for the last 12 weeks')
                      }}
                    </caption>
                    <thead>
                      <tr>
                        <th scope="col">{{ t('Week starting') }}</th>
                        <th scope="col" class="numeric">{{ t('Sign-ups') }}</th>
                        <th scope="col" class="numeric">{{ t('Verifications') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="week in weekly" :key="week.weekStart">
                        <th scope="row">
                          <time :datetime="week.weekStart">{{ day(week.weekStart, true) }}</time>
                        </th>
                        <td class="numeric">{{ number(week.signups) }}</td>
                        <td class="numeric">{{ number(week.verifications) }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </details>
            </section>

            <section class="panel" aria-labelledby="colour-heading">
              <h3 id="colour-heading">{{ t('Pieces played') }}</h3>
              <div class="sides" aria-hidden="true">
                <span class="side-white" :style="{ flexGrow: stats.practice.byColor.w }"></span>
                <span class="side-black" :style="{ flexGrow: stats.practice.byColor.b }"></span>
              </div>
              <ul class="side-legend">
                <li>
                  <span class="side-token">{{ t('White') }}</span>
                  <span class="side-value"
                    >{{ number(stats.practice.byColor.w) }} ({{
                      percent(stats.practice.byColor.w, colorTotal)
                    }})</span
                  >
                </li>
                <li>
                  <span class="side-token is-black">{{ t('Black') }}</span>
                  <span class="side-value"
                    >{{ number(stats.practice.byColor.b) }} ({{
                      percent(stats.practice.byColor.b, colorTotal)
                    }})</span
                  >
                </li>
              </ul>
              <p class="tile-note">{{ t('Share of all saved completions by the side played.') }}</p>
            </section>
          </div>

          <div id="courses" class="panel-row">
            <section class="panel" aria-labelledby="courses-heading">
              <h3 id="courses-heading">{{ t('Courses') }}</h3>
              <div v-if="courses.length" class="table-scroll">
                <table class="data-table">
                  <caption class="sr-only">
                    {{
                      t('Completions and distinct learners for each course')
                    }}
                  </caption>
                  <thead>
                    <tr>
                      <th scope="col">{{ t('Course') }}</th>
                      <th scope="col" class="numeric">{{ t('Completions') }}</th>
                      <th scope="col" class="numeric">{{ t('Learners') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="course in courses" :key="course.lessonId">
                      <th scope="row">
                        {{ t(course.title) }}
                        <span class="row-bar" aria-hidden="true"
                          ><span
                            :style="{ width: `${share(course.completions, courseMaximum)}%` }"
                          ></span
                        ></span>
                      </th>
                      <td class="numeric">{{ number(course.completions) }}</td>
                      <td class="numeric">{{ number(course.learners) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="tile-note">{{ t('No courses published.') }}</p>
            </section>

            <section class="panel" aria-labelledby="lines-heading">
              <h3 id="lines-heading">{{ t('Most practised lines') }}</h3>
              <div v-if="stats.practice.topLines.length" class="table-scroll">
                <table class="data-table">
                  <caption class="sr-only">
                    {{
                      t('Up to ten lines with the most completions')
                    }}
                  </caption>
                  <thead>
                    <tr>
                      <th scope="col" class="rank">{{ t('Rank') }}</th>
                      <th scope="col">{{ t('Line') }}</th>
                      <th scope="col" class="numeric">{{ t('Completions') }}</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(line, index) in stats.practice.topLines"
                      :key="`${line.lessonId}/${line.lineId}`"
                    >
                      <td class="rank">{{ number(index + 1) }}</td>
                      <th scope="row">
                        {{ t(line.lineTitle) }}
                        <small>{{ t(line.courseTitle) }}</small>
                        <span class="row-bar" aria-hidden="true"
                          ><span
                            :style="{ width: `${share(line.completions, lineMaximum)}%` }"
                          ></span
                        ></span>
                      </th>
                      <td class="numeric">{{ number(line.completions) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <p v-else class="tile-note">{{ t('No completions recorded yet.') }}</p>
            </section>
          </div>

          <p class="usage-footnote">
            {{
              t(
                "These figures are calculated when you open this page, only from data the service already stores: accounts, each account's most recent sign-in time and saved lesson completions. Guided versus variation mode, page views, visits and time on page are not recorded, so they cannot be shown. Nothing is sent to third parties.",
              )
            }}
            {{ t('Dates are in UTC; weeks start on Monday.') }}
          </p>
        </div>
      </section>

      <section id="users" class="admin-section users-section" aria-labelledby="users-heading">
        <div class="users-head">
          <div>
            <h2 id="users-heading" ref="usersHeading" tabindex="-1">{{ t('Users') }}</h2>
            <p class="users-summary" aria-live="polite">{{ resultSummary }}</p>
          </div>
          <form
            class="admin-search"
            role="search"
            :aria-label="t('Search accounts')"
            @submit.prevent="navigate()"
          >
            <label for="admin-search">{{ t('Email contains') }}</label>
            <div class="search-row">
              <input
                id="admin-search"
                v-model="search"
                type="search"
                :maxlength="ADMIN_SEARCH_MAX"
                autocomplete="off"
                spellcheck="false"
              />
              <button type="submit" class="primary-link">{{ t('Search accounts') }}</button>
            </div>
          </form>
        </div>
        <p class="admin-status admin-announcement" role="status">{{ t(announcement) }}</p>
        <div v-if="usersError" role="alert" class="admin-error">
          <p>{{ t(usersError) }}</p>
          <RouterLink v-if="signedOut" :to="signInLink">{{ t('Sign in again') }}</RouterLink>
          <button v-else type="button" :disabled="usersLoading" @click="loadUsers">
            {{ t('Try again') }}
          </button>
        </div>
        <template v-if="users">
          <p v-if="!users.items.length" class="users-empty">
            {{ t(users.search ? 'No accounts match this search.' : 'No accounts yet.') }}
          </p>
          <div v-else class="users-frame" :class="{ refreshing: usersLoading }">
            <table class="users-table" :aria-busy="usersLoading">
              <caption class="sr-only">
                {{
                  tableCaption
                }}
              </caption>
              <thead>
                <tr>
                  <th scope="col">{{ t('Email') }}</th>
                  <th scope="col">{{ t('Created') }}</th>
                  <th scope="col">{{ t('Verified') }}</th>
                  <th scope="col">{{ t('Last sign-in') }}</th>
                  <th scope="col" class="numeric">{{ t('Completions') }}</th>
                  <th scope="col">{{ t('Last practice') }}</th>
                  <th scope="col" class="actions-head">{{ t('Actions') }}</th>
                </tr>
              </thead>
              <tbody>
                <template v-for="row in users.items" :key="row.id">
                  <tr :class="{ 'has-panel': openId === row.id || rowErrors.has(row.id) }">
                    <th scope="row" class="user-email" :data-label="t('Email')">
                      <span class="email-text">{{ row.email }}</span>
                      <span v-if="row.admin || isSelf(row)" class="badges">
                        <span v-if="row.admin" class="badge badge-admin">{{ t('Admin') }}</span>
                        <span v-if="isSelf(row)" class="badge badge-self">{{ t('You') }}</span>
                      </span>
                    </th>
                    <td :data-label="t('Created')">
                      <time :datetime="row.createdAt" :title="dateTime(row.createdAt)">{{
                        ago(row.createdAt)
                      }}</time>
                    </td>
                    <td :data-label="t('Verified')">
                      <time
                        v-if="row.verifiedAt"
                        class="state state-yes"
                        :datetime="row.verifiedAt"
                        :title="dateTime(row.verifiedAt)"
                        >{{ ago(row.verifiedAt) }}</time
                      >
                      <span v-else class="state state-no">{{ t('Not verified') }}</span>
                    </td>
                    <td :data-label="t('Last sign-in')">
                      <time
                        v-if="row.lastSignInAt"
                        :datetime="row.lastSignInAt"
                        :title="dateTime(row.lastSignInAt)"
                        >{{ ago(row.lastSignInAt) }}</time
                      >
                      <span v-else class="muted">{{ t('Never recorded') }}</span>
                    </td>
                    <td :data-label="t('Completions')" class="numeric">
                      <span :class="{ muted: !row.completions }">{{
                        number(row.completions)
                      }}</span>
                    </td>
                    <td :data-label="t('Last practice')">
                      <time
                        v-if="row.lastPracticeAt"
                        :datetime="row.lastPracticeAt"
                        :title="dateTime(row.lastPracticeAt)"
                        >{{ ago(row.lastPracticeAt) }}</time
                      >
                      <span v-else class="muted">{{ t('None') }}</span>
                    </td>
                    <td :data-label="t('Actions')" class="user-actions">
                      <button
                        :id="`delete-trigger-${row.id}`"
                        type="button"
                        class="delete-button"
                        :disabled="deleting || blocked(row) || openId === row.id"
                        :aria-describedby="blocked(row) ? `delete-blocked-${row.id}` : undefined"
                        :aria-label="t('Delete {p0}…', { p0: row.email })"
                        @click="openDeletion(row)"
                      >
                        {{ t('Delete…') }}
                      </button>
                      <small v-if="blocked(row)" :id="`delete-blocked-${row.id}`" class="blocked">{{
                        t(blockedReason(row))
                      }}</small>
                      <RouterLink v-if="isSelf(row)" to="/account" class="small-link">{{
                        t('Go to Account')
                      }}</RouterLink>
                    </td>
                  </tr>
                  <tr v-if="openId === row.id || rowErrors.has(row.id)" class="delete-row">
                    <td colspan="7">
                      <form
                        v-if="openId === row.id"
                        class="delete-form"
                        :aria-busy="deleting"
                        :aria-label="t('Delete {p0}', { p0: row.email })"
                        @submit.prevent="remove(row)"
                      >
                        <p id="admin-delete-description">
                          {{ t('Permanently delete') }} <strong>{{ row.email }}</strong
                          >{{
                            t(
                              ", its email links and all of its saved practice. This cannot be undone. The account's other sessions stop working on their next request.",
                            )
                          }}
                        </p>
                        <div class="delete-fields">
                          <div class="delete-password">
                            <label for="admin-delete-password">{{
                              t('Your current admin password')
                            }}</label>
                            <small id="admin-delete-password-help">{{
                              t("Enter your own password, not the learner's.")
                            }}</small>
                            <input
                              id="admin-delete-password"
                              v-model="password"
                              type="password"
                              autocomplete="current-password"
                              required
                              maxlength="256"
                              :disabled="deleting"
                              aria-describedby="admin-delete-description admin-delete-password-help"
                            />
                          </div>
                          <label class="confirm-option">
                            <input
                              v-model="confirmed"
                              type="checkbox"
                              required
                              :disabled="deleting"
                            />
                            <span>{{
                              t(
                                'I understand that this account and its saved practice will be permanently deleted.',
                              )
                            }}</span>
                          </label>
                        </div>
                        <p
                          v-if="rowErrors.has(row.id)"
                          :id="`delete-error-${row.id}`"
                          role="alert"
                          tabindex="-1"
                          class="admin-error-text"
                        >
                          {{ t(rowErrors.get(row.id)) }}
                        </p>
                        <RouterLink v-if="signedOut" :to="signInLink">{{
                          t('Sign in again')
                        }}</RouterLink>
                        <div class="form-actions">
                          <button class="danger-button" :disabled="deleting || !confirmed">
                            {{
                              t(deleting ? 'Deleting account…' : 'Permanently delete this account')
                            }}
                          </button>
                          <button
                            type="button"
                            class="cancel-button"
                            :disabled="deleting"
                            @click="cancelDeletion(row)"
                          >
                            {{ t('Keep this account') }}
                          </button>
                        </div>
                      </form>
                      <p
                        v-else
                        :id="`delete-error-${row.id}`"
                        role="alert"
                        tabindex="-1"
                        class="admin-error-text"
                      >
                        {{ t(rowErrors.get(row.id)) }}
                      </p>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
          <nav
            v-if="users.total > users.limit"
            class="admin-pages"
            :aria-label="t('Account pages')"
          >
            <button
              type="button"
              :aria-disabled="users.page <= 1 || usersLoading"
              @click="users.page > 1 && !usersLoading && navigate(users.page - 1)"
            >
              {{ t('Previous') }}
            </button>
            <span>{{ t('Page {p0} of {p1}', { p0: users.page, p1: pageCount }) }}</span>
            <button
              type="button"
              :aria-disabled="users.page >= pageCount || usersLoading"
              @click="users.page < pageCount && !usersLoading && navigate(users.page + 1)"
            >
              {{ t('Next') }}
            </button>
          </nav>
        </template>
      </section>
    </div>
  </main>
</template>

<style scoped>
/*
 * Dashboard: a quiet section rail, one ink band for practice activity (today in highlighter),
 * then white panels on paper. Chart series: ink ramp (#7f98d0 -> --ink), validated as ordinal.
 */
.admin-page {
  --series-light: #7f98d0;
  --hero-column: #8ea4d6;
  --panel-radius: 10px;
  display: grid;
  grid-template-columns: 176px minmax(0, 1fr);
  gap: 40px;
  padding-block: 36px 72px;
  min-height: 70vh;
}

/* Rail */
.admin-rail {
  position: sticky;
  top: 24px;
  align-self: start;
  display: grid;
  gap: 24px;
}
.rail-nav {
  display: grid;
  gap: 2px;
  padding-left: 0;
  border-left: 1.5px solid var(--rule);
}
.rail-nav a {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  min-height: 40px;
  margin-left: -1.5px;
  padding: 0 12px 0 14px;
  border-left: 3px solid transparent;
  color: var(--pencil);
  font-weight: 600;
  font-size: 15px;
  text-decoration: none;
}
.rail-nav a:hover {
  color: var(--ink);
}
.rail-nav a[aria-current='location'] {
  border-left-color: var(--ink);
  background: linear-gradient(90deg, var(--marker-soft), transparent);
  color: var(--ink);
}
.rail-count {
  padding: 1px 8px;
  border-radius: 999px;
  background: var(--sheet);
  border: 1px solid var(--rule);
  font-size: 12px;
  font-weight: 650;
  color: var(--pencil);
}
.rail-identity {
  margin: 0;
  font-size: 13px;
  line-height: 1.5;
  color: var(--pencil);
}
.rail-identity strong {
  display: block;
  color: var(--ink);
  font-weight: 600;
  overflow-wrap: anywhere;
}

/* Top bar */
.admin-body {
  min-width: 0;
}
.admin-top {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px 32px;
  padding-bottom: 24px;
}
h1 {
  font-size: clamp(34px, 4vw, 48px);
  margin-bottom: 8px;
}
.admin-intro {
  margin: 0;
  max-width: 56ch;
  color: var(--pencil);
  font-size: 15px;
}
.top-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px 16px;
}
.generated {
  margin: 0;
  color: var(--pencil);
  font-size: 13px;
}
.generated time {
  color: var(--ink);
  font-weight: 600;
}
.refresh-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}
.refresh-icon {
  width: 14px;
  height: 14px;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
}
.refresh-button:disabled .refresh-icon {
  animation: spin 0.9s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.admin-notice,
.admin-error {
  margin: 0 0 20px;
  padding: 14px 18px;
  border-left: 3px solid var(--danger);
  border-radius: 0 var(--radius) var(--radius) 0;
  background: var(--sheet);
  color: var(--danger);
  font-size: 15px;
}
.admin-error p {
  margin-top: 0;
}
.admin-error-text {
  margin: 0;
  color: var(--danger);
  font-size: 14px;
  font-weight: 600;
  line-height: 1.55;
}
/* Live regions stay rendered so later announcements are read. */
.admin-status {
  margin: 0;
  font-size: 14px;
}
.admin-status:not(:empty) {
  margin-bottom: 16px;
}
.admin-section {
  min-width: 0;
  scroll-margin-top: 24px;
}
#growth,
#courses {
  scroll-margin-top: 24px;
}
.usage {
  display: grid;
  gap: 20px;
  transition: opacity 0.2s;
}
/* Refetch keeps the frame: the previous figures stay, dimmed, until new ones arrive. */
.refreshing {
  opacity: 0.55;
}

h3 {
  font-size: 16px;
  font-stretch: var(--semi-wide);
  font-weight: 750;
  letter-spacing: 0;
  margin: 0 0 14px;
}
dl,
dd {
  margin: 0;
}

/* Hero band */
.hero-band {
  display: grid;
  grid-template-columns: minmax(240px, 5fr) minmax(0, 8fr);
  gap: 32px;
  padding: 28px;
  border-radius: var(--panel-radius);
  background: var(--ink);
  color: #fff;
}
.hero-band h3 {
  color: #fff;
}
.hero-figures dl {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px 20px;
}
.hero-figures dt {
  color: #c3cde3;
  font-size: 13px;
  line-height: 1.35;
}
.hero-figures dd {
  font-size: 26px;
  font-weight: 750;
  font-stretch: var(--semi-wide);
  font-variant-numeric: proportional-nums;
  line-height: 1.2;
}
.hero-total {
  grid-column: 1 / -1;
  padding-bottom: 18px;
  border-bottom: 1px solid #3a4a6e;
}
.hero-total dd {
  font-size: clamp(52px, 6vw, 76px);
  font-weight: 850;
  font-stretch: var(--wide);
  letter-spacing: -0.03em;
  line-height: 1;
}
.hero-chart {
  min-width: 0;
  display: flex;
  flex-direction: column;
}
.chart-head {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 4px 16px;
  margin-bottom: 14px;
}
.chart-head h3 {
  margin: 0;
}
.chart-note {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 4px 0;
  margin: 0;
  color: #c3cde3;
  font-size: 13px;
}
.note-gap {
  width: 16px;
}
.key {
  display: inline-block;
  width: 10px;
  height: 10px;
  margin-right: 7px;
  border-radius: 2px;
  flex: 0 0 auto;
}
.key-today {
  background: var(--marker);
}
.key-verified,
.key-verifications {
  background: var(--ink);
}
.key-unverified {
  background: var(--rule-strong);
}
.key-signups {
  background: var(--series-light);
}

/* Column charts (HTML lists, one item per mark) */
.plot {
  --plot-height: 180px;
  position: relative;
  padding-left: 32px;
}
.hero-chart .plot {
  --plot-height: 200px;
  flex: 1;
}
.gridlines {
  position: absolute;
  inset: 0 0 26px 0;
  height: var(--plot-height);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  pointer-events: none;
}
.gridlines span {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 0;
  font-size: 11px;
  color: var(--pencil);
}
.gridlines span::after {
  content: '';
  flex: 1;
  border-top: 1px solid var(--rule);
}
.gridlines span {
  width: 100%;
}
.plot-dark .gridlines span {
  color: #9fadc9;
}
.plot-dark .gridlines span::after {
  border-top-color: #33446a;
}
.columns {
  position: relative;
  display: grid;
  grid-auto-flow: column;
  grid-auto-columns: minmax(0, 1fr);
  align-items: end;
  height: var(--plot-height);
  margin: 0;
  padding: 0;
  list-style: none;
}
.columns > li {
  position: relative;
  display: flex;
  align-items: flex-end;
  justify-content: center;
  gap: 2px;
  height: 100%;
  padding-inline: 1px;
  outline-offset: 0;
  cursor: default;
}
.columns > li:focus-visible {
  outline: 2px solid currentColor;
  border-radius: 3px;
}
.column {
  display: block;
  width: 100%;
  max-width: 24px;
  height: var(--size);
  border-radius: 4px 4px 0 0;
  background: var(--hero-column);
}
.column.is-empty {
  height: 2px;
  border-radius: 0;
  background: #3a4a6e;
}
.is-today .column {
  background: var(--marker);
}
.columns > li:hover .column:not(.is-empty),
.columns > li:focus-visible .column:not(.is-empty) {
  filter: brightness(1.12);
}
.columns-paired .column {
  max-width: 14px;
}
.column-signups {
  background: var(--series-light);
}
.column-verifications {
  background: var(--ink);
}
.columns-paired .column.is-empty {
  background: var(--rule);
}
.columns-paired > li:hover .column:not(.is-empty),
.columns-paired > li:focus-visible .column:not(.is-empty) {
  filter: brightness(1.2);
}

/* Tooltip: the item's own text, clipped until hover or focus so it is never gated. */
.tip {
  position: absolute;
  bottom: calc(100% + 8px);
  left: 50%;
  z-index: 2;
  display: grid;
  gap: 2px;
  width: max-content;
  max-width: 220px;
  padding: 8px 10px;
  border: 1px solid var(--rule);
  border-radius: var(--radius);
  background: var(--sheet);
  color: var(--pencil);
  box-shadow: 0 6px 20px rgb(24 37 68 / 18%);
  font-size: 12px;
  line-height: 1.35;
  transform: translateX(-50%);
  pointer-events: none;
  clip-path: inset(50%);
  opacity: 0;
}
.tip strong {
  color: var(--ink);
  font-size: 14px;
}
.tip-start .tip {
  left: 0;
  transform: none;
}
.tip-end .tip {
  left: auto;
  right: 0;
  transform: none;
}
.columns > li:hover .tip,
.columns > li:focus-visible .tip {
  clip-path: none;
  opacity: 1;
}
.axis-x {
  display: flex;
  justify-content: space-between;
  margin-top: 8px;
  font-size: 11px;
  color: var(--pencil);
}
.plot-dark .axis-x {
  color: #9fadc9;
}

/* Tiles and panels */
.tile-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}
.panel-row {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
}
.panel-row-wide {
  grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
}
.tile,
.panel {
  min-width: 0;
  padding: 22px 24px;
  border: 1px solid var(--rule);
  border-radius: var(--panel-radius);
  background: var(--sheet);
}
.tile-note {
  margin: 14px 0 0;
  color: var(--pencil);
  font-size: 13px;
  line-height: 1.5;
}
.tile-total dt {
  font-size: 13px;
  color: var(--pencil);
}
.tile-total dd {
  font-size: 40px;
  font-weight: 800;
  font-stretch: var(--wide);
  font-variant-numeric: proportional-nums;
  letter-spacing: -0.02em;
  line-height: 1.1;
}
.split,
.sides {
  display: flex;
  gap: 2px;
  height: 10px;
  margin-block: 14px;
  border-radius: 3px;
  overflow: hidden;
  background: var(--rule);
}
.split > span,
.sides > span {
  flex-basis: 0;
  min-width: 0;
}
.split-verified {
  background: var(--ink);
}
.split-unverified {
  background: var(--rule-strong);
}
.tile-breakdown {
  display: grid;
  grid-template-columns: repeat(3, auto);
  justify-content: start;
  gap: 4px 24px;
}
.tile-breakdown dt {
  display: flex;
  align-items: center;
  font-size: 13px;
  color: var(--pencil);
}
.tile-breakdown dd {
  font-size: 20px;
  font-weight: 750;
  font-variant-numeric: proportional-nums;
}

/* Sign-in recency: each row carries a share-of-accounts bar beneath it. */
.recency {
  display: grid;
  gap: 12px;
}
.recency > div {
  position: relative;
  display: flex;
  justify-content: space-between;
  gap: 12px;
  padding-bottom: 9px;
  font-size: 14px;
}
.recency > div::before,
.recency > div::after {
  content: '';
  position: absolute;
  left: 0;
  bottom: 0;
  height: 5px;
  border-radius: 3px;
}
.recency > div::before {
  right: 0;
  background: var(--paper);
}
.recency > div::after {
  width: var(--share);
  background: var(--ink);
}
.recency > .is-never::after {
  background: var(--rule-strong);
}
.recency dt {
  color: var(--pencil);
}
.recency dd {
  font-weight: 700;
}

.clean-share {
  display: grid;
  gap: 10px;
  margin: 0;
  color: var(--ink);
  font-size: 14px;
  line-height: 1.5;
}
.clean-share strong {
  font-size: 40px;
  font-weight: 800;
  font-stretch: var(--wide);
  letter-spacing: -0.02em;
  line-height: 1.1;
}
.meter {
  display: block;
  height: 10px;
  border-radius: 3px;
  background: #dfe4ee;
  overflow: hidden;
}
.meter > span {
  display: block;
  height: 100%;
  border-radius: 3px;
  background: var(--ink);
}

.legend {
  display: flex;
  flex-wrap: wrap;
  gap: 4px 16px;
  margin: 0;
  padding: 0;
  list-style: none;
  font-size: 13px;
  color: var(--pencil);
}
.legend li {
  display: flex;
  align-items: center;
}
.table-toggle {
  margin-top: 16px;
  font-size: 14px;
}
.table-toggle summary {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
  color: var(--pencil);
}
.table-toggle[open] summary {
  color: var(--ink);
}

.sides {
  height: 28px;
  border-radius: 5px;
  background: transparent;
}
.side-white {
  background: #dce3ef;
}
.side-black {
  background: var(--ink);
}
.side-legend {
  display: grid;
  gap: 8px;
  margin: 0;
  padding: 0;
  list-style: none;
}
.side-legend li {
  display: flex;
  justify-content: space-between;
  gap: 12px;
}
.side-value {
  font-weight: 700;
}

/* Data tables */
.table-scroll {
  overflow-x: auto;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
.data-table th,
.data-table td {
  padding: 10px 12px 10px 0;
  border-bottom: 1px solid var(--rule);
  text-align: left;
  vertical-align: top;
}
.data-table tr:last-child > * {
  border-bottom: 0;
}
.data-table thead th {
  padding-top: 0;
  font-size: 12px;
  font-weight: 650;
  color: var(--pencil);
}
.data-table tbody th {
  font-weight: 600;
}
.data-table tbody th small {
  display: block;
  color: var(--pencil);
  font-size: 12px;
  font-weight: 500;
}
.numeric {
  text-align: right !important;
  font-variant-numeric: tabular-nums;
}
.data-table td.numeric {
  font-weight: 650;
  padding-right: 0;
}
.data-table td.numeric + td.numeric {
  padding-left: 12px;
}
.rank {
  width: 1%;
  color: var(--pencil);
  white-space: nowrap;
}
.row-bar {
  display: block;
  height: 5px;
  margin-top: 6px;
  border-radius: 3px;
  background: var(--paper);
}
.row-bar > span {
  display: block;
  height: 100%;
  border-radius: 3px;
  background: var(--ink);
}
.usage-footnote {
  max-width: 90ch;
  margin: 4px 0 0;
  color: var(--pencil);
  font-size: 13px;
  line-height: 1.6;
}

/* Users */
.users-section {
  margin-top: 48px;
  padding-top: 32px;
  border-top: 1px solid var(--rule);
}
.users-head {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px 32px;
  margin-bottom: 16px;
}
.users-head h2 {
  margin: 0 0 4px;
}
#users-heading:focus-visible {
  outline: 3px solid var(--ink);
  outline-offset: 5px;
}
.users-summary {
  margin: 0;
  min-height: 1.6em;
  color: var(--pencil);
  font-size: 14px;
}
.admin-search {
  display: grid;
  gap: 6px;
  font-size: 13px;
  color: var(--pencil);
}
.search-row {
  display: flex;
  gap: 8px;
}
.admin-search input {
  width: min(320px, 100%);
}
.admin-search .primary-link {
  min-height: 48px;
  white-space: nowrap;
  font-size: 15px;
}
.users-empty {
  padding: 32px 24px;
  border: 1px dashed var(--rule-strong);
  border-radius: var(--panel-radius);
  color: var(--pencil);
  max-width: none;
}
.users-frame {
  overflow-x: auto;
  border: 1px solid var(--rule);
  border-radius: var(--panel-radius);
  background: var(--sheet);
  transition: opacity 0.2s;
}
.users-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
.users-table th,
.users-table td {
  padding: 12px 14px;
  border-bottom: 1px solid var(--rule);
  text-align: left;
  vertical-align: middle;
}
.users-table thead th {
  position: sticky;
  top: 0;
  background: #f7f8fb;
  font-size: 12px;
  font-weight: 650;
  color: var(--pencil);
  white-space: nowrap;
}
.users-table tbody > tr:not(.delete-row):hover > * {
  background: #f8f9fc;
}
.users-table tbody > tr:last-child > * {
  border-bottom: 0;
}
.users-table tbody th {
  font-weight: 600;
}
.users-table td time,
.users-table td .muted {
  white-space: nowrap;
}
.user-email {
  min-width: 220px;
  overflow-wrap: anywhere;
}
.email-text {
  display: block;
}
.badges {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 4px;
}
.badge {
  padding: 1px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
  line-height: 1.6;
}
.badge-admin {
  background: var(--ink);
  color: #fff;
}
.badge-self {
  background: var(--marker-soft);
  color: var(--ink);
}
.state {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
}
.state::before {
  content: '';
  width: 8px;
  height: 8px;
  border-radius: 50%;
}
.state-yes::before {
  background: var(--ink);
}
.state-no {
  color: var(--pencil);
}
.state-no::before {
  border: 1.5px solid var(--rule-strong);
}
.muted {
  color: var(--pencil);
}
.user-actions {
  width: 1%;
  min-width: 170px;
}
.delete-button {
  min-height: 36px;
  padding: 0 12px;
  border-color: var(--rule-strong);
  color: var(--danger);
  font-size: 14px;
}
.delete-button:hover:not(:disabled) {
  border-color: var(--danger);
}
.delete-button:disabled {
  border-color: var(--rule);
  color: #8a93a6;
}
.blocked {
  display: block;
  max-width: 24ch;
  margin-top: 6px;
  color: var(--pencil);
  font-size: 12px;
  line-height: 1.45;
}
.small-link {
  display: inline-block;
  margin-top: 4px;
  font-size: 12px;
}
.has-panel > * {
  border-bottom-color: transparent !important;
}
.delete-row > td {
  padding: 0 14px 18px;
}
.delete-form {
  display: grid;
  gap: 14px;
  padding: 20px 22px;
  border-left: 3px solid var(--danger);
  border-radius: 0 var(--radius) var(--radius) 0;
  background: #fbf3f2;
}
.delete-form p {
  margin: 0;
  max-width: 80ch;
  line-height: 1.6;
}
.delete-form strong {
  overflow-wrap: anywhere;
}
.delete-fields {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
  gap: 14px 28px;
}
.delete-password {
  display: grid;
  gap: 4px;
  width: min(340px, 100%);
}
.delete-password label {
  font-size: 14px;
  font-weight: 600;
}
.delete-password small {
  color: var(--pencil);
  font-size: 12px;
}
.confirm-option {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  min-height: 44px;
  max-width: 44ch;
  cursor: pointer;
  font-size: 14px;
  line-height: 1.5;
}
.confirm-option input {
  width: 18px;
  height: 18px;
  margin: 2px 0 0;
  flex-shrink: 0;
}
.form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.danger-button {
  border-color: var(--danger);
  background: var(--danger);
  color: #fff;
}
.danger-button:hover:not(:disabled) {
  border-color: #7f1f1f;
  background: #7f1f1f;
}
.admin-pages {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 16px;
  margin-top: 16px;
  font-size: 14px;
}

@media (prefers-reduced-motion: reduce) {
  .refresh-button:disabled .refresh-icon {
    animation: none;
  }
  .usage,
  .users-frame {
    transition: none;
  }
}
@media (max-width: 1080px) {
  .tile-row {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .panel-row-wide {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 900px) {
  .admin-page {
    grid-template-columns: minmax(0, 1fr);
    gap: 20px;
    padding-top: 20px;
  }
  /* The rail becomes a horizontal strip of section links. */
  .admin-rail {
    position: static;
  }
  .rail-nav {
    display: flex;
    gap: 4px;
    overflow-x: auto;
    border-left: 0;
    border-bottom: 1.5px solid var(--rule);
  }
  .rail-nav a {
    flex: 0 0 auto;
    margin: 0 0 -1.5px;
    padding: 0 12px;
    border-left: 0;
    border-bottom: 3px solid transparent;
  }
  .rail-nav a[aria-current='location'] {
    border-bottom-color: var(--ink);
    background: none;
  }
  .rail-identity {
    display: none;
  }
  .hero-band {
    grid-template-columns: 1fr;
    gap: 28px;
  }
}
@media (max-width: 760px) {
  .tile-row,
  .panel-row {
    grid-template-columns: 1fr;
  }
  .hero-band {
    padding: 22px 18px;
  }
  .tile,
  .panel {
    padding: 18px;
  }
  .plot {
    padding-left: 26px;
  }
  .users-head {
    align-items: stretch;
  }
  .admin-search {
    width: 100%;
  }
  .search-row {
    flex-wrap: wrap;
  }
  .admin-search input {
    flex: 1 1 200px;
    width: auto;
  }
  /* Narrow screens show each account as a stacked card with labelled fields. */
  .users-frame {
    overflow: visible;
    border: 0;
    background: transparent;
  }
  .users-table thead {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip-path: inset(50%);
  }
  .users-table,
  .users-table tbody,
  .users-table tr,
  .users-table th,
  .users-table td {
    display: block;
    width: 100%;
  }
  .users-table tbody > tr:not(.delete-row) {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4px 16px;
    margin-top: 12px;
    padding: 14px 16px;
    border: 1px solid var(--rule);
    border-radius: var(--panel-radius);
    background: var(--sheet);
  }
  .users-table tbody > tr:not(.delete-row):hover > * {
    background: transparent;
  }
  .users-table tbody > tr.has-panel {
    border-radius: var(--panel-radius) var(--panel-radius) 0 0;
  }
  .users-table th,
  .users-table td {
    padding: 4px 0;
    border-bottom: 0 !important;
    text-align: left !important;
  }
  .users-table .user-email,
  .users-table .user-actions {
    grid-column: 1 / -1;
    min-width: 0;
  }
  .users-table .user-email {
    padding-bottom: 8px;
  }
  .users-table td::before {
    content: attr(data-label);
    display: block;
    font-size: 12px;
    color: var(--pencil);
  }
  .users-table .user-actions::before {
    display: none;
  }
  .users-table .user-actions {
    width: 100%;
    padding-top: 8px;
  }
  .delete-row > td {
    padding: 0;
  }
  .delete-form {
    border-left: 0;
    border: 1px solid var(--rule);
    border-top: 3px solid var(--danger);
    border-radius: 0 0 var(--panel-radius) var(--panel-radius);
    padding: 16px;
  }
  .admin-pages {
    justify-content: space-between;
  }
}
</style>
