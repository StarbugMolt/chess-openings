import { expect, test } from '@playwright/test'

const notice = {
  version: 1,
  publisher: 'StarbugStone',
  contactEmail: 'chessopenings@starbugstone.com',
  emailProvider: 'O2Switch',
  hostingProvider: 'o2switch',
  hostingBackupDays: 30,
  deploymentBackupDays: 30,
  applicationLogDays: 14,
  unverifiedAccountDays: 0,
  development: false,
  configured: true,
  missing: [],
}

test('public privacy notice shows owner details, retention and account choices on every viewport', async ({
  page,
}) => {
  const errors: string[] = []
  page.on('pageerror', (error) => errors.push(error.message))
  await page.route('**/api/privacy', (route) => route.fulfill({ json: notice }))
  await page.goto('/privacy')
  await expect(page.getByRole('heading', { name: 'Account privacy.' })).toBeVisible()
  await expect(page.getByText('The publisher and data controller is StarbugStone.')).toBeVisible()
  await expect(page.getByRole('link', { name: 'chessopenings@starbugstone.com' })).toHaveAttribute(
    'href',
    'mailto:chessopenings@starbugstone.com',
  )
  await expect(
    page.getByRole('link', { name: 'chessopening.starbugstone.com', exact: true }),
  ).toHaveAttribute('href', 'https://chessopening.starbugstone.com/')
  await expect(
    page.getByText('Account email is delivered through O2Switch.', { exact: false }),
  ).toBeVisible()
  await expect(
    page.getByText('Retained for 30 days in the hosting backup cycle.', { exact: true }),
  ).toBeVisible()
  await expect(
    page.getByText('automatic age-based deletion of unverified accounts', { exact: false }),
  ).toBeVisible()
  await expect(
    page.getByText('You are using the local development version.', { exact: false }),
  ).toHaveCount(0)
  await expect(page.getByRole('link', { name: 'contact the CNIL' })).toHaveAttribute(
    'href',
    'https://www.cnil.fr/fr/adresser-une-plainte',
  )
  await expect(page.getByRole('link', { name: 'Manage your account' })).toHaveAttribute(
    'href',
    '/account',
  )
  expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(
    true,
  )
  expect(errors).toEqual([])
})

test('privacy notice retries an outage and identifies missing public configuration', async ({
  page,
}) => {
  let available = false
  await page.route('**/api/privacy', (route) =>
    route.fulfill(
      available
        ? {
            json: {
              ...notice,
              emailProvider: '',
              configured: false,
              missing: ['emailProvider'],
              development: true,
            },
          }
        : { status: 503, json: { error: 'Unavailable' } },
    ),
  )
  await page.goto('/privacy')
  await expect(page.getByRole('alert')).toContainText('could not load')
  available = true
  await page.getByRole('button', { name: 'Try again' }).click()
  await expect(page.getByRole('status')).toContainText('have not yet been configured')
  await expect(
    page.getByText('You are using the local development version.', { exact: false }),
  ).toBeVisible()
  await expect(
    page.getByText('The account-email provider has not yet been supplied', { exact: false }),
  ).toBeVisible()
  await expect(page.getByRole('alert')).toHaveCount(0)
})
