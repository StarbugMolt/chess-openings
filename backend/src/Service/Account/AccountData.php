<?php

declare(strict_types=1);

namespace App\Service\Account;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Existing account ownership/cascades also govern downloads and deletion. */
final readonly class AccountData
{
    public function __construct(private Connection $connection, private UserPasswordHasherInterface $hasher)
    {
    }

    /** @return array<string, mixed>|null Null means the current password did not match. */
    public function export(int $userId, #[\SensitiveParameter] string $password): ?array
    {
        return $this->connection->transactional(function () use ($userId, $password): ?array {
            $account = $this->confirmedAccount($userId, $password);
            if (null === $account) {
                return null;
            }
            $completions = [];
            $rows = $this->connection->iterateAssociative(<<<'SQL'
SELECT c.attempt_id, c.lesson_id, c.revision, c.line_id, c.playing_color,
       c.hint_count, c.completed_at, c.assessment_json,
       r.title AS lesson_title, l.title AS line_title
FROM user_line_completion c
JOIN lesson_revision r ON r.lesson_id = c.lesson_id AND r.revision = c.revision
JOIN lesson_line l ON l.lesson_id = c.lesson_id AND l.revision = c.revision AND l.id = c.line_id
WHERE c.user_id = ?
ORDER BY c.completed_at, c.attempt_id
SQL, [$userId]);
            foreach ($rows as $row) {
                $completions[] = [
                    'attemptId' => $row['attempt_id'],
                    'lessonId' => $row['lesson_id'],
                    'lessonTitle' => $row['lesson_title'],
                    'revision' => (int) $row['revision'],
                    'lineId' => $row['line_id'],
                    'lineTitle' => $row['line_title'],
                    'playingColor' => $row['playing_color'],
                    'hintCount' => (int) $row['hint_count'],
                    'completedAt' => self::timestamp((string) $row['completed_at']),
                    'assessment' => null === $row['assessment_json'] ? null : json_decode((string) $row['assessment_json'], true, flags: JSON_THROW_ON_ERROR),
                ];
            }
            $links = [];
            foreach ($this->connection->iterateAssociative('SELECT purpose, created_at, expires_at FROM account_email_token WHERE user_id = ? ORDER BY issued_sequence', [$userId]) as $row) {
                $links[] = ['purpose' => $row['purpose'], 'createdAt' => self::timestamp((string) $row['created_at']), 'expiresAt' => self::timestamp((string) $row['expires_at'])];
            }

            return [
                'formatVersion' => 1,
                'exportedAt' => gmdate(DATE_ATOM),
                'account' => ['id' => (string) $account['id'], 'email' => $account['email'], 'createdAt' => self::timestamp((string) $account['created_at']), 'verifiedAt' => null === $account['verified_at'] ? null : self::timestamp((string) $account['verified_at']), 'lastSignInAt' => null === $account['last_login_at'] ? null : self::timestamp((string) $account['last_login_at'])],
                'completions' => $completions,
                'emailLinks' => $links,
            ];
        });
    }

    public function delete(int $userId, #[\SensitiveParameter] string $password): bool
    {
        return $this->connection->transactional(function () use ($userId, $password): bool {
            if (null === $this->confirmedAccount($userId, $password)) {
                return false;
            }
            // Foreign keys remove this account's email tokens and every retained completion.
            $this->connection->delete('user_account', ['id' => $userId]);

            return true;
        });
    }

    /** @return array<string, mixed>|null */
    private function confirmedAccount(int $userId, #[\SensitiveParameter] string $password): ?array
    {
        // Serialize against password resets, token issuance and deletion. Never trust a stale session hash.
        $account = $this->connection->fetchAssociative('SELECT id, email, password, created_at, verified_at, last_login_at FROM user_account WHERE id = ? FOR UPDATE', [$userId]);
        if (false === $account) {
            throw new UnauthorizedHttpException('Session');
        }
        $candidate = new User((string) $account['email']);
        $candidate->setPassword((string) $account['password']);

        return $this->hasher->isPasswordValid($candidate, $password) ? $account : null;
    }

    private static function timestamp(string $value): string
    {
        return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))->format(DATE_ATOM);
    }
}
