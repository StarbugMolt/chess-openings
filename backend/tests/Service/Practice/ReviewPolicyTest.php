<?php

declare(strict_types=1);

namespace App\Tests\Service\Practice;

use App\Service\Practice\ReviewPolicy;
use PHPUnit\Framework\TestCase;

final class ReviewPolicyTest extends TestCase
{
    private function at(string $time): \DateTimeImmutable
    {
        return new \DateTimeImmutable($time, new \DateTimeZone('UTC'));
    }

    public function testNewPracticeIsNotOverdueAndLegacyPracticeIsImmediatelyAvailableWithUnknownRecall(): void
    {
        $policy = new ReviewPolicy();
        $now = $this->at('2026-10-15 10:00:00');
        self::assertSame(['lastAssessedAt' => null, 'lastOutcome' => 'unknown', 'nextReviewAt' => null, 'reviewDue' => false], $policy->schedule([], null, $now));
        self::assertSame(['lastAssessedAt' => null, 'lastOutcome' => 'unknown', 'nextReviewAt' => '2026-10-14T10:00:00+00:00', 'reviewDue' => true], $policy->schedule([], $this->at('2026-10-14 10:00:00'), $now));
    }

    public function testFirstRecallAndExactDueBoundary(): void
    {
        $policy = new ReviewPolicy();
        $events = [['at' => $this->at('2026-10-15 10:00:00'), 'unassisted' => true]];
        $early = $policy->schedule($events, null, $this->at('2026-10-16 09:59:59'));
        self::assertSame('2026-10-16T10:00:00+00:00', $early['nextReviewAt']);
        self::assertFalse($early['reviewDue']);
        self::assertTrue($policy->schedule($events, null, $this->at('2026-10-16 10:00:00'))['reviewDue']);
    }

    public function testDueRecallAdvancesThroughEveryIntervalAndCapsAtThirtyDays(): void
    {
        $policy = new ReviewPolicy();
        $state = $policy->initial();
        $at = $this->at('2026-10-15 10:00:00');
        foreach ([1, 3, 7, 14, 30, 30] as $index => $days) {
            $state = $policy->observe($state, $at, true);
            self::assertSame(min(5, $index + 1), $state['stage']);
            self::assertEquals($at->modify('+'.$days.' days'), $state['nextReviewAt']);
            $at = $state['nextReviewAt'];
        }
    }

    public function testEarlyCleanPracticeDoesNotPostponeOrAdvanceReview(): void
    {
        $policy = new ReviewPolicy();
        $state = $policy->observe($policy->initial(), $this->at('2026-10-15 10:00:00'), true);
        $early = $policy->observe($state, $this->at('2026-10-15 11:00:00'), true);
        self::assertSame(1, $early['stage']);
        self::assertEquals($state['nextReviewAt'], $early['nextReviewAt']);
    }

    public function testAssistanceResetsAndEarlyCleanWaitsForTheTenMinuteReview(): void
    {
        $policy = new ReviewPolicy();
        $state = $policy->observe($policy->initial(), $this->at('2026-10-15 10:00:00'), false);
        self::assertSame(0, $state['stage']);
        self::assertSame('2026-10-15T10:10:00+00:00', $state['nextReviewAt']->format(DATE_ATOM));
        $early = $policy->observe($state, $this->at('2026-10-15 10:09:59'), true);
        self::assertSame(0, $early['stage']);
        self::assertEquals($state['nextReviewAt'], $early['nextReviewAt']);
        $due = $policy->observe($early, $this->at('2026-10-15 10:10:00'), true);
        self::assertSame(1, $due['stage']);
        self::assertSame('2026-10-16T10:10:00+00:00', $due['nextReviewAt']->format(DATE_ATOM));
    }

    public function testSameSecondAssistanceDominatesIndependentlyOfAttemptOrder(): void
    {
        $policy = new ReviewPolicy();
        $at = $this->at('2026-10-15 10:00:00');
        $first = $policy->schedule([['at' => $at, 'unassisted' => true], ['at' => $at, 'unassisted' => false]], null, $at);
        $second = $policy->schedule([['at' => $at, 'unassisted' => false], ['at' => $at, 'unassisted' => true]], null, $at);
        self::assertSame($first, $second);
        self::assertSame('assisted', $first['lastOutcome']);
        self::assertSame('2026-10-15T10:10:00+00:00', $first['nextReviewAt']);
    }

    public function testSameSecondCleanEventsAdvanceOnlyOnceAndUseUtc(): void
    {
        $policy = new ReviewPolicy();
        $at = $this->at('2026-10-15T12:00:00.123+02:00');
        $state = $policy->observe($policy->initial(), $at, true);
        $state = $policy->observe($state, $this->at('2026-10-15T12:00:00.999+02:00'), true);
        self::assertSame(1, $state['stage']);
        self::assertSame('2026-10-16T10:00:00+00:00', $state['nextReviewAt']->format(DATE_ATOM));
    }

    public function testOutOfOrderEvidenceIsRejectedInsteadOfChangingTheSchedule(): void
    {
        $policy = new ReviewPolicy();
        $state = $policy->observe($policy->initial(), $this->at('2026-10-15 10:00:00'), true);
        $this->expectException(\InvalidArgumentException::class);
        $policy->observe($state, $this->at('2026-10-14 10:00:00'), false);
    }
}
