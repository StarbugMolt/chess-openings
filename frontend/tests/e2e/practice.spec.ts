import { expect, test } from '@playwright/test'
import { readFileSync } from 'node:fs'
import type { Lesson } from '../../src/api/lessons'
import type { PracticeOverview } from '../../src/api/practice'

const lessons = JSON.parse(
  readFileSync(new URL('../fixtures/lessons.json', import.meta.url), 'utf8'),
) as Lesson[]
const caro = lessons.find((lesson) => lesson.id === 'caro-kann')!
const user = {
  id: '1',
  email: 'practice@example.test',
  verified: true,
  createdAt: '2026-10-15T08:00:00Z',
  admin: false,
}

test('review links select a current line and side; stale or malformed links explain the fallback', async ({
  page,
}) => {
  await page.route('**/api/me', (route) => route.fulfill({ json: { user } }))
  await page.route('**/api/lessons/caro-kann', (route) =>
    route.fulfill({
      json: { lesson: caro, catalogueRevision: 1, progress: [] },
    }),
  )
  const line = caro.lines[1]!
  await page.goto(`/learn/caro-kann?line=${line.id}&color=b&revision=${caro.revision}`)
  await page.getByText('Other lesson options', { exact: true }).click()
  await expect(page.getByLabel('Line', { exact: true })).toHaveValue(line.id)
  await expect(page.getByLabel('Your pieces', { exact: true })).toHaveValue('b')
  await expect(page.getByRole('button', { name: 'Start lesson', exact: true })).toBeVisible()
  await page.goto(`/learn/caro-kann?line=${line.id}&color=w&revision=${caro.revision + 1}`)
  await expect(page.getByRole('status')).toContainText('Your earlier practice is kept')
  await page.getByText('Other lesson options', { exact: true }).click()
  await expect(page.getByLabel('Line', { exact: true })).toHaveValue(caro.lines[0]!.id)
  await expect(page.getByLabel('Your pieces', { exact: true })).toHaveValue('b')
  await page.goto(`/learn/caro-kann?line=${line.id}&line=missing&color=b&revision=${caro.revision}`)
  await expect(page.getByRole('status')).toContainText('practice link is incomplete')
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
})

test('practice dashboard recovers from an outage, separates history and links an exact Black review', async ({
  page,
}) => {
  await page.route('**/api/me', (route) => route.fulfill({ json: { user } }))
  let unavailable = true
  const overview: PracticeOverview = {
    generatedAt: '2026-10-15T12:00:00Z',
    courses: [
      {
        id: 'caro-kann',
        title: 'Caro-Kann Defense',
        revision: 2,
        recommendedColor: 'b',
        lineCount: 1,
        lines: (['w', 'b'] as const).map((playingColor) => ({
          lineId: 'exchange',
          title: 'The Exchange setup',
          playingColor,
          completions: playingColor === 'b' ? 2 : 0,
          assessedCompletions: 0,
          unassistedCompletions: 0,
          lastPractisedAt: playingColor === 'b' ? '2026-10-15T11:00:00Z' : null,
          lastAssessedAt: null,
          lastOutcome: 'unknown',
          nextReviewAt: playingColor === 'b' ? '2026-10-15T11:00:00Z' : null,
          reviewDue: playingColor === 'b',
          positions: [
            {
              ply: playingColor === 'w' ? 0 : 1,
              san: playingColor === 'w' ? 'e4' : 'c6',
              hint: 'Play the taught move.',
              practices: 0,
              recalled: 0,
              hints: 0,
              retries: 0,
            },
          ],
        })),
      },
    ],
    reviewQueue: [{ lessonId: 'caro-kann', revision: 2, lineId: 'exchange', playingColor: 'b' }],
    history: [
      {
        lessonId: 'caro-kann',
        title: 'Caro-Kann Defense',
        revision: 1,
        lineId: 'exchange',
        lineTitle: 'An earlier Exchange setup',
        playingColor: 'b',
        completions: 4,
        assessedCompletions: 0,
        unassistedCompletions: 0,
        lastPractisedAt: '2026-10-14T11:00:00Z',
      },
    ],
  }
  await page.route('**/api/me/practice', (route) =>
    route.fulfill({
      status: unavailable ? 503 : 200,
      json: unavailable ? { error: 'Unavailable' } : overview,
    }),
  )
  await page.goto('/progress')
  await expect(page.getByRole('alert')).toBeVisible()
  unavailable = false
  await page.getByRole('button', { name: 'Try again', exact: true }).click()
  await expect(page.getByRole('heading', { name: 'Your practice.', exact: true })).toBeVisible()
  const course = page.getByRole('region', { name: 'Caro-Kann Defense practice', exact: true })
  await expect(course).toContainText(/1 of 1 current lines? practised as Black/)
  await page.getByLabel('Pieces for Caro-Kann Defense', { exact: true }).selectOption('w')
  await expect(course).toContainText(/0 of 1 current lines? practised as White/)
  const ready = page.getByRole('region', { name: 'Ready to review', exact: true })
  await expect(ready.getByRole('link', { name: 'Review this line', exact: true })).toHaveAttribute(
    'href',
    '/learn/caro-kann?line=exchange&color=b&revision=2',
  )
  await page.getByText('Earlier course versions', { exact: true }).click()
  await expect(page.getByText('An earlier Exchange setup', { exact: false })).toBeVisible()
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
})

test('practice page preserves the sign-in destination', async ({ page }) => {
  await page.route('**/api/me', (route) =>
    route.fulfill({ status: 401, json: { error: 'Sign in' } }),
  )
  await page.goto('/progress')
  await expect(page).toHaveURL(/\/login\?next=\/progress$/)
})

test('a session that expires during practice recovery reaches the sign-in form', async ({
  page,
}) => {
  let authenticated = true
  await page.route('**/api/me', (route) =>
    route.fulfill({
      status: authenticated ? 200 : 401,
      json: authenticated ? { user } : { error: 'Sign in' },
    }),
  )
  await page.route('**/api/me/practice', (route) =>
    route.fulfill({
      status: authenticated ? 503 : 401,
      json: { error: authenticated ? 'Unavailable' : 'Sign in' },
    }),
  )
  await page.goto('/progress')
  await expect(page.getByRole('alert')).toContainText('could not be loaded')
  authenticated = false
  await page.getByRole('button', { name: 'Try again', exact: true }).click()
  await page.getByRole('link', { name: 'Sign in again', exact: true }).click()
  await expect(page).toHaveURL(/\/login\?next=\/progress$/)
  await expect(page.getByRole('heading', { name: 'Sign in.', exact: true })).toBeVisible()
})
