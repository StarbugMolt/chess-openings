import { describe, expect, it } from 'vitest'
import { readFileSync } from 'node:fs'
import { GuidedTrainer } from '../../src/domain/training/GuidedTrainer'
import type { Lesson } from '../../src/api/lessons'
import { Chess } from 'chess.js'
const lessons = JSON.parse(
  readFileSync(new URL('../fixtures/lessons.json', import.meta.url), 'utf8'),
) as Lesson[]
const italian = lessons.find((lesson) => lesson.id === 'italian')!

describe('finite guided lesson policy', () => {
  for (const lesson of lessons)
    for (const line of lesson.lines)
      for (const color of ['w', 'b'] as const) {
        it(`replays the PHP-authored ${lesson.id}/${line.id} as ${color} through its finite endpoint`, () => {
          const trainer = new GuidedTrainer(lesson, line.id, color)
          for (const step of line.steps) {
            const result = trainer.playerTurn
              ? trainer.play(step.uci.slice(0, 2), step.uci.slice(2, 4))
              : trainer.reply()
            expect(result.kind).toBe('accepted')
            expect(trainer.snapshot().fen.split(' ').slice(0, 4).join(' ')).toBe(step.toKey)
          }
          expect(trainer.complete).toBe(true)
          expect(trainer.path).toEqual(line.steps.map((step) => step.uci))
          expect(trainer.assessment).toEqual({
            version: 1,
            usedHint: false,
            usedRetry: false,
            usedUndo: false,
            decisions: line.steps.flatMap((step, ply) =>
              step.fromKey.split(' ')[1] === color ? [{ ply, attempts: 1, hinted: false }] : [],
            ),
          })
          expect(trainer.reply().kind).toBe('wait')
        })
      }
  it('does not advance a strict c3 line for the legal taught d3 alternative', () => {
    const trainer = new GuidedTrainer(italian, 'centre-plan', 'w')
    for (const step of trainer.line.steps.slice(0, 6)) {
      if (trainer.playerTurn) trainer.play(step.uci.slice(0, 2), step.uci.slice(2, 4))
      else trainer.reply()
    }
    const before = trainer.snapshot().fen
    expect(trainer.play('d2', 'd3')).toMatchObject({
      kind: 'alternative-line',
      lineId: 'quiet-plan',
    })
    expect(trainer.play('d2', 'd4').kind).toBe('outside-line')
    expect(trainer.snapshot().fen).toBe(before)
    expect(trainer.line.id).toBe('centre-plan')
    expect(trainer.play('c2', 'c3').kind).toBe('accepted')
  })
  it('rejects illegal moves and clicks during the opponent turn without changing state', () => {
    const trainer = new GuidedTrainer(italian, 'centre-plan', 'w')
    expect(trainer.play('e2', 'e5').kind).toBe('illegal')
    trainer.play('e2', 'e4')
    expect(trainer.play('g1', 'f3').kind).toBe('wait')
    expect(trainer.ply).toBe(1)
  })
  it('takes back the last player decision together with the automatic reply', () => {
    const trainer = new GuidedTrainer(italian, 'centre-plan', 'w')
    trainer.play('e2', 'e4')
    trainer.reply()
    trainer.play('g1', 'f3')
    trainer.reply()
    trainer.undo()
    expect(trainer.path).toEqual(['e2e4', 'e7e5'])
    expect(trainer.playerTurn).toBe(true)
  })
  it('preserves the initial automatic White move when Black has made no decision to undo', () => {
    const trainer = new GuidedTrainer(italian, 'centre-plan', 'b')
    trainer.reply()
    expect(trainer.canUndo).toBe(false)
    trainer.undo()
    expect(trainer.path).toEqual(['e2e4'])
    expect(trainer.playerTurn).toBe(true)
  })
  it('asks for promotion before evaluating lesson scope and accepts the taught choice', () => {
    const startFen = '7k/P7/8/8/8/8/8/7K w - - 0 1'
    const chess = new Chess(startFen)
    const key = () => chess.fen().split(' ').slice(0, 4).join(' ')
    const steps = ['a7a8q', 'h8h7'].map((uci) => {
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
        hint: 'Promote your pawn.',
        explanation: 'Choose the taught promotion.',
      }
    })
    const lesson: Lesson = {
      id: 'promotion',
      revision: 1,
      recommendedColor: 'w',
      title: 'Promotion',
      introduction: '',
      startFen,
      lines: [{ id: 'queen', title: 'Queen promotion', description: '', steps }],
    }
    const trainer = new GuidedTrainer(lesson, 'queen', 'w')
    expect(trainer.play('a7', 'a8')).toMatchObject({
      kind: 'promotion-required',
      choices: ['n', 'b', 'r', 'q'],
    })
    expect(trainer.ply).toBe(0)
    expect(trainer.play('a7', 'a8', 'n').kind).toBe('outside-line')
    expect(trainer.play('a7', 'a8', 'q').kind).toBe('accepted')
    expect(trainer.reply().kind).toBe('accepted')
    expect(trainer.complete).toBe(true)
    expect(trainer.assessment).toEqual({
      version: 1,
      usedHint: false,
      usedRetry: true,
      usedUndo: false,
      decisions: [{ ply: 0, attempts: 2, hinted: false }],
    })
  })
  it('counts a repeated hint once at the same decision and supplies its board squares', () => {
    const trainer = new GuidedTrainer(italian, 'centre-plan', 'w')
    expect(trainer.hint()).toContain('e2 to e4')
    trainer.hint()
    expect(trainer.hintCount).toBe(1)
    expect(trainer.hintSquares()).toEqual(['e2', 'e4'])
    trainer.play('e2', 'e4')
    trainer.reply()
    trainer.hint()
    expect(trainer.hintCount).toBe(2)
  })
  it('does not reinterpret an active trainer when a newer lesson payload replaces the UI cache', () => {
    const original = structuredClone(italian)
    const trainer = new GuidedTrainer(original, 'centre-plan', 'w')
    const updated = { ...original, revision: original.revision + 1 }
    expect(updated.revision).not.toBe(trainer.lesson.revision)
    trainer.play('e2', 'e4')
    trainer.reply()
    expect(trainer.path).toEqual(['e2e4', 'e7e5'])
  })

  it('measures only complete attempts and counts taught alternatives as neutral exercise retries', () => {
    const trainer = new GuidedTrainer(italian, 'centre-plan', 'w')
    expect(trainer.assessment).toBeUndefined()
    expect(trainer.play('e2', 'e5').kind).toBe('illegal')
    for (const step of trainer.line.steps.slice(0, 6)) {
      if (trainer.playerTurn) trainer.play(step.uci.slice(0, 2), step.uci.slice(2, 4))
      else {
        expect(trainer.play('d2', 'd4').kind).toBe('wait')
        trainer.reply()
      }
    }
    expect(trainer.play('d2', 'd3').kind).toBe('alternative-line')
    expect(trainer.play('d2', 'd4').kind).toBe('outside-line')
    trainer.hint()
    trainer.hint()
    trainer.play('c2', 'c3')
    expect(trainer.assessment).toBeUndefined()
    trainer.reply()
    expect(trainer.assessment).toEqual({
      version: 1,
      usedHint: true,
      usedRetry: true,
      usedUndo: false,
      decisions: [
        { ply: 0, attempts: 1, hinted: false },
        { ply: 2, attempts: 1, hinted: false },
        { ply: 4, attempts: 1, hinted: false },
        { ply: 6, attempts: 3, hinted: true },
      ],
    })
    expect(trainer.hintCount).toBe(1)
  })

  it('marks only effective undo, counts accepted replays and clears assessment state on restart', () => {
    const trainer = new GuidedTrainer(italian, 'centre-plan', 'b')
    trainer.undo()
    trainer.reply()
    trainer.undo()
    trainer.play('e7', 'e5')
    trainer.reply()
    trainer.undo()
    while (!trainer.complete) {
      const step = trainer.expected!
      if (trainer.playerTurn) trainer.play(step.uci.slice(0, 2), step.uci.slice(2, 4))
      else trainer.reply()
    }
    expect(trainer.assessment).toMatchObject({ usedUndo: true, usedRetry: false })
    expect(trainer.assessment?.decisions[0]).toEqual({ ply: 1, attempts: 2, hinted: false })
    trainer.restart()
    expect(trainer.ply).toBe(0)
    expect(trainer.assessment).toBeUndefined()
    expect(trainer.hintCount).toBe(0)
    trainer.reply()
    trainer.undo()
    while (!trainer.complete) {
      const step = trainer.expected!
      if (trainer.playerTurn) trainer.play(step.uci.slice(0, 2), step.uci.slice(2, 4))
      else trainer.reply()
    }
    expect(trainer.assessment).toMatchObject({
      usedHint: false,
      usedRetry: false,
      usedUndo: false,
    })
    expect(trainer.assessment?.decisions.every((decision) => decision.attempts === 1)).toBe(true)
  })
})
