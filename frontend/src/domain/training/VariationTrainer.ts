import { Chess, type Move, type Square } from 'chess.js'
import {
  BrowserChessGame,
  type ChessSnapshot,
  type PromotionPiece,
} from '../chess/BrowserChessGame'
import type { Lesson, LessonLine, LessonStep, PlayingColor } from '../../api/lessons'
import { PracticeAssessmentTracker, type PracticeAssessment } from './PracticeAssessment'

export interface VariationEvidence {
  lessonId: string
  revision: number
  lineId: string
  playingColor: PlayingColor
  completions: number
  reviewDue: boolean
}

export type VariationResult =
  | { kind: 'accepted'; move: Move }
  | { kind: 'illegal' | 'outside-line' | 'wait' }
  | { kind: 'alternative-line'; lineId: string; title: string }
  | { kind: 'promotion-required'; choices: PromotionPiece[] }

const MAX_PLIES = 64
const canonicalKey = (game: Chess): string => game.fen().split(' ').slice(0, 4).join(' ')

/** Copy and freeze the revision so cache replacements or callers cannot alter an attempt. */
function snapshotLesson(lesson: Lesson): Lesson {
  const lines = lesson.lines.map((line) => {
    const steps = line.steps.map((step) => Object.freeze({ ...step }))
    Object.freeze(steps)
    return Object.freeze({ ...line, steps })
  })
  Object.freeze(lines)
  return Object.freeze({ ...lesson, lines })
}

/** Finite selected authored paths; shared chess positions never enable a different prefix. */
export class VariationTrainer {
  readonly lesson: Lesson
  readonly lineIds: string[]
  readonly evidence: readonly Readonly<VariationEvidence>[]
  private readonly game: BrowserChessGame
  private readonly selected: LessonLine[]
  private readonly moves: string[] = []
  private readonly hints = new Set<number>()
  private readonly randomStates: number[]
  private readonly priorities = new Map<string, number>()
  private readonly practice: PracticeAssessmentTracker

  constructor(
    lesson: Lesson,
    lineIds: string[],
    readonly playingColor: PlayingColor,
    readonly seed: number,
    evidence: readonly VariationEvidence[] = [],
  ) {
    if (playingColor !== 'w' && playingColor !== 'b') throw new Error('Invalid playing color')
    if (!Number.isInteger(seed) || seed < 0 || seed > 0xffffffff)
      throw new Error('Invalid variation seed')
    if (
      !Array.isArray(lineIds) ||
      lineIds.length === 0 ||
      lineIds.length > 32 ||
      lineIds.some((id) => typeof id !== 'string' || !id.length) ||
      new Set(lineIds).size !== lineIds.length
    )
      throw new Error('Invalid selected lines')
    const ids = new Set(lineIds)
    const selected = lesson.lines.filter((line) => ids.has(line.id))
    if (selected.length !== ids.size || new Set(selected.map((line) => line.id)).size !== ids.size)
      throw new Error('Unknown or ambiguous selected line')
    for (const line of selected) {
      if (line.steps.length < 2 || line.steps.length > MAX_PLIES)
        throw new Error('Invalid taught line length')
      const replay = new Chess(lesson.startFen)
      for (const step of line.steps) {
        if (!/^[a-h][1-8][a-h][1-8][qrbn]?$/.test(step.uci)) throw new Error('Invalid taught move')
        if (canonicalKey(replay) !== step.fromKey) throw new Error('Discontinuous lesson')
        const move = replay.move({
          from: step.uci.slice(0, 2),
          to: step.uci.slice(2, 4),
          ...(step.uci[4] ? { promotion: step.uci[4] } : {}),
        })
        if (move.san !== step.san || canonicalKey(replay) !== step.toKey)
          throw new Error('Invalid taught move')
      }
    }
    this.lesson = snapshotLesson(lesson)
    this.lineIds = [...lineIds]
    Object.freeze(this.lineIds)
    this.selected = this.lesson.lines.filter((line) => ids.has(line.id))
    this.game = new BrowserChessGame(this.lesson.startFen)
    this.randomStates = [seed]
    this.practice = new PracticeAssessmentTracker(this.game.turn, playingColor)
    this.evidence = Object.freeze(
      evidence
        .filter((row) => row && typeof row === 'object')
        .map((row) => Object.freeze({ ...row })),
    )
    this.setPriorities()
  }

  get ply(): number {
    return this.moves.length
  }
  get eligibleLines(): LessonLine[] {
    return this.selected.filter((line) => this.hasPrefix(line))
  }
  get continuations(): LessonStep[] {
    if (this.ply >= MAX_PLIES) return []
    const distinct = new Map<string, LessonStep>()
    for (const line of this.eligibleLines) {
      const step = line.steps[this.ply]
      if (step && !distinct.has(step.uci)) distinct.set(step.uci, step)
    }
    return [...distinct.values()]
  }
  get complete(): boolean {
    return this.continuations.length === 0
  }
  get playerTurn(): boolean {
    return !this.complete && this.game.turn === this.playingColor
  }
  get expected(): LessonStep | undefined {
    return this.continuations[0]
  }
  get previous(): LessonStep | undefined {
    return this.eligibleLines[0]?.steps[this.ply - 1]
  }
  get completedLine(): LessonLine | undefined {
    return this.complete
      ? this.eligibleLines.find((line) => line.steps.length === this.ply)
      : undefined
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
  get canUndo(): boolean {
    const firstTurn = this.selected[0]?.steps[0]?.fromKey.split(' ')[1]
    return this.ply > (firstTurn === this.playingColor ? 0 : 1)
  }
  snapshot(): ChessSnapshot {
    return this.game.snapshot()
  }

  play(from: string, to: string, promotion?: PromotionPiece): VariationResult {
    if (!this.playerTurn) return { kind: 'wait' }
    const validation = this.game.validate(from, to, promotion)
    if (validation.kind !== 'legal') return validation
    const uci = from + to + (promotion ?? '')
    const step = this.continuations.find((step) => step.uci === uci)
    this.practice.attempt(this.moves, !!step)
    if (step) return this.advance(step)
    const alternative = this.lesson.lines.find(
      (line) => this.hasPrefix(line) && line.steps[this.ply]?.uci === uci,
    )
    return alternative
      ? { kind: 'alternative-line', lineId: alternative.id, title: alternative.title }
      : { kind: 'outside-line' }
  }

  reply(): VariationResult {
    if (this.complete || this.playerTurn) return { kind: 'wait' }
    const choices = this.continuations
    const state = this.randomStates[this.ply] ?? this.seed
    if (choices.length === 1) return this.advance(choices[0]!, state)

    // Mulberry32 accepts every uint32 seed, including zero. Draw once per distinct choice.
    const nextState = (state + 0x6d2b79f5) >>> 0
    let mixed = Math.imul(nextState ^ (nextState >>> 15), nextState | 1)
    mixed ^= mixed + Math.imul(mixed ^ (mixed >>> 7), mixed | 61)
    const random = ((mixed ^ (mixed >>> 14)) >>> 0) / 0x100000000
    const weights = choices.map((step) =>
      Math.max(
        ...this.eligibleLines
          .filter((line) => line.steps[this.ply]?.uci === step.uci)
          .map((line) => this.priorities.get(line.id) ?? 9),
      ),
    )
    let index = Math.floor(random * choices.length)
    if (!weights.every((weight) => weight === weights[0])) {
      let ticket = random * weights.reduce((sum, weight) => sum + weight, 0)
      index = weights.findIndex((weight) => (ticket -= weight) < 0)
    }
    const step = choices[index]
    if (!step) throw new Error('No taught continuation')
    return this.advance(step, nextState)
  }

  hint(): string {
    if (!this.playerTurn || !this.expected) return ''
    this.hints.add(this.ply)
    this.practice.hint(this.moves)
    return this.expected.hint
  }
  hintSquares(): [Square, Square] | undefined {
    const uci = this.playerTurn ? this.expected?.uci : undefined
    return uci ? [uci.slice(0, 2) as Square, uci.slice(2, 4) as Square] : undefined
  }

  /** Return to the last player decision, keeping Black's initial automatic White move. */
  undo(): void {
    if (!this.canUndo) return
    this.practice.undo()
    do {
      this.game.undo()
      this.moves.pop()
      this.randomStates.pop()
    } while (this.ply && this.game.turn !== this.playingColor)
  }

  restart(): void {
    this.game.restart()
    this.moves.length = 0
    this.randomStates.splice(1)
    this.hints.clear()
    this.practice.restart()
  }

  private setPriorities(): void {
    const groups = new Map<string, LessonLine[]>()
    for (const line of this.selected) {
      const path = line.steps.map((step) => step.uci).join(' ')
      const group = groups.get(path) ?? []
      group.push(line)
      groups.set(path, group)
    }
    for (const group of groups.values()) {
      const identities = new Set(group.map((line) => line.id))
      const evidence = this.evidence.filter(
        (row) =>
          row.lessonId === this.lesson.id &&
          row.revision === this.lesson.revision &&
          row.playingColor === this.playingColor &&
          identities.has(row.lineId) &&
          Number.isSafeInteger(row.completions) &&
          row.completions >= 0,
      )
      const completions = Math.max(0, ...evidence.map((row) => row.completions))
      const due = evidence.some((row) => row.completions === completions && row.reviewDue === true)
      const priority = 1 + Math.floor(8 / (1 + completions)) + (due ? 4 : 0)
      for (const line of group) this.priorities.set(line.id, priority)
    }
  }

  private hasPrefix(line: LessonLine): boolean {
    return (
      line.steps.length >= this.ply &&
      this.moves.every((uci, index) => line.steps[index]?.uci === uci)
    )
  }

  private advance(
    step: LessonStep,
    randomState = this.randomStates[this.ply] ?? this.seed,
  ): VariationResult {
    const result = this.game.play(
      step.uci.slice(0, 2),
      step.uci.slice(2, 4),
      step.uci[4] as PromotionPiece | undefined,
    )
    if (result.kind !== 'played') throw new Error('Invalid taught move')
    this.moves.push(step.uci)
    this.randomStates.push(randomState)
    return { kind: 'accepted', move: result.move }
  }
}
