import { afterEach, describe, expect, it, vi } from 'vitest'
import { getPrivacyNotice, privacyNotice, type PrivacyNotice } from '../../src/api/privacy'

const valid: PrivacyNotice = {
  version: 1,
  publisher: 'StarbugStone',
  contactEmail: 'chessopenings@starbugstone.com',
  emailProvider: 'O2Switch',
  hostingProvider: 'o2switch',
  hostingBackupDays: 30,
  deploymentBackupDays: 30,
  applicationLogDays: 14,
  unverifiedAccountDays: 0,
  development: false,
  configured: true,
  missing: [],
}

afterEach(() => vi.unstubAllGlobals())
describe('public privacy configuration', () => {
  it('whitelists public fields and requires coherent missing details', () => {
    expect(privacyNotice({ ...valid, secret: 'never returned' })).toEqual(valid)
    expect(
      privacyNotice({ ...valid, emailProvider: '', configured: false, missing: ['emailProvider'] })
        .configured,
    ).toBe(false)
    for (const patch of [
      { configured: false },
      { missing: ['publisher'] },
      { emailProvider: '' },
      { emailProvider: 'smtp://private:password@host' },
      { contactEmail: 'not an email' },
      { hostingBackupDays: -1 },
      { applicationLogDays: 1.5 },
      { deploymentBackupDays: Infinity },
      { unverifiedAccountDays: -1 },
      { hostingProvider: 'unknown' },
    ])
      expect(() => privacyNotice({ ...valid, ...patch })).toThrow()
  })

  it('loads without sending cookies and supports cancellation', async () => {
    const fetcher = vi.fn().mockResolvedValue({ ok: true, json: async () => valid })
    vi.stubGlobal('fetch', fetcher)
    const signal = new AbortController().signal
    await expect(getPrivacyNotice(signal)).resolves.toEqual(valid)
    expect(fetcher).toHaveBeenCalledWith('/api/privacy', {
      credentials: 'omit',
      cache: 'no-store',
      signal,
    })
  })

  it('rejects outages and malformed server data', async () => {
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false }))
    await expect(getPrivacyNotice()).rejects.toThrow('temporarily unavailable')
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: true, json: async () => ({}) }))
    await expect(getPrivacyNotice()).rejects.toThrow('Invalid privacy notice')
  })
})
