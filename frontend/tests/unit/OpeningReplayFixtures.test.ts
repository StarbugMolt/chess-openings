import { describe, expect, it } from 'vitest'
import { Chess } from 'chess.js'
import fixtures from '../../../backend/tests/Fixtures/Openings/golden.json'

const key = (fen: string) => fen.split(' ').slice(0, 4).join(' ')

describe('shared PHP/browser replay contract', () => {
  for (const fixture of fixtures) {
    it(fixture.id, () => {
      const chess = new Chess(fixture.initialFen)
      expect({ fen: chess.fen(), key: key(chess.fen()) }).toEqual(fixture.initial)
      for (const san of 'illegalSan' in fixture ? (fixture.illegalSan ?? []) : []) {
        expect(() => chess.move(san, { strict: true })).toThrow()
        expect(chess.fen()).toBe(fixture.initial.fen)
      }
      for (const expected of fixture.moves) {
        const move = chess.move(expected.san, { strict: true })
        const fen = chess.fen()
        expect({
          uci: move.from + move.to + (move.promotion ?? ''),
          san: move.san,
          fen,
          key: key(fen),
        }).toEqual(expected)
      }
    })
  }
})
