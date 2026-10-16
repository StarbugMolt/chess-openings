import { readonly, ref, watch } from 'vue'
import ui from './ui.json'
import lessons from './lessons.json'

export const languages = [
  { code: 'en', name: 'English' },
  { code: 'fr', name: 'Français' },
  { code: 'es', name: 'Español' },
] as const
export type Locale = (typeof languages)[number]['code']
export type Text = string | { source: string; values: Record<string, Text | number | undefined> }
type Translations = Record<string, { fr: string; es: string }>
const catalogues: Translations = { ...ui, ...lessons }
const storageKey = 'chess-openings.language'
const currentLocale = ref<Locale>('en')
export const locale = readonly(currentLocale)

export function supportedLocale(value: string | null | undefined): Locale | undefined {
  const language = value?.toLowerCase().split(/[-_]/)[0]
  return languages.find((candidate) => candidate.code === language)?.code
}

export function resolveLocale(saved: string | null, preferred: readonly string[]): Locale {
  return supportedLocale(saved) ?? preferred.map(supportedLocale).find(Boolean) ?? 'en'
}

export function setLocale(value: string): void {
  const supported = supportedLocale(value)
  if (!supported) return
  currentLocale.value = supported
  try {
    localStorage.setItem(storageKey, supported)
  } catch {
    // Storage restrictions must not prevent language selection or training.
  }
}

export function initializeLocale(): void {
  let saved: string | null = null
  try {
    saved = localStorage.getItem(storageKey)
  } catch {
    // Use the browser languages when persistent storage is unavailable.
  }
  currentLocale.value = resolveLocale(saved, navigator.languages ?? [navigator.language])
  document.documentElement.lang = currentLocale.value
}

watch(
  currentLocale,
  (value) => {
    if (typeof document !== 'undefined') document.documentElement.lang = value
  },
  { flush: 'sync' },
)

/** Keep message data in state so changing language also updates existing feedback. */
export function message(source: string, values: Record<string, Text | number | undefined>): Text {
  return { source, values }
}

/** Retain individual source messages when a sentence includes several lesson titles. */
export function joinText(values: readonly Text[], separator: string): Text {
  return values.reduce<Text>(
    (left, right, index) =>
      index === 0 ? right : message('{left}{separator}{right}', { left, separator, right }),
    '',
  )
}

/** Exact English source keys keep a changed/future lesson safely in its original wording. */
export function t(
  text: Text | undefined,
  values: Record<string, Text | number | undefined> = {},
): string {
  if (text === undefined) return ''
  const source = typeof text === 'string' ? text : text.source
  const parameters = typeof text === 'string' ? values : text.values
  const translated =
    currentLocale.value === 'en' ? source : (catalogues[source]?.[currentLocale.value] ?? source)
  return translated.replace(/\{(\w+)\}/g, (placeholder, name: string) => {
    const value = parameters[name]
    return value === undefined
      ? placeholder
      : typeof value === 'number'
        ? formatNumber(value)
        : t(value)
  })
}

export function formatNumber(value: number): string {
  return new Intl.NumberFormat(currentLocale.value).format(value)
}

export function formatDate(value: string): string {
  return new Intl.DateTimeFormat(currentLocale.value, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}
