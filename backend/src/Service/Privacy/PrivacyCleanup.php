<?php

declare(strict_types=1);

namespace App\Service\Privacy;

use Doctrine\DBAL\Connection;

final readonly class PrivacyCleanup
{
    public function __construct(private Connection $connection)
    {
    }

    /** @return array<string, array{eligible: int, removed: int}> */
    public function run(bool $apply, int $limit, \DateTimeImmutable $now): array
    {
        if ($limit < 1 || $limit > 10000) {
            throw new \InvalidArgumentException('Cleanup limit must be 1–10000 per category.');
        }
        $utc = $now->setTimezone(new \DateTimeZone('UTC'));
        $result = [];
        foreach ([
            'emailTokens' => ['account_email_token', $utc->format('Y-m-d H:i:s')],
            'requestLimits' => ['account_request_limit', $utc->getTimestamp()],
        ] as $name => [$table, $expiry]) {
            $eligible = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM '.$table.' WHERE expires_at <= ?', [$expiry]);
            $removed = $apply ? (int) $this->connection->executeStatement('DELETE FROM '.$table.' WHERE expires_at <= ? ORDER BY expires_at LIMIT '.$limit, [$expiry]) : 0;
            $result[$name] = ['eligible' => $eligible, 'removed' => $removed];
        }
        // Account retention is owner-controlled; neither verified nor unverified accounts are purged.
        $result['accounts'] = ['eligible' => 0, 'removed' => 0];

        return $result;
    }
}
