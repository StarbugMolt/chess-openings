import type { PlayingColor } from './lessons'

export interface PracticePosition {
  ply: number
  san: string
  hint: string
  practices: number
  recalled: number
  hints: number
  retries: number
}
export interface PracticeLine {
  lineId: string
  title: string
  playingColor: PlayingColor
  completions: number
  assessedCompletions: number
  unassistedCompletions: number
  lastPractisedAt: string | null
  lastAssessedAt: string | null
  lastOutcome: 'unknown' | 'assisted' | 'recalled'
  nextReviewAt: string | null
  reviewDue: boolean
  positions: PracticePosition[]
}
export interface PracticeCourse {
  id: string
  title: string
  revision: number
  recommendedColor: PlayingColor
  lineCount: number
  lines: PracticeLine[]
}
export interface ReviewIdentity {
  lessonId: string
  revision: number
  lineId: string
  playingColor: PlayingColor
}
export interface PracticeHistory extends ReviewIdentity {
  title: string
  lineTitle: string
  completions: number
  assessedCompletions: number
  unassistedCompletions: number
  lastPractisedAt: string
}
export interface PracticeOverview {
  generatedAt: string
  courses: PracticeCourse[]
  reviewQueue: ReviewIdentity[]
  history: PracticeHistory[]
}
export class PracticeError extends Error {
  constructor(
    message: string,
    readonly status: number,
  ) {
    super(message)
  }
}

function invalid(): never {
  throw new Error('Invalid practice response')
}
function object(value: unknown): Record<string, unknown> {
  if (!value || typeof value !== 'object' || Array.isArray(value)) invalid()
  return value as Record<string, unknown>
}
function list(value: unknown): unknown[] {
  if (!Array.isArray(value)) invalid()
  return value
}
function text(value: unknown): string {
  if (typeof value !== 'string' || !value.trim()) invalid()
  return value
}
function id(value: unknown): string {
  const result = text(value)
  if (!/^[a-z0-9][a-z0-9-]{0,47}$/.test(result)) invalid()
  return result
}
function integer(value: unknown, minimum = 0, maximum = Number.MAX_SAFE_INTEGER): number {
  if (
    typeof value !== 'number' ||
    !Number.isSafeInteger(value) ||
    value < minimum ||
    value > maximum
  )
    invalid()
  return value
}
function boolean(value: unknown): boolean {
  if (typeof value !== 'boolean') invalid()
  return value
}
function color(value: unknown): PlayingColor {
  if (value !== 'w' && value !== 'b') invalid()
  return value
}
function date(value: unknown): string {
  const result = text(value)
  if (!/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,3})?(?:Z|[+-]\d{2}:\d{2})$/.test(result))
    invalid()
  const milliseconds = Date.parse(result)
  // Date.parse silently normalizes impossible calendar dates such as February 30.
  const localDate = result.slice(0, 10)
  const calendar = new Date(`${localDate}T00:00:00Z`)
  if (!Number.isFinite(milliseconds) || calendar.toISOString().slice(0, 10) !== localDate) invalid()
  return result
}
function nullableDate(value: unknown): string | null {
  return value === null ? null : date(value)
}
function identity(value: unknown): ReviewIdentity {
  const row = object(value)
  return {
    lessonId: id(row.lessonId),
    revision: integer(row.revision, 1, 65535),
    lineId: id(row.lineId),
    playingColor: color(row.playingColor),
  }
}
function key(value: ReviewIdentity): string {
  return JSON.stringify([value.lessonId, value.revision, value.lineId, value.playingColor])
}
function counts(row: Record<string, unknown>) {
  const completions = integer(row.completions)
  const assessedCompletions = integer(row.assessedCompletions)
  const unassistedCompletions = integer(row.unassistedCompletions)
  if (assessedCompletions > completions || unassistedCompletions > assessedCompletions) invalid()
  return { completions, assessedCompletions, unassistedCompletions }
}
function line(value: unknown): PracticeLine {
  const row = object(value)
  const totals = counts(row)
  const lastPractisedAt = nullableDate(row.lastPractisedAt)
  const lastAssessedAt = nullableDate(row.lastAssessedAt)
  const nextReviewAt = nullableDate(row.nextReviewAt)
  const lastOutcome = row.lastOutcome
  const reviewDue = boolean(row.reviewDue)
  if (lastOutcome !== 'unknown' && lastOutcome !== 'assisted' && lastOutcome !== 'recalled')
    invalid()
  if (
    (totals.completions === 0) !== (lastPractisedAt === null) ||
    (totals.assessedCompletions === 0) !== (lastAssessedAt === null) ||
    (totals.assessedCompletions === 0) !== (lastOutcome === 'unknown') ||
    (lastOutcome === 'recalled' && totals.unassistedCompletions === 0) ||
    (!totals.completions && (reviewDue || nextReviewAt !== null)) ||
    (lastPractisedAt && lastAssessedAt && Date.parse(lastAssessedAt) > Date.parse(lastPractisedAt))
  )
    invalid()
  let previousPly = -1
  const positions = list(row.positions).map((value): PracticePosition => {
    const position = object(value)
    const ply = integer(position.ply, 0, 63)
    const practices = integer(position.practices)
    const recalled = integer(position.recalled)
    const hints = integer(position.hints)
    const retries = integer(position.retries)
    if (
      ply <= previousPly ||
      practices > totals.assessedCompletions ||
      recalled > practices ||
      hints > practices ||
      recalled + hints > practices ||
      retries > 63 * practices
    )
      invalid()
    previousPly = ply
    return {
      ply,
      san: text(position.san),
      hint: text(position.hint),
      practices,
      recalled,
      hints,
      retries,
    }
  })
  if (positions.length > 32) invalid()
  return {
    lineId: id(row.lineId),
    title: text(row.title),
    playingColor: color(row.playingColor),
    ...totals,
    lastPractisedAt,
    lastAssessedAt,
    lastOutcome,
    nextReviewAt,
    reviewDue,
    positions,
  }
}
function parseOverview(value: unknown): PracticeOverview {
  const payload = object(value)
  const courses = list(payload.courses).map((value): PracticeCourse => {
    const row = object(value)
    const lines = list(row.lines).map(line)
    const lineCount = integer(row.lineCount, 1)
    const identities = new Map<string, Set<PlayingColor>>()
    const titles = new Map<string, string>()
    for (const entry of lines) {
      const pieces = identities.get(entry.lineId) ?? new Set<PlayingColor>()
      if (
        pieces.has(entry.playingColor) ||
        (titles.has(entry.lineId) && titles.get(entry.lineId) !== entry.title)
      )
        invalid()
      pieces.add(entry.playingColor)
      identities.set(entry.lineId, pieces)
      titles.set(entry.lineId, entry.title)
    }
    if (
      identities.size !== lineCount ||
      lines.length !== lineCount * 2 ||
      [...identities.values()].some((pieces) => pieces.size !== 2)
    )
      invalid()
    return {
      id: id(row.id),
      title: text(row.title),
      revision: integer(row.revision, 1, 65535),
      recommendedColor: color(row.recommendedColor),
      lineCount,
      lines,
    }
  })
  if (new Set(courses.map((course) => course.id)).size !== courses.length) invalid()
  const reviewQueue = list(payload.reviewQueue).map(identity)
  if (reviewQueue.length > 20 || new Set(reviewQueue.map(key)).size !== reviewQueue.length)
    invalid()
  for (const review of reviewQueue) {
    const course = courses.find(
      (course) => course.id === review.lessonId && course.revision === review.revision,
    )
    const target = course?.lines.find(
      (line) => line.lineId === review.lineId && line.playingColor === review.playingColor,
    )
    if (!target?.reviewDue || !target.completions) invalid()
  }
  const history = list(payload.history).map((value): PracticeHistory => {
    const row = object(value)
    const reference = identity(row)
    const current = courses.find((course) => course.id === reference.lessonId)
    if (current && reference.revision >= current.revision) invalid()
    const totals = counts(row)
    if (!totals.completions) invalid()
    return {
      ...reference,
      title: text(row.title),
      lineTitle: text(row.lineTitle),
      ...totals,
      lastPractisedAt: date(row.lastPractisedAt),
    }
  })
  if (new Set(history.map(key)).size !== history.length) invalid()
  return { generatedAt: date(payload.generatedAt), courses, reviewQueue, history }
}

export async function getPractice(signal?: AbortSignal): Promise<PracticeOverview> {
  const response = await fetch('/api/me/practice', {
    credentials: 'same-origin',
    cache: 'no-store',
    ...(signal ? { signal } : {}),
  })
  if (!response.ok)
    throw new PracticeError(
      response.status === 401
        ? 'Your session has ended. Sign in to see your practice.'
        : 'Your practice could not be loaded. Please try again.',
      response.status,
    )
  return parseOverview(await response.json())
}
