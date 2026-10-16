import { expect, test, type Page } from '@playwright/test'

async function playByCoordinates(page: Page, from: string, to: string) {
  await page.getByLabel('From square').fill(from)
  await page.getByLabel('To square').fill(to)
  await page.getByRole('button', { name: 'Play move', exact: true }).click()
}

test('board input, illegal moves, orientation, undo and restart stay in sync', async ({ page }) => {
  const errors: string[] = []
  page.on('pageerror', (error) => errors.push(error.message))
  await page.goto('/dev/board')
  await playByCoordinates(page, 'e2', 'e5')
  await expect(page.getByRole('status')).toHaveText('That move is not legal in this position.')
  await playByCoordinates(page, 'e2', 'e4')
  await expect(page.getByRole('status')).toHaveText('Played e4.')
  await expect(page.getByRole('list', { name: 'Move history' })).toHaveText('e4')
  await page.getByRole('button', { name: 'Flip board' }).click()
  await expect(page.locator('.chess-board')).toHaveClass(/orientation-black/)
  await page.getByRole('button', { name: 'Undo', exact: true }).click()
  await expect(page.getByText('No moves yet.')).toBeVisible()
  await playByCoordinates(page, 'd2', 'd4')
  await page.getByRole('button', { name: 'Restart', exact: true }).click()
  await expect(page.getByText('No moves yet.')).toBeVisible()
  expect(errors).toEqual([])
})

test('tap-to-move validates through chess.js', async ({ page }, testInfo) => {
  await page.goto('/dev/board')
  const board = page.locator('cg-board')
  await expect(board).toBeVisible()
  await page.evaluate(() => document.fonts.ready.then(() => undefined))
  await board.scrollIntoViewIfNeeded()
  // Scroll events invalidate Chessground's cached bounds before coordinate input.
  await page.evaluate(
    () =>
      new Promise<void>((resolve) =>
        requestAnimationFrame(() => requestAnimationFrame(() => resolve())),
      ),
  )
  const bounds = await board.boundingBox()
  if (!bounds) throw new Error('Board not mounted')
  // Chessground exposes a single drawing surface, so square interactions use board-relative points.
  const tap = (x: number, y: number) =>
    testInfo.project.name === 'mobile'
      ? board.tap({ position: { x, y } })
      : board.click({ position: { x, y } })
  await tap((bounds.width * 4.5) / 8, (bounds.height * 6.5) / 8)
  await expect(board.locator('square.move-dest')).toHaveCount(2)
  await tap((bounds.width * 4.5) / 8, (bounds.height * 4.5) / 8)
  await expect(page.getByRole('status')).toHaveText('Played e4.')
})

test('dragging a piece advances the same authoritative position', async ({ page }, testInfo) => {
  test.skip(testInfo.project.name === 'mobile', 'Touch interaction is covered by the tap test.')
  await page.goto('/dev/board')
  await page.locator('cg-board').scrollIntoViewIfNeeded()
  const board = await page.locator('cg-board').boundingBox()
  if (!board) throw new Error('Board not mounted')
  await page.mouse.move(board.x + (board.width * 4.5) / 8, board.y + (board.height * 6.5) / 8)
  await page.mouse.down()
  await page.mouse.move(board.x + (board.width * 4.5) / 8, board.y + (board.height * 4.5) / 8, {
    steps: 12,
  })
  await page.mouse.up()
  await expect(page.getByRole('status')).toHaveText('Played e4.')
})

test('promotion requires a choice and handles underpromotion', async ({ page }) => {
  await page.goto('/dev/board')
  await page.getByText('Rules fixtures', { exact: true }).click()
  await page.getByRole('button', { name: 'Load promotion fixture' }).click()
  await page.getByRole('button', { name: 'Play move', exact: true }).click()
  await expect(page.getByRole('group', { name: 'Choose promotion piece' })).toBeVisible()
  await page.getByRole('button', { name: 'Knight', exact: true }).click()
  await expect(page.getByRole('status')).toHaveText('Played a8=N.')
  await expect(page.getByRole('list', { name: 'Move history' })).toHaveText('a8=N')
})

test('navigation cleans up the board and mobile layout fits the viewport', async ({ page }) => {
  await page.goto('/dev/board')
  await expect(page.locator('cg-board')).toHaveCount(1)
  await page.getByRole('link', { name: '← Home' }).click()
  await expect(page.getByRole('heading', { name: /Learn the line/ })).toBeVisible()
  await expect(page.locator('cg-board')).toHaveCount(1)
  await page.goto('/dev/board')
  await expect(page.locator('cg-board')).toHaveCount(1)
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(
    true,
  )
  await expect.poll(async () => (await page.request.get('/api/health')).status()).toBe(200)
})
