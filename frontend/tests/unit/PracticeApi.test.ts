import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  getPractice,
  PracticeError,
  type PracticeLine,
  type PracticeOverview,
} from '../../src/api/practice'

const earlier = '2026-10-14T09:00:00+00:00'
const recent = '2026-10-15T09:00:00Z'
function newLine(lineId: string, playingColor: 'w' | 'b'): PracticeLine {
  return {
    lineId,
    title: lineId === 'exchange' ? 'Exchange setup' : 'Panov development',
    playingColor,
    completions: 0,
    assessedCompletions: 0,
    unassistedCompletions: 0,
    lastPractisedAt: null,
    lastAssessedAt: null,
    lastOutcome: 'unknown',
    nextReviewAt: null,
    reviewDue: false,
    positions: [],
  }
}
function valid(): PracticeOverview {
  const legacy = {
    ...newLine('exchange', 'b'),
    completions: 2,
    lastPractisedAt: earlier,
    reviewDue: true,
  }
  const measured: PracticeLine = {
    ...newLine('panov', 'b'),
    completions: 3,
    assessedCompletions: 2,
    unassistedCompletions: 1,
    lastPractisedAt: recent,
    lastAssessedAt: recent,
    lastOutcome: 'assisted',
    nextReviewAt: '2026-10-15T09:10:00Z',
    reviewDue: true,
    positions: [
      {
        ply: 1,
        san: 'c6',
        hint: 'Move your pawn from c7 to c6.',
        practices: 2,
        recalled: 1,
        hints: 1,
        retries: 1,
      },
      {
        ply: 3,
        san: 'd5',
        hint: 'Challenge the centre with d5.',
        practices: 2,
        recalled: 2,
        hints: 0,
        retries: 0,
      },
    ],
  }
  return {
    generatedAt: '2026-10-15T10:00:00+00:00',
    courses: [
      {
        id: 'caro-kann',
        title: 'Caro-Kann Defense',
        revision: 2,
        recommendedColor: 'b',
        lineCount: 2,
        lines: [newLine('exchange', 'w'), legacy, newLine('panov', 'w'), measured],
      },
    ],
    reviewQueue: [
      { lessonId: 'caro-kann', revision: 2, lineId: 'panov', playingColor: 'b' },
      { lessonId: 'caro-kann', revision: 2, lineId: 'exchange', playingColor: 'b' },
    ],
    history: [
      {
        lessonId: 'caro-kann',
        title: 'Caro-Kann Defense',
        revision: 1,
        lineId: 'exchange',
        lineTitle: 'Earlier Exchange setup',
        playingColor: 'b',
        completions: 1,
        assessedCompletions: 0,
        unassistedCompletions: 0,
        lastPractisedAt: earlier,
      },
    ],
  }
}
function serve(payload: unknown) {
  const fetch = vi.fn().mockImplementation(async () => Response.json(payload))
  vi.stubGlobal('fetch', fetch)
  return fetch
}
const measured = (payload: PracticeOverview) => payload.courses[0]!.lines[3]!
afterEach(() => vi.unstubAllGlobals())

describe('private practice response boundaries', () => {
  it('accepts empty, legacy and measured facts without inventing recall or changing server queue order', async () => {
    const payload = valid()
    serve(payload)
    const result = await getPractice()
    expect(result).toEqual(payload)
    expect(result.courses[0]!.lines[0]!.completions).toBe(0)
    expect(result.courses[0]!.lines[1]!.lastOutcome).toBe('unknown')
    expect(result.reviewQueue.map((entry) => entry.lineId)).toEqual(['panov', 'exchange'])
  })
  it('allows an overview with no published courses or recorded practice', async () => {
    serve({ generatedAt: recent, courses: [], reviewQueue: [], history: [] })
    expect((await getPractice()).courses).toEqual([])
  })
  it('uses private same-origin cookies, no-store and the caller abort signal', async () => {
    const fetch = serve(valid())
    const controller = new AbortController()
    await getPractice(controller.signal)
    expect(fetch).toHaveBeenCalledWith('/api/me/practice', {
      credentials: 'same-origin',
      cache: 'no-store',
      signal: controller.signal,
    })
  })
  it('respects server reviewDue rather than deriving review status from the browser clock', async () => {
    const payload = valid()
    measured(payload).nextReviewAt = '2099-01-01T00:00:00Z'
    serve(payload)
    expect((await getPractice()).courses[0]!.lines[3]!.reviewDue).toBe(true)
  })
  it.each([401, 403, 503])(
    'distinguishes HTTP %s from malformed successful data',
    async (status) => {
      vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response(null, { status })))
      await expect(getPractice()).rejects.toMatchObject({ status })
      await expect(getPractice()).rejects.toBeInstanceOf(PracticeError)
    },
  )
  it('propagates abort/network failures without replacing them with signed-out state', async () => {
    const failure = new DOMException('Request aborted', 'AbortError')
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(failure))
    await expect(getPractice()).rejects.toBe(failure)
  })

  const corruptions: [string, (payload: PracticeOverview) => void][] = [
    [
      'missing colour entry',
      (p) => {
        p.courses[0]!.lines.pop()
      },
    ],
    [
      'duplicate colour entry',
      (p) => {
        p.courses[0]!.lines[2] = p.courses[0]!.lines[0]!
      },
    ],
    [
      'wrong denominator',
      (p) => {
        p.courses[0]!.lineCount = 3
      },
    ],
    [
      'duplicate course identity',
      (p) => {
        p.courses.push(p.courses[0]!)
      },
    ],
    [
      'inconsistent line titles across colours',
      (p) => {
        p.courses[0]!.lines[1]!.title = 'Different line'
      },
    ],
    [
      'negative counter',
      (p) => {
        measured(p).completions = -1
      },
    ],
    [
      'fractional counter',
      (p) => {
        measured(p).completions = 1.5
      },
    ],
    [
      'unsafe counter',
      (p) => {
        measured(p).completions = Number.MAX_SAFE_INTEGER + 1
      },
    ],
    [
      'assessed total greater than completion total',
      (p) => {
        measured(p).assessedCompletions = 4
      },
    ],
    [
      'unassisted total greater than assessed total',
      (p) => {
        measured(p).unassistedCompletions = 3
      },
    ],
    [
      'missing practice date',
      (p) => {
        measured(p).lastPractisedAt = null
      },
    ],
    [
      'missing assessment date',
      (p) => {
        measured(p).lastAssessedAt = null
      },
    ],
    [
      'assessment newer than practice',
      (p) => {
        measured(p).lastAssessedAt = '2026-10-16T09:00:00Z'
      },
    ],
    [
      'unknown measured outcome',
      (p) => {
        measured(p).lastOutcome = 'unknown'
      },
    ],
    [
      'recall without an unassisted practice',
      (p) => {
        measured(p).lastOutcome = 'recalled'
        measured(p).unassistedCompletions = 0
      },
    ],
    [
      'date without timezone',
      (p) => {
        p.generatedAt = '2026-10-15T10:00:00'
      },
    ],
    [
      'impossible calendar date',
      (p) => {
        p.generatedAt = '2026-02-30T10:00:00Z'
      },
    ],
    [
      'new line marked review due',
      (p) => {
        p.courses[0]!.lines[0]!.reviewDue = true
      },
    ],
    [
      'new line with practice date',
      (p) => {
        p.courses[0]!.lines[0]!.lastPractisedAt = recent
      },
    ],
    [
      'repeated decision ply',
      (p) => {
        measured(p).positions[1]!.ply = 1
      },
    ],
    [
      'unordered decisions',
      (p) => {
        measured(p).positions.reverse()
      },
    ],
    [
      'decision outside finite path cap',
      (p) => {
        measured(p).positions[0]!.ply = 64
      },
    ],
    [
      'position practice greater than assessed total',
      (p) => {
        measured(p).positions[0]!.practices = 3
      },
    ],
    [
      'recalled more than practised',
      (p) => {
        measured(p).positions[0]!.recalled = 3
      },
    ],
    [
      'hints more than practised',
      (p) => {
        measured(p).positions[0]!.hints = 3
      },
    ],
    [
      'hinted position also credited as recalled',
      (p) => {
        measured(p).positions[0]!.recalled = 2
      },
    ],
    [
      'unbounded position retries',
      (p) => {
        measured(p).positions[0]!.retries = 127
      },
    ],
    [
      'review of another course',
      (p) => {
        p.reviewQueue[0]!.lessonId = 'london'
      },
    ],
    [
      'review of another revision',
      (p) => {
        p.reviewQueue[0]!.revision = 1
      },
    ],
    [
      'review of another line',
      (p) => {
        p.reviewQueue[0]!.lineId = 'missing'
      },
    ],
    [
      'review of an unpractised colour',
      (p) => {
        p.reviewQueue[0]!.playingColor = 'w'
      },
    ],
    [
      'duplicate queued identity',
      (p) => {
        p.reviewQueue.push(p.reviewQueue[0]!)
      },
    ],
    [
      'more than 20 queued identities',
      (p) => {
        p.reviewQueue = Array.from({ length: 21 }, () => p.reviewQueue[0]!)
      },
    ],
    [
      'current revision in earlier history',
      (p) => {
        p.history[0]!.revision = 2
      },
    ],
    [
      'future revision in earlier history',
      (p) => {
        p.history[0]!.revision = 3
      },
    ],
    [
      'duplicate retained history identity',
      (p) => {
        p.history.push(p.history[0]!)
      },
    ],
    [
      'earlier history without practice',
      (p) => {
        p.history[0]!.completions = 0
      },
    ],
  ]
  it.each(corruptions)(
    'rejects %s before showing misleading private progress',
    async (_, corrupt) => {
      const payload = valid()
      corrupt(payload)
      serve(payload)
      await expect(getPractice()).rejects.toThrow('Invalid practice response')
    },
  )
  it.each([
    null,
    [],
    { ...valid(), courses: 'all' },
    { ...valid(), history: {} },
    { ...valid(), courses: [{ ...valid().courses[0], recommendedColor: 'black' }] },
    { ...valid(), courses: [{ ...valid().courses[0], revision: '2' }] },
    {
      ...valid(),
      courses: [
        {
          ...valid().courses[0],
          lines: valid().courses[0]!.lines.map((line) => ({ ...line, completions: '2' })),
        },
      ],
    },
    {
      ...valid(),
      courses: [
        {
          ...valid().courses[0],
          lines: valid().courses[0]!.lines.map((line) => ({ ...line, reviewDue: 'false' })),
        },
      ],
    },
  ])('rejects malformed response shapes and untyped values', async (payload) => {
    serve(payload)
    await expect(getPractice()).rejects.toThrow('Invalid practice response')
  })
})
