import { afterEach, describe, expect, it, vi } from 'vitest'
import { computed } from 'vue'
import { readFileSync, globSync } from 'node:fs'
import { parse } from '@vue/compiler-sfc'
import { baseParse, type RootNode, type TemplateChildNode } from '@vue/compiler-dom'
import ts from 'typescript'
import {
  formatDate,
  formatNumber,
  initializeLocale,
  joinText,
  locale,
  message,
  resolveLocale,
  setLocale,
  supportedLocale,
  t,
} from '../../src/i18n'
import ui from '../../src/i18n/ui.json'
import lessons from '../../src/i18n/lessons.json'

const catalogues: Record<string, { fr: string; es: string }> = { ...ui, ...lessons }
afterEach(() => {
  setLocale('en')
  vi.unstubAllGlobals()
})

describe('language selection', () => {
  it('honours a saved choice, browser preference order and regional language tags', () => {
    expect(resolveLocale('es', ['fr-CA', 'en-US'])).toBe('es')
    expect(resolveLocale(null, ['fr-CA', 'en-US'])).toBe('fr')
    expect(resolveLocale(null, ['de-DE', 'ES-mx', 'fr'])).toBe('es')
    expect(resolveLocale('invalid', ['de-DE', 'it'])).toBe('en')
    expect(supportedLocale('FR_fr')).toBe('fr')
    expect(supportedLocale('')).toBeUndefined()
  })

  it('uses the browser default until the user saves a choice', () => {
    const storage = new Map<string, string>()
    vi.stubGlobal('localStorage', {
      getItem: (key: string) => storage.get(key) ?? null,
      setItem: (key: string, value: string) => storage.set(key, value),
    })
    vi.stubGlobal('navigator', { languages: ['fr-CH', 'en'] })
    vi.stubGlobal('document', { documentElement: { lang: '' } })
    initializeLocale()
    expect(locale.value).toBe('fr')
    expect(document.documentElement.lang).toBe('fr')
    setLocale('es')
    expect(storage.get('chess-openings.language')).toBe('es')
    initializeLocale()
    expect(locale.value).toBe('es')
    expect(document.documentElement.lang).toBe('es')
    setLocale('unsupported')
    expect(locale.value).toBe('es')
  })

  it('keeps detection and the current selection working when storage is blocked', () => {
    vi.stubGlobal('localStorage', {
      getItem: () => {
        throw new Error('Blocked')
      },
      setItem: () => {
        throw new Error('Blocked')
      },
    })
    vi.stubGlobal('navigator', { languages: ['es-AR'] })
    vi.stubGlobal('document', { documentElement: { lang: '' } })
    initializeLocale()
    expect(locale.value).toBe('es')
    expect(() => setLocale('fr')).not.toThrow()
    expect(document.documentElement.lang).toBe('fr')
  })
})

describe('translated content', () => {
  it('updates existing nested feedback and joined lesson names without rebuilding state', () => {
    const feedback = message(
      'That move belongs to “{p0}”, another taught line. This exercise follows the selected line, so the board stays here. Try again or use a hint.',
      { p0: "Queen's Gambit" },
    )
    const label = computed(() => t(feedback))
    expect(label.value).toContain("Queen's Gambit")
    setLocale('fr')
    expect(label.value).toContain('Gambit dame')
    setLocale('es')
    expect(label.value).toContain('Gambito de dama')
    const names = joinText(['Italian Game', 'French Defense'], ' · ')
    expect(t(names)).toBe('Apertura italiana · Defensa francesa')
    setLocale('fr')
    expect(t(names)).toBe('Partie italienne · Défense française')
  })

  it('translates authored teaching text while retaining coordinates, notation and unknown future text', () => {
    setLocale('fr')
    expect(t('Move your knight from g1 to f3.')).toBe('Déplacez votre cavalier de g1 en f3.')
    expect(t('Move your bishop from b4 to c3, capturing the white knight with check.')).toContain(
      'c3',
    )
    expect(t('Nf3')).toBe('Nf3')
    expect(t('A future revised explanation.')).toBe('A future revised explanation.')
    expect(t('Unknown {placeholder}')).toBe('Unknown {placeholder}')
    setLocale('es')
    expect(t('Move your knight from g1 to f3.')).toBe('Mueve tu caballo de g1 a f3.')
  })

  it('formats numbers and dates using the selected language', () => {
    setLocale('fr')
    expect(formatNumber(1.5)).toBe('1,5')
    expect(t('Page {p0}', { p0: 1.5 })).toBe('Page 1,5')
    const value = '2026-10-16T12:30:00Z'
    expect(formatDate(value)).toBe(
      new Intl.DateTimeFormat('fr', { dateStyle: 'medium', timeStyle: 'short' }).format(
        new Date(value),
      ),
    )
    setLocale('es')
    expect(t('Page {p0}', { p0: 2 })).toBe('Página 2')
  })

  it('provides nonempty French and Spanish messages with matching placeholders', () => {
    const placeholders = (value: string) =>
      [...value.matchAll(/\{(\w+)\}/g)].map((match) => match[1]).sort()
    for (const [source, translations] of Object.entries(catalogues)) {
      for (const language of ['fr', 'es'] as const) {
        expect(translations[language].trim(), `${language}: ${source}`).not.toBe('')
        expect(placeholders(translations[language]), `${language}: ${source}`).toEqual(
          placeholders(source),
        )
      }
    }
  })

  it('covers every static message used in the application templates and scripts', () => {
    const missing = new Set<string>()
    function checkExpression(source: string) {
      const file = ts.createSourceFile('messages.ts', source, ts.ScriptTarget.Latest, true)
      function visit(node: ts.Node) {
        if (
          ts.isCallExpression(node) &&
          ['t', 'message', 'textMessage'].includes(node.expression.getText(file))
        ) {
          const argument = node.arguments[0]
          if (
            argument &&
            ts.isStringLiteral(argument) &&
            argument.text &&
            !catalogues[argument.text]
          )
            missing.add(argument.text)
        }
        ts.forEachChild(node, visit)
      }
      visit(file)
    }
    function visitTemplate(node: RootNode | TemplateChildNode) {
      if (node.type === 5) checkExpression(node.content.loc.source)
      if (node.type === 1)
        for (const property of node.props) {
          if (property.type === 7 && property.exp) checkExpression(property.exp.loc.source)
        }
      if (node.type === 0 || node.type === 1)
        for (const child of node.children) visitTemplate(child)
    }
    for (const path of globSync('src/**/*.vue')) {
      const { descriptor } = parse(readFileSync(path, 'utf8'))
      if (descriptor.scriptSetup) checkExpression(descriptor.scriptSetup.content)
      if (descriptor.template) visitTemplate(baseParse(descriptor.template.content))
    }
    expect([...missing]).toEqual([])
  })
})
