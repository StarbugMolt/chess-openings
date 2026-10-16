import { expect, test } from '@playwright/test'

// UI contracts use deterministic API responses; the PHP integration suite separately
// exercises real sessions, MariaDB, hashes, tokens and Mailpit without production data.
test('account forms complete registration, verification, sign-in and sign-out', async ({
  page,
}) => {
  let authenticated = false
  let verified = false
  const mutations: string[] = []
  const user = {
    id: '1',
    email: 'learner@example.test',
    verified: true,
    createdAt: '2026-10-15T10:00:00Z',
    admin: false,
  }
  await page.route('**/api/me', (route) =>
    route.fulfill({
      status: authenticated ? 200 : 401,
      json: authenticated ? { user } : { error: 'Sign in.' },
    }),
  )
  await page.route('**/api/auth/**', async (route) => {
    const action = new URL(route.request().url()).pathname.split('/').pop() ?? ''
    if (action === 'csrf') return route.fulfill({ json: { csrfToken: 'session-csrf' } })
    expect(route.request().headers()['x-csrf-token']).toBe('session-csrf')
    mutations.push(action)
    if (action === 'register')
      return route.fulfill({
        status: 202,
        json: { message: 'If this address is eligible, an email will arrive shortly.' },
      })
    if (action === 'verify-email') {
      expect(route.request().postDataJSON().token).toBe('a'.repeat(64))
      expect(route.request().postDataJSON()).toEqual({ token: 'a'.repeat(64) })
      verified = true
      return route.fulfill({ json: { message: 'Done. You can now sign in.' } })
    }
    if (action === 'login') {
      expect(route.request().postDataJSON().rememberMe).toBe(true)
      expect(verified).toBe(true)
      authenticated = true
      return route.fulfill({ json: { user } })
    }
    if (action === 'logout') {
      authenticated = false
      return route.fulfill({ json: { message: 'Signed out.' } })
    }
    throw new Error(`Unexpected account request ${action}`)
  })
  await page.goto('/account')
  await expect(page).toHaveURL(/\/login(?:\?|$)/)
  await page.getByRole('link', { name: 'Create an account', exact: true }).click()
  await page.getByLabel('Email address', { exact: true }).fill(user.email)
  await page.getByLabel('New password', { exact: true }).fill('a long test password')
  await page.getByRole('button', { name: 'Create account' }).click()
  await expect(page.getByRole('status')).toContainText('Check your email')
  await page.goto(`/verify-email#token=${'a'.repeat(64)}`)
  await expect(page).toHaveURL(/\/verify-email$/)
  expect(mutations).toEqual(['register']) // email preview/navigation cannot consume the token
  await expect(page.locator('input[type=password]')).toHaveCount(0)
  await page.getByRole('button', { name: 'Verify email' }).click()
  await expect(page.getByRole('status')).toContainText('now sign in')
  await page.locator('.account-links').getByRole('link', { name: 'Sign in', exact: true }).click()
  await page.getByLabel('Email address', { exact: true }).fill(user.email)
  await page.getByLabel('Password', { exact: true }).fill('a long test password')
  await page.getByRole('checkbox', { name: /Remember me for 30 days/ }).check()
  await page.getByRole('button', { name: 'Sign in', exact: true }).click()
  await expect(page).toHaveURL(/\/learn$/)
  await page.getByRole('link', { name: 'Account', exact: true }).click()
  await expect(page.getByRole('region', { name: 'Account details' })).toContainText(user.email)
  await page.reload()
  await expect(page.getByRole('region', { name: 'Account details' })).toContainText(user.email)
  expect(
    await page.evaluate(() =>
      Object.keys(localStorage).some((key) => /auth|token|password|email/i.test(key)),
    ),
  ).toBe(false)
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  await page.getByRole('button', { name: 'Sign out', exact: true }).click()
  await expect(page).toHaveURL(/\/$/)
  expect(mutations).toEqual(['register', 'verify-email', 'login', 'logout'])
})

test('password-reset links stay out of the address bar and errors are actionable', async ({
  page,
}) => {
  await page.route('**/api/me', (route) =>
    route.fulfill({ status: 401, json: { error: 'Sign in.' } }),
  )
  await page.route('**/api/auth/csrf', (route) => route.fulfill({ json: { csrfToken: 'csrf' } }))
  await page.route('**/api/auth/reset-password', async (route) => {
    expect(route.request().postDataJSON().token).toBe('b'.repeat(64))
    return route.fulfill({
      status: 400,
      json: { error: 'This link is invalid or expired. Request a new email.' },
    })
  })
  await page.goto(`/reset-password#token=${'b'.repeat(64)}`)
  await expect(page).toHaveURL(/\/reset-password$/)
  await page.getByLabel('New password', { exact: true }).fill('a replacement password')
  await page.getByRole('button', { name: 'Save new password' }).click()
  await expect(page.getByRole('alert')).toContainText('invalid or expired')
  await expect(page.getByLabel('New password', { exact: true })).toHaveValue('')
  await page.getByRole('link', { name: 'Reset password', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Reset your password.' })).toBeVisible()
  await page.locator('.site-footer').getByRole('link', { name: 'Privacy' }).click()
  await expect(page.getByRole('heading', { name: 'Account privacy.' })).toBeVisible()
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
})
