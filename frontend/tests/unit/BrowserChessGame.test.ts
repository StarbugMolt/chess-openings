import { describe, expect, it } from 'vitest'
import { BrowserChessGame } from '../../src/domain/chess/BrowserChessGame'

describe('browser chess rules', () => {
  it('rejects an illegal move without changing the position or turn', () => {
    const game = new BrowserChessGame()
    const before = game.snapshot()
    expect(game.play('e2', 'e5').kind).toBe('illegal')
    expect(game.play('e7', 'e5').kind).toBe('illegal')
    expect(game.snapshot()).toEqual(before)
    expect(game.play('e2', 'e4').kind).toBe('played')
    expect(game.snapshot().turn).toBe('b')
    expect(game.snapshot().history).toEqual(['e4'])
  })

  it('replays castling and restores the exact original state on undo', () => {
    const game = new BrowserChessGame('r3k2r/8/8/8/8/8/8/R3K2R w KQkq - 0 1')
    const before = game.snapshot()
    expect(game.play('e1', 'g1').kind).toBe('played')
    expect(game.snapshot().fen).toBe('r3k2r/8/8/8/8/8/8/R4RK1 b kq - 1 1')
    expect(game.snapshot().history).toEqual(['O-O'])
    game.undo()
    expect(game.snapshot()).toEqual(before)
  })

  it('captures en-passant legally and rejects a pinned en-passant move', () => {
    const game = new BrowserChessGame('4k3/8/8/3pP3/8/8/8/4K3 w - d6 0 2')
    expect(game.play('e5', 'd6').kind).toBe('played')
    expect(game.snapshot().fen).toBe('4k3/8/3P4/8/8/8/8/4K3 b - - 0 2')
    const pinned = new BrowserChessGame('k3r3/8/8/3pP3/8/8/8/4K3 w - d6 0 2')
    expect(pinned.play('e5', 'd6').kind).toBe('illegal')
  })

  it('requires an explicit promotion choice and supports underpromotion', () => {
    const game = new BrowserChessGame('7k/P7/8/8/8/8/8/7K w - - 0 1')
    const before = game.snapshot().fen
    const attempt = game.play('a7', 'a8')
    expect(attempt.kind).toBe('promotion-required')
    expect(game.snapshot().fen).toBe(before)
    expect(game.snapshot().destinations.get('a7')).toEqual(['a8'])
    expect(game.play('a7', 'a8', 'n').kind).toBe('played')
    expect(game.snapshot().fen).toBe('N6k/8/8/8/8/8/8/7K b - - 0 1')
  })

  it('keeps move clocks in replay state and resets to its supplied start', () => {
    const fen = '8/8/8/8/8/1k6/8/5K1R b - - 12 20'
    const game = new BrowserChessGame(fen)
    expect(game.play('b3', 'c3').kind).toBe('played')
    expect(game.snapshot().turn).toBe('w')
    expect(game.snapshot().fen.endsWith('13 21')).toBe(true)
    game.restart()
    expect(game.snapshot().fen).toBe(fen)
    expect(game.snapshot().history).toEqual([])
  })
})
