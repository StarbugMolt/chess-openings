<?php

declare(strict_types=1);

namespace App\Service\Practice;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @phpstan-import-type ReviewState from ReviewPolicy
 *
 * @phpstan-type Step array{uci: string, san: string, fromKey: string, hint: string}
 * @phpstan-type Line array{id: string, title: string, steps: list<Step>}
 * @phpstan-type Lesson array{id: string, revision: int, title: string, lines: list<Line>}
 * @phpstan-type Course array{id: string, title: string, revision: int, recommendedColor: string, lineCount: int}
 * @phpstan-type Position array{ply: int, san: string, hint: string, practices: int, recalled: int, hints: int, retries: int}
 * @phpstan-type Practice array{lessonId: string, title: string, revision: int, lineId: string, lineTitle: string, playingColor: string, steps: list<Step>, completions: int, assessedCompletions: int, unassistedCompletions: int, lastPractisedAt: ?\DateTimeImmutable, review: ReviewState, positions: array<int, Position>}
 */
final readonly class PracticeProjector
{
    public function __construct(private AssessmentValidator $assessments, private ReviewPolicy $reviews)
    {
    }

    /**
     * Rows must already be scoped to the authenticated user and ordered by
     * completed_at within each lesson/revision/line/colour identity. Fold them
     * without retaining raw summaries or an unbounded event list.
     *
     * @param list<Course>                                $courses
     * @param callable(string, int): array<string, mixed> $readLesson
     * @param iterable<array<string, mixed>>              $rows
     *
     * @return array<string, mixed>
     */
    public function project(array $courses, callable $readLesson, iterable $rows, \DateTimeImmutable $now): array
    {
        /** @var array<string, Lesson> $lessons */
        $lessons = [];
        $load = function (string $id, int $revision) use (&$lessons, $readLesson): array {
            $key = $id.'\0'.$revision;

            return $lessons[$key] ??= $this->lesson($readLesson($id, $revision), $id, $revision);
        };
        /** @var array<string, Practice> $practices */
        $practices = [];
        $courseKeys = [];
        $currentRevisions = [];
        foreach ($courses as $course) {
            $lesson = $load($course['id'], $course['revision']);
            $currentRevisions[$course['id']] = $course['revision'];
            $keys = [];
            foreach ($lesson['lines'] as $line) {
                foreach (['w', 'b'] as $color) {
                    $key = $this->identity($lesson['id'], $lesson['revision'], $line['id'], $color);
                    $practices[$key] = $this->empty($lesson, $line, $color);
                    $keys[] = $key;
                }
            }
            $courseKeys[] = $keys;
        }
        foreach ($rows as $row) {
            $id = (string) $row['lesson_id'];
            $revision = (int) $row['revision'];
            $lineId = (string) $row['line_id'];
            $color = (string) $row['playing_color'];
            $key = $this->identity($id, $revision, $lineId, $color);
            if (!isset($practices[$key])) {
                $lesson = $load($id, $revision);
                $line = null;
                foreach ($lesson['lines'] as $candidate) {
                    if ($candidate['id'] === $lineId) {
                        $line = $candidate;
                        break;
                    }
                }
                if (null === $line || !in_array($color, ['w', 'b'], true)) {
                    throw new \RuntimeException('Practice references an unknown retained taught line or colour.');
                }
                $practices[$key] = $this->empty($lesson, $line, $color);
            }
            $practice = &$practices[$key];
            $at = new \DateTimeImmutable((string) $row['completed_at'], new \DateTimeZone('UTC'));
            ++$practice['completions'];
            if (null === $practice['lastPractisedAt'] || $at > $practice['lastPractisedAt']) {
                $practice['lastPractisedAt'] = $at;
            }
            if (null !== ($row['assessment_json'] ?? null)) {
                try {
                    $assessment = $this->assessments->validate(json_decode((string) $row['assessment_json'], true, flags: JSON_THROW_ON_ERROR), $practice['steps'], $color, (int) $row['hint_count']);
                } catch (\JsonException|BadRequestHttpException $error) {
                    throw new \RuntimeException('Stored practice assessment is invalid.', previous: $error);
                }
                ++$practice['assessedCompletions'];
                $unassisted = !$assessment['usedHint'] && !$assessment['usedRetry'] && !$assessment['usedUndo'];
                if ($unassisted) {
                    ++$practice['unassistedCompletions'];
                }
                $practice['review'] = $this->reviews->observe($practice['review'], $at, $unassisted);
                foreach ($assessment['decisions'] as $decision) {
                    $position = &$practice['positions'][$decision['ply']];
                    ++$position['practices'];
                    if (1 === $decision['attempts'] && !$decision['hinted'] && !$assessment['usedUndo']) {
                        ++$position['recalled'];
                    }
                    $position['hints'] += (int) $decision['hinted'];
                    $position['retries'] += $decision['attempts'] - 1;
                    unset($position);
                }
            }
            unset($practice);
        }
        $projectedCourses = [];
        $queue = [];
        $ordinal = 0;
        foreach ($courses as $index => $course) {
            $lines = [];
            foreach ($courseKeys[$index] as $key) {
                $practice = $practices[$key];
                $line = $this->present($practice, $now);
                $lines[] = $line;
                if ($line['reviewDue']) {
                    $queue[] = ['lessonId' => $practice['lessonId'], 'revision' => $practice['revision'], 'lineId' => $practice['lineId'], 'playingColor' => $practice['playingColor'], 'due' => $line['nextReviewAt'], 'ordinal' => $ordinal];
                }
                ++$ordinal;
            }
            $projectedCourses[] = ['id' => $course['id'], 'title' => $course['title'], 'revision' => $course['revision'], 'recommendedColor' => $course['recommendedColor'], 'lineCount' => intdiv(count($lines), 2), 'lines' => $lines];
        }
        usort($queue, static fn (array $a, array $b): int => [$a['due'], $a['ordinal']] <=> [$b['due'], $b['ordinal']]);
        $queue = array_map(static fn (array $entry): array => ['lessonId' => $entry['lessonId'], 'revision' => $entry['revision'], 'lineId' => $entry['lineId'], 'playingColor' => $entry['playingColor']], array_slice($queue, 0, 20));
        $history = [];
        foreach ($practices as $practice) {
            if (0 === $practice['completions'] || ($currentRevisions[$practice['lessonId']] ?? null) === $practice['revision']) {
                continue;
            }
            $history[] = ['lessonId' => $practice['lessonId'], 'title' => $practice['title'], 'revision' => $practice['revision'], 'lineId' => $practice['lineId'], 'lineTitle' => $practice['lineTitle'], 'playingColor' => $practice['playingColor'], 'completions' => $practice['completions'], 'assessedCompletions' => $practice['assessedCompletions'], 'unassistedCompletions' => $practice['unassistedCompletions'], 'lastPractisedAt' => $this->iso($practice['lastPractisedAt'])];
        }
        usort($history, static fn (array $a, array $b): int => [$a['lessonId'], -$a['revision'], $a['lineId'], $a['playingColor']] <=> [$b['lessonId'], -$b['revision'], $b['lineId'], $b['playingColor']]);

        return ['generatedAt' => $this->iso($now), 'courses' => $projectedCourses, 'reviewQueue' => $queue, 'history' => $history];
    }

    /** @param Practice $practice
     *
     * @return array<string, mixed>
     */
    private function present(array $practice, \DateTimeImmutable $now): array
    {
        return ['lineId' => $practice['lineId'], 'title' => $practice['lineTitle'], 'playingColor' => $practice['playingColor'], 'completions' => $practice['completions'], 'assessedCompletions' => $practice['assessedCompletions'], 'unassistedCompletions' => $practice['unassistedCompletions'], 'lastPractisedAt' => $this->iso($practice['lastPractisedAt']), ...$this->reviews->result($practice['review'], $practice['lastPractisedAt'], $now), 'positions' => array_values($practice['positions'])];
    }

    /** @param Lesson $lesson
     * @param Line $line
     *
     * @return Practice
     */
    private function empty(array $lesson, array $line, string $color): array
    {
        $positions = [];
        foreach ($line['steps'] as $ply => $step) {
            if ($color === (explode(' ', $step['fromKey'])[1] ?? null)) {
                $positions[$ply] = ['ply' => $ply, 'san' => $step['san'], 'hint' => $step['hint'], 'practices' => 0, 'recalled' => 0, 'hints' => 0, 'retries' => 0];
            }
        }

        return ['lessonId' => $lesson['id'], 'title' => $lesson['title'], 'revision' => $lesson['revision'], 'lineId' => $line['id'], 'lineTitle' => $line['title'], 'playingColor' => $color, 'steps' => $line['steps'], 'completions' => 0, 'assessedCompletions' => 0, 'unassistedCompletions' => 0, 'lastPractisedAt' => null, 'review' => $this->reviews->initial(), 'positions' => $positions];
    }

    private function identity(string $lesson, int $revision, string $line, string $color): string
    {
        return implode('\0', [$lesson, $revision, $line, $color]);
    }

    /** @param array<string, mixed> $value
     *
     * @return Lesson
     */
    private function lesson(array $value, string $id, int $revision): array
    {
        if (($value['id'] ?? null) !== $id || ($value['revision'] ?? null) !== $revision || !is_string($value['title'] ?? null) || !is_array($value['lines'] ?? null) || !array_is_list($value['lines']) || [] === $value['lines']) {
            throw new \RuntimeException('Invalid retained lesson metadata.');
        }
        $lines = [];
        foreach ($value['lines'] as $line) {
            if (!is_array($line) || !is_string($line['id'] ?? null) || !is_string($line['title'] ?? null) || !is_array($line['steps'] ?? null) || !array_is_list($line['steps']) || count($line['steps']) < 2 || count($line['steps']) > 64) {
                throw new \RuntimeException('Invalid retained lesson line.');
            }
            $steps = [];
            foreach ($line['steps'] as $step) {
                if (!is_array($step) || !is_string($step['uci'] ?? null) || !is_string($step['san'] ?? null) || !is_string($step['fromKey'] ?? null) || !is_string($step['hint'] ?? null)) {
                    throw new \RuntimeException('Invalid retained lesson decision.');
                }
                $steps[] = ['uci' => $step['uci'], 'san' => $step['san'], 'fromKey' => $step['fromKey'], 'hint' => $step['hint']];
            }
            $lines[] = ['id' => $line['id'], 'title' => $line['title'], 'steps' => $steps];
        }

        return ['id' => $id, 'revision' => $revision, 'title' => $value['title'], 'lines' => $lines];
    }

    private function iso(?\DateTimeImmutable $at): ?string
    {
        return $at?->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ATOM);
    }
}
