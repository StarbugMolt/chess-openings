import type { LocationQuery } from 'vue-router'
import type { CatalogueQuery } from '../api/catalogue'

/** Canonical, bounded URL values. Invalid optional filters become an ordinary first-page search. */
export function catalogueQuery(query: LocationQuery): CatalogueQuery {
  const first = (value: LocationQuery[string] | undefined) => {
    const item = Array.isArray(value) ? value[0] : value
    return typeof item === 'string' ? item.trim() : ''
  }
  const page = first(query.page)
  const number = /^\d+$/.test(page) ? Number(page) : 1
  const eco = first(query.eco).toUpperCase()
  return {
    search: first(query.search).slice(0, 100),
    eco: /^[A-E][0-9]{0,2}$/.test(eco) ? eco : '',
    page: Number.isSafeInteger(number) && number >= 1 && number <= 9999 ? number : 1,
  }
}
