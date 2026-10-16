import { expect, test, type Page } from '@playwright/test'

async function move(page: Page, from: string, to: string) {
  await page.getByLabel('From square').fill(from)
  await page.getByLabel('To square').fill(to)
  await page.getByRole('button', { name: 'Play move', exact: true }).click()
}

test('wooden sound follows committed moves and persisted mute', async ({ page }) => {
  await page.addInitScript(() => {
    const original = AudioBufferSourceNode.prototype.start
    let sounds = 0
    Object.defineProperty(window, 'playedMoveSounds', { get: () => sounds })
    AudioBufferSourceNode.prototype.start = function (...args) {
      sounds++
      original.apply(this, args)
    }
  })
  const sounds = () => page.evaluate(() => Reflect.get(window, 'playedMoveSounds') as number)
  await page.goto('/dev/board')
  const toggle = page.getByRole('button', { name: 'Move sound', exact: true })
  await expect(toggle).toHaveAttribute('aria-pressed', 'true')
  expect(await sounds()).toBe(0)
  await move(page, 'e2', 'e5')
  expect(await sounds()).toBe(0)
  await move(page, 'e2', 'e4')
  await expect.poll(sounds).toBe(1)
  await page.getByRole('button', { name: 'Flip board' }).click()
  await page.getByRole('button', { name: 'Undo', exact: true }).click()
  expect(await sounds()).toBe(1)
  await toggle.click()
  await move(page, 'd2', 'd4')
  expect(await sounds()).toBe(1)
  await page.reload()
  await expect(toggle).toHaveAttribute('aria-pressed', 'false')
  await move(page, 'e2', 'e4')
  expect(await sounds()).toBe(0)
  await toggle.click()
  await page.getByText('Rules fixtures', { exact: true }).click()
  await page.getByRole('button', { name: 'Load promotion fixture' }).click()
  await move(page, 'a7', 'a8')
  expect(await sounds()).toBe(0)
  await page.getByRole('button', { name: 'Queen', exact: true }).click()
  await expect.poll(sounds).toBe(1)
})

test('blocked audio and storage do not interrupt moves', async ({ page }) => {
  const errors: string[] = []
  page.on('pageerror', (error) => errors.push(error.message))
  await page.addInitScript(() => {
    Object.defineProperty(window, 'AudioContext', {
      value: class {
        constructor() {
          throw new Error('Audio unavailable')
        }
      },
    })
    const get = Storage.prototype.getItem
    const set = Storage.prototype.setItem
    Storage.prototype.getItem = function (key) {
      if (key === 'chess-openings.move-sound') throw new Error('Storage unavailable')
      return get.call(this, key)
    }
    Storage.prototype.setItem = function (key, value) {
      if (key === 'chess-openings.move-sound') throw new Error('Storage unavailable')
      set.call(this, key, value)
    }
  })
  await page.goto('/dev/board')
  await move(page, 'e2', 'e4')
  await expect(page.getByRole('status')).toHaveText('Played e4.')
  await page.getByRole('button', { name: 'Move sound', exact: true }).click()
  await move(page, 'e7', 'e5')
  await expect(page.getByRole('status')).toHaveText('Played e5.')
  expect(errors).toEqual([])
})
