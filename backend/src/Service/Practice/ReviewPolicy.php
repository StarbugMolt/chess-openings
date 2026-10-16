<?php

declare(strict_types=1);

namespace App\Service\Practice;

/** @phpstan-type ReviewState array{stage: int, lastAssessedAt: ?\DateTimeImmutable, lastOutcome: 'unknown'|'assisted'|'recalled', nextReviewAt: ?\DateTimeImmutable, beforeStage: int, beforeDue: ?\DateTimeImmutable, bucketAssisted: bool} */
final class ReviewPolicy
{
    private const DAYS = [1, 3, 7, 14, 30];

    /** @return ReviewState */
    public function initial(): array
    {
        return ['stage' => 0, 'lastAssessedAt' => null, 'lastOutcome' => 'unknown', 'nextReviewAt' => null, 'beforeStage' => 0, 'beforeDue' => null, 'bucketAssisted' => false];
    }

    /**
     * Fold timestamp-ordered events using constant memory. Same-second events
     * share one bucket; an assisted event always overrides its clean peers.
     *
     * @param ReviewState $state
     *
     * @return ReviewState
     */
    public function observe(array $state, \DateTimeImmutable $at, bool $unassisted): array
    {
        $utc = new \DateTimeZone('UTC');
        $at = new \DateTimeImmutable($at->setTimezone($utc)->format('Y-m-d H:i:s'), $utc);
        $previous = $state['lastAssessedAt'];
        if (null !== $previous && $at < $previous) {
            throw new \InvalidArgumentException('Review events must be ordered by their server timestamp.');
        }
        if (null === $previous || $at != $previous) {
            $state['beforeStage'] = $state['stage'];
            $state['beforeDue'] = $state['nextReviewAt'];
            $state['bucketAssisted'] = !$unassisted;
        } else {
            $state['bucketAssisted'] = $state['bucketAssisted'] || !$unassisted;
        }
        $state['lastAssessedAt'] = $at;
        if ($state['bucketAssisted']) {
            $state['stage'] = 0;
            $state['lastOutcome'] = 'assisted';
            $state['nextReviewAt'] = $at->add(new \DateInterval('PT10M'));
        } else {
            $state['lastOutcome'] = 'recalled';
            if (null === $state['beforeDue'] || $at >= $state['beforeDue']) {
                $state['stage'] = min(count(self::DAYS), $state['beforeStage'] + 1);
                $state['nextReviewAt'] = $at->add(new \DateInterval('P'.self::DAYS[$state['stage'] - 1].'D'));
            } else {
                $state['stage'] = $state['beforeStage'];
                $state['nextReviewAt'] = $state['beforeDue'];
            }
        }

        return $state;
    }

    /**
     * @param ReviewState $state
     *
     * @return array{lastAssessedAt: ?string, lastOutcome: 'unknown'|'assisted'|'recalled', nextReviewAt: ?string, reviewDue: bool}
     */
    public function result(array $state, ?\DateTimeImmutable $lastPractice, \DateTimeImmutable $now): array
    {
        $due = $state['nextReviewAt'] ?? $lastPractice;

        return ['lastAssessedAt' => $this->iso($state['lastAssessedAt']), 'lastOutcome' => $state['lastOutcome'], 'nextReviewAt' => $this->iso($due), 'reviewDue' => null !== $due && $due <= $now];
    }

    /**
     * @param iterable<array{at: \DateTimeImmutable, unassisted: bool}> $events
     *
     * @return array{lastAssessedAt: ?string, lastOutcome: 'unknown'|'assisted'|'recalled', nextReviewAt: ?string, reviewDue: bool}
     */
    public function schedule(iterable $events, ?\DateTimeImmutable $lastPractice, \DateTimeImmutable $now): array
    {
        $state = $this->initial();
        foreach ($events as $event) {
            $state = $this->observe($state, $event['at'], $event['unassisted']);
        }

        return $this->result($state, $lastPractice, $now);
    }

    private function iso(?\DateTimeImmutable $at): ?string
    {
        return $at?->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ATOM);
    }
}
