import { describe, expect, it } from 'vitest'
import { catalogueQuery } from '../../src/domain/catalogueQuery'

describe('catalogue URL normalization', () => {
  it.each(['abc', '0', '-2', '1.5', '10000', '9007199254740993'])(
    'recovers from page=%s',
    (page) => {
      expect(catalogueQuery({ page }).page).toBe(1)
    },
  )
  it('normalizes repeated params, whitespace and lower-case ECO codes', () => {
    expect(
      catalogueQuery({ page: ['2', '3'], eco: ' c5 ', search: [' London ', 'French'] }),
    ).toEqual({ page: 2, eco: 'C5', search: 'London' })
  })
  it('drops invalid ECO filters and bounds search text', () => {
    expect(catalogueQuery({ eco: 'Z99', search: 'a'.repeat(150) })).toEqual({
      page: 1,
      eco: '',
      search: 'a'.repeat(100),
    })
  })
})
