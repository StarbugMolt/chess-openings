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
const day = (value: string, withYear = false) =>
  new Date(`${value}T00:00:00Z`).toLocaleDateString(locale.value, {
    timeZone: 'UTC',
    day: 'numeric',
    month: 'short',
    ...(withYear ? { year: 'numeric' } : {}),
  })
const dateTime = formatDate
const dailyMax = computed(() =>
  Math.max(0, ...(stats.value?.practice.daily.map((entry) => entry.completions) ?? [])),
)
const bar = (value: number, maximum: number) => ({
  width: `${maximum ? Math.max(value ? 2 : 0, (value / maximum) * 100) : 0}%`,
})
const colorTotal = computed(() =>
  stats.value ? stats.value.practice.byColor.w + stats.value.practice.byColor.b : 0,
)

// Users
const users = ref<AdminUserPage>()
const usersLoading = ref(false)
const usersError = ref<Text>('')
const search = ref('')
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
    <span class="eyebrow">{{ t('ADMIN') }}</span>
    <h1>{{ t('Admin.') }}</h1>
    <p class="admin-intro">
      {{
        t(
          'Usage figures and account administration for this service. Only accounts with admin access can open this page.',
        )
      }}
    </p>
    <nav class="admin-sections" :aria-label="t('Admin sections')">
      <a href="#usage">{{ t('Usage') }}</a
      ><a href="#users">{{ t('Users') }}</a>
    </nav>
    <p v-if="signedOut" class="admin-notice" role="alert">
      {{ t('Your session has ended.') }}
      <RouterLink :to="signInLink">{{ t('Sign in again') }}</RouterLink> {{ t('to continue.') }}
    </p>
    <p v-else-if="notAdmin" class="admin-notice" role="alert">
      {{ t('Your account no longer has admin access.') }}
      <RouterLink to="/learn">{{ t('Choose a lesson') }}</RouterLink> {{ t('instead.') }}
    </p>

    <section id="usage" class="admin-section" aria-labelledby="usage-heading">
      <h2 id="usage-heading">{{ t('Usage') }}</h2>
      <p class="admin-note">
        {{
          t(
            "These figures are calculated when you open this page, only from data the service already stores: accounts, each account's most recent sign-in time and saved lesson completions. Guided versus variation mode, page views, visits and time on page are not recorded, so they cannot be shown. Nothing is sent to third parties.",
          )
        }}
      </p>
      <p role="status" class="admin-status">
        {{ statsLoading ? t('Loading usage statistics…') : '' }}
      </p>
      <div v-if="statsError" role="alert" class="admin-error">
        <p>{{ t(statsError) }}</p>
        <button type="button" :disabled="statsLoading" @click="loadStats">
          {{ t('Try again') }}
        </button>
      </div>
      <template v-if="stats">
        <p class="admin-generated">
          {{ t('Calculated') }}
          <time :datetime="stats.generatedAt">{{ dateTime(stats.generatedAt) }}</time
          >{{ t('. Dates are in UTC; weeks start on Monday.') }}
        </p>
        <div class="headline-grid">
          <section class="admin-card" aria-labelledby="accounts-heading">
            <h3 id="accounts-heading">{{ t('Accounts') }}</h3>
            <dl>
              <div>
                <dt>{{ t('Total') }}</dt>
                <dd>{{ number(stats.accounts.total) }}</dd>
              </div>
              <div>
                <dt>{{ t('Verified') }}</dt>
                <dd>{{ number(stats.accounts.verified) }}</dd>
              </div>
              <div>
                <dt>{{ t('Unverified') }}</dt>
                <dd>{{ number(stats.accounts.unverified) }}</dd>
              </div>
              <div>
                <dt>{{ t('Admins') }}</dt>
                <dd>{{ number(stats.accounts.admins) }}</dd>
              </div>
            </dl>
          </section>
          <section class="admin-card" aria-labelledby="signins-heading">
            <h3 id="signins-heading">{{ t('Accounts signed in') }}</h3>
            <dl>
              <div>
                <dt>{{ t('Last 24 hours') }}</dt>
                <dd>{{ number(stats.signIns.last1Day) }}</dd>
              </div>
              <div>
                <dt>{{ t('Last 7 days') }}</dt>
                <dd>{{ number(stats.signIns.last7Days) }}</dd>
              </div>
              <div>
                <dt>{{ t('Last 30 days') }}</dt>
                <dd>{{ number(stats.signIns.last30Days) }}</dd>
              </div>
              <div>
                <dt>{{ t('Never recorded') }}</dt>
                <dd>{{ number(stats.signIns.neverRecorded) }}</dd>
              </div>
            </dl>
            <p class="admin-note">
              {{
                t(
                  '“Never recorded” includes accounts that have not signed in since sign-in times started being kept.',
                )
              }}
            </p>
          </section>
          <section class="admin-card" aria-labelledby="practice-heading">
            <h3 id="practice-heading">{{ t('Practice') }}</h3>
            <dl>
              <div>
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
        </div>

        <div class="detail-grid">
          <section class="admin-card" aria-labelledby="weekly-heading">
            <h3 id="weekly-heading">{{ t('Sign-ups by week') }}</h3>
            <div class="table-scroll">
              <table>
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
                  <tr v-for="week in stats.accounts.weekly" :key="week.weekStart">
                    <th scope="row">
                      <time :datetime="week.weekStart">{{ day(week.weekStart, true) }}</time>
                    </th>
                    <td class="numeric">{{ number(week.signups) }}</td>
                    <td class="numeric">{{ number(week.verifications) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
          <section class="admin-card" aria-labelledby="daily-heading">
            <h3 id="daily-heading">{{ t('Completions by day') }}</h3>
            <p class="admin-note">
              {{ t('Last 30 days. Each bar is scaled to the busiest day.') }}
            </p>
            <ol class="bar-list" :aria-label="t('Completions for each of the last 30 days')">
              <li v-for="entry in stats.practice.daily" :key="entry.date">
                <time :datetime="entry.date">{{ day(entry.date) }}</time>
                <span class="bar" aria-hidden="true"
                  ><span :style="bar(entry.completions, dailyMax)"></span
                ></span>
                <span class="bar-value"
                  >{{ number(entry.completions) }}
                  <span class="sr-only">{{
                    t(entry.completions === 1 ? 'completion' : 'completions')
                  }}</span></span
                >
              </li>
            </ol>
          </section>
        </div>

        <div class="detail-grid">
          <section class="admin-card" aria-labelledby="courses-heading">
            <h3 id="courses-heading">{{ t('Courses') }}</h3>
            <div class="table-scroll">
              <table>
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
                  <tr v-for="course in stats.practice.byCourse" :key="course.lessonId">
                    <th scope="row">{{ t(course.title) }}</th>
                    <td class="numeric">{{ number(course.completions) }}</td>
                    <td class="numeric">{{ number(course.learners) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="!stats.practice.byCourse.length" class="admin-note">
              {{ t('No courses published.') }}
            </p>
          </section>
          <section class="admin-card" aria-labelledby="lines-heading">
            <h3 id="lines-heading">{{ t('Most practised lines') }}</h3>
            <div v-if="stats.practice.topLines.length" class="table-scroll">
              <table>
                <caption class="sr-only">
                  {{
                    t('Up to ten lines with the most completions')
                  }}
                </caption>
                <thead>
                  <tr>
                    <th scope="col">{{ t('Line') }}</th>
                    <th scope="col">{{ t('Course') }}</th>
                    <th scope="col" class="numeric">{{ t('Completions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="line in stats.practice.topLines"
                    :key="`${line.lessonId}/${line.lineId}`"
                  >
                    <th scope="row">{{ t(line.lineTitle) }}</th>
                    <td>{{ t(line.courseTitle) }}</td>
                    <td class="numeric">{{ number(line.completions) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-else class="admin-note">{{ t('No completions recorded yet.') }}</p>
          </section>
        </div>

        <div class="detail-grid">
          <section class="admin-card" aria-labelledby="colour-heading">
            <h3 id="colour-heading">{{ t('Pieces played') }}</h3>
            <ul class="bar-list split-list">
              <li>
                <span>{{ t('White') }}</span>
                <span class="bar" aria-hidden="true"
                  ><span :style="bar(stats.practice.byColor.w, colorTotal)"></span
                ></span>
                <span class="bar-value"
                  >{{ number(stats.practice.byColor.w) }} ({{
                    percent(stats.practice.byColor.w, colorTotal)
                  }})</span
                >
              </li>
              <li>
                <span>{{ t('Black') }}</span>
                <span class="bar" aria-hidden="true"
                  ><span :style="bar(stats.practice.byColor.b, colorTotal)"></span
                ></span>
                <span class="bar-value"
                  >{{ number(stats.practice.byColor.b) }} ({{
                    percent(stats.practice.byColor.b, colorTotal)
                  }})</span
                >
              </li>
            </ul>
          </section>
          <section class="admin-card" aria-labelledby="clean-heading">
            <h3 id="clean-heading">{{ t('Completed without help') }}</h3>
            <p v-if="stats.practice.assessed" class="clean-share">
              <strong>{{ percent(stats.practice.cleanAssessed, stats.practice.assessed) }}</strong>
              {{
                t('{p0} of {p1} assessed completions used no hint, retry or takeback.', {
                  p0: stats.practice.cleanAssessed,
                  p1: stats.practice.assessed,
                })
              }}
            </p>
            <p v-else class="admin-note">{{ t('No assessed completions yet.') }}</p>
            <p class="admin-note">
              {{ t('Older completions saved before assessments were recorded are not included.') }}
            </p>
          </section>
        </div>
      </template>
    </section>

    <section id="users" class="admin-section" aria-labelledby="users-heading">
      <h2 id="users-heading" ref="usersHeading" tabindex="-1">{{ t('Users') }}</h2>
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
          <button type="submit">{{ t('Search accounts') }}</button>
        </div>
      </form>
      <p class="admin-status" aria-live="polite">{{ resultSummary }}</p>
      <p class="admin-status admin-announcement" role="status">{{ t(announcement) }}</p>
      <div v-if="usersError" role="alert" class="admin-error">
        <p>{{ t(usersError) }}</p>
        <RouterLink v-if="signedOut" :to="signInLink">{{ t('Sign in again') }}</RouterLink>
        <button v-else type="button" :disabled="usersLoading" @click="loadUsers">
          {{ t('Try again') }}
        </button>
      </div>
      <template v-if="users">
        <p v-if="!users.items.length" class="admin-note">
          {{ t(users.search ? 'No accounts match this search.' : 'No accounts yet.') }}
        </p>
        <div v-else class="table-scroll">
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
                <th scope="col">{{ t('Actions') }}</th>
              </tr>
            </thead>
            <tbody>
              <template v-for="row in users.items" :key="row.id">
                <tr :class="{ 'has-panel': openId === row.id || rowErrors.has(row.id) }">
                  <th scope="row" class="user-email" :data-label="t('Email')">
                    <span>{{ row.email }}</span>
                    <span v-if="row.admin" class="badge">{{ t('Admin') }}</span>
                    <span v-if="isSelf(row)" class="badge badge-self">{{ t('You') }}</span>
                  </th>
                  <td :data-label="t('Created')">
                    <time :datetime="row.createdAt">{{ dateTime(row.createdAt) }}</time>
                  </td>
                  <td :data-label="t('Verified')">
                    <time v-if="row.verifiedAt" :datetime="row.verifiedAt">{{
                      dateTime(row.verifiedAt)
                    }}</time>
                    <span v-else class="muted">{{ t('Not verified') }}</span>
                  </td>
                  <td :data-label="t('Last sign-in')">
                    <time v-if="row.lastSignInAt" :datetime="row.lastSignInAt">{{
                      dateTime(row.lastSignInAt)
                    }}</time>
                    <span v-else class="muted">{{ t('Never recorded') }}</span>
                  </td>
                  <td :data-label="t('Completions')" class="numeric">
                    {{ number(row.completions) }}
                  </td>
                  <td :data-label="t('Last practice')">
                    <time v-if="row.lastPracticeAt" :datetime="row.lastPracticeAt">{{
                      dateTime(row.lastPracticeAt)
                    }}</time>
                    <span v-else class="muted">{{ t('None') }}</span>
                  </td>
                  <td :data-label="t('Actions')" class="user-actions">
                    <button
                      :id="`delete-trigger-${row.id}`"
                      type="button"
                      class="danger-button"
                      :disabled="deleting || blocked(row) || openId === row.id"
                      :aria-describedby="blocked(row) ? `delete-blocked-${row.id}` : undefined"
                      :aria-label="t('Delete {p0}…', { p0: row.email })"
                      @click="openDeletion(row)"
                    >
                      {{ t('Delete…') }}
                    </button>
                    <small v-if="blocked(row)" :id="`delete-blocked-${row.id}`" class="muted">{{
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
                      <label class="confirm-option">
                        <input v-model="confirmed" type="checkbox" required :disabled="deleting" />
                        <span>{{
                          t(
                            'I understand that this account and its saved practice will be permanently deleted.',
                          )
                        }}</span>
                      </label>
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
        <nav v-if="users.total > users.limit" class="admin-pages" :aria-label="t('Account pages')">
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
  </main>
</template>

<style scoped>
.admin-page {
  padding-block: 52px 80px;
  min-height: 70vh;
}
h1 {
  font-size: clamp(34px, 5vw, 54px);
  letter-spacing: -1.4px;
  margin-block: 18px;
}
h2 {
  font-size: 30px;
  margin: 0 0 12px;
}
h3 {
  font-size: 20px;
  margin: 0 0 14px;
}
.admin-intro,
.admin-note,
.admin-generated {
  max-width: 720px;
  color: var(--muted);
  line-height: 1.7;
  font-size: 14px;
}
.admin-card .admin-note {
  font-size: 13px;
}
.admin-sections {
  display: flex;
  gap: 18px;
  font-size: 14px;
}
.admin-sections a {
  display: inline-flex;
  align-items: center;
  min-height: 44px;
}
.admin-section {
  border-top: 1px solid var(--line);
  margin-top: 32px;
  padding-top: 32px;
  min-width: 0;
}
.admin-notice {
  padding: 14px 18px;
  border: 1px solid #d9b9ad;
  border-radius: 6px;
  background: #fff7f4;
  color: #7d3025;
}
/* Live regions stay rendered so later announcements are read. */
.admin-status:empty {
  margin: 0;
}
.admin-status {
  font-size: 14px;
  color: var(--ink);
}
.admin-error {
  padding: 14px 18px;
  border: 1px solid #d9b9ad;
  border-radius: 6px;
  background: #fff7f4;
  color: #7d3025;
  font-size: 14px;
}
.admin-error p {
  margin-top: 0;
}
.admin-error-text {
  color: #8b382b;
  font-size: 14px;
  line-height: 1.6;
  margin: 0;
}
.headline-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
  margin-top: 20px;
}
.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 20px;
  margin-top: 20px;
}
.admin-card {
  min-width: 0;
  padding: 24px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #faf9f4;
}
dl {
  display: grid;
  gap: 10px;
  margin: 0;
}
dl div {
  display: flex;
  justify-content: space-between;
  gap: 12px;
  border-bottom: 1px solid var(--line);
  padding-bottom: 8px;
  font-size: 14px;
}
dt {
  color: var(--muted);
}
dd {
  margin: 0;
  font-weight: 650;
  font-variant-numeric: tabular-nums;
  color: var(--ink);
}
.table-scroll {
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}
th,
td {
  padding: 8px 10px 8px 0;
  border-bottom: 1px solid var(--line);
  text-align: left;
  vertical-align: top;
}
thead th {
  font-size: 12px;
  font-weight: 650;
  color: var(--muted);
}
tbody th {
  font-weight: 500;
}
.numeric {
  text-align: right;
  font-variant-numeric: tabular-nums;
}
.bar-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 4px;
  font-size: 13px;
}
.bar-list li {
  display: grid;
  grid-template-columns: 64px minmax(0, 1fr) minmax(48px, auto);
  align-items: center;
  gap: 10px;
}
.bar {
  display: block;
  height: 10px;
  border-radius: 2px;
  background: #eceee4;
  overflow: hidden;
}
.bar > span {
  display: block;
  height: 100%;
  background: var(--green);
}
.bar-value {
  text-align: right;
  font-variant-numeric: tabular-nums;
  color: var(--ink);
}
.split-list {
  gap: 12px;
  font-size: 14px;
}
.split-list li {
  grid-template-columns: 56px minmax(0, 1fr) minmax(80px, auto);
}
.clean-share {
  font-size: 14px;
  line-height: 1.7;
  color: var(--ink);
}
.clean-share strong {
  display: block;
  font:
    34px Georgia,
    serif;
}
.admin-search {
  display: grid;
  gap: 8px;
  margin-block: 20px;
  font-size: 13px;
}
.search-row {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.admin-search input {
  width: min(360px, 100%);
  min-height: 44px;
  padding: 12px;
  border: 1px solid #bec8b8;
  border-radius: 4px;
  background: #fff;
  color: var(--ink);
}
button {
  min-height: 44px;
  padding: 10px 16px;
  border: 1px solid #aab79f;
  border-radius: 4px;
  background: #eeefe5;
  color: var(--ink);
  font-size: 13px;
}
button:disabled,
button[aria-disabled='true'] {
  opacity: 0.5;
  cursor: default;
}
.danger-button,
.cancel-button {
  border: 1px solid #943a2c;
  background: #fff7f4;
  color: #7d3025;
  line-height: 1.4;
}
.cancel-button {
  border-color: #afbaa4;
  background: #f4f4e9;
  color: var(--ink);
}
.users-table {
  margin-top: 8px;
}
.user-email {
  overflow-wrap: anywhere;
}
.user-email > span:first-child {
  display: block;
}
.badge {
  display: inline-block;
  margin: 4px 6px 0 0;
  padding: 2px 8px;
  border: 1px solid var(--green);
  border-radius: 20px;
  color: var(--green);
  font-size: 11px;
  font-weight: 650;
  letter-spacing: 0.4px;
}
.badge-self {
  border-color: #afbaa4;
  color: var(--muted);
}
.muted {
  color: var(--muted);
}
.user-actions {
  min-width: 150px;
}
.user-actions small {
  display: block;
  margin-top: 6px;
  font-size: 12px;
  line-height: 1.5;
}
.small-link {
  display: inline-block;
  margin-top: 4px;
  font-size: 12px;
}
.has-panel > * {
  border-bottom-color: transparent;
}
.delete-row > td {
  padding: 0 0 18px;
}
.delete-form {
  display: grid;
  gap: 12px;
  max-width: 560px;
  padding: 20px;
  border: 1px solid #d9b9ad;
  border-radius: 6px;
  background: #fffaf7;
}
.delete-form p {
  margin: 0;
  line-height: 1.6;
  color: var(--ink);
}
.delete-form strong {
  overflow-wrap: anywhere;
}
.delete-form label {
  font-size: 13px;
}
.delete-form small {
  margin-top: -6px;
  color: var(--muted);
  font-size: 12px;
}
.delete-form input[type='password'] {
  min-height: 44px;
  padding: 12px;
  border: 1px solid #bec8b8;
  border-radius: 4px;
  background: #fff;
  color: var(--ink);
}
.confirm-option {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  min-height: 44px;
  cursor: pointer;
  line-height: 1.5;
}
.confirm-option input {
  width: 18px;
  height: 18px;
  margin: 2px 0 0;
  flex-shrink: 0;
  accent-color: var(--ink);
}
.form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.admin-pages {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-top: 20px;
  font-size: 14px;
}
#users-heading:focus-visible {
  outline: 3px solid #b67339;
  outline-offset: 5px;
}
@media (max-width: 950px) {
  .headline-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 760px) {
  .headline-grid,
  .detail-grid {
    grid-template-columns: 1fr;
  }
  .admin-card {
    padding: 18px;
  }
  /* Narrow screens show each account as a stacked card with labelled fields. */
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
    margin-top: 14px;
    padding: 14px 16px;
    border: 1px solid var(--line);
    border-radius: 8px;
    background: #faf9f4;
  }
  .users-table tbody > tr.has-panel {
    border-radius: 8px 8px 0 0;
  }
  .users-table th,
  .users-table td {
    padding: 6px 0;
    border-bottom: 0;
    text-align: left;
  }
  .users-table td::before {
    content: attr(data-label);
    display: block;
    font-size: 12px;
    color: var(--muted);
  }
  .user-actions {
    min-width: 0;
  }
  .delete-row > td {
    padding: 0;
  }
  .delete-form {
    max-width: none;
    border-radius: 0 0 8px 8px;
    padding: 16px;
  }
}
</style>
