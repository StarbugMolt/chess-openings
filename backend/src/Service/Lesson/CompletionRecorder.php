<?php

declare(strict_types=1);

namespace App\Service\Lesson;

use App\Service\Practice\AssessmentValidator;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final readonly class CompletionRecorder
{
    public function __construct(private Connection $connection, private LessonReader $lessons, private AssessmentValidator $assessments)
    {
    }

    /** @param array<string, mixed> $data */
    public function record(int $userId, array $data): void
    {
        $id = $data['lessonId'] ?? null;
        $revision = $data['revision'] ?? null;
        $lineId = $data['lineId'] ?? null;
        $color = $data['playingColor'] ?? null;
        $attempt = $data['attemptId'] ?? null;
        $hints = $data['hintCount'] ?? null;
        $path = $data['path'] ?? null;
        $mode = array_key_exists('mode', $data) ? $data['mode'] : 'guided';
        if (!is_string($id) || strlen($id) > 48 || !is_int($revision) || $revision < 1 || $revision > 65535 || !is_string($lineId) || strlen($lineId) > 48
            || !in_array($color, ['w', 'b'], true) || !is_string($attempt) || 1 !== preg_match('/\A[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}\z/', $attempt)
            || !is_int($hints) || $hints < 0 || $hints > 64 || !is_array($path) || !array_is_list($path) || count($path) > 64
            || !in_array($mode, ['guided', 'variation'], true)) {
            throw new BadRequestHttpException();
        }
        $scope = null;
        if ('variation' === $mode) {
            $scope = $data['selectedLineIds'] ?? null;
            if (!is_array($scope) || !array_is_list($scope) || [] === $scope || count($scope) > 32) {
                throw new BadRequestHttpException();
            }
            foreach ($scope as $selectedId) {
                if (!is_string($selectedId) || '' === $selectedId || strlen($selectedId) > 48) {
                    throw new BadRequestHttpException();
                }
            }
            if (count(array_unique($scope)) !== count($scope) || !in_array($lineId, $scope, true)) {
                throw new BadRequestHttpException();
            }
        }
        $lesson = $this->lessons->lesson($id, $revision);
        $selected = null;
        $scopedPaths = [];
        foreach ($lesson['lines'] as $line) {
            if ($line['id'] === $lineId) {
                $selected = $line;
            }
            if (null !== $scope && in_array($line['id'], $scope, true)) {
                $scopedPaths[] = array_column($line['steps'], 'uci');
            }
        }
        if (null === $selected || $path !== array_column($selected['steps'], 'uci') || 'guided' === $mode && $hints > count($path)) {
            throw new BadRequestHttpException();
        }
        if (null !== $scope) {
            if (count($scopedPaths) !== count($scope)) {
                throw new BadRequestHttpException();
            }
            // Keep whole authored prefixes compatible. Shared graph positions never
            // permit splicing another line's suffix or stopping before a scoped reply.
            foreach ($scopedPaths as $scopedPath) {
                if (count($scopedPath) > count($path) && $path === array_slice($scopedPath, 0, count($path))) {
                    throw new BadRequestHttpException();
                }
            }
        }
        $row = ['user_id' => $userId, 'attempt_id' => $attempt, 'lesson_id' => $id, 'revision' => $revision, 'line_id' => $lineId, 'playing_color' => $color, 'hint_count' => $hints];
        if (array_key_exists('assessment', $data)) {
            $row['assessment_json'] = $this->assessments->normalize($data['assessment'], $selected['steps'], $color, $hints);
        }
        try {
            $this->connection->insert('user_line_completion', [...$row, 'completed_at' => gmdate('Y-m-d H:i:s')]);
        } catch (UniqueConstraintViolationException) {
            $existing = $this->connection->fetchAssociative('SELECT * FROM user_line_completion WHERE user_id=? AND attempt_id=?', [$userId, $attempt]);
            foreach ($row as $key => $value) {
                if (false === $existing || (string) ($existing[$key] ?? null) !== (string) $value) {
                    throw new ConflictHttpException();
                }
            }
        }
    }
}
