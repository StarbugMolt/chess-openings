import type { LocationQuery } from 'vue-router'
import { retryAfter } from './account'
import { csrfRequest } from './http'

/** Contract: docs/ADMIN_REVIEW.md §8. The server enforces ROLE_ADMIN; these shapes only render it. */
export interface AdminUser {
  id: string
  email: string
  createdAt: string
  verifiedAt: string | null
  lastSignInAt: string | null
  admin: boolean
  completions: number
  lastPracticeAt: string | null
}
export interface AdminUserPage {
  items: AdminUser[]
  page: number
  limit: number
  total: number
  search: string
}
export interface AdminUserQuery {
  search: string
  page: number
}
export interface AdminWeek {
  weekStart: string
  signups: number
  verifications: number
}
export interface AdminDay {
  date: string
  completions: number
}
export interface AdminCourseStats {
  lessonId: string
  title: string
  completions: number
  learners: number
}
export interface AdminLineStats {
  lessonId: string
  lineId: string
  courseTitle: string
  lineTitle: string
  completions: number
}
export interface AdminStats {
  generatedAt: string
  accounts: {
    total: number
    verified: number
    unverified: number
    admins: number
    weekly: AdminWeek[]
  }
  signIns: { last1Day: number; last7Days: number; last30Days: number; neverRecorded: number }
  practice: {
    total: number
    last7Days: number
    last30Days: number
    learners7Days: number
    learners30Days: number
    daily: AdminDay[]
    byCourse: AdminCourseStats[]
    topLines: AdminLineStats[]
    byColor: { w: number; b: number }
    assessed: number
    cleanAssessed: number
  }
}

/** `status` 0 means the request never reached the service (offline or blocked). */
export class AdminError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly retryAfter?: number,
  ) {
    super(message)
  }
}

export const ADMIN_SEARCH_MAX = 254
export const ADMIN_PAGE_MAX = 9999
const DAY = 86_400_000

function invalid(): never {
  throw new AdminError('The admin service returned an unexpected response.', 502)
}
function object(value: unknown): Record<string, unknown> {
  if (!value || typeof value !== 'object' || Array.isArray(value)) invalid()
  return value as Record<string, unknown>
}
function list(value: unknown, minimum = 0, maximum = Number.MAX_SAFE_INTEGER): unknown[] {
  if (!Array.isArray(value) || value.length < minimum || value.length > maximum) invalid()
  return value
}
function text(value: unknown, maximum = 1000): string {
  if (typeof value !== 'string' || !value.trim() || value.length > maximum) invalid()
  return value
}
function slug(value: unknown): string {
  const result = text(value)
  if (!/^[a-z0-9][a-z0-9-]{0,47}$/.test(result)) invalid()
  return result
}
function count(value: unknown, maximum = Number.MAX_SAFE_INTEGER): number {
  if (typeof value !== 'number' || !Number.isSafeInteger(value) || value < 0 || value > maximum)
    invalid()
  return value
}
function boolean(value: unknown): boolean {
  if (typeof value !== 'boolean') invalid()
  return value
}
function calendar(day: string): number {
  // Date.parse silently normalizes impossible calendar dates such as February 30.
  const time = Date.parse(`${day}T00:00:00Z`)
  if (!Number.isFinite(time) || new Date(time).toISOString().slice(0, 10) !== day) invalid()
  return time
}
function timestamp(value: unknown): string {
  const result = text(value)
  if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|[+-]\d{2}:\d{2})$/.test(result))
    invalid()
  calendar(result.slice(0, 10))
  if (!Number.isFinite(Date.parse(result))) invalid()
  return result
}
function nullableTimestamp(value: unknown): string | null {
  return value === null ? null : timestamp(value)
}
function day(value: unknown): string {
  const result = text(value)
  if (!/^\d{4}-\d{2}-\d{2}$/.test(result)) invalid()
  calendar(result)
  return result
}
/** Series are oldest -> newest, consecutive, and include zero entries. */
function consecutive(days: string[], step: number): void {
  for (let index = 1; index < days.length; index++)
    if (calendar(days[index]!) - calendar(days[index - 1]!) !== step) invalid()
}

export function adminUser(value: unknown): AdminUser {
  const row = object(value)
  if (typeof row.id !== 'string' || !/^[1-9]\d{0,19}$/.test(row.id)) invalid()
  const completions = count(row.completions)
  const lastPracticeAt = nullableTimestamp(row.lastPracticeAt)
  if ((completions === 0) !== (lastPracticeAt === null)) invalid()
  return {
    id: row.id,
    email: text(row.email, 254),
    createdAt: timestamp(row.createdAt),
    verifiedAt: nullableTimestamp(row.verifiedAt),
    lastSignInAt: nullableTimestamp(row.lastSignInAt),
    admin: boolean(row.admin),
    completions,
    lastPracticeAt,
  }
}

export function adminUserPage(value: unknown): AdminUserPage {
  const data = object(value)
  const limit = count(data.limit, 1000)
  if (limit < 1) invalid()
  const page = count(data.page, ADMIN_PAGE_MAX)
  if (page < 1) invalid()
  const total = count(data.total)
  const items = list(data.items, 0, limit).map(adminUser)
  if (typeof data.search !== 'string' || data.search.length > ADMIN_SEARCH_MAX) invalid()
  // A page past the end is empty; otherwise every row must fall inside the reported total.
  if ((page - 1) * limit + items.length > total && items.length) invalid()
  if (new Set(items.map((item) => item.id)).size !== items.length) invalid()
  return { items, page, limit, total, search: data.search }
}

export function adminStats(value: unknown): AdminStats {
  const data = object(value)
  const accounts = object(data.accounts)
  const signIns = object(data.signIns)
  const practice = object(data.practice)
  const total = count(accounts.total)
  const bounded = (field: unknown) => count(field, total)
  const weekly = list(accounts.weekly, 12, 12).map((value): AdminWeek => {
    const row = object(value)
    const weekStart = day(row.weekStart)
    if (new Date(calendar(weekStart)).getUTCDay() !== 1) invalid()
    return { weekStart, signups: count(row.signups), verifications: count(row.verifications) }
  })
  consecutive(
    weekly.map((week) => week.weekStart),
    7 * DAY,
  )
  const daily = list(practice.daily, 30, 30).map((value): AdminDay => {
    const row = object(value)
    return { date: day(row.date), completions: count(row.completions) }
  })
  consecutive(
    daily.map((entry) => entry.date),
    DAY,
  )
  const byCourse = list(practice.byCourse, 0, 1000).map((value): AdminCourseStats => {
    const row = object(value)
    const completions = count(row.completions)
    const learners = count(row.learners, completions)
    return { lessonId: slug(row.lessonId), title: text(row.title), completions, learners }
  })
  if (new Set(byCourse.map((course) => course.lessonId)).size !== byCourse.length) invalid()
  const topLines = list(practice.topLines, 0, 10).map((value): AdminLineStats => {
    const row = object(value)
    return {
      lessonId: slug(row.lessonId),
      lineId: slug(row.lineId),
      courseTitle: text(row.courseTitle),
      lineTitle: text(row.lineTitle),
      completions: count(row.completions),
    }
  })
  for (let index = 1; index < topLines.length; index++)
    if (topLines[index]!.completions > topLines[index - 1]!.completions) invalid()
  const byColor = object(practice.byColor)
  const assessed = count(practice.assessed)
  return {
    generatedAt: timestamp(data.generatedAt),
    accounts: {
      total,
      verified: bounded(accounts.verified),
      unverified: bounded(accounts.unverified),
      admins: bounded(accounts.admins),
      weekly,
    },
    signIns: {
      last1Day: bounded(signIns.last1Day),
      last7Days: bounded(signIns.last7Days),
      last30Days: bounded(signIns.last30Days),
      neverRecorded: bounded(signIns.neverRecorded),
    },
    practice: {
      total: count(practice.total),
      last7Days: count(practice.last7Days),
      last30Days: count(practice.last30Days),
      learners7Days: count(practice.learners7Days),
      learners30Days: count(practice.learners30Days),
      daily,
      byCourse,
      topLines,
      byColor: { w: count(byColor.w), b: count(byColor.b) },
      assessed,
      cleanAssessed: count(practice.cleanAssessed, assessed),
    },
  }
}

const fallbacks: Record<number, string> = {
  400: 'The request was not accepted. Check the details and try again.',
  401: 'Your session has ended. Sign in again to continue.',
  403: 'Admin access was refused.',
  404: 'This account no longer exists.',
  409: 'This account cannot be deleted here.',
  429: 'Too many confirmation attempts. Please try again later.',
}

async function responseData(response: Response): Promise<Record<string, unknown>> {
  let data: unknown
  try {
    data = await response.json()
  } catch {
    data = undefined
  }
  if (!response.ok) {
    const error =
      data && typeof data === 'object' && 'error' in data && typeof data.error === 'string'
        ? data.error
        : undefined
    throw new AdminError(
      error || fallbacks[response.status] || 'The admin service is unavailable. Please try again.',
      response.status,
      response.status === 429 ? retryAfter(response.headers.get('Retry-After')) : undefined,
    )
  }
  if (data === undefined) throw new AdminError('The admin service is unavailable.', 502)
  return object(data)
}

async function request<T>(path: string, parse: (value: unknown) => T): Promise<T> {
  let response: Response
  try {
    response = await fetch(path, { credentials: 'same-origin', cache: 'no-store' })
  } catch {
    throw new AdminError('Could not reach the admin service. Check your connection.', 0)
  }
  return parse(await responseData(response))
}

/** Canonical, bounded URL values; invalid values fall back to an unfiltered first page. */
export function adminQuery(query: LocationQuery): AdminUserQuery {
  const first = (value: LocationQuery[string] | undefined) => {
    const item = Array.isArray(value) ? value[0] : value
    return typeof item === 'string' ? item.trim() : ''
  }
  const page = first(query.page)
  const number = /^\d{1,4}$/.test(page) ? Number(page) : 1
  return {
    search: first(query.search).slice(0, ADMIN_SEARCH_MAX).trim(),
    page: number >= 1 && number <= ADMIN_PAGE_MAX ? number : 1,
  }
}

export function getAdminUsers(query: AdminUserQuery): Promise<AdminUserPage> {
  const parameters = new URLSearchParams({ search: query.search, page: String(query.page) })
  return request(`/api/admin/users?${parameters}`, adminUserPage)
}

export function getAdminStats(): Promise<AdminStats> {
  return request('/api/admin/stats', adminStats)
}

/** `password` is the signed-in admin's own current password, sent only in the JSON body. */
export async function deleteAdminUser(id: string, password: string): Promise<void> {
  if (!/^[1-9]\d{0,19}$/.test(id)) throw new AdminError('Unknown account.', 404)
  let response: Response
  try {
    response = await csrfRequest(`/api/admin/users/${id}`, { password }, responseData, 'DELETE')
  } catch (cause) {
    if (cause instanceof AdminError) throw cause
    if (cause instanceof TypeError)
      throw new AdminError('Could not reach the admin service. Check your connection.', 0)
    throw new AdminError('Refresh the page and try again.', 502)
  }
  const data = await responseData(response)
  if (data.deleted !== true) {
    throw new AdminError('Could not confirm the deletion. Refresh the list to check.', 502)
  }
}
