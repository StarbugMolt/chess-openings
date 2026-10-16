import { Chess, type Move, type Square } from 'chess.js'
import { BrowserChessGame, type PromotionPiece } from '../chess/BrowserChessGame'
import type { Lesson, LessonLine, PlayingColor } from '../../api/lessons'
import { PracticeAssessmentTracker, type PracticeAssessment } from './PracticeAssessment'

export type GuidedResult =
  | { kind: 'accepted'; move: Move }
  | { kind: 'illegal' | 'outside-line' | 'wait' }
  | { kind: 'alternative-line'; lineId: string; title: string }
  | { kind: 'promotion-required'; choices: PromotionPiece[] }

/** No Vue or opponent search: only the selected finite authored path can advance. */
export class GuidedTrainer {
  private game: BrowserChessGame
  private readonly moves: string[] = []
  private readonly hints = new Set<number>()
  private readonly practice: PracticeAssessmentTracker
  readonly line: LessonLine

  constructor(
    readonly lesson: Lesson,
    readonly lineId: string,
    readonly playingColor: PlayingColor,
  ) {
    const line = lesson.lines.find((line) => line.id === lineId)
    if (!line) throw new Error('Unknown taught line')
    this.line = line
    const replay = new Chess(lesson.startFen)
    for (const step of line.steps) {
      if (replay.fen().split(' ').slice(0, 4).join(' ') !== step.fromKey)
        throw new Error('Discontinuous lesson')
      const move = replay.move({
        from: step.uci.slice(0, 2),
        to: step.uci.slice(2, 4),
        ...(step.uci[4] ? { promotion: step.uci[4] } : {}),
      })
      if (move.san !== step.san || replay.fen().split(' ').slice(0, 4).join(' ') !== step.toKey)
        throw new Error('Invalid taught move')
    }
    this.game = new BrowserChessGame(lesson.startFen)
    this.practice = new PracticeAssessmentTracker(this.game.turn, playingColor)
  }

  get ply(): number {
    return this.moves.length
  }
  get complete(): boolean {
    return this.ply === this.line.steps.length
  }
  get playerTurn(): boolean {
    return !this.complete && this.game.turn === this.playingColor
  }
  get expected() {
    return this.line.steps[this.ply]
  }
  get previous() {
    return this.line.steps[this.ply - 1]
  }
  get hintCount(): number {
    return this.hints.size
  }
  get path(): string[] {
    return [...this.moves]
  }
  get assessment(): PracticeAssessment | undefined {
    return this.complete ? this.practice.assessment(this.moves) : undefined
  }
  snapshot() {
    return this.game.snapshot()
  }

  play(from: string, to: string, promotion?: PromotionPiece): GuidedResult {
    if (!this.playerTurn) return { kind: 'wait' }
    const validation = this.game.validate(from, to, promotion)
    if (validation.kind !== 'legal') return validation
    const uci = from + to + (promotion ?? '')
    this.practice.attempt(this.moves, uci === this.expected?.uci)
    if (uci !== this.expected?.uci) {
      const alternative = this.lesson.lines.find((line) =>
        line.steps.some((step) => step.fromKey === this.expected?.fromKey && step.uci === uci),
      )
      return alternative
        ? { kind: 'alternative-line', lineId: alternative.id, title: alternative.title }
        : { kind: 'outside-line' }
    }
    return this.advance()
  }

  reply(): GuidedResult {
    if (this.complete || this.playerTurn) return { kind: 'wait' }
    return this.advance()
  }

  private advance(): GuidedResult {
    const step = this.expected
    if (!step) throw new Error('No taught continuation')
    const result = this.game.play(
      step.uci.slice(0, 2),
      step.uci.slice(2, 4),
      step.uci[4] as 'q' | 'r' | 'b' | 'n' | undefined,
    )
    if (result.kind !== 'played') throw new Error('Invalid taught move')
    this.moves.push(step.uci)
    return { kind: 'accepted', move: result.move }
  }

  hint(): string {
    if (!this.playerTurn || !this.expected) return ''
    this.hints.add(this.ply)
    this.practice.hint(this.moves)
    return this.expected.hint
  }

  /** Take back the last player decision and its automatic reply. */
  undo(): void {
    if (!this.canUndo) return
    this.practice.undo()
    do {
      this.game.undo()
      this.moves.pop()
    } while (this.moves.length && this.game.turn !== this.playingColor)
  }

  get canUndo(): boolean {
    const firstTurn = this.line.steps[0]?.fromKey.split(' ')[1]
    return this.moves.length > (firstTurn === this.playingColor ? 0 : 1)
  }

  restart(): void {
    this.game.restart()
    this.moves.length = 0
    this.hints.clear()
    this.practice.restart()
  }

  hintSquares(): [Square, Square] | undefined {
    const uci = this.expected?.uci
    return uci ? [uci.slice(0, 2) as Square, uci.slice(2, 4) as Square] : undefined
  }
}
