import { expect, test, type Page, type TestInfo } from '@playwright/test'
import type { LessonPayload } from '../../src/api/lessons'
import type { PracticeOverview } from '../../src/api/practice'

async function square(page: Page, key: string, info: TestInfo, flipped = false) {
  await page.locator('cg-board').scrollIntoViewIfNeeded()
  const board = await page.locator('cg-board').boundingBox()
  if (!board) throw new Error('Board missing')
  const file = key.charCodeAt(0) - 97
  const rank = Number(key[1]) - 1
  const x = board.x + (board.width * ((flipped ? 7 - file : file) + 0.5)) / 8
  const y = board.y + (board.height * ((flipped ? rank : 7 - rank) + 0.5)) / 8
  if (info.project.name === 'mobile') await page.touchscreen.tap(x, y)
  else await page.locator('cg-board').click({ position: { x: x - board.x, y: y - board.y } })
}

test('a new learner verifies email, plays a full London lesson with board hints, reloads and sees saved practice', async ({
  page,
  browser,
}, info) => {
  test.setTimeout(90000)
  test.skip(
    process.env.LIVE_LESSON_TEST !== '1',
    'Run tests/browser/run.sh for an isolated database and real email/API journey.',
  )
  const errors: string[] = []
  await page.emulateMedia({ reducedMotion: 'reduce' })
  page.on('pageerror', (error) => errors.push(error.message))
  const email = `browser-${Date.now()}-${info.project.name}@example.test`
  const password = 'LondonPractice!2026'
  const csrf = (await (await page.request.get('/api/auth/csrf')).json()) as { csrfToken: string }
  const registration = await page.request.post('/api/auth/register', {
    data: { email, password },
    headers: { 'X-CSRF-Token': csrf.csrfToken },
  })
  expect(registration.status()).toBe(202)
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
  await page.goto(`/verify-email#token=${token}`)
  await expect(page.locator('input[type=password]')).toHaveCount(0)
  await page.getByRole('button', { name: 'Verify email', exact: true }).click()
  await expect(page.getByRole('status')).toContainText('now sign in')
  await page.goto('/login?next=/learn/london')
  await page.getByLabel('Email address', { exact: true }).fill(email)
  await page.getByLabel('Password', { exact: true }).fill(password)
  await page.getByRole('button', { name: 'Sign in', exact: true }).click()
  await expect(page).toHaveURL(/^http:\/\/[^/]+\/learn\/london$/)
  const graphResponse = await page.request.get('/api/lessons/london')
  expect(graphResponse.status()).toBe(200)
  const payload = (await graphResponse.json()) as LessonPayload
  const line = payload.lesson.lines[0]!
  await page.getByRole('button', { name: 'Start lesson', exact: true }).click()
  await square(page, 'd2', info)
  await square(page, 'd3', info)
  await expect(page.locator('.lesson-feedback')).toContainText('legal, but outside this lesson')
  for (let i = 0; i < line.steps.length; i += 2) {
    const step = line.steps[i]!
    await page.getByRole('button', { name: 'Hint', exact: true }).click()
    await expect(page.locator('square.hint-origin:visible')).toHaveCount(1)
    await expect(page.locator('square.hint-origin:visible')).toHaveCSS('animation-name', 'none')
    await expect(page.locator('square.hint-destination:visible')).toHaveCount(0)
    await square(page, step.uci.slice(0, 2), info)
    await expect(page.locator('square.hint-destination:visible')).toHaveCount(1)
    await expect(
      page.locator('.mobile-board-hint:visible, [aria-label="Move hint"]:visible'),
    ).toContainText('Why:')
    await square(page, step.uci.slice(2, 4), info)
    if (i + 2 < line.steps.length)
      await expect(page.getByRole('button', { name: 'Hint', exact: true })).toBeEnabled()
    // The teaching note for the move just played stays visible without opening anything.
    await expect(
      page
        .getByRole('region', { name: /^(Why these moves|How the line ends)$/ })
        .locator('visible=true'),
    ).toContainText(step.explanation)
  }
  await expect(page.getByRole('heading', { name: 'Line complete.', exact: true })).toBeVisible()
  await expect(page.getByText('Practice saved to your account.', { exact: true })).toBeVisible()
  await page.reload()
  await expect(page.getByText('You have practised this line before.')).toBeVisible()
  await page.getByRole('link', { name: '← All lessons', exact: true }).click()
  await page.getByRole('radio', { name: 'Black defences', exact: true }).check()
  const courses = (await (await page.request.get('/api/courses')).json()) as {
    courses: { recommendedColor: 'w' | 'b' }[]
  }
  await expect(page.locator('.lesson-card')).toHaveCount(
    courses.courses.filter((course) => course.recommendedColor === 'b').length,
  )
  await page.getByRole('link', { name: 'Learn the Caro-Kann Defense', exact: false }).click()
  const caroResponse = await page.request.get('/api/lessons/caro-kann')
  const caro = (await caroResponse.json()) as LessonPayload
  const continuation = caro.lesson.lines[1]!
  await page.getByText('Other lesson options', { exact: true }).click()
  await page.getByLabel('Line', { exact: true }).selectOption(continuation.id)
  await expect(page.getByLabel('Your pieces', { exact: true })).toHaveValue('b')
  await page.getByRole('button', { name: 'Start lesson', exact: true }).click()
  await page.getByText('Enter a move with the keyboard', { exact: true }).click()
  for (let i = 1; i < continuation.steps.length; i += 2) {
    const step = continuation.steps[i]!
    await expect(page.getByRole('button', { name: 'Hint', exact: true })).toBeEnabled()
    if (i === 1) {
      await page.getByLabel('From square', { exact: true }).fill(step.uci.slice(0, 2))
      await page.getByLabel('To square', { exact: true }).fill(step.uci.slice(2, 4))
      await page.getByRole('button', { name: 'Play move', exact: true }).click()
    } else {
      await page.getByRole('button', { name: 'Hint', exact: true }).click()
      await square(page, step.uci.slice(0, 2), info, true)
      await expect(page.locator('square.hint-destination:visible')).toHaveCount(1)
      await square(page, step.uci.slice(2, 4), info, true)
    }
  }
  await expect(page.getByRole('heading', { name: 'Line complete.', exact: true })).toBeVisible()
  await expect(page.getByText('Practice saved to your account.', { exact: true })).toBeVisible()
  await page.reload()
  await page.getByText('Other lesson options', { exact: true }).click()
  await page.getByLabel('Line', { exact: true }).selectOption(continuation.id)
  await expect(page.getByText('You have practised this line before.')).toBeVisible()
  const practice = (await (await page.request.get('/api/me/practice')).json()) as PracticeOverview
  const course = practice.courses.find((entry) => entry.id === 'caro-kann')!
  const tracked = course.lines.find(
    (entry) => entry.lineId === continuation.id && entry.playingColor === 'b',
  )!
  expect(tracked.completions).toBe(1)
  expect(tracked.assessedCompletions).toBe(1)
  expect(tracked.unassistedCompletions).toBe(0)
  expect(tracked.lastOutcome).toBe('assisted')
  expect(tracked.positions.reduce((sum, entry) => sum + entry.recalled, 0)).toBe(1)
  expect(tracked.positions.reduce((sum, entry) => sum + entry.hints, 0)).toBe(
    continuation.steps.filter((step) => step.fromKey.split(' ')[1] === 'b').length - 1,
  )
  expect(tracked.reviewDue).toBe(false)
  expect(Date.parse(tracked.nextReviewAt!)).toBeGreaterThan(Date.parse(practice.generatedAt))
  await page.goto('/progress')
  const progressCard = page.getByRole('region', { name: 'Caro-Kann Defense practice', exact: true })
  await expect(progressCard).toContainText(
    `1 of ${course.lineCount} current lines practised as Black`,
  )
  await progressCard.getByText(`View ${course.lineCount} lines`, { exact: true }).click()
  const review = progressCard.getByRole('link', {
    name: `Review Caro-Kann Defense: ${continuation.title} as Black`,
    exact: true,
  })
  await review.click()
  await expect(page).toHaveURL(
    new RegExp(
      `\\/learn\\/caro-kann\\?line=${continuation.id}&color=b&revision=${caro.lesson.revision}$`,
    ),
  )
  await page.getByText('Other lesson options', { exact: true }).click()
  await expect(page.getByLabel('Line', { exact: true })).toHaveValue(continuation.id)
  await expect(page.getByLabel('Your pieces', { exact: true })).toHaveValue('b')

  // A genuinely separate cookie jar/session reads the same private saved practice.
  const other = await browser.newContext({ baseURL: new URL(page.url()).origin })
  try {
    const token = (await (await other.request.get('/api/auth/csrf')).json()) as {
      csrfToken: string
    }
    const login = await other.request.post('/api/auth/login', {
      data: { email, password },
      headers: { 'X-CSRF-Token': token.csrfToken },
    })
    expect(login.status()).toBe(200)
    const otherPage = await other.newPage()
    await otherPage.goto('/progress')
    await expect(
      otherPage.getByRole('region', { name: 'Caro-Kann Defense practice', exact: true }),
    ).toContainText(`1 of ${course.lineCount} current lines practised as Black`)
    await otherPage.reload()
    await expect(
      otherPage.getByRole('heading', { name: 'Your practice.', exact: true }),
    ).toBeVisible()
  } finally {
    await other.close()
  }
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  expect(errors).toEqual([])
})

test('Black can be played manually in the developer board after a White move', async ({
  page,
}, info) => {
  await page.goto('/dev/board')
  await square(page, 'e2', info)
  await square(page, 'e4', info)
  await expect(page.getByRole('status')).toHaveText('Played e4.')
  await square(page, 'e7', info)
  await square(page, 'e5', info)
  await expect(page.getByRole('status')).toHaveText('Played e5.')
  await expect(page.getByRole('list', { name: 'Move history' })).toHaveText('e4e5')
})

test('reference results group equal London names and expose their recorded sequences', async ({
  page,
}) => {
  await page.goto('/openings?search=London&page=1')
  const entry = page
    .getByRole('list', { name: 'Named openings' })
    .locator('> li')
    .filter({ has: page.locator('strong').filter({ hasText: /^London System$/ }) })
  await expect(entry).toHaveCount(1)
  await entry.locator('summary').click()
  await expect(entry.getByText('3 recorded sequences · View moves')).toBeVisible()
  await expect(entry.locator('.reference-sequences > li')).toHaveCount(3)
  const viewer = entry.getByRole('group', { name: 'London System on a board' })
  const finalPosition = entry.locator('.sequence-status')
  await expect(viewer.getByRole('img')).toBeVisible()
  await expect(finalPosition).toHaveText(/^Position after /)
  await viewer.getByRole('button', { name: 'Starting position', exact: true }).click()
  await expect(finalPosition).toHaveText('Starting position')
  await viewer.getByRole('button', { name: 'Next move', exact: true }).click()
  await expect(finalPosition).toHaveText('Position after 1. d4')
  await entry.locator('.reference-sequences button').nth(2).click()
  await expect(entry.locator('.reference-sequences button').nth(2)).toHaveAttribute(
    'aria-pressed',
    'true',
  )
  await expect(entry.getByRole('link', { name: 'London System beginner lesson →' })).toBeVisible()
})
