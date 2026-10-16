import { afterEach, describe, expect, it, vi } from 'vitest'
import { getLesson, LessonError } from '../../src/api/lessons'
import lessons from '../fixtures/lessons.json'

const valid = () => ({
  lesson: structuredClone(lessons[0]),
  catalogueRevision: 1,
  progress: [] as unknown[],
})
afterEach(() => vi.unstubAllGlobals())
describe('lesson response boundaries', () => {
  it('accepts a complete typed immutable lesson snapshot', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(Response.json(valid())))
    expect((await getLesson('italian')).lesson.recommendedColor).toBe('w')
  })
  it.each(['revision', 'completions'])('rejects non-integer progress %s', async (field) => {
    const payload = valid()
    payload.progress = [
      {
        lessonId: 'italian',
        lineId: 'centre-plan',
        playingColor: 'w',
        revision: 1,
        completions: 1,
        [field]: '2',
      },
    ]
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(Response.json(payload)))
    await expect(getLesson('italian')).rejects.toThrow('Invalid lesson number')
  })
  it('rejects an invalid starting position before mounting a board', async () => {
    const payload = valid()
    payload.lesson!.startFen = 'broken'
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(Response.json(payload)))
    await expect(getLesson('italian')).rejects.toThrow('Invalid lesson position')
  })
  it('distinguishes a missing lesson from account authentication errors', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status: 404 })))
    await expect(getLesson('missing')).rejects.toBeInstanceOf(LessonError)
  })
})
