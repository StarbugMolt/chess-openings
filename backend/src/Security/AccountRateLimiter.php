<?php

declare(strict_types=1);

namespace App\Security;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/** Fixed windows, shared by all releases; stores HMACs rather than email/IP addresses. */
final class AccountRateLimiter
{
    public function __construct(private readonly Connection $connection, #[Autowire('%kernel.secret%')] private readonly string $secret)
    {
    }

    public function consume(string $action, string $identity, int $maximum, int $seconds): void
    {
        $now = time();
        $key = $this->key($action, $identity);
        $this->connection->executeStatement('DELETE FROM account_request_limit WHERE expires_at <= ? LIMIT 100', [$now]);
        $this->connection->transactional(function () use ($key, $maximum, $seconds, $now): void {
            $this->connection->executeStatement('INSERT INTO account_request_limit (bucket_hash, attempts, expires_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE attempts = IF(expires_at <= ?, 1, attempts + 1), expires_at = IF(expires_at <= ?, ?, expires_at)', [$key, $now + $seconds, $now, $now, $now + $seconds]);
            $row = $this->connection->fetchAssociative('SELECT attempts, expires_at FROM account_request_limit WHERE bucket_hash = ? FOR UPDATE', [$key]);
            if (false !== $row && (int) $row['attempts'] > $maximum) {
                throw new TooManyRequestsHttpException(max(1, (int) $row['expires_at'] - $now));
            }
        });
    }

    public function reset(string $action, string $identity): void
    {
        $this->connection->delete('account_request_limit', ['bucket_hash' => $this->key($action, $identity)]);
    }

    /** Group IPv6 privacy addresses into one /64; canonicalise mapped IPv4 too. */
    public static function clientNetwork(?string $ip): string
    {
        $packed = null === $ip ? false : @inet_pton($ip);
        if (false === $packed) {
            return 'unknown';
        }
        if (16 === strlen($packed)) {
            if (str_starts_with($packed, str_repeat("\0", 10)."\xff\xff")) {
                return (string) inet_ntop(substr($packed, 12));
            }

            return inet_ntop(substr($packed, 0, 8).str_repeat("\0", 8)).'/64';
        }

        return (string) inet_ntop($packed);
    }

    private function key(string $action, string $identity): string
    {
        return hash_hmac('sha256', $action."\0".$identity, $this->secret);
    }
}
