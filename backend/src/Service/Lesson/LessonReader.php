<?php

declare(strict_types=1);

namespace App\Service\Lesson;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class LessonReader
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return list<array{id: string, title: string, introduction: string, revision: int, lineCount: int, recommendedColor: string}> */
    public function courses(): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT l.id,r.title,r.introduction,r.revision,r.recommended_color,(SELECT COUNT(*) FROM lesson_line x WHERE x.lesson_id=l.id AND x.revision=r.revision) AS line_count FROM lesson l JOIN lesson_revision r ON r.lesson_id=l.id AND r.revision=l.current_revision ORDER BY l.sort_order,l.id');

        return array_map(static fn (array $row): array => ['id' => (string) $row['id'], 'title' => (string) $row['title'], 'introduction' => (string) $row['introduction'], 'revision' => (int) $row['revision'], 'lineCount' => (int) $row['line_count'], 'recommendedColor' => (string) $row['recommended_color']], $rows);
    }

    /** @return array<string, mixed> */
    public function lesson(string $id, ?int $revision = null): array
    {
        $row = $this->connection->fetchAssociative('SELECT r.*,p.position_key FROM lesson l JOIN lesson_revision r ON r.lesson_id=l.id AND r.revision=COALESCE(?,l.current_revision) JOIN chess_position p ON p.id=r.start_position_id WHERE l.id=?', [$revision, $id]);
        if (false === $row) {
            throw new NotFoundHttpException();
        }
        $revision = (int) $row['revision'];
        $lines = [];
        foreach ($this->connection->fetchAllAssociative('SELECT * FROM lesson_line WHERE lesson_id=? AND revision=? ORDER BY sort_order,id', [$id, $revision]) as $line) {
            $steps = $this->connection->fetchAllAssociative('SELECT s.ply,m.uci,m.san,f.position_key AS from_key,t.position_key AS to_key,s.hint,s.explanation FROM lesson_line_step s JOIN chess_move m ON m.id=s.chess_move_id JOIN chess_position f ON f.id=m.from_position_id JOIN chess_position t ON t.id=m.to_position_id WHERE s.lesson_id=? AND s.revision=? AND s.line_id=? ORDER BY s.ply', [$id, $revision, $line['id']]);
            $position = $row['position_key'];
            foreach ($steps as $index => $step) {
                if ((int) $step['ply'] !== $index + 1 || $position !== $step['from_key']) {
                    throw new \RuntimeException('Authored lesson path is discontinuous.');
                }
                $position = $step['to_key'];
            }
            if (count($steps) !== (int) $line['ply_count'] || count($steps) < 2 || count($steps) > 64) {
                throw new \RuntimeException('Authored lesson length is invalid.');
            }
            $lines[] = ['id' => (string) $line['id'], 'title' => (string) $line['title'], 'description' => (string) $line['description'], 'steps' => array_map(static fn (array $step): array => ['uci' => (string) $step['uci'], 'san' => (string) $step['san'], 'fromKey' => (string) $step['from_key'], 'toKey' => (string) $step['to_key'], 'hint' => (string) $step['hint'], 'explanation' => (string) $step['explanation']], $steps)];
        }
        if ([] === $lines) {
            throw new \RuntimeException('Published lesson has no lines.');
        }

        return ['id' => $id, 'revision' => $revision, 'recommendedColor' => (string) $row['recommended_color'], 'title' => (string) $row['title'], 'introduction' => (string) $row['introduction'], 'startFen' => $row['position_key'].' 0 1', 'lines' => $lines];
    }

    /** @return list<array<string, mixed>> */
    public function progress(int $userId): array
    {
        $rows = $this->connection->fetchAllAssociative('SELECT lesson_id AS lessonId,revision,line_id AS lineId,playing_color AS playingColor,COUNT(*) AS completions,MAX(completed_at) AS lastPractisedAt FROM user_line_completion WHERE user_id=? GROUP BY lesson_id,revision,line_id,playing_color', [$userId]);

        return array_map(static fn (array $row): array => ['lessonId' => (string) $row['lessonId'], 'revision' => (int) $row['revision'], 'lineId' => (string) $row['lineId'], 'playingColor' => (string) $row['playingColor'], 'completions' => (int) $row['completions'], 'lastPractisedAt' => (new \DateTimeImmutable((string) $row['lastPractisedAt'], new \DateTimeZone('UTC')))->format(DATE_ATOM)], $rows);
    }
}
