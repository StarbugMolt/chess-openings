<?php

declare(strict_types=1);

namespace App\Service\Admin;

use Doctrine\DBAL\Connection;

/** Console-only administrator grants. No HTTP route reaches this service. */
final readonly class AdminGrants
{
    public const GRANTED = 'granted';
    public const ALREADY_ADMIN = 'already-admin';
    public const REVOKED = 'revoked';
    public const NOT_ADMIN = 'not-admin';
    public const UNKNOWN = 'unknown';
    public const UNVERIFIED = 'unverified';

    public function __construct(private Connection $connection)
    {
    }

    /** Same normalisation as AccountRequest::email(). */
    public static function normaliseEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    /** @return self::GRANTED|self::ALREADY_ADMIN|self::UNKNOWN|self::UNVERIFIED */
    public function grant(string $email): string
    {
        return $this->connection->transactional(function () use ($email): string {
            $account = $this->connection->fetchAssociative('SELECT id, verified_at, admin_granted_at FROM user_account WHERE email = ? FOR UPDATE', [self::normaliseEmail($email)]);
            if (false === $account) {
                return self::UNKNOWN;
            }
            if (null === $account['verified_at']) {
                return self::UNVERIFIED;
            }
            if (null !== $account['admin_granted_at']) {
                return self::ALREADY_ADMIN;
            }
            $this->connection->executeStatement('UPDATE user_account SET admin_granted_at = ? WHERE id = ?', [gmdate('Y-m-d H:i:s'), $account['id']]);

            return self::GRANTED;
        });
    }

    /** @return self::REVOKED|self::NOT_ADMIN|self::UNKNOWN */
    public function revoke(string $email): string
    {
        return $this->connection->transactional(function () use ($email): string {
            $account = $this->connection->fetchAssociative('SELECT id, admin_granted_at FROM user_account WHERE email = ? FOR UPDATE', [self::normaliseEmail($email)]);
            if (false === $account) {
                return self::UNKNOWN;
            }
            if (null === $account['admin_granted_at']) {
                return self::NOT_ADMIN;
            }
            $this->connection->executeStatement('UPDATE user_account SET admin_granted_at = NULL WHERE id = ?', [$account['id']]);

            return self::REVOKED;
        });
    }

    /** @return list<array{email: string, grantedAt: string}> */
    public function list(): array
    {
        $admins = [];
        foreach ($this->connection->fetchAllAssociative('SELECT email, admin_granted_at FROM user_account WHERE admin_granted_at IS NOT NULL ORDER BY admin_granted_at, id') as $row) {
            $admins[] = ['email' => (string) $row['email'], 'grantedAt' => (new \DateTimeImmutable((string) $row['admin_granted_at'], new \DateTimeZone('UTC')))->format(DATE_ATOM)];
        }

        return $admins;
    }
}
