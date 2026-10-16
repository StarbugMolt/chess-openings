import { validateFen } from 'chess.js'
import { csrfPost } from './http'
import type { PracticeAssessment } from '../domain/training/PracticeAssessment'

export type PlayingColor = 'w' | 'b'
export interface LessonStep {
  uci: string
  san: string
  fromKey: string
  toKey: string
  hint: string
  explanation: string
}
export interface LessonLine {
  id: string
  title: string
  description: string
  steps: LessonStep[]
}
export interface Lesson {
  id: string
  revision: number
  recommendedColor: PlayingColor
  title: string
  introduction: string
  startFen: string
  lines: LessonLine[]
}
export interface Course {
  id: string
  title: string
  introduction: string
  revision: number
  recommendedColor: PlayingColor
  lineCount: number
}
export interface Progress {
  lessonId: string
  revision: number
  lineId: string
  playingColor: PlayingColor
  completions: number
}
export interface LessonPayload {
  lesson: Lesson
  catalogueRevision: number
  progress: Progress[]
}
export interface Completion {
  attemptId: string
  lessonId: string
  revision: number
  lineId: string
  playingColor: PlayingColor
  hintCount: number
  path: string[]
  mode?: 'guided' | 'variation'
  selectedLineIds?: string[]
  assessment?: PracticeAssessment
}

function object(value: unknown): Record<string, unknown> {
  if (!value || typeof value !== 'object' || Array.isArray(value))
    throw new Error('Invalid lesson response')
  return value as Record<string, unknown>
}
function text(value: unknown): string {
  if (typeof value !== 'string') throw new Error('Invalid lesson text')
  return value
}
function integer(value: unknown): number {
  if (typeof value !== 'number' || !Number.isSafeInteger(value) || value < 1)
    throw new Error('Invalid lesson number')
  return value
}
function color(value: unknown): PlayingColor {
  if (value !== 'w' && value !== 'b') throw new Error('Invalid lesson color')
  return value
}
function fen(value: unknown, canonical = false): string {
  const result = text(value)
  if (
    (canonical && result.split(' ').length !== 4) ||
    !validateFen(canonical ? `${result} 0 1` : result).ok
  )
    throw new Error('Invalid lesson position')
  return result
}
export class LessonError extends Error {
  constructor(
    message: string,
    readonly status: number,
  ) {
    super(message)
  }
}
function list(value: unknown): unknown[] {
  if (!Array.isArray(value)) throw new Error('Invalid lesson list')
  return value as unknown[]
}
async function data(response: Response): Promise<Record<string, unknown>> {
  if (response.status === 401)
    throw new LessonError('Your session has ended. Sign in to continue.', 401)
  if (!response.ok)
    throw new LessonError(
      response.status === 404
        ? 'This lesson is not available.'
        : 'Could not save or load your lesson. Please try again.',
      response.status,
    )
  return object(await response.json())
}
export async function getCourses(): Promise<Course[]> {
  const payload = await data(
    await fetch('/api/courses', { credentials: 'omit', cache: 'no-cache' }),
  )
  return list(payload.courses).map((value) => {
    const row = object(value)
    return {
      id: text(row.id),
      title: text(row.title),
      introduction: text(row.introduction),
      revision: integer(row.revision),
      recommendedColor: color(row.recommendedColor),
      lineCount: integer(row.lineCount),
    }
  })
}
export async function getLesson(id: string, signal?: AbortSignal): Promise<LessonPayload> {
  const payload = await data(
    await fetch(`/api/lessons/${encodeURIComponent(id)}`, {
      credentials: 'same-origin',
      cache: 'no-store',
      ...(signal ? { signal } : {}),
    }),
  )
  const row = object(payload.lesson)
  const lesson: Lesson = {
    id: text(row.id),
    revision: integer(row.revision),
    recommendedColor: color(row.recommendedColor),
    title: text(row.title),
    introduction: text(row.introduction),
    startFen: fen(row.startFen),
    lines: list(row.lines).map((value) => {
      const line = object(value)
      const steps = list(line.steps).map((value): LessonStep => {
        const step = object(value)
        return {
          uci: text(step.uci),
          san: text(step.san),
          fromKey: fen(step.fromKey, true),
          toKey: fen(step.toKey, true),
          hint: text(step.hint),
          explanation: text(step.explanation),
        }
      })
      if (steps.length < 2 || steps.length > 64) throw new Error('Invalid lesson length')
      return {
        id: text(line.id),
        title: text(line.title),
        description: text(line.description),
        steps,
      }
    }),
  }
  if (!lesson.lines.length || lesson.revision < 1) throw new Error('Invalid lesson')
  const progress = list(payload.progress).map((value): Progress => {
    const row = object(value)
    if (row.playingColor !== 'w' && row.playingColor !== 'b')
      throw new Error('Invalid progress color')
    return {
      lessonId: text(row.lessonId),
      revision: integer(row.revision),
      lineId: text(row.lineId),
      playingColor: row.playingColor,
      completions: integer(row.completions),
    }
  })
  return { lesson, catalogueRevision: integer(payload.catalogueRevision), progress }
}
export async function saveCompletion(completion: Completion): Promise<void> {
  await csrfPost('/api/me/completions', completion, data)
}
