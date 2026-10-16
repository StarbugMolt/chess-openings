import { expect, test } from '@playwright/test'

test('catalogue search, paging and revision refresh happen at navigation boundaries', async ({
  page,
}) => {
  let revision = 1
  let statusChecks = 0
  await page.route('**/api/openings/catalogue-status', async (route) => {
    statusChecks++
    await route.fulfill({
      json: {
        catalogueRevision: revision,
        sourceSha: 'a'.repeat(40),
        updatedAt: '2026-10-15T08:00:00Z',
        entryCount: 21,
      },
    })
  })
  await page.route(/\/api\/openings\?/, async (route) => {
    const params = new URL(route.request().url()).searchParams
    const current = Number(params.get('page') ?? 1)
    const filtered = params.get('search') === 'Italian'
    await route.fulfill({
      json: {
        catalogueRevision: revision,
        items: [
          {
            id: String(current),
            sourceId: String(current).repeat(64),
            eco: 'C50',
            name: filtered ? `Italian Game: revision ${revision}` : `Opening page ${current}`,
          },
        ],
        page: current,
        limit: 20,
        total: filtered ? 1 : 21,
      },
    })
  })
  await page.goto('/')
  await page.getByRole('link', { name: 'Openings', exact: true }).click()
  await expect(page.getByRole('list', { name: 'Named openings' })).toContainText('Opening page 1')
  await page.getByRole('button', { name: 'Next', exact: true }).click()
  await expect(page.getByRole('list', { name: 'Named openings' })).toContainText('Opening page 2')
  await page.getByLabel('Opening name').fill('Italian')
  await page.getByRole('button', { name: 'Search openings', exact: true }).click()
  await expect(page.getByRole('list', { name: 'Named openings' })).toContainText('revision 1')
  const before = statusChecks
  revision = 2
  await expect(page.getByRole('list', { name: 'Named openings' })).toContainText('revision 1')
  expect(statusChecks).toBe(before)
  await page.getByRole('button', { name: 'Search openings', exact: true }).click()
  await expect(page.getByRole('list', { name: 'Named openings' })).toContainText('revision 2')
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
})

test('catalogue outage has a usable retry', async ({ page }) => {
  let ready = false
  await page.route('**/api/openings/catalogue-status', async (route) => {
    if (!ready) return route.fulfill({ status: 503, json: { error: 'Unavailable' } })
    return route.fulfill({
      json: { catalogueRevision: 0, sourceSha: null, updatedAt: null, entryCount: 0 },
    })
  })
  await page.route(/\/api\/openings\?/, async (route) => {
    await route.fulfill({ json: { catalogueRevision: 0, items: [], page: 1, limit: 20, total: 0 } })
  })
  await page.goto('/openings')
  await expect(page.getByRole('alert')).toContainText('Please try again')
  ready = true
  await page.getByRole('button', { name: 'Try again', exact: true }).click()
  await expect(
    page.getByText('The opening library is being prepared.', { exact: true }),
  ).toBeVisible()
})
