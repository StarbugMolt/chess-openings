<?php

declare(strict_types=1);

namespace App\Tests\Service\Practice;

use App\Service\OpeningImport\PChessMoveReplayer;
use App\Service\Practice\AssessmentValidator;
use App\Service\Practice\PracticeProjector;
use App\Service\Practice\ReviewPolicy;
use PHPUnit\Framework\TestCase;

final class PracticeProjectorTest extends TestCase
{
    private function lesson(string $id = 'italian', int $revision = 1): array
    {
        $lines = [];
        foreach (['main' => ['e4', 'e5', 'Nf3', 'Nc6'], 'other' => ['e4', 'e5', 'Bc4', 'Nc6']] as $lineId => $sans) {
            $replay = (new PChessMoveReplayer())->replay($sans);
            $from = $replay->initialKey;
            $steps = [];
            foreach ($replay->moves as $move) {
                $steps[] = ['uci' => $move->uci, 'san' => $move->san, 'fromKey' => $from, 'hint' => 'Hint: '.$move->san];
                $from = $move->key;
            }
            $lines[] = ['id' => $lineId, 'title' => $lineId.' title', 'steps' => $steps];
        }

        return ['id' => $id, 'revision' => $revision, 'title' => 'Course '.$id, 'lines' => $lines];
    }

    private function course(string $id = 'italian', int $revision = 1): array
    {
        return ['id' => $id, 'revision' => $revision, 'title' => 'Course '.$id, 'recommendedColor' => 'w', 'lineCount' => 2];
    }

    private function assessment(string $color = 'w'): array
    {
        return ['version' => 1, 'usedHint' => false, 'usedRetry' => false, 'usedUndo' => false, 'decisions' => array_map(static fn (int $ply): array => ['ply' => $ply, 'attempts' => 1, 'hinted' => false], 'w' === $color ? [0, 2] : [1, 3])];
    }

    private function row(?array $assessment = null, string $color = 'w', string $line = 'main', int $revision = 1, string $time = '2026-10-15 10:00:00', string $id = 'italian'): array
    {
        return ['lesson_id' => $id, 'revision' => $revision, 'line_id' => $line, 'playing_color' => $color, 'hint_count' => ($assessment['usedHint'] ?? false) ? 1 : 0, 'assessment_json' => null === $assessment ? null : json_encode($assessment, JSON_THROW_ON_ERROR), 'completed_at' => $time];
    }

    private function project(iterable $rows, int $revision = 1): array
    {
        return (new PracticeProjector(new AssessmentValidator(), new ReviewPolicy()))->project([$this->course(revision: $revision)], fn (string $id, int $revision): array => $this->lesson($id, $revision), $rows, new \DateTimeImmutable('2026-10-15 12:00:00', new \DateTimeZone('UTC')));
    }

    public function testEmptyStateListsBothColoursWithoutInventingPracticeOrDueWork(): void
    {
        $result = $this->project([]);
        self::assertSame('2026-10-15T12:00:00+00:00', $result['generatedAt']);
        self::assertSame(2, $result['courses'][0]['lineCount']);
        self::assertCount(4, $result['courses'][0]['lines']);
        foreach ($result['courses'][0]['lines'] as $line) {
            self::assertSame(0, $line['completions']);
            self::assertSame('unknown', $line['lastOutcome']);
            self::assertNull($line['nextReviewAt']);
            self::assertFalse($line['reviewDue']);
        }
        self::assertSame([0, 2], array_column($result['courses'][0]['lines'][0]['positions'], 'ply'));
        self::assertSame([1, 3], array_column($result['courses'][0]['lines'][1]['positions'], 'ply'));
        self::assertSame([], $result['reviewQueue']);
        self::assertSame([], $result['history']);
    }

    public function testLegacyExposureHasUnknownRecallAndNoFabricatedPositionEvidence(): void
    {
        $result = $this->project([$this->row()]);
        $line = $result['courses'][0]['lines'][0];
        self::assertSame(1, $line['completions']);
        self::assertSame(0, $line['assessedCompletions']);
        self::assertSame(0, $line['unassistedCompletions']);
        self::assertNull($line['lastAssessedAt']);
        self::assertSame('unknown', $line['lastOutcome']);
        self::assertTrue($line['reviewDue']);
        self::assertSame('2026-10-15T10:00:00+00:00', $line['nextReviewAt']);
        self::assertSame([0, 0], array_column($line['positions'], 'practices'));
        self::assertSame([['lessonId' => 'italian', 'revision' => 1, 'lineId' => 'main', 'playingColor' => 'w']], $result['reviewQueue']);
    }

    public function testPreMigrationRowsWithoutAnAssessmentColumnRemainLegacy(): void
    {
        $legacy = $this->row();
        unset($legacy['assessment_json']);
        self::assertSame($this->project([$this->row()]), $this->project([$legacy]));
    }

    public function testMeasuredRecallOnlyCreditsTheExactAuthoredLineAndColour(): void
    {
        $result = $this->project([$this->row($this->assessment())]);
        [$white, $black, $other] = $result['courses'][0]['lines'];
        self::assertSame(1, $white['assessedCompletions']);
        self::assertSame(1, $white['unassistedCompletions']);
        self::assertSame('recalled', $white['lastOutcome']);
        self::assertSame('2026-10-16T10:00:00+00:00', $white['nextReviewAt']);
        self::assertSame([1, 1], array_column($white['positions'], 'recalled'));
        self::assertSame(0, $black['completions']);
        self::assertSame(0, $other['completions']);
        self::assertSame([0, 0], array_column($other['positions'], 'practices'));
    }

    public function testBlackHintAndRetryStayOnTheirOwnDecisionContexts(): void
    {
        $assessment = [...$this->assessment('b'), 'usedHint' => true, 'usedRetry' => true];
        $assessment['decisions'][0] = ['ply' => 1, 'attempts' => 3, 'hinted' => true];
        $result = $this->project([$this->row($assessment, 'b')]);
        [$white, $black] = $result['courses'][0]['lines'];
        self::assertSame(0, $white['completions']);
        self::assertSame('assisted', $black['lastOutcome']);
        self::assertSame('2026-10-15T10:10:00+00:00', $black['nextReviewAt']);
        self::assertSame([1, 1], array_column($black['positions'], 'practices'));
        self::assertSame([0, 1], array_column($black['positions'], 'recalled'));
        self::assertSame([1, 0], array_column($black['positions'], 'hints'));
        self::assertSame([2, 0], array_column($black['positions'], 'retries'));
    }

    public function testUndoBlocksAllPositionRecallWithoutInventingWhereAbandonedHintsOccurred(): void
    {
        $assessment = [...$this->assessment(), 'usedUndo' => true, 'usedHint' => true];
        $assessment['decisions'][0]['attempts'] = 2;
        $line = $this->project([$this->row($assessment)])['courses'][0]['lines'][0];
        self::assertSame(0, $line['unassistedCompletions']);
        self::assertSame([0, 0], array_column($line['positions'], 'recalled'));
        self::assertSame([0, 0], array_column($line['positions'], 'hints'));
        self::assertSame([1, 0], array_column($line['positions'], 'retries'));
    }

    public function testEarlierRevisionHistoryDoesNotInflateCurrentCoverageAndPathsLoadOnce(): void
    {
        $loads = [];
        $result = (new PracticeProjector(new AssessmentValidator(), new ReviewPolicy()))->project([$this->course(revision: 2)], function (string $id, int $revision) use (&$loads): array {
            $loads[] = $id.'/'.$revision;

            return $this->lesson($id, $revision);
        }, [$this->row($this->assessment()), $this->row(time: '2026-10-15 11:00:00')], new \DateTimeImmutable('2026-10-15T12:00:00Z'));
        self::assertSame(['italian/2', 'italian/1'], $loads);
        self::assertSame([0, 0, 0, 0], array_column($result['courses'][0]['lines'], 'completions'));
        self::assertSame([], $result['reviewQueue']);
        self::assertCount(1, $result['history']);
        self::assertSame(1, $result['history'][0]['revision']);
        self::assertSame(2, $result['history'][0]['completions']);
        self::assertSame(1, $result['history'][0]['assessedCompletions']);
        self::assertSame(1, $result['history'][0]['unassistedCompletions']);
    }

    public function testSameSecondAssistedOutcomeIsIndependentOfAttemptUuidOrdering(): void
    {
        $clean = $this->row($this->assessment());
        $assisted = $this->row([...$this->assessment(), 'usedUndo' => true]);
        $first = $this->project([$clean, $assisted]);
        $second = $this->project([$assisted, $clean]);
        self::assertSame($first, $second);
        self::assertSame('assisted', $first['courses'][0]['lines'][0]['lastOutcome']);
        self::assertSame(2, $first['courses'][0]['lines'][0]['assessedCompletions']);
        self::assertSame(1, $first['courses'][0]['lines'][0]['unassistedCompletions']);
    }

    public function testLargeStreamingLedgerIsNotTruncatedAndReusesOneRetainedPath(): void
    {
        $loads = 0;
        $record = $this->row($this->assessment());
        $rows = static function () use ($record): \Generator {
            foreach (range(1, 10000) as $_) {
                yield $record;
            }
        };
        $result = (new PracticeProjector(new AssessmentValidator(), new ReviewPolicy()))->project([$this->course()], function (string $id, int $revision) use (&$loads): array {
            ++$loads;

            return $this->lesson($id, $revision);
        }, $rows(), new \DateTimeImmutable('2026-10-15T12:00:00Z'));
        $line = $result['courses'][0]['lines'][0];
        self::assertSame(1, $loads);
        self::assertSame(10000, $line['completions']);
        self::assertSame(10000, $line['assessedCompletions']);
        self::assertSame([10000, 10000], array_column($line['positions'], 'practices'));
        self::assertSame('2026-10-16T10:00:00+00:00', $line['nextReviewAt']);
    }

    public function testReviewQueueIsBoundedAndOrderedByDueThenCourseLineAndColour(): void
    {
        $courses = [];
        $rows = [];
        foreach (range(0, 11) as $index) {
            $id = 'course-'.$index;
            $courses[] = $this->course($id);
            foreach (['main', 'other'] as $line) {
                foreach (['w', 'b'] as $color) {
                    $rows[] = $this->row(color: $color, line: $line, id: $id, time: 11 === $index ? '2026-10-14 10:00:00' : '2026-10-15 10:00:00');
                }
            }
        }
        $result = (new PracticeProjector(new AssessmentValidator(), new ReviewPolicy()))->project($courses, fn (string $id, int $revision): array => $this->lesson($id, $revision), $rows, new \DateTimeImmutable('2026-10-15T12:00:00Z'));
        self::assertCount(20, $result['reviewQueue']);
        self::assertSame('course-11', $result['reviewQueue'][0]['lessonId']);
        self::assertSame('course-0', $result['reviewQueue'][4]['lessonId']);
        self::assertSame(['w', 'b', 'w', 'b'], array_column(array_slice($result['reviewQueue'], 0, 4), 'playingColor'));
        self::assertCount(12, $result['courses']);
    }

    public function testStoredCorruptEvidenceFailsRatherThanInventingRecall(): void
    {
        $row = [...$this->row(), 'assessment_json' => '{invalid'];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Stored practice assessment is invalid');
        $this->project([$row]);
    }

    public function testUnknownRetainedLineFailsInsteadOfSilentlyDroppingHistory(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('unknown retained taught line');
        $this->project([$this->row(line: 'missing')]);
    }
}
