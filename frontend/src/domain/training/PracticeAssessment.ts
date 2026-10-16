import type { PlayingColor } from '../../api/lessons'

export interface PracticeAssessment {
  version: 1
  usedHint: boolean
  usedRetry: boolean
  usedUndo: boolean
  decisions: { ply: number; attempts: number; hinted: boolean }[]
}

/** Attempt-local counters only; abandoned contexts never become final-position evidence. */
export class PracticeAssessmentTracker {
  private readonly contexts = new Map<string, { attempts: number; hinted: boolean }>()
  private usedHint = false
  private usedRetry = false
  private usedUndo = false

  constructor(
    private readonly firstTurn: PlayingColor,
    private readonly playingColor: PlayingColor,
  ) {}

  attempt(prefix: readonly string[], accepted: boolean): void {
    const context = this.context(prefix)
    context.attempts = Math.min(64, context.attempts + 1)
    if (!accepted) this.usedRetry = true
  }

  hint(prefix: readonly string[]): void {
    this.context(prefix).hinted = true
    this.usedHint = true
  }

  undo(): void {
    this.usedUndo = true
  }

  restart(): void {
    this.contexts.clear()
    this.usedHint = false
    this.usedRetry = false
    this.usedUndo = false
  }

  /** Called only for a validated completed path; every player decision must have been accepted. */
  assessment(path: readonly string[]): PracticeAssessment {
    const decisions: PracticeAssessment['decisions'] = []
    for (let ply = this.firstTurn === this.playingColor ? 0 : 1; ply < path.length; ply += 2) {
      const context = this.contexts.get(path.slice(0, ply).join(' '))
      if (!context || context.attempts < 1) throw new Error('Missing accepted player decision')
      decisions.push({ ply, ...context })
    }
    return {
      version: 1,
      usedHint: this.usedHint,
      usedRetry: this.usedRetry,
      usedUndo: this.usedUndo,
      decisions,
    }
  }

  private context(prefix: readonly string[]) {
    const key = prefix.join(' ')
    let context = this.contexts.get(key)
    if (!context) {
      // At most 32 selected finite paths, each with at most 64 prefix contexts.
      if (prefix.length >= 64 || this.contexts.size >= 2048)
        throw new Error('Practice context exceeds the finite lesson scope')
      context = { attempts: 0, hinted: false }
      this.contexts.set(key, context)
    }
    return context
  }
}
