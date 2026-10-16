<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Admin-only account listing and deletion; deletion reuses the account foreign-key cascades. */
final readonly class AdminUsers
{
    public const PAGE_SIZE = 50;
    public const DELETED = 'deleted';
    public const WRONG_PASSWORD = 'wrong-password';
    public const UNKNOWN = 'unknown';
    public const REFUSED = 'refused';

    public function __construct(private Connection $connection, private UserPasswordHasherInterface $hasher, #[Target('admin')] private LoggerInterface $logger)
    {
    }

    /** Treat the search as a literal substring: LIKE wildcards and the escape character are escaped. */
    public static function likePattern(string $search): string
    {
        return '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
    }

    /**
     * @return array{items: list<array{id: string, email: string, createdAt: string, verifiedAt: ?string, lastSignInAt: ?string, admin: bool, completions: int, lastPracticeAt: ?string}>, page: int, limit: int, total: int, search: string}
     */
    public function list(string $search, int $page): array
    {
        $where = '' === $search ? '' : "WHERE u.email LIKE ? ESCAPE '!'";
        $parameters = '' === $search ? [] : [self::likePattern($search)];
        $total = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM user_account u '.$where, $parameters);
        $limit = self::PAGE_SIZE;
        $offset = ($page - 1) * $limit;
        $rows = $this->connection->fetchAllAssociative(<<<SQL
SELECT u.id, u.email, u.created_at, u.verified_at, u.last_login_at, u.admin_granted_at,
       (SELECT COUNT(*) FROM user_line_completion c WHERE c.user_id = u.id) AS completions,
       (SELECT MAX(c.completed_at) FROM user_line_completion c WHERE c.user_id = u.id) AS last_practice_at
FROM user_account u
{$where}
ORDER BY u.created_at DESC, u.id DESC
LIMIT {$limit} OFFSET {$offset}
SQL, $parameters);
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => (string) $row['id'],
                'email' => (string) $row['email'],
                'createdAt' => self::timestamp((string) $row['created_at']),
                'verifiedAt' => self::optionalTimestamp($row['verified_at']),
                'lastSignInAt' => self::optionalTimestamp($row['last_login_at']),
                'admin' => null !== $row['admin_granted_at'],
                'completions' => (int) $row['completions'],
                'lastPracticeAt' => self::optionalTimestamp($row['last_practice_at']),
            ];
        }

        return ['items' => $items, 'page' => $page, 'limit' => self::PAGE_SIZE, 'total' => $total, 'search' => $search];
    }

    /**
     * Re-check the admin's own password against a fresh locked row, then delete the target.
     *
     * @return self::DELETED|self::WRONG_PASSWORD|self::UNKNOWN|self::REFUSED
     */
    public function delete(int $adminId, int $targetId, #[\SensitiveParameter] string $password): string
    {
        $result = $this->connection->transactional(function () use ($adminId, $targetId, $password): string {
            // Lock both rows in primary-key order with one statement to avoid lock-order deadlocks.
            $rows = $this->connection->fetchAllAssociativeIndexed('SELECT id, email, password, admin_granted_at FROM user_account WHERE id IN (?, ?) ORDER BY id FOR UPDATE', [$adminId, $targetId]);
            $admin = $rows[$adminId] ?? null;
            if (null === $admin) {
                throw new UnauthorizedHttpException('Session');
            }
            if (null === $admin['admin_granted_at']) {
                // Revoked between the access check and this transaction.
                throw new AccessDeniedHttpException();
            }
            $candidate = new User((string) $admin['email']);
            $candidate->setPassword((string) $admin['password']);
            if (!$this->hasher->isPasswordValid($candidate, $password)) {
                return self::WRONG_PASSWORD;
            }
            $target = $rows[$targetId] ?? null;
            if (null === $target) {
                return self::UNKNOWN;
            }
            if ($targetId === $adminId || null !== $target['admin_granted_at']) {
                return self::REFUSED;
            }
            // Foreign keys remove the account's email tokens and every retained completion.
            $this->connection->delete('user_account', ['id' => $targetId]);

            return self::DELETED;
        });
        if (self::DELETED === $result) {
            // Identifiers and time only: the deleted email must not outlive the account in logs.
            $this->logger->info('Administrator deleted an account.', ['adminId' => $adminId, 'deletedAccountId' => $targetId, 'deletedAt' => gmdate(DATE_ATOM)]);
        }

        return $result;
    }

    private static function optionalTimestamp(mixed $value): ?string
    {
        return null === $value ? null : self::timestamp((string) $value);
    }

    private static function timestamp(string $value): string
    {
        return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))->format(DATE_ATOM);
    }
}
