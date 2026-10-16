import { describe, expect, it } from 'vitest'
import { selectReview } from '../../src/domain/training/ReviewLink'
import type { Lesson } from '../../src/api/lessons'

const lesson: Lesson = {
  id: 'caro-kann',
  revision: 2,
  recommendedColor: 'b',
  title: 'Caro-Kann',
  introduction: 'Fixture',
  startFen: '',
  lines: [
    { id: 'exchange', title: 'Exchange', description: 'Fixture', steps: [] },
    { id: 'classical', title: 'Classical', description: 'Fixture', steps: [] },
  ],
}

describe('revision-bound review links', () => {
  it('uses the recommended side and first line for an ordinary lesson visit', () => {
    expect(selectReview(lesson, {})).toEqual({ lineId: 'exchange', playingColor: 'b', notice: '' })
    expect(selectReview(lesson, { unrelated: 'value' }).notice).toBe('')
  })
  it('selects the exact current line and either explicit playing side', () => {
    for (const color of ['w', 'b'])
      expect(selectReview(lesson, { line: 'classical', color, revision: '2' })).toEqual({
        lineId: 'classical',
        playingColor: color,
        notice: '',
      })
  })
  it('does not carry a same-ID line from an older or newer revision into setup', () => {
    for (const revision of ['1', '3', '65535']) {
      const result = selectReview(lesson, { line: 'classical', color: 'w', revision })
      expect(result).toMatchObject({ lineId: 'exchange', playingColor: 'b' })
      expect(result.notice).toContain('earlier practice is kept')
    }
  })
  for (const query of [
    { line: 'classical' },
    { color: 'b' },
    { revision: '2' },
    { line: 'missing', color: 'b', revision: '2' },
    { line: ['exchange', 'classical'], color: 'b', revision: '2' },
    { line: 'classical', color: ['w', 'b'], revision: '2' },
    { line: 'classical', color: 'black', revision: '2' },
    ...[null, 2, '0', '02', '+2', '2.0', '2e0', ' 2', '2 ', ['2'], '999999', ''].map(
      (revision) => ({ line: 'classical', color: 'b', revision }),
    ),
  ])
    it(`falls back explicitly for malformed parameters ${JSON.stringify(query)}`, () => {
      const result = selectReview(lesson, query)
      expect(result).toMatchObject({ lineId: 'exchange', playingColor: 'b' })
      expect(result.notice).toContain('practice link is incomplete')
    })
})
