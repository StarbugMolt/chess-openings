import { afterEach, describe, expect, it, vi } from 'vitest'
import {
  AdminError,
  adminQuery,
  adminStats,
  adminUser,
  adminUserPage,
  deleteAdminUser,
  getAdminStats,
  getAdminUsers,
  type AdminStats,
} from '../../src/api/admin'

const response = (body: unknown, status = 200, headers: Record<string, string> = {}) =>
  new Response(JSON.stringify(body), { status, headers })

const row = {
  id: '12',
  email: 'learner@example.test',
  createdAt: '2026-10-01T09:00:00+00:00',
  verifiedAt: '2026-10-01T09:05:00+00:00',
  lastSignInAt: null,
  admin: false,
  completions: 3,
  lastPracticeAt: '2026-10-15T18:00:00+00:00',
}
const page = { items: [row], page: 1, limit: 50, total: 1, search: '' }

function series(start: string, count: number, step: number): string[] {
  const first = Date.parse(`${start}T00:00:00Z`)
  return Array.from({ length: count }, (_, index) =>
    new Date(first + index * step * 86_400_000).toISOString().slice(0, 10),
  )
}
function stats(): AdminStats {
  return {
    generatedAt: '2026-10-16T12:00:00+00:00',
    accounts: {
      total: 4,
      verified: 3,
      unverified: 1,
      admins: 1,
      weekly: series('2026-07-27', 12, 7).map((weekStart, index) => ({
        weekStart,
        signups: index === 11 ? 2 : 0,
        verifications: index === 11 ? 1 : 0,
      })),
    },
    signIns: { last1Day: 1, last7Days: 2, last30Days: 3, neverRecorded: 1 },
    practice: {
      total: 9,
      last7Days: 4,
      last30Days: 9,
      learners7Days: 2,
      learners30Days: 3,
      daily: series('2026-09-17', 30, 1).map((date, index) => ({
        date,
        completions: index === 29 ? 4 : 0,
      })),
      byCourse: [
        { lessonId: 'italian', title: 'Italian Game', completions: 9, learners: 3 },
        { lessonId: 'london', title: 'London System', completions: 0, learners: 0 },
      ],
      topLines: [
        {
          lessonId: 'italian',
          lineId: 'giuoco-piano',
          courseTitle: 'Italian Game',
          lineTitle: 'Giuoco Piano',
          completions: 9,
        },
      ],
      byColor: { w: 6, b: 3 },
      assessed: 5,
      cleanAssessed: 2,
    },
  }
}

describe('admin response parsers', () => {
  it('accepts the frozen user-page contract and rejects inconsistent rows', () => {
    expect(adminUserPage(page)).toEqual(page)
    expect(adminUser({ ...row, completions: 0, lastPracticeAt: null }).completions).toBe(0)
    expect(adminUserPage({ ...page, items: [], page: 3 }).items).toEqual([])
    for (const invalid of [
      { ...row, id: 12 },
      { ...row, id: '012' },
      { ...row, admin: 'false' },
      { ...row, email: '' },
      { ...row, createdAt: '2026-02-30T09:00:00+00:00' },
      { ...row, lastSignInAt: '' },
      { ...row, completions: -1 },
      { ...row, completions: 0 },
      { ...row, lastPracticeAt: null },
    ])
      expect(() => adminUser(invalid)).toThrow(AdminError)
    expect(() => adminUserPage({ ...page, items: [row, row], total: 2 })).toThrow(AdminError)
    expect(() => adminUserPage({ ...page, total: 0 })).toThrow(AdminError)
    expect(() => adminUserPage({ ...page, page: 0 })).toThrow(AdminError)
    expect(() => adminUserPage({ ...page, search: null })).toThrow(AdminError)
    expect(() => adminUserPage({ ...page, items: {} })).toThrow(AdminError)
  })

  it('accepts complete statistics and rejects malformed series or counts', () => {
    expect(adminStats(stats())).toEqual(stats())
    const broken: ((value: AdminStats) => void)[] = [
      (value) => value.accounts.weekly.pop(),
      (value) => value.practice.daily.shift(),
      (value) => (value.accounts.weekly[0]!.weekStart = '2026-07-28'),
      (value) => value.practice.daily.reverse(),
      (value) => (value.practice.daily[3]!.date = '2026-09-31'),
      (value) => (value.accounts.verified = 5),
      (value) => (value.signIns.last7Days = -1),
      (value) => (value.practice.cleanAssessed = 6),
      (value) => (value.practice.byCourse[0]!.learners = 10),
      (value) => (value.practice.byCourse[1]!.lessonId = 'italian'),
      (value) => (value.practice.topLines[0]!.lineId = 'Bad Id'),
      (value) =>
        (value.practice.topLines = Array.from({ length: 11 }, () => stats().practice.topLines[0]!)),
      (value) => value.practice.topLines.push({ ...value.practice.topLines[0]!, completions: 10 }),
      (value) => ((value.practice.byColor as unknown as Record<string, unknown>).w = '6'),
      (value) => ((value as unknown as Record<string, unknown>).generatedAt = null),
    ]
    for (const mutate of broken) {
      const value = stats()
      mutate(value)
      expect(() => adminStats(value)).toThrow(AdminError)
    }
  })

  it('canonicalizes bounded search and page query values', () => {
    expect(adminQuery({})).toEqual({ search: '', page: 1 })
    expect(adminQuery({ search: ['  ex@  ', 'other'], page: '3' })).toEqual({
      search: 'ex@',
      page: 3,
    })
    expect(adminQuery({ search: 'a'.repeat(300), page: '10000' })).toEqual({
      search: 'a'.repeat(254),
      page: 1,
    })
    expect(adminQuery({ page: '-2' }).page).toBe(1)
    expect(adminQuery({ page: '2.5' }).page).toBe(1)
  })
})

describe('admin requests', () => {
  afterEach(() => vi.unstubAllGlobals())

  it('reads private pages and statistics without caching', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response(page))
      .mockResolvedValueOnce(response(stats()))
    vi.stubGlobal('fetch', fetch)
    await expect(getAdminUsers({ search: '50% _off@', page: 2 })).resolves.toEqual(page)
    await expect(getAdminStats()).resolves.toEqual(stats())
    expect(fetch.mock.calls[0]?.[0]).toBe('/api/admin/users?search=50%25+_off%40&page=2')
    expect(fetch.mock.calls[1]?.[0]).toBe('/api/admin/stats')
    for (const call of fetch.mock.calls)
      expect(call[1]).toMatchObject({ credentials: 'same-origin', cache: 'no-store' })
  })

  it('maps every documented failure status to a typed error', async () => {
    const cases: [number, unknown, Record<string, string>, Partial<AdminError>][] = [
      [401, { error: 'Sign in.' }, {}, { status: 401, message: 'Sign in.' }],
      [403, { error: 'Forbidden.' }, {}, { status: 403 }],
      [429, { error: 'Too many.' }, { 'Retry-After': '61' }, { status: 429, retryAfter: 61 }],
      [503, { error: 'Unavailable.' }, {}, { status: 503, message: 'Unavailable.' }],
      [500, 'not an object', {}, { status: 500 }],
    ]
    for (const [status, body, headers, expected] of cases) {
      vi.stubGlobal('fetch', vi.fn().mockResolvedValue(response(body, status, headers)))
      const error = await getAdminStats().catch((cause: unknown) => cause)
      expect(error).toBeInstanceOf(AdminError)
      expect(error).toMatchObject(expected)
      expect((error as AdminError).message).not.toBe('')
    }
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(new Response('<html>', { status: 502 })))
    await expect(getAdminStats()).rejects.toMatchObject({ status: 502 })
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue(response({ ...stats(), accounts: null })))
    await expect(getAdminStats()).rejects.toMatchObject({ status: 502 })
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new TypeError('Failed to fetch')))
    await expect(getAdminUsers({ search: '', page: 1 })).rejects.toMatchObject({ status: 0 })
  })

  it('deletes with a fresh session CSRF token and the admin password only in the body', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response({ csrfToken: 'fresh-csrf' }))
      .mockResolvedValueOnce(response({ deleted: true }))
    vi.stubGlobal('fetch', fetch)
    await deleteAdminUser('12', 'admin password')
    expect(fetch.mock.calls[0]?.[0]).toBe('/api/auth/csrf')
    const [path, init] = fetch.mock.calls[1]!
    expect(path).toBe('/api/admin/users/12')
    expect(init).toMatchObject({ method: 'DELETE', credentials: 'same-origin', cache: 'no-store' })
    expect(init.headers['X-CSRF-Token']).toBe('fresh-csrf')
    expect(JSON.parse(init.body)).toEqual({ password: 'admin password' })
    expect(String(path)).not.toContain('password')
  })

  it('surfaces deletion refusals with their status and never sends a malformed id', async () => {
    for (const [status, error] of [
      [400, 'Your password is incorrect.'],
      [404, 'Not found.'],
      [409, 'Refused.'],
    ] as const) {
      vi.stubGlobal(
        'fetch',
        vi
          .fn()
          .mockResolvedValueOnce(response({ csrfToken: 'csrf' }))
          .mockResolvedValueOnce(response({ error }, status)),
      )
      await expect(deleteAdminUser('12', 'pw')).rejects.toMatchObject({ status, message: error })
    }
    vi.stubGlobal(
      'fetch',
      vi
        .fn()
        .mockResolvedValueOnce(response({ csrfToken: 'csrf' }))
        .mockResolvedValueOnce(response({ deleted: false })),
    )
    await expect(deleteAdminUser('12', 'pw')).rejects.toMatchObject({ status: 502 })
    const fetch = vi.fn().mockResolvedValue(response({ error: 'Unavailable.' }, 503))
    vi.stubGlobal('fetch', fetch)
    await expect(deleteAdminUser('12', 'pw')).rejects.toMatchObject({ status: 503 })
    expect(fetch).toHaveBeenCalledTimes(1)
    await expect(deleteAdminUser('../me', 'pw')).rejects.toMatchObject({ status: 404 })
    expect(fetch).toHaveBeenCalledTimes(1)
  })
})
