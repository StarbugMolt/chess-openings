import type { Lesson, PlayingColor } from '../../api/lessons'

interface ReviewSelection {
  lineId: string
  playingColor: PlayingColor
  notice: string
}

/** A saved review link may preselect a current line, but cannot migrate old practice. */
export function selectReview(
  lesson: Lesson,
  query: Readonly<Record<string, unknown>>,
): ReviewSelection {
  const fallback: ReviewSelection = {
    lineId: lesson.lines[0]?.id ?? '',
    playingColor: lesson.recommendedColor,
    notice: '',
  }
  if (!['line', 'color', 'revision'].some((key) => key in query)) return fallback
  const revision = query.revision
  if (typeof revision !== 'string' || !/^[1-9]\d{0,4}$/.test(revision))
    return {
      ...fallback,
      notice: 'This practice link is incomplete. Choose a line and your pieces below.',
    }
  if (Number(revision) !== lesson.revision)
    return {
      ...fallback,
      notice:
        'This course has been updated. Your earlier practice is kept; choose a line in the latest version below.',
    }
  if (
    typeof query.line !== 'string' ||
    !lesson.lines.some((line) => line.id === query.line) ||
    (query.color !== 'w' && query.color !== 'b')
  )
    return {
      ...fallback,
      notice: 'This practice link is incomplete. Choose a line and your pieces below.',
    }
  return { lineId: query.line, playingColor: query.color, notice: '' }
}
