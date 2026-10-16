import { describe, expect, it } from 'vitest'
import { PracticeAssessmentTracker } from '../../src/domain/training/PracticeAssessment'

describe('bounded final-path practice assessment', () => {
  it('includes only player decisions, with opponent moves providing context rather than attempts', () => {
    const white = new PracticeAssessmentTracker('w', 'w')
    white.attempt([], true)
    white.attempt(['e2e4', 'e7e5'], true)
    expect(white.assessment(['e2e4', 'e7e5', 'g1f3', 'b8c6'])).toEqual({
      version: 1,
      usedHint: false,
      usedRetry: false,
      usedUndo: false,
      decisions: [
        { ply: 0, attempts: 1, hinted: false },
        { ply: 2, attempts: 1, hinted: false },
      ],
    })
    const black = new PracticeAssessmentTracker('w', 'b')
    black.attempt(['e2e4'], true)
    expect(black.assessment(['e2e4', 'e7e5']).decisions).toEqual([
      { ply: 1, attempts: 1, hinted: false },
    ])
    const blackToMove = new PracticeAssessmentTracker('b', 'b')
    blackToMove.attempt([], true)
    expect(blackToMove.assessment(['g8f6', 'g1f3']).decisions[0]?.ply).toBe(0)
  })

  it('preserves whole-attempt flags without attributing abandoned contexts to the final path', () => {
    const tracker = new PracticeAssessmentTracker('w', 'w')
    tracker.attempt([], true)
    tracker.hint(['e2e4', 'e7e5'])
    tracker.hint(['e2e4', 'e7e5'])
    tracker.attempt(['e2e4', 'e7e5'], false)
    tracker.attempt(['e2e4', 'e7e5'], true)
    tracker.undo()
    tracker.attempt([], true)
    tracker.attempt(['d2d4', 'd7d5'], true)
    expect(tracker.assessment(['d2d4', 'd7d5', 'g1f3', 'g8f6'])).toEqual({
      version: 1,
      usedHint: true,
      usedRetry: true,
      usedUndo: true,
      decisions: [
        { ply: 0, attempts: 2, hinted: false },
        { ply: 2, attempts: 1, hinted: false },
      ],
    })
  })

  it('retains hint and accepted attempt counts when the exact decision context is replayed', () => {
    const tracker = new PracticeAssessmentTracker('w', 'w')
    tracker.hint([])
    tracker.attempt([], true)
    tracker.undo()
    tracker.hint([])
    tracker.attempt([], true)
    expect(tracker.assessment(['e2e4', 'e7e5'])).toMatchObject({
      usedHint: true,
      usedRetry: false,
      usedUndo: true,
      decisions: [{ ply: 0, attempts: 2, hinted: true }],
    })
  })

  it('saturates retries at 64 and returns detached summaries', () => {
    const tracker = new PracticeAssessmentTracker('w', 'w')
    for (let count = 0; count < 100; count++) tracker.attempt([], false)
    tracker.attempt([], true)
    const summary = tracker.assessment(['e2e4', 'e7e5'])
    expect(summary.decisions).toEqual([{ ply: 0, attempts: 64, hinted: false }])
    summary.decisions[0]!.attempts = 1
    summary.decisions.push({ ply: 2, attempts: 1, hinted: true })
    expect(tracker.assessment(['e2e4', 'e7e5']).decisions).toEqual([
      { ply: 0, attempts: 64, hinted: false },
    ])
  })

  it('resets hints, retries, undo and context counts for a new attempt', () => {
    const tracker = new PracticeAssessmentTracker('w', 'w')
    tracker.hint([])
    tracker.attempt([], false)
    tracker.attempt([], true)
    tracker.undo()
    tracker.restart()
    tracker.attempt([], true)
    expect(tracker.assessment(['d2d4', 'd7d5'])).toEqual({
      version: 1,
      usedHint: false,
      usedRetry: false,
      usedUndo: false,
      decisions: [{ ply: 0, attempts: 1, hinted: false }],
    })
  })
})
