import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useCatalogueStore } from '../../src/stores/catalogue'

const query = { search: 'Italian', eco: '', page: 1 }
const status = (revision: number) => ({
  catalogueRevision: revision,
  sourceSha: 'a'.repeat(40),
  updatedAt: '2026-10-15T08:00:00.000000Z',
  entryCount: 1,
})
const page = (revision: number, name = 'Italian Game') => ({
  catalogueRevision: revision,
  items: [{ id: '1', sourceId: 'a'.repeat(64), eco: 'C50', name }],
  page: 1,
  limit: 20,
  total: 1,
})
const response = (payload: unknown, etag: string) =>
  new Response(JSON.stringify(payload), { headers: { ETag: etag } })

describe('catalogue revision boundaries', () => {
  beforeEach(() => setActivePinia(createPinia()))
  afterEach(() => vi.unstubAllGlobals())

  it('uses conditional requests and clears page caches on the next navigation revision', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response(status(1), '"status-1"'))
      .mockResolvedValueOnce(response(page(1), '"page-1"'))
      .mockResolvedValueOnce(new Response(null, { status: 304 }))
      .mockResolvedValueOnce(new Response(null, { status: 304 }))
      .mockResolvedValueOnce(response(status(2), '"status-2"'))
      .mockResolvedValueOnce(response(page(2, 'Italian Game: new source name'), '"page-2"'))
    vi.stubGlobal('fetch', fetch)
    const store = useCatalogueStore()
    await store.open(query)
    expect(store.page?.items[0]?.name).toBe('Italian Game')
    await store.open(query)
    expect(fetch.mock.calls[2]?.[1].headers['If-None-Match']).toBe('"status-1"')
    expect(fetch.mock.calls[3]?.[1].headers['If-None-Match']).toBe('"page-1"')
    expect(store.status?.catalogueRevision).toBe(1)
    // No watchers/timers fetch or update an already displayed catalogue.
    await Promise.resolve()
    expect(fetch).toHaveBeenCalledTimes(4)
    await store.open(query)
    expect(fetch.mock.calls[5]?.[1].headers['If-None-Match']).toBeUndefined()
    expect(store.status?.catalogueRevision).toBe(2)
    expect(store.page?.items[0]?.name).toBe('Italian Game: new source name')
    expect(fetch.mock.calls.every((call) => call[1].credentials === 'omit')).toBe(true)
  })

  it('captures a new session revision without mutating an earlier session snapshot', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response(status(1), '"s1"'))
      .mockResolvedValueOnce(response(status(2), '"s2"'))
    vi.stubGlobal('fetch', fetch)
    const store = useCatalogueStore()
    const firstSession = await store.forNewSession()
    const nextSession = await store.forNewSession()
    expect(firstSession.catalogueRevision).toBe(1)
    expect(nextSession.catalogueRevision).toBe(2)
    expect(Object.isFrozen(firstSession)).toBe(true)
    expect(store.status?.catalogueRevision).toBe(2)
  })

  it('retries a status/page pair if activation happens between requests', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response(status(1), '"s1"'))
      .mockResolvedValueOnce(response(page(2), '"p2"'))
      .mockResolvedValueOnce(response(status(2), '"s2"'))
      .mockResolvedValueOnce(response(page(2), '"p2"'))
    vi.stubGlobal('fetch', fetch)
    const store = useCatalogueStore()
    await store.open(query)
    expect(store.error).toBe('')
    expect(store.status?.catalogueRevision).toBe(2)
    expect(store.page?.catalogueRevision).toBe(2)
    expect(fetch).toHaveBeenCalledTimes(4)
  })

  it('rejects a late response from an older navigation', async () => {
    let finishOld: (value: Response) => void = () => undefined
    const fetch = vi
      .fn()
      .mockImplementationOnce(() => new Promise<Response>((resolve) => (finishOld = resolve)))
      .mockResolvedValueOnce(response(status(2), '"s2"'))
      .mockResolvedValueOnce(response(page(2), '"p2"'))
    vi.stubGlobal('fetch', fetch)
    const store = useCatalogueStore()
    const old = store.open(query)
    await store.open({ ...query, search: 'Italian Game' })
    finishOld(response(status(1), '"s1"'))
    await old
    expect(store.status?.catalogueRevision).toBe(2)
    expect(store.page?.catalogueRevision).toBe(2)
    expect(store.loading).toBe(false)
  })
  it('captures a session without aborting the visible page request or accepting a stale status', async () => {
    let finishSession!: (value: Response) => void
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response(status(1), '"s1"'))
      .mockResolvedValueOnce(response(page(1), '"p1"'))
      .mockImplementationOnce(
        () =>
          new Promise<Response>((resolve) => {
            finishSession = resolve
          }),
      )
      .mockResolvedValueOnce(response(status(2), '"s2"'))
      .mockResolvedValueOnce(response(page(2), '"p2"'))
    vi.stubGlobal('fetch', fetch)
    const store = useCatalogueStore()
    await store.open(query)
    const session = store.forNewSession()
    await store.open({ ...query, search: 'London' })
    finishSession(response(status(1), '"old"'))
    expect((await session).catalogueRevision).toBe(1)
    expect(store.status?.catalogueRevision).toBe(2)
    expect(store.page?.catalogueRevision).toBe(2)
    expect(store.loading).toBe(false)
  })

  it('shows a recoverable error for unavailable or malformed responses', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(new Response('', { status: 503 }))
      .mockResolvedValueOnce(response({ catalogueRevision: 'bad' }, '"bad"'))
    vi.stubGlobal('fetch', fetch)
    const store = useCatalogueStore()
    await store.open(query)
    expect(store.error).toContain('Please try again')
    await store.open(query)
    expect(store.error).toContain('Please try again')
    expect(store.page).toBeUndefined()
    expect(store.loading).toBe(false)
  })
})
