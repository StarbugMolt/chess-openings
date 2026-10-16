import { expect, test } from '@playwright/test'

test('route titles, active navigation and focus follow public navigation', async ({ page }) => {
  await page.goto('/')
  await page.getByRole('link', { name: 'Learn', exact: true }).click()
  await expect(page).toHaveTitle('Guided lessons · Chess Openings')
  await expect(page.getByRole('link', { name: 'Learn', exact: true })).toHaveAttribute(
    'aria-current',
    'page',
  )
  await expect(page.locator('#main-content')).toBeFocused()
  await page.locator('.site-footer').getByRole('link', { name: 'Privacy', exact: true }).click()
  await expect(page).toHaveTitle('Account privacy · Chess Openings')
  await expect(page.locator('#main-content')).toBeFocused()
  expect(await page.evaluate(() => scrollY)).toBe(0)
  await expect(
    page.getByRole('link', { name: 'Dependency licences', exact: true }),
  ).toHaveAttribute('href', '/third-party-notices.txt')
})

test('malformed catalogue URLs recover and paging preserves the button focus', async ({ page }) => {
  let checks = 0
  await page.route('**/api/openings/catalogue-status', (route) => {
    checks++
    return route.fulfill({
      json: {
        catalogueRevision: 1,
        sourceSha: 'a'.repeat(40),
        updatedAt: '2026-10-15T08:00:00Z',
        entryCount: 21,
      },
    })
  })
  await page.route(/\/api\/openings\?/, (route) => {
    const current = Number(new URL(route.request().url()).searchParams.get('page'))
    return route.fulfill({
      json: {
        catalogueRevision: 1,
        page: current,
        limit: 20,
        total: 21,
        items: [
          {
            id: String(current),
            sourceId: String(current).repeat(64),
            name: `Page ${current} result`,
            eco: 'C50',
          },
        ],
      },
    })
  })
  await page.goto('/openings?page=10000&eco=c50')
  await expect(page.getByRole('list', { name: 'Named openings' })).toContainText('Page 1 result')
  expect(new URL(page.url()).searchParams.get('page')).toBe('1')
  expect(new URL(page.url()).searchParams.get('eco')).toBe('C50')
  const next = page.getByRole('button', { name: 'Next', exact: true })
  await next.click()
  await expect(page.getByRole('list', { name: 'Named openings' })).toContainText('Page 2 result')
  await expect(next).toBeFocused()
  const before = checks
  await page.getByRole('link', { name: 'Skip to content', exact: true }).focus()
  await page.keyboard.press('Enter')
  await expect(page).toHaveURL(/#main-content$/)
  // Wait for navigation/focus to settle; no background polling or hash-triggered fetch occurs.
  await expect(page.locator('#main-content')).toBeFocused()
  expect(checks).toBe(before)
})

test('an account outage is explained instead of presented as a normal signed-out state', async ({
  page,
}) => {
  await page.route('**/api/me', (route) =>
    route.fulfill({ status: 503, json: { error: 'Unavailable' } }),
  )
  await page.goto('/account')
  await expect(page).toHaveURL(/\/login\?.*session=unavailable/)
  await expect(page.getByRole('alert')).toContainText('could not check your sign-in')
})
