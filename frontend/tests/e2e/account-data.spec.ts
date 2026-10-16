import { readFile } from 'node:fs/promises'
import { expect, test } from '@playwright/test'
import type { LessonPayload } from '../../src/api/lessons'

test('account data controls explain confirmation failures and recover an expired session', async ({
  page,
}) => {
  let authenticated = true
  let expired = false
  const user = {
    id: '7',
    email: 'data@example.test',
    verified: true,
    createdAt: '2026-10-15T08:00:00Z',
    admin: false,
  }
  await page.route('**/api/me', (route) =>
    route.fulfill({
      status: authenticated ? 200 : 401,
      json: authenticated ? { user } : { error: 'Sign in.' },
    }),
  )
  await page.route('**/api/auth/csrf', (route) =>
    route.fulfill({ json: { csrfToken: 'private-csrf' } }),
  )
  await page.route('**/api/me/data-export', (route) => {
    expect(route.request().method()).toBe('POST')
    expect(route.request().headers()['x-csrf-token']).toBe('private-csrf')
    if (expired) {
      authenticated = false
      return route.fulfill({ status: 401, json: { error: 'Sign in.' } })
    }
    return route.fulfill({
      status: 429,
      headers: { 'Retry-After': '61' },
      json: { error: 'Too many requests.' },
    })
  })
  await page.goto('/account')
  const download = page.getByRole('region', { name: 'Download your data', exact: true })
  await download.getByLabel('Current password for download').fill('private password')
  await download.getByRole('button', { name: 'Download my data', exact: true }).click()
  await expect(download.getByRole('alert')).toContainText('61 seconds')
  await expect(download.getByLabel('Current password for download')).toHaveValue('')
  await expect(page).toHaveURL(/\/account$/)
  expired = true
  await download.getByLabel('Current password for download').fill('private password')
  await download.getByRole('button', { name: 'Download my data', exact: true }).click()
  await expect(page.getByText('Your session has ended.', { exact: false })).toBeVisible()
  await page.getByRole('link', { name: 'Sign in again', exact: true }).click()
  await expect(page).toHaveURL(/\/login\?next=\/account$/)
  expect(
    await page.evaluate(() =>
      Object.values(localStorage).some((value) => value.includes('private password')),
    ),
  ).toBe(false)
})

test('a learner downloads owned practice, cancels deletion, then deletes with a password and loses both sessions', async ({
  page,
  browser,
}, info) => {
  test.skip(process.env.LIVE_LESSON_TEST !== '1', 'Use the isolated database browser runner.')
  test.setTimeout(60000)
  const errors: string[] = []
  page.on('pageerror', (error) => errors.push(error.message))
  const email = `data-${Date.now()}-${info.project.name}@example.test`
  const password = 'PrivateAccount!2026'
  const csrf = async () =>
    ((await (await page.request.get('/api/auth/csrf')).json()) as { csrfToken: string }).csrfToken
  const register = await page.request.post('/api/auth/register', {
    data: { email, password },
    headers: { 'X-CSRF-Token': await csrf() },
  })
  expect(register.status()).toBe(202)
  let messageId: string | undefined
  await expect
    .poll(async () => {
      const mailbox = (await (
        await page.request.get('http://mailpit:8025/api/v1/messages')
      ).json()) as { messages: { ID: string; To: { Address: string }[] }[] }
      messageId = mailbox.messages.find((message) =>
        message.To.some((to) => to.Address === email),
      )?.ID
      return messageId
    })
    .toBeTruthy()
  const mail = (await (
    await page.request.get(`http://mailpit:8025/api/v1/message/${messageId}`)
  ).json()) as { Text: string }
  const token = mail.Text.match(/#token=([a-f0-9]{64})/)?.[1]
  expect(token).toBeTruthy()
  expect(
    (
      await page.request.post('/api/auth/verify-email', {
        data: { token },
        headers: { 'X-CSRF-Token': await csrf() },
      })
    ).status(),
  ).toBe(200)
  await page.goto('/login?next=/account')
  await page.getByLabel('Email address', { exact: true }).fill(email)
  await page.getByLabel('Password', { exact: true }).fill(password)
  await page.getByRole('checkbox', { name: /Remember me for 30 days/ }).check()
  await page.getByRole('button', { name: 'Sign in', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Your account.', exact: true })).toBeVisible()
  const graph = (await (await page.request.get('/api/lessons/italian')).json()) as LessonPayload
  const line = graph.lesson.lines[0]!
  const assessment = {
    version: 1,
    usedHint: false,
    usedRetry: false,
    usedUndo: false,
    decisions: line.steps.flatMap((step, ply) =>
      step.fromKey.split(' ')[1] === 'w' ? [{ ply, attempts: 1, hinted: false }] : [],
    ),
  }
  expect(
    (
      await page.request.post('/api/me/completions', {
        headers: { 'X-CSRF-Token': await csrf() },
        data: {
          lessonId: 'italian',
          revision: graph.lesson.revision,
          lineId: line.id,
          playingColor: 'w',
          attemptId: 'f0000000-0000-4000-8000-000000000001',
          path: line.steps.map((step) => step.uci),
          hintCount: 0,
          assessment,
        },
      })
    ).status(),
  ).toBe(200)
  const other = await browser.newContext({ baseURL: new URL(page.url()).origin })
  try {
    const otherCsrf = (await (await other.request.get('/api/auth/csrf')).json()) as {
      csrfToken: string
    }
    expect(
      (
        await other.request.post('/api/auth/login', {
          headers: { 'X-CSRF-Token': otherCsrf.csrfToken },
          data: { email, password, rememberMe: true },
        })
      ).status(),
    ).toBe(200)
    const download = page.getByRole('region', { name: 'Download your data', exact: true })
    await download.getByLabel('Current password for download').fill('wrong password')
    await download.getByRole('button', { name: 'Download my data', exact: true }).click()
    await expect(download.getByRole('alert')).toContainText('current password')
    await expect(download.getByLabel('Current password for download')).toHaveValue('')
    await download.getByLabel('Current password for download').fill(password)
    const ready = page.waitForEvent('download')
    await download.getByRole('button', { name: 'Download my data', exact: true }).click()
    const file = await ready
    expect(file.suggestedFilename()).toBe('chess-openings-data.json')
    const path = await file.path()
    expect(path).toBeTruthy()
    const contents = await readFile(path!, 'utf8')
    const exported = JSON.parse(contents) as {
      account: { email: string }
      completions: { lessonId: string; assessment: unknown }[]
    }
    expect(exported.account.email).toBe(email)
    expect(exported.completions).toHaveLength(1)
    expect(exported.completions[0]?.lessonId).toBe('italian')
    expect(exported.completions[0]?.assessment).toEqual(assessment)
    expect(contents).not.toContain(password)
    expect(contents).not.toContain('token_hash')
    await expect(download.getByRole('status')).toContainText('download is ready')
    const deletion = page.getByRole('region', { name: 'Delete your account', exact: true })
    await deletion.getByRole('button', { name: 'Delete my account…', exact: true }).click()
    await expect(deletion.getByLabel('Current password for deletion')).toBeFocused()
    await deletion.getByLabel('Current password for deletion').fill(password)
    await deletion.getByRole('button', { name: 'Keep my account', exact: true }).click()
    await expect(
      deletion.getByRole('button', { name: 'Delete my account…', exact: true }),
    ).toBeFocused()
    expect((await other.request.get('/api/me')).status()).toBe(200)
    await deletion.getByRole('button', { name: 'Delete my account…', exact: true }).click()
    await expect(
      deletion.getByRole('button', { name: 'Permanently delete my account', exact: true }),
    ).toBeDisabled()
    await deletion.getByRole('checkbox').check()
    await deletion.getByLabel('Current password for deletion').fill('wrong password')
    await deletion
      .getByRole('button', { name: 'Permanently delete my account', exact: true })
      .click()
    await expect(deletion.getByRole('alert')).toContainText('current password')
    await expect(deletion.getByLabel('Current password for deletion')).toHaveValue('')
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
    await deletion.getByLabel('Current password for deletion').fill(password)
    await deletion
      .getByRole('button', { name: 'Permanently delete my account', exact: true })
      .click()
    await expect(page.getByRole('heading', { name: 'Account deleted.', exact: true })).toBeFocused()
    await expect(
      page.getByText('Your account and saved practice have been removed. You are signed out.'),
    ).toBeVisible()
    expect((await page.request.get('/api/me')).status()).toBe(401)
    expect((await other.request.get('/api/me/practice')).status()).toBe(401)
    expect(
      (await page.context().cookies()).some((cookie) => cookie.name === 'CHESS_REMEMBER'),
    ).toBe(false)
    expect(
      await page.evaluate(() =>
        Object.keys(localStorage).some((key) => /auth|token|password|email/i.test(key)),
      ),
    ).toBe(false)
    await page.reload()
    await expect(page).toHaveURL(/\/login\?next=\/account$/)
    expect(errors).toEqual([])
  } finally {
    await other.close()
  }
})
