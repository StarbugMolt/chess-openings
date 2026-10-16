import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { accountAction, accountUser, deleteAccount, exportAccountData } from '../../src/api/account'
import { useAccountStore } from '../../src/stores/account'

const user = {
  id: '7',
  email: 'learner@example.test',
  verified: true,
  createdAt: '2026-10-15T10:00:00Z',
}
const response = (body: unknown, status = 200) => new Response(JSON.stringify(body), { status })

describe('private account requests', () => {
  beforeEach(() => setActivePinia(createPinia()))
  afterEach(() => vi.unstubAllGlobals())

  it('loads only the current session user and clears it after a 401 or outage', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response({ user }))
      .mockResolvedValueOnce(response({ error: 'Sign in' }, 401))
      .mockRejectedValueOnce(new Error('Offline'))
    vi.stubGlobal('fetch', fetch)
    const store = useAccountStore()
    await store.load()
    expect(store.user).toEqual(user)
    await store.load(true)
    expect(store.user).toBeNull()
    expect(store.unavailable).toBe(false)
    await store.load(true)
    expect(store.user).toBeNull()
    expect(store.unavailable).toBe(true)
    expect(
      fetch.mock.calls.every(
        (call) =>
          call[0] === '/api/me' &&
          call[1].cache === 'no-store' &&
          call[1].credentials === 'same-origin',
      ),
    ).toBe(true)
  })

  it('acquires session CSRF before login and logout without persisting credentials', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response({ csrfToken: 'before-login' }))
      .mockResolvedValueOnce(response({ user }))
      .mockResolvedValueOnce(response({ csrfToken: 'after-login' }))
      .mockResolvedValueOnce(response({ message: 'Signed out.' }))
    vi.stubGlobal('fetch', fetch)
    const store = useAccountStore()
    await store.login(user.email, 'a long test password', true)
    expect(store.user).toEqual(user)
    await store.logout()
    expect(store.user).toBeNull()
    expect(fetch.mock.calls[1]?.[1].headers['X-CSRF-Token']).toBe('before-login')
    expect(JSON.parse(fetch.mock.calls[1]?.[1].body).rememberMe).toBe(true)
    expect(fetch.mock.calls[3]?.[1].headers['X-CSRF-Token']).toBe('after-login')
    expect(Object.keys(store.$state)).toEqual(['user', 'unavailable', 'loaded'])
  })

  it('does not let a delayed anonymous read overwrite a completed login', async () => {
    let resolveRead!: (value: Response) => void
    const fetch = vi
      .fn()
      .mockImplementationOnce(
        () =>
          new Promise<Response>((resolve) => {
            resolveRead = resolve
          }),
      )
      .mockResolvedValueOnce(response({ csrfToken: 'csrf' }))
      .mockResolvedValueOnce(response({ user }))
    vi.stubGlobal('fetch', fetch)
    const store = useAccountStore()
    const pending = store.load()
    await store.login(user.email, 'a long test password')
    resolveRead(response({ error: 'Sign in' }, 401))
    await pending
    expect(store.user).toEqual(user)
  })
  it('shares the startup request between the application and route guard', async () => {
    const fetch = vi.fn().mockResolvedValue(response({ user }))
    vi.stubGlobal('fetch', fetch)
    const store = useAccountStore()
    await Promise.all([store.load(true), store.load()])
    await store.load()
    expect(fetch).toHaveBeenCalledTimes(1)
    expect(store.loaded).toBe(true)
  })

  it('stops before mutation if CSRF acquisition fails and preserves API errors', async () => {
    const fetch = vi.fn().mockResolvedValueOnce(response({ error: 'Unavailable' }, 503))
    vi.stubGlobal('fetch', fetch)
    await expect(accountAction('register', {})).rejects.toThrow('Unavailable')
    expect(fetch).toHaveBeenCalledTimes(1)
    fetch
      .mockResolvedValueOnce(response({ csrfToken: 'csrf' }))
      .mockResolvedValueOnce(response({ error: 'Too many requests.' }, 429))
    await expect(accountAction('request-reset', { email: user.email })).rejects.toMatchObject({
      status: 429,
    })
  })

  it('rejects malformed users before they can become account state', () => {
    expect(() => accountUser({ ...user, id: 7 })).toThrow('unexpected user')
    expect(() => accountUser({ ...user, verified: 'yes' })).toThrow('unexpected user')
  })

  it('downloads JSON only after a fresh session CSRF request and puts the password in the body', async () => {
    const file = { formatVersion: 1, account: { email: user.email }, completions: [] }
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response({ csrfToken: 'fresh-csrf' }))
      .mockResolvedValueOnce(
        new Response(JSON.stringify(file), { headers: { 'Content-Type': 'application/json' } }),
      )
    vi.stubGlobal('fetch', fetch)
    const blob = await exportAccountData(' current password ')
    expect(JSON.parse(await blob.text())).toEqual(file)
    expect(fetch.mock.calls[1]?.[0]).toBe('/api/me/data-export')
    expect(fetch.mock.calls[1]?.[1]).toMatchObject({
      method: 'POST',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'X-CSRF-Token': 'fresh-csrf' },
      body: JSON.stringify({ password: ' current password ' }),
    })
  })

  it('does not download an error or a successful HTML response', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response({ csrfToken: 'csrf' }))
      .mockResolvedValueOnce(response({ error: 'Check your current password.' }, 400))
      .mockResolvedValueOnce(response({ csrfToken: 'csrf' }))
      .mockResolvedValueOnce(new Response('<html>Unavailable</html>'))
    vi.stubGlobal('fetch', fetch)
    await expect(exportAccountData('wrong')).rejects.toMatchObject({ status: 400 })
    await expect(exportAccountData('correct')).rejects.toThrow('download is unavailable')
  })

  it('keeps the signed-in account after failed deletion and exposes the confirmation retry time', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(response({ user }))
      .mockResolvedValueOnce(response({ csrfToken: 'csrf' }))
      .mockResolvedValueOnce(
        new Response(JSON.stringify({ error: 'Too many requests.' }), {
          status: 429,
          headers: { 'Retry-After': '71' },
        }),
      )
    vi.stubGlobal('fetch', fetch)
    const store = useAccountStore()
    await store.load()
    await expect(store.deleteAccount('wrong')).rejects.toMatchObject({
      status: 429,
      retryAfter: 71,
    })
    expect(store.user).toEqual(user)
    expect(fetch.mock.calls[2]?.[0]).toBe('/api/me')
    expect(fetch.mock.calls[2]?.[1].method).toBe('DELETE')
  })

  it('does not accept a successful response that failed to confirm deletion', async () => {
    vi.stubGlobal(
      'fetch',
      vi
        .fn()
        .mockResolvedValueOnce(response({ csrfToken: 'csrf' }))
        .mockResolvedValueOnce(response({})),
    )
    await expect(deleteAccount('password')).rejects.toThrow('Could not confirm account deletion')
  })

  it('clears account state only after confirmed deletion and ignores an older pending account read', async () => {
    let resolveRead!: (response: Response) => void
    const fetch = vi
      .fn()
      .mockImplementationOnce(
        () =>
          new Promise<Response>((resolve) => {
            resolveRead = resolve
          }),
      )
      .mockResolvedValueOnce(response({ csrfToken: 'csrf' }))
      .mockResolvedValueOnce(response({ deleted: true }))
    vi.stubGlobal('fetch', fetch)
    const store = useAccountStore()
    const earlier = store.load()
    await store.deleteAccount('password')
    resolveRead(response({ user }))
    await earlier
    expect(store.user).toBeNull()
    expect(store.loaded).toBe(true)
    expect(store.unavailable).toBe(false)
  })
})
