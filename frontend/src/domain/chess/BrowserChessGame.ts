import { Chess, DEFAULT_POSITION, type Color, type Move, type Square } from 'chess.js'

export type PromotionPiece = 'q' | 'r' | 'b' | 'n'
export type MoveAttempt =
  | { kind: 'played'; move: Move }
  | { kind: 'illegal' }
  | { kind: 'promotion-required'; choices: PromotionPiece[] }
export type MoveValidation =
  | { kind: 'legal'; move: Move }
  | { kind: 'illegal' }
  | { kind: 'promotion-required'; choices: PromotionPiece[] }

export interface ChessSnapshot {
  fen: string
  turn: Color
  destinations: Map<Square, Square[]>
  lastMove: [Square, Square] | undefined
  history: string[]
  gameOver: boolean
  inCheck: boolean
}

/** Browser chess rules only. Lesson scope belongs to the separate training policy. */
export class BrowserChessGame {
  private readonly game: Chess

  constructor(private readonly initialFen: string = DEFAULT_POSITION) {
    this.game = new Chess(initialFen)
  }

  get turn(): Color {
    return this.game.turn()
  }

  snapshot(): ChessSnapshot {
    const destinations = new Map<Square, Square[]>()
    for (const move of this.game.moves({ verbose: true })) {
      const squares = destinations.get(move.from) ?? []
      if (!squares.includes(move.to)) squares.push(move.to)
      destinations.set(move.from, squares)
    }
    const last = this.game.history({ verbose: true }).at(-1)
    return {
      fen: this.game.fen(),
      turn: this.game.turn(),
      destinations,
      lastMove: last ? [last.from, last.to] : undefined,
      history: this.game.history(),
      gameOver: this.game.isGameOver(),
      inCheck: this.game.isCheck(),
    }
  }

  validate(from: string, to: string, promotion?: PromotionPiece): MoveValidation {
    if (!/^[a-h][1-8]$/.test(from) || !/^[a-h][1-8]$/.test(to)) return { kind: 'illegal' }
    const candidates = this.game
      .moves({ square: from as Square, verbose: true })
      .filter((move) => move.to === to)
    if (candidates.length === 0) return { kind: 'illegal' }
    const choices = candidates.flatMap((move) =>
      move.promotion ? [move.promotion as PromotionPiece] : [],
    )
    if (choices.length > 0 && !promotion) return { kind: 'promotion-required', choices }
    const move = candidates.find((move) => (move.promotion ?? '') === (promotion ?? ''))
    return move ? { kind: 'legal', move } : { kind: 'illegal' }
  }

  play(from: string, to: string, promotion?: PromotionPiece): MoveAttempt {
    const validation = this.validate(from, to, promotion)
    if (validation.kind !== 'legal') return validation
    try {
      const move = this.game.move(promotion ? { from, to, promotion } : { from, to })
      return { kind: 'played', move }
    } catch {
      return { kind: 'illegal' }
    }
  }

  undo(): Move | null {
    return this.game.undo()
  }

  restart(): void {
    this.game.load(this.initialFen)
  }
}
