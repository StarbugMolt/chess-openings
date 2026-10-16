import { expect, test, type APIRequestContext, type Browser } from '@playwright/test'

const adminEmail = process.env.E2E_ADMIN_EMAIL ?? ''
const adminPassword = process.env.E2E_ADMIN_PASSWORD ?? ''

async function csrf(request: APIRequestContext): Promise<string> {
  return ((await (await request.get('/api/auth/csrf')).json()) as { csrfToken: string }).csrfToken
}

/** Registers, verifies through Mailpit and signs in a disposable learner in its own browser context. */
async function signedInLearner(browser: Browser, baseURL: string, project: string) {
  const context = await browser.newContext({ baseURL })
  const email = `admin-live-${Date.now()}-${project}@example.test`
  const password = 'AdminLiveLearner!2026'
  const request = context.request
  const registered = await request.post('/api/auth/register', {
    data: { email, password },
    headers: { 'X-CSRF-Token': await csrf(request) },
  })
  expect(registered.status()).toBe(202)
  let messageId: string | undefined
  await expect
    .poll(
      async () => {
        const mailbox = (await (
          await request.get('http://mailpit:8025/api/v1/messages')
        ).json()) as { messages: { ID: string; To: { Address: string }[] }[] }
        messageId = mailbox.messages.find((message) =>
          message.To.some((to) => to.Address === email),
        )?.ID
        return messageId
      },
      { timeout: 10000 },
    )
    .toBeTruthy()
  const mail = (await (
    await request.get(`http://mailpit:8025/api/v1/message/${messageId}`)
  ).json()) as { Text: string }
  const token = mail.Text.match(/#token=([a-f0-9]{64})/)?.[1]
  expect(token).toBeTruthy()
  const verified = await request.post('/api/auth/verify-email', {
    data: { token },
    headers: { 'X-CSRF-Token': await csrf(request) },
  })
  expect(verified.status()).toBe(200)
  const login = await request.post('/api/auth/login', {
    data: { email, password },
    headers: { 'X-CSRF-Token': await csrf(request) },
  })
  expect(login.status()).toBe(200)
  return { context, email }
}

test('an administrator sees real usage, finds a learner and deletes the account', async ({
  page,
  browser,
  baseURL,
}, info) => {
  test.setTimeout(90000)
  test.skip(
    process.env.LIVE_LESSON_TEST !== '1' || !adminEmail || !adminPassword,
    'Use tests/browser/run.sh, which seeds a disposable administrator.',
  )
  const errors: string[] = []
  page.on('pageerror', (error) => errors.push(error.message))
  const learner = await signedInLearner(browser, baseURL!, info.project.name)

  // A signed-in learner is not an administrator.
  const learnerPage = await learner.context.newPage()
  await learnerPage.goto('/admin')
  await expect(learnerPage).toHaveURL(/^http:\/\/[^/]+\/learn$/)
  expect((await learner.context.request.get('/api/admin/stats')).status()).toBe(403)
  await expect(
    learnerPage
      .getByRole('navigation', { name: 'Main navigation' })
      .getByRole('link', { name: 'Admin', exact: true }),
  ).toHaveCount(0)

  await page.goto('/login?next=/admin')
  await page.getByLabel('Email address', { exact: true }).fill(adminEmail)
  await page.getByLabel('Password', { exact: true }).fill(adminPassword)
  await page.getByRole('button', { name: 'Sign in', exact: true }).click()
  await expect(page).toHaveURL(/^http:\/\/[^/]+\/admin$/)
  await expect(page.getByRole('heading', { name: 'Admin.', exact: true })).toBeVisible()
  await expect(
    page
      .getByRole('navigation', { name: 'Main navigation' })
      .getByRole('link', { name: 'Admin', exact: true }),
  ).toBeVisible()

  // Real aggregates: at least the seeded admin and this learner exist; one admin is recorded.
  const accounts = page
    .getByRole('region', { name: 'Usage', exact: true })
    .getByRole('region', { name: 'Accounts', exact: true })
  await expect(accounts.getByRole('definition')).toHaveCount(4)
  const [total, , , admins] = await accounts.getByRole('definition').allInnerTexts()
  expect(Number(total)).toBeGreaterThanOrEqual(2)
  expect(Number(admins)).toBeGreaterThanOrEqual(1)

  const users = page.getByRole('region', { name: 'Users', exact: true })
  await users.getByLabel('Email contains', { exact: true }).fill(learner.email)
  await users.getByRole('button', { name: 'Search accounts', exact: true }).click()
  await expect(page).toHaveURL(/search=/)
  await expect(users.getByText(learner.email, { exact: true })).toBeVisible()

  await users.getByRole('button', { name: `Delete ${learner.email}…` }).click()
  await users.getByLabel('Your current admin password', { exact: true }).fill(adminPassword)
  const form = users.getByRole('form', { name: `Delete ${learner.email}` })
  await form.getByRole('checkbox').check()
  await form.getByRole('button', { name: 'Permanently delete this account' }).click()
  await expect(users.getByRole('status')).toHaveText(`Deleted the account ${learner.email}.`)
  await expect(users.getByText(learner.email, { exact: true })).toHaveCount(0)

  // The deleted learner's existing session can no longer use the account.
  expect((await learner.context.request.get('/api/me')).status()).toBe(401)
  await learner.context.close()
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  expect(errors).toEqual([])
})
