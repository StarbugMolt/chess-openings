<?php

declare(strict_types=1);

namespace App\Service\Practice;

use App\Service\Lesson\LessonReader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\TransactionIsolationLevel;

final readonly class PracticeReader
{
    public function __construct(private Connection $connection, private LessonReader $lessons, private PracticeProjector $projector)
    {
    }

    /** @return array<string, mixed> */
    public function read(int $userId): array
    {
        $previous = $this->connection->getTransactionIsolation();
        $changeIsolation = TransactionIsolationLevel::REPEATABLE_READ !== $previous;
        if ($changeIsolation) {
            $this->connection->setTransactionIsolation(TransactionIsolationLevel::REPEATABLE_READ);
        }
        try {
            // Publication pointers, retained paths and user rows share one read view.
            return $this->connection->transactional(function () use ($userId): array {
                $courses = $this->lessons->courses();
                // SELECT * deliberately also works before the proposed nullable
                // assessment column exists; its absent value means unknown recall.
                $rows = $this->connection->iterateAssociative('SELECT * FROM user_line_completion WHERE user_id=? ORDER BY lesson_id,revision,line_id,playing_color,completed_at,attempt_id', [$userId]);

                return $this->projector->project($courses, fn (string $id, int $revision): array => $this->lessons->lesson($id, $revision), $rows, new \DateTimeImmutable('now', new \DateTimeZone('UTC')));
            });
        } finally {
            if ($changeIsolation) {
                $this->connection->setTransactionIsolation($previous);
            }
        }
    }
}
