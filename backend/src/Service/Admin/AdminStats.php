<?php

declare(strict_types=1);

namespace App\Service\Admin;

use Doctrine\DBAL\Connection;

/** Aggregate-only usage statistics derived on request from existing account and practice rows (UTC). */
final readonly class AdminStats
{
    public const WEEKS = 12;
    public const DAYS = 30;
    public const TOP_LINES = 10;

    public function __construct(private Connection $connection)
    {
    }

    /** @return array<string, mixed> */
    public function stats(?\DateTimeImmutable $now = null): array
    {
        $now = ($now ?? new \DateTimeImmutable('now'))->setTimezone(new \DateTimeZone('UTC'));
        $now = $now->setTime((int) $now->format('H'), (int) $now->format('i'), (int) $now->format('s'));
        $ago = static fn (string $interval): string => $now->sub(new \DateInterval($interval))->format('Y-m-d H:i:s');
        $weeks = self::weekStarts($now);
        $days = self::days($now);

        $accounts = $this->connection->fetchAssociative(<<<'SQL'
SELECT COUNT(*) AS total, COUNT(verified_at) AS verified, COUNT(admin_granted_at) AS admins,
       COALESCE(SUM(last_login_at >= ?), 0) AS last1, COALESCE(SUM(last_login_at >= ?), 0) AS last7,
       COALESCE(SUM(last_login_at >= ?), 0) AS last30, COALESCE(SUM(last_login_at IS NULL), 0) AS never
FROM user_account
SQL, [$ago('P1D'), $ago('P7D'), $ago('P30D')]) ?: [];
        $weekly = [];
        $signups = $this->series('SELECT DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY) AS bucket, COUNT(*) AS n FROM user_account WHERE created_at >= ? GROUP BY bucket', $weeks[0]);
        $verifications = $this->series('SELECT DATE_SUB(DATE(verified_at), INTERVAL WEEKDAY(verified_at) DAY) AS bucket, COUNT(*) AS n FROM user_account WHERE verified_at >= ? GROUP BY bucket', $weeks[0]);
        foreach ($weeks as $week) {
            $weekly[] = ['weekStart' => $week, 'signups' => $signups[$week] ?? 0, 'verifications' => $verifications[$week] ?? 0];
        }

        $practice = $this->connection->fetchAssociative(<<<'SQL'
SELECT COUNT(*) AS total,
       COALESCE(SUM(completed_at >= ?), 0) AS last7, COALESCE(SUM(completed_at >= ?), 0) AS last30,
       COUNT(DISTINCT CASE WHEN completed_at >= ? THEN user_id END) AS learners7,
       COUNT(DISTINCT CASE WHEN completed_at >= ? THEN user_id END) AS learners30,
       COALESCE(SUM(playing_color = 'w'), 0) AS white, COALESCE(SUM(playing_color = 'b'), 0) AS black,
       COUNT(assessment_json) AS assessed,
       COALESCE(SUM(assessment_json IS NOT NULL
           AND JSON_EXTRACT(assessment_json, '$.usedHint') = 'false'
           AND JSON_EXTRACT(assessment_json, '$.usedRetry') = 'false'
           AND JSON_EXTRACT(assessment_json, '$.usedUndo') = 'false'), 0) AS clean
FROM user_line_completion
SQL, [$ago('P7D'), $ago('P30D'), $ago('P7D'), $ago('P30D')]) ?: [];
        $perDay = $this->series('SELECT DATE(completed_at) AS bucket, COUNT(*) AS n FROM user_line_completion WHERE completed_at >= ? GROUP BY bucket', $days[0]);
        $daily = [];
        foreach ($days as $day) {
            $daily[] = ['date' => $day, 'completions' => $perDay[$day] ?? 0];
        }

        $byCourse = [];
        foreach ($this->connection->fetchAllAssociative(<<<'SQL'
SELECT l.id AS lesson_id, r.title, COUNT(c.attempt_id) AS completions, COUNT(DISTINCT c.user_id) AS learners
FROM lesson l
JOIN lesson_revision r ON r.lesson_id = l.id AND r.revision = l.current_revision
LEFT JOIN user_line_completion c ON c.lesson_id = l.id
GROUP BY l.id, r.title, l.sort_order
ORDER BY l.sort_order, l.id
SQL) as $row) {
            $byCourse[] = ['lessonId' => (string) $row['lesson_id'], 'title' => (string) $row['title'], 'completions' => (int) $row['completions'], 'learners' => (int) $row['learners']];
        }

        // One entry per lesson/line identity; titles come from the newest revision with completions.
        $topLines = [];
        foreach ($this->connection->fetchAllAssociative(<<<'SQL'
SELECT t.lesson_id, t.line_id, t.completions, r.title AS course_title, ll.title AS line_title
FROM (
    SELECT lesson_id, line_id, COUNT(*) AS completions, MAX(revision) AS revision
    FROM user_line_completion
    GROUP BY lesson_id, line_id
) t
JOIN lesson_revision r ON r.lesson_id = t.lesson_id AND r.revision = t.revision
JOIN lesson_line ll ON ll.lesson_id = t.lesson_id AND ll.revision = t.revision AND ll.id = t.line_id
ORDER BY t.completions DESC, t.lesson_id, t.line_id
LIMIT 10
SQL) as $row) {
            $topLines[] = ['lessonId' => (string) $row['lesson_id'], 'lineId' => (string) $row['line_id'], 'courseTitle' => (string) $row['course_title'], 'lineTitle' => (string) $row['line_title'], 'completions' => (int) $row['completions']];
        }

        $total = (int) ($accounts['total'] ?? 0);
        $verified = (int) ($accounts['verified'] ?? 0);

        return [
            'generatedAt' => $now->format(DATE_ATOM),
            'accounts' => ['total' => $total, 'verified' => $verified, 'unverified' => $total - $verified, 'admins' => (int) ($accounts['admins'] ?? 0), 'weekly' => $weekly],
            'signIns' => ['last1Day' => (int) ($accounts['last1'] ?? 0), 'last7Days' => (int) ($accounts['last7'] ?? 0), 'last30Days' => (int) ($accounts['last30'] ?? 0), 'neverRecorded' => (int) ($accounts['never'] ?? 0)],
            'practice' => [
                'total' => (int) ($practice['total'] ?? 0),
                'last7Days' => (int) ($practice['last7'] ?? 0),
                'last30Days' => (int) ($practice['last30'] ?? 0),
                'learners7Days' => (int) ($practice['learners7'] ?? 0),
                'learners30Days' => (int) ($practice['learners30'] ?? 0),
                'daily' => $daily,
                'byCourse' => $byCourse,
                'topLines' => $topLines,
                'byColor' => ['w' => (int) ($practice['white'] ?? 0), 'b' => (int) ($practice['black'] ?? 0)],
                'assessed' => (int) ($practice['assessed'] ?? 0),
                'cleanAssessed' => (int) ($practice['clean'] ?? 0),
            ],
        ];
    }

    /** @return non-empty-list<string> Monday-start UTC weeks, oldest first, ending with the current week. */
    public static function weekStarts(\DateTimeImmutable $now): array
    {
        $date = $now->setTimezone(new \DateTimeZone('UTC'))->setTime(0, 0);
        $monday = $date->modify('-'.((int) $date->format('N') - 1).' days');
        $weeks = [];
        for ($i = self::WEEKS - 1; $i >= 0; --$i) {
            $weeks[] = $monday->modify('-'.(7 * $i).' days')->format('Y-m-d');
        }

        return $weeks;
    }

    /** @return non-empty-list<string> UTC dates, oldest first, ending today. */
    public static function days(\DateTimeImmutable $now): array
    {
        $date = $now->setTimezone(new \DateTimeZone('UTC'))->setTime(0, 0);
        $days = [];
        for ($i = self::DAYS - 1; $i >= 0; --$i) {
            $days[] = $date->modify('-'.$i.' days')->format('Y-m-d');
        }

        return $days;
    }

    /** @return array<string, int> */
    private function series(string $sql, string $firstDate): array
    {
        $counts = [];
        foreach ($this->connection->fetchAllAssociative($sql, [$firstDate.' 00:00:00']) as $row) {
            $counts[(string) $row['bucket']] = (int) $row['n'];
        }

        return $counts;
    }
}
