import { expect, test, type Page } from '@playwright/test'
import type { PrivacyNotice } from '../../src/api/privacy'
import type { LessonPayload } from '../../src/api/lessons'
import { readFileSync } from 'node:fs'

const teaching = JSON.parse(
  readFileSync(new URL('../../src/i18n/lessons.json', import.meta.url), 'utf8'),
) as Record<string, { fr: string; es: string }>
async function choose(page: Page, language: string) {
  await page.locator('.language-trigger').click()
  await page
    .locator('#language-options')
    .getByRole('button', { name: language, exact: true })
    .click()
  await expect(page.locator('#language-options')).toHaveCount(0)
  await expect(page.locator('.language-trigger')).toBeFocused()
}

test.describe('French browser preference', () => {
  test.use({ locale: 'fr-CA' })

  test('detects the browser language, updates navigation and remembers explicit choices', async ({
    page,
    context,
  }) => {
    const errors: string[] = []
    page.on('pageerror', (error) => errors.push(error.message))
    await page.goto('/')
    await expect(page.locator('html')).toHaveAttribute('lang', 'fr')
    await expect(page).toHaveTitle('Apprendre les ouvertures d’échecs · Chess Openings')
    await expect(page.getByRole('link', { name: 'Apprendre', exact: true })).toBeVisible()
    await page.locator('.language-trigger').focus()
    await page.keyboard.press('Enter')
    await expect(page.locator('#language-options button[aria-pressed=true]')).toBeFocused()
    await page.keyboard.press('Escape')
    await expect(page.locator('.language-trigger')).toBeFocused()
    await choose(page, 'Español')
    await expect(page.locator('html')).toHaveAttribute('lang', 'es')
    await expect(page).toHaveTitle('Aprende aperturas de ajedrez · Chess Openings')
    await page.getByRole('link', { name: 'Aprender', exact: true }).click()
    await expect(page.getByRole('heading', { name: 'Aprende una apertura.' })).toBeVisible()
    await expect(
      page.getByRole('heading', { name: 'Apertura italiana', exact: true }),
    ).toBeVisible()
    await expect(page.locator('.lesson-cards')).toContainText('Juega con blancas')
    await page.reload()
    await expect(page.locator('html')).toHaveAttribute('lang', 'es')
    const other = await context.newPage()
    await other.goto('/login')
    await expect(other.locator('html')).toHaveAttribute('lang', 'es')
    await expect(other.getByRole('heading', { name: 'Inicia sesión.' })).toBeVisible()
    await other.close()
    await choose(page, 'English')
    await page.reload()
    await expect(page.locator('html')).toHaveAttribute('lang', 'en')
    expect(errors).toEqual([])
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  })

  test('privacy translations preserve the configured publisher, contact and retention policy', async ({
    page,
  }) => {
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
    } satisfies PrivacyNotice
    await page.route('**/api/privacy', (route) => route.fulfill({ json: notice }))
    await page.goto('/privacy')
    await expect(
      page.getByRole('heading', { name: 'Confidentialité des comptes.', exact: true }),
    ).toBeVisible()
    await expect(
      page.getByText('L’éditeur et responsable du traitement est StarbugStone.', { exact: true }),
    ).toBeVisible()
    await expect(
      page.getByRole('link', { name: notice.contactEmail, exact: true }),
    ).toHaveAttribute('href', 'mailto:' + notice.contactEmail)
    await expect(page.locator('.retention-list')).toContainText('14 jours sans nouvelle entrée')
    await expect(page.locator('.retention-list')).toContainText('30 jours dans le cycle')
    await choose(page, 'Español')
    await expect(
      page.getByRole('heading', { name: 'Privacidad de las cuentas.', exact: true }),
    ).toBeVisible()
    await expect(
      page.getByText('El editor y responsable del tratamiento es StarbugStone.', { exact: true }),
    ).toBeVisible()
    await expect(page.locator('.retention-list')).toContainText('14 días sin nuevas entradas')
    await expect(
      page.getByRole('link', { name: 'contactar con la CNIL', exact: true }),
    ).toHaveAttribute('href', 'https://www.cnil.fr/fr/adresser-une-plainte')
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  })

  test('language selection remains usable with blocked storage', async ({ page }) => {
    await page.addInitScript(() => {
      // Pinia's development-only devtools also read storage during module loading.
      // Deny our preference while leaving that unrelated tooling operational.
      const read = Storage.prototype.getItem
      const write = Storage.prototype.setItem
      Storage.prototype.getItem = function (key) {
        if (key === 'chess-openings.language') throw new Error('Storage disabled')
        return read.call(this, key)
      }
      Storage.prototype.setItem = function (key, value) {
        if (key === 'chess-openings.language') throw new Error('Storage disabled')
        write.call(this, key, value)
      }
    })
    await page.goto('/register')
    await expect(page.locator('html')).toHaveAttribute('lang', 'fr')
    await choose(page, 'Español')
    await expect(page.getByRole('heading', { name: 'Crea una cuenta.' })).toBeVisible()
    await page.reload()
    await expect(page.locator('html')).toHaveAttribute('lang', 'fr')
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  })

  test('translated emails, teaching notes and live switches preserve guided and variation practice', async ({
    page,
  }, info) => {
    test.skip(process.env.LIVE_LESSON_TEST !== '1', 'Use the isolated browser database runner.')
    test.setTimeout(90000)
    const email = `i18n-${Date.now()}-${info.project.name}@example.test`
    const password = 'LanguagePractice!2026'
    await page.goto('/register')
    await page.getByLabel('Adresse e-mail', { exact: true }).fill(email)
    await page.getByLabel('Nouveau mot de passe', { exact: true }).fill(password)
    const request = page.waitForRequest(
      (request) => request.url().endsWith('/api/auth/register') && request.method() === 'POST',
    )
    await page.getByRole('button', { name: 'Créer le compte', exact: true }).click()
    expect((await request).headers()['accept-language']).toBe('fr')
    await expect(page.getByRole('status')).toContainText('Consultez votre messagerie')
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
    ).json()) as { Text: string; Subject: string }
    expect(mail.Subject).toBe('Confirmez votre adresse e-mail Chess Openings')
    const link = new URL(mail.Text.match(/https?:\/\/\S+#token=[a-f0-9]{64}/)![0])
    expect(link.searchParams.get('lang')).toBe('fr')
    await choose(page, 'Español')
    await page.goto(link.pathname + link.search + link.hash)
    await expect(page.locator('html')).toHaveAttribute('lang', 'fr')
    await page.getByRole('button', { name: 'Confirmer l’adresse e-mail', exact: true }).click()
    await expect(page.getByRole('status')).toContainText('Adresse e-mail confirmée')
    await page.goto('/login?next=/learn/london')
    await page.getByLabel('Adresse e-mail', { exact: true }).fill(email)
    await page.getByLabel('Mot de passe', { exact: true }).fill(password)
    const lessonResponse = page.waitForResponse(
      (response) => response.url().endsWith('/api/lessons/london') && response.status() === 200,
    )
    await page.getByRole('button', { name: 'Se connecter', exact: true }).click()
    await expect(page).toHaveURL(/\/learn\/london$/)
    const payload = (await (await lessonResponse).json()) as LessonPayload
    const line = payload.lesson.lines[0]!
    await expect(
      page.getByRole('heading', { name: 'Système de Londres', exact: true }),
    ).toBeVisible()
    await page.getByRole('button', { name: 'Commencer la leçon', exact: true }).click()
    await page.getByRole('button', { name: 'Indice', exact: true }).click()
    await expect(page.locator('.hint:visible, .mobile-board-hint:visible')).toContainText(
      teaching[line.steps[0]!.hint]!.fr,
    )
    await choose(page, 'Español')
    await expect(page).toHaveTitle('Lección de Sistema Londres · Chess Openings')
    await expect(page.locator('.hint:visible, .mobile-board-hint:visible')).toContainText(
      teaching[line.steps[0]!.hint]!.es,
    )
    await page.getByText('Introducir un movimiento con el teclado', { exact: true }).click()
    for (let index = 0; index < line.steps.length; index += 2) {
      const step = line.steps[index]!
      await expect(
        page.getByRole('button', { name: 'Jugar movimiento', exact: true }),
      ).toBeEnabled()
      await page.getByLabel('Casilla de origen', { exact: true }).fill(step.uci.slice(0, 2))
      await page.getByLabel('Casilla de destino', { exact: true }).fill(step.uci.slice(2, 4))
      await page.getByRole('button', { name: 'Jugar movimiento', exact: true }).click()
    }
    await expect(
      page.getByRole('heading', { name: 'Línea completada.', exact: true }),
    ).toBeVisible()
    await expect(page.getByText('Práctica guardada en tu cuenta.', { exact: true })).toBeVisible()
    await expect(page.locator('.move-notes:visible')).toContainText(
      teaching[line.steps.at(-1)!.explanation]!.es,
    )
    await page.getByRole('link', { name: 'Ver tus prácticas y repasos →', exact: true }).click()
    await expect(page.getByRole('heading', { name: 'Tus prácticas.', exact: true })).toBeVisible()
    await expect(page.locator('.course-progress-grid')).toContainText('Sistema Londres')
    await page.goto('/train/london')
    await page.getByRole('button', { name: 'Empezar entrenamiento', exact: true }).click()
    await choose(page, 'English')
    await expect(
      page.getByRole('heading', { name: 'Variation practice', exact: true }),
    ).toBeVisible()
    await expect(
      page.getByRole('button', { name: 'Change training lines', exact: true }),
    ).toBeVisible()
    await page.getByText('Enter a move with the keyboard', { exact: true }).click()
    for (let index = 0; index < line.steps.length; index += 2) {
      const step = line.steps[index]!
      await expect(page.getByRole('button', { name: 'Play move', exact: true })).toBeEnabled()
      await page.getByLabel('From square', { exact: true }).fill(step.uci.slice(0, 2))
      await page.getByLabel('To square', { exact: true }).fill(step.uci.slice(2, 4))
      await page.getByRole('button', { name: 'Play move', exact: true }).click()
    }
    await expect(
      page.getByRole('heading', { name: 'Practice complete.', exact: true }),
    ).toBeVisible()
    await expect(page.getByText('Practice saved to your account.', { exact: true })).toBeVisible()
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  })
})

test.describe('unsupported browser language', () => {
  test.use({ locale: 'de-DE' })
  test('uses English and translates reference controls without changing source search names', async ({
    page,
  }) => {
    await page.goto('/openings')
    await expect(page.locator('html')).toHaveAttribute('lang', 'en')
    await choose(page, 'Français')
    await expect(page).toHaveTitle('Répertoire des ouvertures · Chess Openings')
    await page.getByRole('button', { name: 'Partie italienne', exact: true }).click()
    await expect(page).toHaveURL(/search=Italian\+Game/)
    await expect(page.getByRole('searchbox')).toHaveValue('Italian Game')
    await expect(page.getByRole('list', { name: 'Ouvertures nommées', exact: true })).toContainText(
      'Italian Game',
    )
    await page.locator('.opening-results summary').first().click()
    await expect(page.getByRole('button', { name: 'Coup précédent', exact: true })).toBeVisible()
    await page.getByRole('button', { name: 'Position initiale', exact: true }).click()
    await expect(page.locator('.sequence-status')).toHaveText('Position initiale')
    await choose(page, 'Español')
    await expect(page.locator('.sequence-status')).toHaveText('Posición inicial')
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true)
  })
})
