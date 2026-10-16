import { expect, test, type Page } from '@playwright/test'
import type { AdminStats, AdminUser } from '../../src/api/admin'

// Deterministic mocked API: the contract is docs/ADMIN_REVIEW.md §8. A separate live journey
// exercises the real Symfony endpoints, sessions and database.
const admin = {
  id: '1',
  email: 'owner@example.test',
  verified: true,
  createdAt: '2026-07-01T08:00:00+00:00',
  admin: true,
}
const learner = { ...admin, id: '30', email: 'learner@example.test', admin: false }

function row(id: number, email: string, extra: Partial<AdminUser> = {}): AdminUser {
  return {
    id: String(id),
    email,
    createdAt: '2026-10-01T09:00:00+00:00',
    verifiedAt: '2026-10-01T09:05:00+00:00',
    lastSignInAt: null,
    admin: false,
    completions: 0,
    lastPracticeAt: null,
    ...extra,
  }
}

function series(start: string, count: number, step: number): string[] {
  const first = Date.parse(`${start}T00:00:00Z`)
  return Array.from({ length: count }, (_, index) =>
    new Date(first + index * step * 86_400_000).toISOString().slice(0, 10),
  )
}
const stats: AdminStats = {
  generatedAt: '2026-10-16T12:00:00+00:00',
  accounts: {
    total: 120,
    verified: 110,
    unverified: 10,
    admins: 2,
    weekly: series('2026-07-27', 12, 7).map((weekStart, index) => ({
      weekStart,
      signups: index + 1,
      verifications: index,
    })),
  },
  signIns: { last1Day: 7, last7Days: 21, last30Days: 64, neverRecorded: 33 },
  practice: {
    total: 345,
    last7Days: 40,
    last30Days: 150,
    learners7Days: 12,
    learners30Days: 29,
    daily: series('2026-09-17', 30, 1).map((date, index) => ({
      date,
      completions: index === 29 ? 17 : index % 5,
    })),
    byCourse: [
      { lessonId: 'italian', title: 'Italian Game', completions: 200, learners: 25 },
      { lessonId: 'slav', title: 'Slav Defense', completions: 0, learners: 0 },
    ],
    topLines: [
      {
        lessonId: 'italian',
        lineId: 'giuoco-piano',
        courseTitle: 'Italian Game',
        lineTitle: 'Giuoco Piano main line',
        completions: 120,
      },
    ],
    byColor: { w: 230, b: 115 },
    assessed: 50,
    cleanAssessed: 20,
  },
}

interface MockOptions {
  me?: () => typeof admin
  rows?: AdminUser[]
  remove?: (id: string, password: string) => { status: number; json: unknown }
}
async function mockAdmin(page: Page, options: MockOptions = {}) {
  const rows = options.rows ?? [
    row(1, admin.email, { admin: true, lastSignInAt: '2026-10-16T08:00:00+00:00' }),
    ...Array.from({ length: 119 }, (_, index) =>
      row(index + 2, `${index % 2 ? 'learner' : 'student'}${index + 2}@example.test`),
    ),
  ]
  const requests: URL[] = []
  const deletions: { id: string; csrf: string | undefined; body: unknown }[] = []
  await page.route('**/api/me', (route) =>
    route.fulfill({ json: { user: (options.me ?? (() => admin))() } }),
  )
  await page.route('**/api/auth/csrf', (route) =>
    route.fulfill({ json: { csrfToken: 'admin-csrf' } }),
  )
  await page.route('**/api/admin/stats', (route) => route.fulfill({ json: stats }))
  await page.route(
    (url) => url.pathname === '/api/admin/users',
    (route) => {
      const url = new URL(route.request().url())
      requests.push(url)
      const search = url.searchParams.get('search') ?? ''
      const number = Number(url.searchParams.get('page') ?? '1')
      const matching = rows.filter((item) => item.email.includes(search))
      return route.fulfill({
        json: {
          items: matching.slice((number - 1) * 50, number * 50),
          page: number,
          limit: 50,
          total: matching.length,
          search,
        },
      })
    },
  )
  await page.route('**/api/admin/users/*', (route) => {
    const request = route.request()
    expect(request.method()).toBe('DELETE')
    const id = new URL(request.url()).pathname.split('/').pop()!
    const body = request.postDataJSON() as { password: string }
    deletions.push({ id, csrf: request.headers()['x-csrf-token'], body })
    const result = options.remove?.(id, body.password) ?? { status: 200, json: { deleted: true } }
    if (result.status === 200)
      rows.splice(
        rows.findIndex((item) => item.id === id),
        1,
      )
    return route.fulfill(result)
  })
  return { requests, deletions }
}

const noOverflow = (page: Page) =>
  page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)

test('the Admin link and page are only offered to admins', async ({ page }) => {
  let current = learner
  await mockAdmin(page, { me: () => current })
  const navigation = page.getByRole('navigation', { name: 'Main navigation' })
  await page.goto('/')
  await expect(navigation.getByRole('link', { name: 'Account', exact: true })).toBeVisible()
  await expect(navigation.getByRole('link', { name: 'Admin', exact: true })).toHaveCount(0)
  await page.goto('/admin')
  await expect(page).toHaveURL(/\/learn$/)

  current = admin
  await page.goto('/')
  const link = navigation.getByRole('link', { name: 'Admin', exact: true })
  await expect(link).toBeVisible()
  await link.click()
  await expect(page).toHaveURL(/\/admin$/)
  await expect(page).toHaveTitle('Admin · Chess Openings')
  await expect(page.getByRole('heading', { name: 'Admin.', exact: true })).toBeVisible()
  expect(await noOverflow(page)).toBe(true)
})

test('signed-out visitors are sent to sign in with the admin destination', async ({ page }) => {
  await page.route('**/api/me', (route) =>
    route.fulfill({ status: 401, json: { error: 'Sign in.' } }),
  )
  await page.goto('/admin?search=learner')
  await expect(page).toHaveURL(/\/login\?/)
  expect(new URL(page.url()).searchParams.get('next')).toBe('/admin?search=learner')
})

test('usage statistics show numeric values and explain what is not recorded', async ({ page }) => {
  const errors: string[] = []
  page.on('pageerror', (error) => errors.push(error.message))
  await mockAdmin(page)
  await page.goto('/admin')
  const usage = page.getByRole('region', { name: 'Usage', exact: true })
  await expect(usage).toContainText('page views')
  await expect(usage).toContainText('not recorded')
  const accounts = usage.getByRole('region', { name: 'Accounts', exact: true })
  await expect(accounts.getByRole('definition')).toHaveText(['120', '110', '10', '2'])
  const signIns = usage.getByRole('region', { name: 'Accounts signed in', exact: true })
  await expect(signIns.getByRole('definition')).toHaveText(['7', '21', '64', '33'])
  const practice = usage.getByRole('region', { name: 'Practice', exact: true })
  await expect(practice.getByRole('definition')).toHaveText(['345', '40', '150', '12', '29'])
  const weekly = usage.getByRole('region', { name: 'Sign-ups by week', exact: true })
  await expect(weekly.locator('tbody tr')).toHaveCount(12)
  await expect(weekly.locator('tbody tr').last()).toContainText('12')
  const daily = usage.getByRole('list', { name: 'Completions for each of the last 30 days' })
  await expect(daily.getByRole('listitem')).toHaveCount(30)
  await expect(daily.getByRole('listitem').last()).toContainText('17 completions')
  const courses = usage.getByRole('region', { name: 'Courses', exact: true })
  await expect(courses.locator('tbody tr')).toHaveText([
    /Italian Game\s*200\s*25/,
    /Slav Defense\s*0\s*0/,
  ])
  await expect(
    usage.getByRole('region', { name: 'Most practised lines', exact: true }),
  ).toContainText('Giuoco Piano main line')
  await expect(usage.getByRole('region', { name: 'Pieces played', exact: true })).toContainText(
    /White\s*230 \(67%\)\s*Black\s*115 \(33%\)/,
  )
  await expect(
    usage.getByRole('region', { name: 'Completed without help', exact: true }),
  ).toContainText(/40%\s*20 of 50 assessed completions used no hint, retry or takeback/)
  expect(await noOverflow(page)).toBe(true)
  expect(errors).toEqual([])
})

test('account search and paging are kept in the address', async ({ page }) => {
  const { requests } = await mockAdmin(page)
  await page.goto('/admin?page=1&search=')
  await expect(page).toHaveURL(/\/admin$/)
  const users = page.getByRole('region', { name: 'Users', exact: true })
  await expect(users).toContainText('120 accounts in total')
  await expect(users).toContainText('Page 1 of 3')
  await users.getByRole('button', { name: 'Next', exact: true }).click()
  await expect(page).toHaveURL(/\/admin\?page=2$/)
  await expect(users).toContainText('Page 2 of 3')
  await expect(users.getByRole('button', { name: 'Next', exact: true })).toBeFocused()

  await users.getByLabel('Email contains', { exact: true }).fill(' learner ')
  await users.getByRole('button', { name: 'Search accounts', exact: true }).click()
  await expect(page).toHaveURL(/\/admin\?search=learner$/)
  await expect(users).toContainText('59 accounts with an email containing “learner”')
  await expect(users).toContainText('Page 1 of 2')
  await page.goto('/admin?search=learner&page=2')
  await expect(users.getByLabel('Email contains', { exact: true })).toHaveValue('learner')
  await expect(users.locator('tbody > tr')).toHaveCount(9)
  await page.goto('/admin?search=nobody')
  await expect(users).toContainText('No accounts match this search.')
  expect(requests.map((url) => url.search)).toEqual(
    expect.arrayContaining(['?search=&page=2', '?search=learner&page=1', '?search=learner&page=2']),
  )
  expect(await noOverflow(page)).toBe(true)
})

test('deletion requires the admin password and confirmation and explains refusals', async ({
  page,
}, info) => {
  const rows = [
    row(1, admin.email, { admin: true }),
    row(2, 'second-admin@example.test', { admin: true }),
    row(30, 'target@example.test', { completions: 3, lastPracticeAt: '2026-10-15T18:00:00+00:00' }),
    row(31, 'promoted@example.test'),
  ]
  const { deletions } = await mockAdmin(page, {
    rows,
    remove: (id, password) =>
      id === '31'
        ? { status: 409, json: { error: 'This account cannot be deleted here.' } }
        : password === 'admin password'
          ? { status: 200, json: { deleted: true } }
          : { status: 400, json: { error: 'Your password is incorrect.' } },
  })
  if (info.project.name === 'mobile') await page.setViewportSize({ width: 390, height: 844 })
  await page.goto('/admin#users')
  const users = page.getByRole('region', { name: 'Users', exact: true })
  await expect(users).toContainText('4 accounts in total')

  const own = users.getByRole('button', { name: `Delete ${admin.email}…` })
  await expect(own).toBeDisabled()
  await expect(own).toHaveAccessibleDescription(/This is your account/)
  const other = users.getByRole('button', { name: 'Delete second-admin@example.test…' })
  await expect(other).toBeDisabled()
  await expect(other).toHaveAccessibleDescription(/Admin accounts cannot be deleted here/)

  const trigger = users.getByRole('button', { name: 'Delete target@example.test…' })
  await trigger.click()
  const passwordField = users.getByLabel('Your current admin password', { exact: true })
  await expect(passwordField).toBeFocused()
  await users.getByRole('button', { name: 'Keep this account', exact: true }).click()
  await expect(trigger).toBeFocused()
  await expect(passwordField).toHaveCount(0)

  await trigger.click()
  const form = users.getByRole('form', { name: 'Delete target@example.test' })
  await expect(form).toContainText('cannot be undone')
  const submit = form.getByRole('button', { name: 'Permanently delete this account' })
  await expect(submit).toBeDisabled()
  await passwordField.fill('wrong password')
  await form.getByRole('checkbox').check()
  expect(await noOverflow(page)).toBe(true)
  await submit.click()
  await expect(form.getByRole('alert')).toHaveText('Your password is incorrect.')
  await expect(passwordField).toHaveValue('')
  await expect(passwordField).toBeFocused()

  await passwordField.fill('admin password')
  await submit.click()
  await expect(users.getByRole('status')).toHaveText('Deleted the account target@example.test.')
  await expect(users.getByRole('heading', { name: 'Users', exact: true })).toBeFocused()
  await expect(users).toContainText('3 accounts in total')
  await expect(users.getByText('target@example.test', { exact: true })).toHaveCount(0)

  const promoted = users.getByRole('button', { name: 'Delete promoted@example.test…' })
  await promoted.click()
  await passwordField.fill('admin password')
  await users.getByRole('checkbox').check()
  await users.getByRole('button', { name: 'Permanently delete this account' }).click()
  const refusal = users.getByRole('alert')
  await expect(refusal).toContainText('The server refused this deletion')
  await expect(refusal).toBeFocused()
  await expect(promoted).toBeDisabled()
  await expect(promoted).toHaveAccessibleDescription(/server refused/)
  await expect(passwordField).toHaveCount(0)

  expect(deletions).toEqual([
    { id: '30', csrf: 'admin-csrf', body: { password: 'wrong password' } },
    { id: '30', csrf: 'admin-csrf', body: { password: 'admin password' } },
    { id: '31', csrf: 'admin-csrf', body: { password: 'admin password' } },
  ])
  const navigation = page.getByRole('navigation', { name: 'Main navigation' })
  await expect(navigation.getByRole('link', { name: 'Admin', exact: true })).toBeVisible()
  expect(await noOverflow(page)).toBe(true)
})

test('an expired session during deletion offers sign-in again', async ({ page }) => {
  let signedIn = true
  await mockAdmin(page, {
    rows: [row(1, admin.email, { admin: true }), row(40, 'expired@example.test')],
    remove: () => {
      signedIn = false
      return { status: 401, json: { error: 'Sign in.' } }
    },
  })
  await page.route('**/api/me', (route) =>
    route.fulfill(
      signedIn ? { json: { user: admin } } : { status: 401, json: { error: 'Sign in.' } },
    ),
  )
  await page.goto('/admin')
  const users = page.getByRole('region', { name: 'Users', exact: true })
  await users.getByRole('button', { name: 'Delete expired@example.test…' }).click()
  await users.getByLabel('Your current admin password', { exact: true }).fill('admin password')
  await users.getByRole('checkbox').check()
  await users.getByRole('button', { name: 'Permanently delete this account' }).click()
  await expect(users.getByRole('alert')).toContainText('Your session has ended')
  await expect(page.getByRole('navigation', { name: 'Main navigation' })).not.toContainText('Admin')
  await users.getByRole('link', { name: 'Sign in again', exact: true }).click()
  await expect(page).toHaveURL(/\/login\?/)
  expect(new URL(page.url()).searchParams.get('next')).toBe('/admin')
})

test('language switches translate admin controls without resetting an open confirmation', async ({
  page,
}) => {
  await mockAdmin(page, { rows: [row(30, learner.email)] })
  await page.goto('/admin')
  await page.getByRole('button', { name: `Delete ${learner.email}…`, exact: true }).click()
  await page.getByLabel('Your current admin password', { exact: true }).fill('PendingPassword!2026')

  for (const choice of [
    {
      language: 'Français',
      code: 'fr',
      title: 'Administration',
      password: 'Votre mot de passe administrateur actuel',
      remove: 'Supprimer',
      created: 'Création',
    },
    {
      language: 'Español',
      code: 'es',
      title: 'Administración',
      password: 'Tu contraseña actual de administrador',
      remove: 'Eliminar',
      created: 'Creación',
    },
  ]) {
    await page.locator('.language-trigger').click()
    await page
      .locator('#language-options')
      .getByRole('button', { name: choice.language, exact: true })
      .click()
    await expect(page.locator('html')).toHaveAttribute('lang', choice.code)
    await expect(page).toHaveTitle(`${choice.title} · Chess Openings`)
    await expect(page.getByRole('heading', { name: `${choice.title}.`, exact: true })).toBeVisible()
    await expect(
      page.getByRole('form', { name: `${choice.remove} ${learner.email}`, exact: true }),
    ).toBeVisible()
    await expect(page.getByLabel(choice.password, { exact: true })).toHaveValue(
      'PendingPassword!2026',
    )
    await expect(
      page.locator('.users-table tbody tr').first().locator('td').first(),
    ).toHaveAttribute('data-label', choice.created)
    expect(await noOverflow(page)).toBe(true)
  }
})

test('chart columns are one tab stop and arrow keys reveal each value', async ({ page }) => {
  await mockAdmin(page)
  await page.goto('/admin')
  const daily = page.getByRole('list', { name: 'Completions for each of the last 30 days' })
  const days = daily.getByRole('listitem')
  await expect(days).toHaveCount(30)
  await expect(days.last()).toHaveAttribute('tabindex', '0')
  await expect(days.first()).toHaveAttribute('tabindex', '-1')
  await days.last().focus()
  await expect(days.last().locator('.tip')).toBeVisible()
  await page.keyboard.press('ArrowLeft')
  await expect(days.nth(28)).toBeFocused()
  await expect(days.nth(28).locator('.tip')).toContainText('3 completions')
  await page.keyboard.press('Home')
  await expect(days.first()).toBeFocused()
  await expect(days.first()).toHaveAttribute('tabindex', '0')
  await expect(page.getByRole('link', { name: 'Overview', exact: true })).toHaveAttribute(
    'aria-current',
    'location',
  )
  expect(await noOverflow(page)).toBe(true)
})

test('weekly charts scale both sign-ups and later email verifications', async ({ page }) => {
  await mockAdmin(page)
  await page.route('**/api/admin/stats', (route) =>
    route.fulfill({
      json: {
        ...stats,
        accounts: {
          ...stats.accounts,
          weekly: stats.accounts.weekly.map((week, index) => ({
            ...week,
            signups: 1,
            verifications: index === 11 ? 40 : 0,
          })),
        },
      },
    }),
  )
  await page.goto('/admin')
  const growth = page.getByRole('region', { name: 'Sign-ups by week' })
  await expect(growth.locator('.gridlines span')).toHaveText(['50', '25', '0'])
  await expect(growth.locator('.column-verifications').last()).toHaveCSS('--size', '80%')
  await expect(growth.locator('.column-signups').last()).toHaveCSS('--size', '2%')
})
