import { readFileSync } from 'node:fs'
import { Chess, DEFAULT_POSITION } from 'chess.js'
import { describe, expect, it } from 'vitest'
import type { Lesson, LessonLine, PlayingColor } from '../../src/api/lessons'
import type { PromotionPiece } from '../../src/domain/chess/BrowserChessGame'
import {
  VariationTrainer,
  type VariationEvidence,
} from '../../src/domain/training/VariationTrainer'

const lessons = JSON.parse(
  readFileSync(new URL('../fixtures/lessons.json', import.meta.url), 'utf8'),
) as Lesson[]
const italian = lessons.find((lesson) => lesson.id === 'italian')!
const caroKann = lessons.find((lesson) => lesson.id === 'caro-kann')!
const ids = (lesson: Lesson) => lesson.lines.map((line) => line.id)

function line(id: string, moves: string[], startFen = DEFAULT_POSITION): LessonLine {
  const chess = new Chess(startFen)
  const key = () => chess.fen().split(' ').slice(0, 4).join(' ')
  return {
    id,
    title: id,
    description: '',
    steps: moves.map((uci) => {
      const fromKey = key()
      const move = chess.move({
        from: uci.slice(0, 2),
        to: uci.slice(2, 4),
        ...(uci[4] ? { promotion: uci[4] } : {}),
      })
      return {
        uci,
        san: move.san,
        fromKey,
        toKey: key(),
        hint: `Move ${uci.slice(0, 2)} to ${uci.slice(2, 4)}.`,
        explanation: `Original explanation for ${id}: ${move.san}.`,
      }
    }),
  }
}

function lesson(lines: LessonLine[], startFen = DEFAULT_POSITION): Lesson {
  return {
    id: 'fixture',
    title: 'Fixture course',
    revision: 1,
    introduction: '',
    recommendedColor: 'w',
    startFen,
    lines,
  }
}

function play(trainer: VariationTrainer, uci: string) {
  return trainer.play(uci.slice(0, 2), uci.slice(2, 4), uci[4] as PromotionPiece | undefined)
}

function finish(trainer: VariationTrainer): void {
  for (let count = 0; !trainer.complete && count < 64; count++) {
    const next = trainer.expected
    expect(next).toBeDefined()
    expect(trainer.playerTurn ? play(trainer, next!.uci).kind : trainer.reply().kind).toBe(
      'accepted',
    )
  }
  expect(trainer.complete).toBe(true)
}

describe('finite selected-path variation policy', () => {
  for (const course of lessons)
    for (const selectedLine of course.lines)
      for (const color of ['w', 'b'] as const)
        it(`replays only ${course.id}/${selectedLine.id} as ${color} through its authored endpoint`, () => {
          const trainer = new VariationTrainer(course, [selectedLine.id], color, 0)
          for (const step of selectedLine.steps) {
            expect(trainer.playerTurn ? play(trainer, step.uci).kind : trainer.reply().kind).toBe(
              'accepted',
            )
            expect(trainer.snapshot().fen.split(' ').slice(0, 4).join(' ')).toBe(step.toKey)
          }
          expect(trainer.complete).toBe(true)
          expect(trainer.completedLine?.id).toBe(selectedLine.id)
          expect(trainer.path).toEqual(selectedLine.steps.map((step) => step.uci))
          expect(trainer.assessment).toEqual({
            version: 1,
            usedHint: false,
            usedRetry: false,
            usedUndo: false,
            decisions: selectedLine.steps.flatMap((step, ply) =>
              step.fromKey.split(' ')[1] === color ? [{ ply, attempts: 1, hinted: false }] : [],
            ),
          })
          expect(trainer.continuations).toEqual([])
          expect(trainer.expected).toBeUndefined()
          expect(trainer.reply().kind).toBe('wait')
          expect(play(trainer, selectedLine.steps[0]!.uci).kind).toBe('wait')
        })

  for (const branch of italian.lines)
    it(`accepts the real Italian ${branch.id} player reply when both lines are selected`, () => {
      const trainer = new VariationTrainer(italian, ids(italian), 'w', 12)
      for (const step of branch.steps.slice(0, 6))
        if (trainer.playerTurn) expect(play(trainer, step.uci).kind).toBe('accepted')
        else expect(trainer.reply().kind).toBe('accepted')
      expect(trainer.continuations.map((step) => step.uci)).toEqual(['c2c3', 'd2d3'])
      expect(play(trainer, branch.steps[6]!.uci).kind).toBe('accepted')
      expect(trainer.eligibleLines.map((line) => line.id)).toEqual([branch.id])
      expect(trainer.reply().kind).toBe('accepted')
      expect(trainer.completedLine?.id).toBe(branch.id)
    })

  it('neutral rejection and illegal/opponent-turn input leave the board and branch state unchanged', () => {
    const trainer = new VariationTrainer(italian, ids(italian), 'w', 5)
    const before = trainer.snapshot()
    expect(trainer.play('e2', 'e5').kind).toBe('illegal')
    expect(trainer.play('d2', 'd4').kind).toBe('outside-line')
    expect(trainer.snapshot()).toEqual(before)
    expect(trainer.path).toEqual([])
    expect(trainer.eligibleLines.map((line) => line.id)).toEqual(ids(italian))
    expect(trainer.play('e2', 'e4').kind).toBe('accepted')
    expect(trainer.play('g1', 'f3').kind).toBe('wait')
    expect(trainer.reply().kind).toBe('accepted')
    expect(trainer.path).toEqual(['e2e4', 'e7e5'])
  })

  it('identifies a contextual unselected course line without following it', () => {
    const trainer = new VariationTrainer(italian, ['centre-plan'], 'w', 1)
    for (const step of italian.lines[0]!.steps.slice(0, 6))
      if (trainer.playerTurn) play(trainer, step.uci)
      else trainer.reply()
    const before = trainer.snapshot().fen
    expect(trainer.play('d2', 'd3')).toMatchObject({
      kind: 'alternative-line',
      lineId: 'quiet-plan',
    })
    expect(trainer.snapshot().fen).toBe(before)
    expect(trainer.play('c2', 'c3').kind).toBe('accepted')
  })

  it('uses seeded White branch choices while playing Black in the real Italian course', () => {
    const chosen = new Set<string>()
    for (let seed = 0; seed < 32; seed++) {
      const trainer = new VariationTrainer(italian, ids(italian), 'b', seed)
      expect(trainer.playerTurn).toBe(false)
      expect(trainer.hintSquares()).toBeUndefined()
      expect(trainer.hint()).toBe('')
      expect(trainer.hintCount).toBe(0)
      expect(trainer.reply().kind).toBe('accepted')
      expect(trainer.path).toEqual(['e2e4'])
      expect(trainer.canUndo).toBe(false)
      trainer.undo()
      expect(trainer.path).toEqual(['e2e4'])
      finish(trainer)
      chosen.add(trainer.completedLine!.id)
      expect(trainer.completedLine!.steps.map((step) => step.uci)).toEqual(trainer.path)
    }
    expect(chosen).toEqual(new Set(ids(italian)))
  })

  it('seeds both real Caro-Kann White branches and reaches castling only on the Panov line', () => {
    const chosen = new Set<string>()
    for (let seed = 0; seed < 32; seed++) {
      const trainer = new VariationTrainer(caroKann, ids(caroKann), 'b', seed)
      while (trainer.ply < 6) {
        if (trainer.playerTurn) play(trainer, trainer.expected!.uci)
        else trainer.reply()
      }
      expect(trainer.continuations.map((step) => step.uci)).toEqual(['f1d3', 'c2c4'])
      finish(trainer)
      const completed = trainer.completedLine!
      chosen.add(completed.id)
      expect(trainer.path).toEqual(completed.steps.map((step) => step.uci))
      if (completed.id === 'panov-development') {
        expect(trainer.ply).toBe(18)
        expect(trainer.previous?.san).toBe('O-O')
      } else expect(trainer.ply).toBe(10)
    }
    expect(chosen).toEqual(new Set(ids(caroKann)))
  })

  it('does not splice suffixes or credit a different selected prefix after a transposition', () => {
    const first = line('knight-first', ['g1f3', 'g8f6', 'g2g3', 'g7g6', 'f1g2', 'f8g7'])
    const second = line('pawn-first', ['g2g3', 'g7g6', 'g1f3', 'g8f6', 'd2d3', 'd7d6'])
    expect(first.steps[3]!.toKey).toBe(second.steps[3]!.toKey)
    const trainer = new VariationTrainer(lesson([first, second]), [first.id, second.id], 'w', 4, [
      {
        lessonId: 'fixture',
        revision: 1,
        lineId: second.id,
        playingColor: 'w',
        completions: 0,
        reviewDue: true,
      },
    ])
    for (const step of first.steps.slice(0, 4))
      if (trainer.playerTurn) play(trainer, step.uci)
      else trainer.reply()
    expect(trainer.eligibleLines.map((line) => line.id)).toEqual([first.id])
    expect(trainer.continuations.map((step) => step.uci)).toEqual(['f1g2'])
    const before = trainer.snapshot().fen
    expect(trainer.play('d2', 'd3').kind).toBe('outside-line')
    expect(trainer.snapshot().fen).toBe(before)
    expect(trainer.play('f1', 'g2').kind).toBe('accepted')
    expect(trainer.reply().kind).toBe('accepted')
    expect(trainer.completedLine?.id).toBe(first.id)
    expect(trainer.path).toEqual(first.steps.map((step) => step.uci))
  })

  it('extends a selected prefix endpoint, but ends there when the longer path is unselected', () => {
    const short = line('short', ['e2e4', 'e7e5'])
    const long = line('long', ['e2e4', 'e7e5', 'g1f3', 'b8c6'])
    const course = lesson([short, long])
    const trainer = new VariationTrainer(course, ['short', 'long'], 'w', 8)
    play(trainer, 'e2e4')
    trainer.reply()
    expect(trainer.complete).toBe(false)
    expect(trainer.completedLine).toBeUndefined()
    expect(trainer.eligibleLines.map((line) => line.id)).toEqual(['short', 'long'])
    finish(trainer)
    expect(trainer.completedLine?.id).toBe('long')
    const onlyShort = new VariationTrainer(course, ['short'], 'w', 8)
    finish(onlyShort)
    expect(onlyShort.ply).toBe(2)
    expect(onlyShort.completedLine?.id).toBe('short')
  })

  it('finishes repeated positions at the 64-ply authored cap without looping or truncating early', () => {
    const cycle = line(
      'finite-cycle',
      Array<string[]>(16).fill(['g1f3', 'g8f6', 'f3g1', 'f6g8']).flat(),
    )
    const trainer = new VariationTrainer(lesson([cycle]), [cycle.id], 'w', 0xffffffff)
    finish(trainer)
    expect(trainer.ply).toBe(64)
    expect(trainer.completedLine?.id).toBe(cycle.id)
    expect(trainer.snapshot().gameOver).toBe(true)
    expect(trainer.reply().kind).toBe('wait')
    expect(trainer.hintSquares()).toBeUndefined()
    expect(trainer.assessment?.decisions).toHaveLength(32)
    expect(trainer.assessment?.decisions.every((decision) => decision.attempts === 1)).toBe(true)
  })

  it('deduplicates random moves so duplicate line records cannot weight opponent choices', () => {
    const kingPawn = line('king-pawn', ['e2e4', 'e7e5'])
    const queenPawn = line('queen-pawn', ['d2d4', 'd7d5'])
    const original = lesson([kingPawn, queenPawn])
    const duplicate = lesson([kingPawn, { ...kingPawn, id: 'duplicate' }, queenPawn])
    const choices = new Set<string>()
    for (let seed = 0; seed < 64; seed++) {
      const first = new VariationTrainer(original, ids(original), 'b', seed)
      const second = new VariationTrainer(duplicate, ids(duplicate), 'b', seed)
      expect(second.continuations.map((step) => step.uci)).toEqual(['e2e4', 'd2d4'])
      first.reply()
      second.reply()
      expect(second.path).toEqual(first.path)
      choices.add(first.path[0]!)
    }
    expect(choices).toEqual(new Set(['e2e4', 'd2d4']))
  })

  it('uses lesson order for hints and stable duplicate endpoint attribution, independent of selection order', () => {
    const first = line('first', ['e2e4', 'e7e5'])
    const same = { ...first, id: 'same' }
    const trainer = new VariationTrainer(lesson([first, same]), ['same', 'first'], 'w', 3)
    expect(trainer.continuations).toHaveLength(1)
    expect(trainer.expected?.hint).toBe(first.steps[0]!.hint)
    finish(trainer)
    expect(trainer.eligibleLines.map((line) => line.id)).toEqual(['first', 'same'])
    expect(trainer.completedLine?.id).toBe('first')
  })

  it('restores candidate paths and seeded reply state when undoing a complete player decision', () => {
    const main = line('main', ['e2e4', 'e7e5', 'g1f3', 'b8c6'])
    const sicilian = line('sicilian', ['e2e4', 'c7c5', 'g1f3', 'd7d6'])
    const course = lesson([main, sicilian])
    const trainer = new VariationTrainer(course, ids(course), 'w', 0)
    play(trainer, 'e2e4')
    trainer.reply()
    const firstChoice = trainer.path
    expect(trainer.eligibleLines).toHaveLength(1)
    trainer.undo()
    expect(trainer.path).toEqual([])
    expect(trainer.eligibleLines).toHaveLength(2)
    expect(trainer.previous).toBeUndefined()
    expect(trainer.playerTurn).toBe(true)
    play(trainer, 'e2e4')
    trainer.reply()
    expect(trainer.path).toEqual(firstChoice)
    play(trainer, 'g1f3')
    trainer.undo()
    expect(trainer.path).toEqual(firstChoice)
    expect(trainer.playerTurn).toBe(true)
  })

  it('restores each seeded fork after multiple draws and keeps retries from consuming a choice', () => {
    const course = lesson([
      line('open-knight', ['e2e4', 'e7e5', 'g1f3', 'b8c6']),
      line('open-pawn', ['e2e4', 'e7e5', 'g1f3', 'd7d6']),
      line('sicilian-knight', ['e2e4', 'c7c5', 'g1f3', 'b8c6']),
      line('sicilian-pawn', ['e2e4', 'c7c5', 'g1f3', 'd7d6']),
    ])
    const trainer = new VariationTrainer(course, ids(course), 'w', 5)
    expect(trainer.play('d2', 'd4').kind).toBe('outside-line')
    play(trainer, 'e2e4')
    trainer.reply()
    const firstDecision = trainer.path
    expect(trainer.play('f1', 'c4').kind).toBe('outside-line')
    play(trainer, 'g1f3')
    trainer.reply()
    const original = trainer.path
    trainer.undo()
    expect(trainer.path).toEqual(firstDecision)
    play(trainer, 'g1f3')
    trainer.reply()
    expect(trainer.path).toEqual(original)
    trainer.undo()
    trainer.undo()
    finish(trainer)
    expect(trainer.path).toEqual(original)
    const cleanAttempt = new VariationTrainer(course, ids(course), 'w', 5)
    finish(cleanAttempt)
    expect(cleanAttempt.path).toEqual(original)
  })

  it('undoes a final automatic move and preserves the initial White reply when playing Black', () => {
    const trainer = new VariationTrainer(italian, ids(italian), 'b', 17)
    trainer.reply()
    const opening = trainer.path
    play(trainer, 'e7e5')
    trainer.reply()
    trainer.undo()
    expect(trainer.path).toEqual(opening)
    expect(trainer.canUndo).toBe(false)
    expect(trainer.playerTurn).toBe(true)
    finish(trainer)
    expect(trainer.complete).toBe(true)
    trainer.undo()
    expect(trainer.complete).toBe(false)
    expect(trainer.completedLine).toBeUndefined()
    expect(trainer.playerTurn).toBe(true)
  })

  it('counts a decision hint once, keeps original coaching and restores all attempt state on restart', () => {
    const trainer = new VariationTrainer(italian, ids(italian), 'b', 9)
    trainer.reply()
    expect(trainer.hint()).toBe(italian.lines[0]!.steps[1]!.hint)
    trainer.hint()
    expect(trainer.hintSquares()).toEqual(['e7', 'e5'])
    expect(trainer.hintCount).toBe(1)
    const firstPosition = trainer.snapshot().fen
    play(trainer, 'e7e5')
    trainer.reply()
    trainer.hint()
    expect(trainer.hintCount).toBe(2)
    trainer.undo()
    expect(trainer.snapshot().fen).toBe(firstPosition)
    trainer.hint()
    expect(trainer.hintCount).toBe(2)
    finish(trainer)
    const firstPath = trainer.path
    trainer.restart()
    expect(trainer.ply).toBe(0)
    expect(trainer.hintCount).toBe(0)
    expect(trainer.eligibleLines).toHaveLength(italian.lines.length)
    expect(trainer.canUndo).toBe(false)
    expect(trainer.playerTurn).toBe(false)
    expect(trainer.snapshot().fen).toBe(italian.startFen)
    finish(trainer)
    expect(trainer.path).toEqual(firstPath)
  })

  it('retains whole-attempt hint decisions when undo changes to a shorter completed path', () => {
    const long = line('long', ['e2e4', 'e7e5', 'g1f3', 'b8c6', 'f1c4', 'f8c5'])
    const short = line('short', ['d2d4', 'd7d5'])
    const trainer = new VariationTrainer(lesson([long, short]), ['long', 'short'], 'w', 2)
    for (let turn = 0; turn < 3; turn++) {
      trainer.hint()
      play(trainer, trainer.expected!.uci)
      trainer.reply()
    }
    expect(trainer.hintCount).toBe(3)
    trainer.undo()
    trainer.undo()
    trainer.undo()
    trainer.hint()
    expect(trainer.hintCount).toBe(3)
    play(trainer, 'd2d4')
    trainer.reply()
    expect(trainer.completedLine?.id).toBe('short')
    expect(trainer.path).toHaveLength(2)
    expect(trainer.hintCount).toBe(3)
  })

  it('requests a promotion before checking scope and accepts any selected taught promotion', () => {
    const startFen = '7k/P7/8/8/8/8/8/7K w - - 0 1'
    const queen = line('queen', ['a7a8q', 'h8h7'], startFen)
    const knight = line('knight', ['a7a8n', 'h8h7'], startFen)
    const course = lesson([queen, knight], startFen)
    for (const promotion of ['q', 'n'] as const) {
      const trainer = new VariationTrainer(course, ids(course), 'w', 1)
      expect(trainer.play('a7', 'a8')).toMatchObject({
        kind: 'promotion-required',
        choices: ['n', 'b', 'r', 'q'],
      })
      expect(trainer.ply).toBe(0)
      expect(trainer.play('a7', 'a8', 'r').kind).toBe('outside-line')
      expect(trainer.play('a7', 'a8', promotion).kind).toBe('accepted')
      expect(trainer.reply().kind).toBe('accepted')
      expect(trainer.completedLine?.id).toBe(promotion === 'q' ? 'queen' : 'knight')
      expect(trainer.assessment).toEqual({
        version: 1,
        usedHint: false,
        usedRetry: true,
        usedUndo: false,
        decisions: [{ ply: 0, attempts: 2, hinted: false }],
      })
    }
  })

  it('pins a private immutable lesson and selected identities across caller/cache mutation', () => {
    const original = structuredClone(italian)
    const scope = ids(original)
    const trainer = new VariationTrainer(original, scope, 'w', 0)
    original.revision++
    original.lines[0]!.steps[0]!.uci = 'd2d4'
    scope.length = 0
    expect(trainer.lesson.revision).toBe(italian.revision)
    expect(trainer.lineIds).toEqual(ids(italian))
    expect(trainer.play('e2', 'e4').kind).toBe('accepted')
    trainer.path.push('e7e6')
    trainer.eligibleLines.pop()
    trainer.continuations.pop()
    expect(trainer.path).toEqual(['e2e4'])
    expect(trainer.eligibleLines).toHaveLength(2)
    expect(() => {
      trainer.lesson.lines[0]!.steps[1]!.uci = 'e7e6'
    }).toThrow()
    expect(trainer.reply().kind).toBe('accepted')
    expect(trainer.path).toEqual(['e2e4', 'e7e5'])
  })
})

describe('variation final-path practice assessment', () => {
  it('keeps identical transposed positions separate when their full taught prefixes differ', () => {
    const first = line('knight-first', ['g1f3', 'g8f6', 'g2g3', 'g7g6', 'f1g2', 'f8g7'])
    const second = line('pawn-first', ['g2g3', 'g7g6', 'g1f3', 'g8f6', 'd2d3', 'd7d6'])
    expect(first.steps[4]!.fromKey).toBe(second.steps[4]!.fromKey)
    const course = lesson([first, second])
    const trainer = new VariationTrainer(course, ids(course), 'w', 0)
    for (const step of first.steps.slice(0, 4))
      if (trainer.playerTurn) play(trainer, step.uci)
      else trainer.reply()
    trainer.hint()
    expect(play(trainer, 'd2d3').kind).toBe('outside-line')
    finish(trainer)
    trainer.undo()
    trainer.undo()
    trainer.undo()
    for (const step of second.steps)
      if (trainer.playerTurn) expect(play(trainer, step.uci).kind).toBe('accepted')
      else expect(trainer.reply().kind).toBe('accepted')
    expect(trainer.completedLine?.id).toBe(second.id)
    expect(trainer.assessment?.decisions[2]).toEqual({ ply: 4, attempts: 1, hinted: false })
    expect(trainer.assessment).toMatchObject({
      usedHint: true,
      usedRetry: true,
      usedUndo: true,
    })
  })

  it('keeps abandoned branch assistance global without crediting the final branch positions', () => {
    const long = line('long', ['e2e4', 'e7e5', 'g1f3', 'b8c6', 'f1c4', 'f8c5'])
    const other = line('other', ['d2d4', 'd7d5', 'g1f3', 'g8f6'])
    const course = lesson([long, other])
    const trainer = new VariationTrainer(course, ids(course), 'w', 0)
    expect(trainer.assessment).toBeUndefined()
    expect(trainer.play('e2', 'e5').kind).toBe('illegal')
    play(trainer, 'e2e4')
    expect(play(trainer, 'g1f3').kind).toBe('wait')
    trainer.reply()
    trainer.hint()
    trainer.hint()
    expect(play(trainer, 'd2d4').kind).toBe('outside-line')
    play(trainer, 'g1f3')
    trainer.reply()
    trainer.hint()
    trainer.undo()
    trainer.undo()
    play(trainer, 'd2d4')
    trainer.reply()
    play(trainer, 'g1f3')
    trainer.reply()
    expect(trainer.completedLine?.id).toBe('other')
    expect(trainer.assessment).toEqual({
      version: 1,
      usedHint: true,
      usedRetry: true,
      usedUndo: true,
      decisions: [
        { ply: 0, attempts: 2, hinted: false },
        { ply: 2, attempts: 1, hinted: false },
      ],
    })
    expect(trainer.hintCount).toBe(2)
    trainer.restart()
    expect(trainer.assessment).toBeUndefined()
    finish(trainer)
    expect(trainer.assessment).toMatchObject({
      usedHint: false,
      usedRetry: false,
      usedUndo: false,
    })
    expect(trainer.assessment?.decisions.every((decision) => decision.attempts === 1)).toBe(true)
  })

  it('tracks repeated hints by full context while retaining the legacy per-ply hint count', () => {
    const first = line('first', ['e2e4', 'e7e5', 'g1f3', 'b8c6'])
    const second = line('second', ['d2d4', 'd7d5', 'g1f3', 'g8f6'])
    const course = lesson([first, second])
    const trainer = new VariationTrainer(course, ids(course), 'w', 0)
    play(trainer, 'e2e4')
    trainer.reply()
    trainer.hint()
    play(trainer, 'g1f3')
    trainer.reply()
    trainer.undo()
    trainer.undo()
    play(trainer, 'd2d4')
    trainer.reply()
    trainer.hint()
    trainer.hint()
    play(trainer, 'g1f3')
    trainer.reply()
    expect(trainer.hintCount).toBe(1)
    expect(trainer.assessment?.decisions).toEqual([
      { ply: 0, attempts: 2, hinted: false },
      { ply: 2, attempts: 1, hinted: true },
    ])
    const copy = trainer.assessment!
    copy.decisions[1]!.hinted = false
    expect(trainer.assessment?.decisions[1]?.hinted).toBe(true)
  })

  it('does not count automatic promotion or ineffective initial undo as player assistance', () => {
    const startFen = '7k/P7/8/8/8/8/8/7K w - - 0 1'
    const queen = line('queen', ['a7a8q', 'h8h7'], startFen)
    const trainer = new VariationTrainer(lesson([queen], startFen), ['queen'], 'b', 0)
    trainer.undo()
    expect(trainer.hint()).toBe('')
    trainer.reply()
    trainer.undo()
    play(trainer, 'h8h7')
    expect(trainer.assessment).toEqual({
      version: 1,
      usedHint: false,
      usedRetry: false,
      usedUndo: false,
      decisions: [{ ply: 1, attempts: 1, hinted: false }],
    })
  })

  it('bounds repeated legal retries while still accepting the supported move immediately afterwards', () => {
    const trainer = new VariationTrainer(italian, ids(italian), 'w', 1)
    for (let count = 0; count < 80; count++) expect(play(trainer, 'd2d4').kind).toBe('outside-line')
    finish(trainer)
    expect(trainer.assessment?.usedRetry).toBe(true)
    expect(trainer.assessment?.decisions[0]?.attempts).toBe(64)
    expect(
      trainer.assessment?.decisions.slice(1).every((decision) => decision.attempts === 1),
    ).toBe(true)
  })
})

describe('immutable progress-weighted supported replies', () => {
  const first = line('first', ['e2e4', 'e7e5'])
  const second = line('second', ['d2d4', 'd7d5'])
  const course = lesson([first, second])
  const evidence = (lineId: string, completions: number, reviewDue = false): VariationEvidence => ({
    lessonId: course.id,
    revision: course.revision,
    lineId,
    playingColor: 'b',
    completions,
    reviewDue,
  })

  it('favours an under-practised supported move and gives a due review additional priority', () => {
    const unweighted = new VariationTrainer(course, ids(course), 'b', 0)
    const weighted = new VariationTrainer(course, ids(course), 'b', 0, [evidence('first', 100)])
    const due = new VariationTrainer(course, ids(course), 'b', 0, [evidence('first', 100, true)])
    unweighted.reply()
    weighted.reply()
    due.reply()
    expect(unweighted.path).toEqual(['e2e4'])
    expect(weighted.path).toEqual(['d2d4'])
    expect(due.path).toEqual(['e2e4'])
  })

  it('preserves existing uniform seed results when all supported choices have equal priorities', () => {
    for (let seed = 0; seed < 32; seed++) {
      const original = new VariationTrainer(course, ids(course), 'b', seed)
      const equal = new VariationTrainer(course, ids(course), 'b', seed, [
        evidence('first', 4, true),
        evidence('second', 4, true),
      ])
      finish(original)
      finish(equal)
      expect(equal.path).toEqual(original.path)
    }
  })

  it('ignores old revisions, other accounts of the scope, invalid exposure and unknown lines', () => {
    const irrelevant = [
      { ...evidence('first', 100, true), lessonId: 'another-course' },
      { ...evidence('first', 100, true), revision: course.revision + 1 },
      { ...evidence('first', 100, true), playingColor: 'w' as const },
      evidence('missing', 100, true),
      evidence('first', -1, true),
      evidence('first', 1.5, true),
      evidence('first', Number.NaN, true),
      evidence('first', Number.POSITIVE_INFINITY, true),
    ]
    for (let seed = 0; seed < 16; seed++) {
      const expected = new VariationTrainer(course, ids(course), 'b', seed)
      const actual = new VariationTrainer(course, ids(course), 'b', seed, irrelevant)
      expected.reply()
      actual.reply()
      expect(actual.path).toEqual(expected.path)
    }
    const selected = new VariationTrainer(course, ['second'], 'b', 0, [evidence('first', 0, true)])
    selected.reply()
    expect(selected.path).toEqual(['d2d4'])
  })

  it('keeps valid legacy exposure when the optional review-due evidence is absent', () => {
    const incomplete = { ...evidence('first', 100) } as Partial<VariationEvidence>
    delete incomplete.reviewDue
    const trainer = new VariationTrainer(course, ids(course), 'b', 0, [
      incomplete as VariationEvidence,
    ])
    trainer.reply()
    expect(trainer.path).toEqual(['d2d4'])
  })

  it('uses the most-practised identical full path so an unpractised duplicate alias cannot bias it', () => {
    const duplicate = lesson([first, { ...first, id: 'alias' }, second])
    for (let seed = 0; seed < 32; seed++) {
      const expected = new VariationTrainer(course, ids(course), 'b', seed, [
        evidence('first', 100),
      ])
      const actual = new VariationTrainer(duplicate, ids(duplicate), 'b', seed, [
        evidence('first', 100),
        evidence('alias', 0, true),
      ])
      expected.reply()
      actual.reply()
      expect(actual.path).toEqual(expected.path)
    }
  })

  it('takes maximum supporting path priority rather than adding weights for the same next move', () => {
    const extra = line('extra', ['e2e4', 'c7c5'])
    const another = line('another', ['e2e4', 'e7e6'])
    const broader = lesson([first, extra, another, second])
    const expected = new VariationTrainer(course, ids(course), 'b', 0, [evidence('first', 100)])
    const actual = new VariationTrainer(broader, ids(broader), 'b', 0, [
      evidence('first', 100),
      evidence('extra', 100),
      evidence('another', 100),
    ])
    expected.reply()
    actual.reply()
    expect(actual.path).toEqual(['d2d4'])
    expect(actual.path).toEqual(expected.path)
  })

  it('freezes its progress snapshot and restores weighted branch/random state on undo and restart', () => {
    const forked = lesson([
      line('open-knight', ['e2e4', 'e7e5', 'g1f3', 'b8c6']),
      line('open-pawn', ['e2e4', 'e7e5', 'g1f3', 'd7d6']),
      line('sicilian-knight', ['e2e4', 'c7c5', 'g1f3', 'b8c6']),
      line('sicilian-pawn', ['e2e4', 'c7c5', 'g1f3', 'd7d6']),
    ])
    const weights = ids(forked).map((lineId, index) => ({
      ...evidence(lineId, index % 2 ? 100 : 0, index < 2),
      playingColor: 'w' as const,
    }))
    const trainer = new VariationTrainer(forked, ids(forked), 'w', 0, weights)
    weights[0]!.completions = 1000
    weights.length = 0
    expect(trainer.evidence).toHaveLength(4)
    expect(() => {
      ;(trainer.evidence[0] as VariationEvidence).completions = 1000
    }).toThrow()
    finish(trainer)
    const original = trainer.path
    trainer.undo()
    finish(trainer)
    expect(trainer.path).toEqual(original)
    trainer.undo()
    trainer.undo()
    finish(trainer)
    expect(trainer.path).toEqual(original)
    trainer.restart()
    finish(trainer)
    expect(trainer.path).toEqual(original)
    expect(trainer.assessment?.usedUndo).toBe(false)
  })
})

describe('variation scope and authored path validation', () => {
  for (const scope of [[], ['missing'], ['centre-plan', 'centre-plan'], ['']])
    it(`rejects invalid selected IDs ${JSON.stringify(scope)}`, () => {
      expect(() => new VariationTrainer(italian, scope, 'w', 1)).toThrow()
    })

  it('rejects ambiguous duplicate identities and selections above 32 lines', () => {
    const first = line('first', ['e2e4', 'e7e5'])
    const ambiguous = lesson([first, { ...first }])
    expect(() => new VariationTrainer(ambiguous, ['first'], 'w', 1)).toThrow()
    const many = lesson(
      Array.from({ length: 33 }, (_, index) => ({ ...first, id: `line-${index}` })),
    )
    expect(() => new VariationTrainer(many, ids(many), 'w', 1)).toThrow()
    expect(() => new VariationTrainer(many, ids(many).slice(0, 32), 'w', 1)).not.toThrow()
  })

  for (const seed of [-1, 0.5, 0x100000000, Number.NaN, Number.POSITIVE_INFINITY])
    it(`rejects non-uint32 seed ${seed}`, () => {
      expect(() => new VariationTrainer(italian, ids(italian), 'w', seed)).toThrow()
    })

  it('rejects unsupported colours', () => {
    expect(() => new VariationTrainer(italian, ids(italian), 'white' as PlayingColor, 1)).toThrow()
  })

  for (const corruption of ['fromKey', 'toKey', 'san', 'uci', 'illegal'] as const)
    it(`rejects selected paths with corrupted ${corruption} facts before any interaction`, () => {
      const invalid = structuredClone(italian)
      const step = invalid.lines[1]!.steps[0]!
      if (corruption === 'fromKey') step.fromKey = step.toKey
      else if (corruption === 'toKey') step.toKey = step.fromKey
      else if (corruption === 'san') step.san = 'd4'
      else if (corruption === 'uci') step.uci = 'not-uci'
      else step.uci = 'e2e5'
      expect(() => new VariationTrainer(invalid, ids(invalid), 'w', 0)).toThrow()
    })

  it('validates every selected path while leaving malformed unselected paths unavailable', () => {
    const course = structuredClone(italian)
    course.lines[1]!.steps[0]!.san = 'd4'
    const trainer = new VariationTrainer(course, [course.lines[0]!.id], 'w', 0)
    finish(trainer)
    expect(trainer.completedLine?.id).toBe('centre-plan')
  })

  it('rejects empty, single-ply and more than 64-ply selected lines', () => {
    const first = line('one', ['e2e4'])
    const empty = { ...first, id: 'empty', steps: [] }
    const tooLong = line('long', [
      ...Array<string[]>(16).fill(['g1f3', 'g8f6', 'f3g1', 'f6g8']).flat(),
      'g1f3',
    ])
    for (const invalid of [first, empty, tooLong])
      expect(() => new VariationTrainer(lesson([invalid]), [invalid.id], 'w', 1)).toThrow()
  })
})
