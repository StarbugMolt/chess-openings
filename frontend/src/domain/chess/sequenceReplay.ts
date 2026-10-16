import { Chess, DEFAULT_POSITION } from 'chess.js'

export interface SequenceFrame {
  /** Full FEN after this frame's move; frame 0 is the starting position. */
  fen: string
  /** Numbered SAN for display, for example "3. Bb5" or "3... a6"; empty for the start. */
  label: string
  lastMove?: [string, string]
  check: boolean
}

/** Replays a reference PGN movetext ("1. e4 e5 2. Nf3") into board frames, or returns undefined if any move is illegal. */
export function replaySequence(pgn: string): SequenceFrame[] | undefined {
  const tokens = pgn
    .trim()
    .split(/\s+/)
    .filter((token) => token !== '' && !/^\d+\.+$/.test(token))
  if (tokens.length === 0 || tokens.length > 64) return undefined
  const game = new Chess(DEFAULT_POSITION)
  const frames: SequenceFrame[] = [{ fen: game.fen(), label: '', check: false }]
  for (const [index, san] of tokens.entries()) {
    let move
    try {
      move = game.move(san)
    } catch {
      return undefined
    }
    const number = Math.floor(index / 2) + 1
    frames.push({
      fen: game.fen(),
      label: index % 2 === 0 ? `${number}. ${move.san}` : `${number}... ${move.san}`,
      lastMove: [move.from, move.to],
      check: game.inCheck(),
    })
  }
  return frames
}
