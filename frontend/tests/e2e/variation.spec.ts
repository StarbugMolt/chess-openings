import { expect, test, type Page, type TestInfo } from '@playwright/test'
import type { Completion, Lesson, LessonLine, LessonPayload } from '../../src/api/lessons'

async function createLearner(page: Page, info: TestInfo) {
  const email = `variation-${Date.now()}-${info.project.name}@example.test`
  const password = 'VariationPractice!2026'
  const csrf = async () =>
    ((await (await page.request.get('/api/auth/csrf')).json()) as { csrfToken: string }).csrfToken
  expect(
    (
      await page.request.post('/api/auth/register', {
        data: { email, password },
        headers: { 'X-CSRF-Token': await csrf() },
      })
    ).status(),
  ).toBe(202)
  let messageId: string | undefined
  await expect
    .poll(
      async () => {
        const mailbox = (await (
          await page.request.get('http://mailpit:8025/api/v1/messages')
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
  await page.goto('/login?next=/train/caro-kann')
  await page.getByLabel('Email address', { exact: true }).fill(email)
  await page.getByLabel('Password', { exact: true }).fill(password)
  await page.getByRole('button', { name: 'Sign in', exact: true }).click()
  await expect(page).toHaveURL(/^http:\/\/[^/]+\/train\/caro-kann$/)
}

async function square(page: Page, key: string, info: TestInfo, black = false) {
  const surface = page.locator('cg-board')
  await surface.scrollIntoViewIfNeeded()
  const bounds = await surface.boundingBox()
  if (!bounds) throw new Error('Training board missing')
  const file = key.charCodeAt(0) - 97
  const rank = Number(key[1]) - 1
  const x = (bounds.width * ((black ? 7 - file : file) + 0.5)) / 8
  const y = (bounds.height * ((black ? rank : 7 - rank) + 0.5)) / 8
  if (info.project.name === 'mobile') await page.touchscreen.tap(bounds.x + x, bounds.y + y)
  else await surface.click({ position: { x, y } })
}

async function history(page: Page): Promise<string[]> {
  return page.getByRole('list', { name: 'Training move history' }).locator('li').allTextContents()
}

async function playScope(
  page: Page,
  info: TestInfo,
  lesson: Lesson,
  lines: LessonLine[],
  black: boolean,
  prefer?: string,
) {
  const end = page.getByRole('heading', { name: 'Practice complete.', exact: true })
  const hint = page.getByRole('button', { name: 'Hint', exact: true })
  await page.getByText('Moves played (chess notation)', { exact: true }).click()
  await page.getByText('Enter a move with the keyboard', { exact: true }).click()
  let usedCoordinates = false
  for (let decision = 0; decision < 33; decision++) {
    await expect
      .poll(
        async () =>
          (await end.isVisible()) ||
          (await hint.evaluateAll((buttons) =>
            buttons.some((button) => !(button as HTMLButtonElement).disabled),
          )),
      )
      .toBe(true)
    if (await end.isVisible()) break
    const played = await history(page)
    const candidates = lines.filter(
      (line) =>
        played.every((san, index) => line.steps[index]?.san === san) &&
        line.steps.length > played.length,
    )
    const next = (candidates.find((line) => line.id === prefer) ?? candidates[0])?.steps[
      played.length
    ]
    if (!next) throw new Error(`No supported response after ${played.join(' ')}`)
    if (!usedCoordinates) {
      await page.getByLabel('From square', { exact: true }).fill(next.uci.slice(0, 2))
      await page.getByLabel('To square', { exact: true }).fill(next.uci.slice(2, 4))
      await page.getByRole('button', { name: 'Play move', exact: true }).click()
      usedCoordinates = true
    } else {
      await hint.click()
      await expect(page.locator('square.hint-origin:visible')).toHaveCount(1)
      await expect(page.getByRole('note', { name: 'Move hint' })).toContainText('Why:')
      await square(page, next.uci.slice(0, 2), info, black)
      const suggested = candidates[0]?.steps[played.length]
      await expect(page.locator('square.hint-destination:visible')).toHaveCount(
        suggested?.uci.slice(0, 2) === next.uci.slice(0, 2) ? 1 : 0,
      )
      await square(page, next.uci.slice(2, 4), info, black)
    }
    // Chessground defers its move callback. Wait for the authoritative history
    // before checking whose turn it is, otherwise the old Hint state can win.
    await expect
      .poll(() => page.getByRole('list', { name: 'Training move history' }).locator('li').count())
      .toBeGreaterThan(played.length)
  }
  await expect(end).toBeVisible()
  const played = await history(page)
  const exact = lines.find(
    (line) =>
      line.steps.length === played.length &&
      line.steps.every((step, index) => step.san === played[index]),
  )
  expect(exact, `Terminal path must be one exact ${lesson.title} selected line`).toBeTruthy()
  await expect(page.getByRole('region', { name: 'Current variation' })).toContainText(exact!.title)
  return exact!
}

test('variation practice keeps chosen paths, supports Black, hints, name reveal, save retry and learned defaults', async ({
  page,
}, info) => {
  test.setTimeout(90000)
  test.skip(
    process.env.LIVE_LESSON_TEST !== '1',
    'Use tests/browser/run.sh for isolated real API/session data.',
  )
  const errors: string[] = []
  page.on('pageerror', (error) => errors.push(error.message))
  await page.emulateMedia({ reducedMotion: 'reduce' })
  await createLearner(page, info)
  const response = await page.request.get('/api/lessons/caro-kann')
  expect(response.status()).toBe(200)
  const caro = ((await response.json()) as LessonPayload).lesson
  const scope = caro.lines.slice(0, 2)
  expect(scope).toHaveLength(2)
  await expect(page.getByLabel('Your pieces', { exact: true })).toHaveValue('b')
  await expect(page.getByRole('button', { name: 'Start training', exact: true })).toBeDisabled()
  await expect(page.getByRole('button', { name: 'All learned', exact: true })).toBeDisabled()
  await page.getByRole('button', { name: 'Main line only', exact: true }).click()
  await expect(page.getByText('One line is selected:', { exact: false })).toBeVisible()
  await page.getByRole('checkbox', { name: scope[1]!.title, exact: true }).check()
  await expect(
    page.getByRole('checkbox', { name: 'Hide variation names during practice', exact: true }),
  ).toBeChecked()
  let firstSave: Completion | undefined
  let savedRetry: Completion | undefined
  let refuseOnce = true
  let italianSaveWaiting = false
  let releaseItalianSave = () => {}
  await page.route('**/api/me/completions', async (route) => {
    const body = route.request().postDataJSON() as Completion
    if (body.mode === 'variation') {
      if (refuseOnce) {
        refuseOnce = false
        firstSave = body
        return route.fulfill({ status: 503, json: { error: 'Temporary save failure' } })
      }
      savedRetry = body
      if (body.lessonId === 'italian') {
        await new Promise<void>((resolve) => {
          releaseItalianSave = resolve
          italianSaveWaiting = true
        })
      }
    }
    return route.continue()
  })
  await page.getByRole('button', { name: 'Start training', exact: true }).click()
  await expect(page.locator('.chess-board')).toHaveClass(/orientation-black/)
  await expect(page.getByRole('button', { name: 'Take back my move', exact: true })).toBeDisabled()
  await expect(page.getByRole('region', { name: 'Current variation' })).toHaveCount(0)
  await page.getByRole('button', { name: 'Reveal variation names', exact: true }).click()
  await expect(page.getByRole('region', { name: 'Current variation' })).toContainText(
    scope[0]!.title,
  )
  const completed = await playScope(page, info, caro, scope, true)
  await expect(page.getByRole('button', { name: 'Try saving again', exact: true })).toBeVisible()
  await page.getByRole('button', { name: 'Try saving again', exact: true }).click()
  await expect(page.getByText('Practice saved to your account.', { exact: true })).toBeVisible()
  expect(savedRetry).toEqual(firstSave)
  expect(savedRetry?.selectedLineIds).toEqual(scope.map((line) => line.id))
  expect(savedRetry?.lineId).toBe(completed.id)
  await page.reload()
  const group = page.getByRole('group', { name: 'Training lines', exact: true })
  await expect(group.locator('input:checked')).toHaveCount(1)
  await expect(page.getByRole('checkbox', { name: completed.title, exact: true })).toBeChecked()
  await page.getByLabel('Your pieces', { exact: true }).selectOption('w')
  await expect(group.locator('input:checked')).toHaveCount(0)
  await page.getByLabel('Your pieces', { exact: true }).selectOption('b')
  await expect(page.getByRole('checkbox', { name: completed.title, exact: true })).toBeChecked()

  await page.getByRole('link', { name: '← All lessons', exact: true }).click()
  await page
    .getByRole('link', { name: 'Practise variations in Italian Game', exact: false })
    .click()
  const italian = ((await (await page.request.get('/api/lessons/italian')).json()) as LessonPayload)
    .lesson
  await page.getByRole('button', { name: 'Select all available', exact: true }).click()
  // Train the first two lines while the remaining curriculum stays available to select.
  for (const line of italian.lines.slice(2))
    await page.getByRole('checkbox', { name: line.title, exact: true }).uncheck()
  await page.getByRole('button', { name: 'Start training', exact: true }).click()
  await expect(page.getByRole('region', { name: 'Current variation' })).toHaveCount(0)
  // A legal move outside scope must not advance or trigger an automatic reply.
  await square(page, 'c2', info)
  await square(page, 'c4', info)
  await expect(page.locator('.training-feedback')).toContainText(
    'legal, but outside the selected lines',
  )
  await page.getByText('Moves played (chess notation)', { exact: true }).click()
  await expect(page.getByRole('list', { name: 'Training move history' }).locator('li')).toHaveCount(
    0,
  )
  await page.getByText('Moves played (chess notation)', { exact: true }).click()
  // The learner deliberately chooses the second line's branch; the trainer must credit that line.
  const quieter = italian.lines[1]
  expect(quieter).toBeTruthy()
  try {
    const italianCompleted = await playScope(
      page,
      info,
      italian,
      italian.lines.slice(0, 2),
      false,
      quieter!.id,
    )
    expect(italianCompleted.id).toBe(quieter!.id)
    await expect.poll(() => italianSaveWaiting).toBe(true)
    await page.getByRole('button', { name: 'Change training lines', exact: true }).click()
    await expect(page.getByRole('button', { name: 'All learned', exact: true })).toBeDisabled()
    releaseItalianSave()
    await expect(page.getByRole('button', { name: 'All learned', exact: true })).toBeEnabled()
    await page.getByRole('button', { name: 'All learned', exact: true }).click()
    await expect(page.getByRole('checkbox', { name: quieter!.title, exact: true })).toBeChecked()
  } finally {
    releaseItalianSave()
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  expect(errors).toEqual([])
})

test('variation route preserves the sign-in destination', async ({ page }) => {
  await page.route('**/api/me', (route) =>
    route.fulfill({ status: 401, json: { error: 'Sign in' } }),
  )
  await page.goto('/train/italian')
  await expect(page).toHaveURL(/\/login\?next=\/train\/italian$/)
  await expect(page.getByRole('heading', { name: 'Sign in.', exact: true })).toBeVisible()
})
