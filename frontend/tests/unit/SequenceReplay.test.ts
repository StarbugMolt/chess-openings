import { describe, expect, it } from 'vitest'
import { replaySequence } from '../../src/domain/chess/sequenceReplay'

describe('replaySequence', () => {
  it('replays reference movetext into numbered frames', () => {
    const frames = replaySequence('1. e4 e5 2. Nf3 Nc6 3. Bb5')!
    expect(frames).toHaveLength(6)
    expect(frames[0]).toMatchObject({ label: '', check: false })
    expect(frames.map((frame) => frame.label).slice(1)).toEqual([
      '1. e4',
      '1... e5',
      '2. Nf3',
      '2... Nc6',
      '3. Bb5',
    ])
    expect(frames[5]?.lastMove).toEqual(['f1', 'b5'])
    expect(frames[5]?.fen).toBe('r1bqkbnr/pppp1ppp/2n5/1B2p3/4P3/5N2/PPPP1PPP/RNBQK2R b KQkq - 3 3')
  })

  it('flags checks and handles castling', () => {
    const frames = replaySequence('1. e4 e5 2. Nf3 Nc6 3. Bc4 Nf6 4. Ng5 d5 5. exd5 Na5 6. Bb5+')!
    expect(frames.at(-1)).toMatchObject({ label: '6. Bb5+', check: true })
    const castled = replaySequence('1. e4 e5 2. Nf3 Nc6 3. Bc4 Bc5 4. O-O')!
    expect(castled.at(-1)?.lastMove).toEqual(['e1', 'g1'])
  })

  it('rejects illegal or empty sequences instead of showing a wrong board', () => {
    expect(replaySequence('1. e4 e4')).toBeUndefined()
    expect(replaySequence('')).toBeUndefined()
    expect(replaySequence('1. Qh5 Ke7 2. Qxf7#')).toBeUndefined()
  })
})
