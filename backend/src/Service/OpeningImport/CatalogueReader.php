<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\TransactionIsolationLevel;

final readonly class CatalogueReader
{
    public function __construct(private Connection $connection)
    {
    }

    /**
     * @template T
     *
     * @param callable(array{batchId: ?int, catalogueRevision: int, sourceSha: ?string, updatedAt: ?string}): T $read
     *
     * @return T
     */
    public function snapshot(callable $read): mixed
    {
        $previous = $this->connection->getTransactionIsolation();
        $changeIsolation = TransactionIsolationLevel::REPEATABLE_READ !== $previous;
        if ($changeIsolation) {
            $this->connection->setTransactionIsolation(TransactionIsolationLevel::REPEATABLE_READ);
        }
        try {
            return $this->connection->transactional(function () use ($read): mixed {
                $row = $this->connection->fetchAssociative('SELECT s.active_batch_id,s.catalogue_revision,s.last_updated_at,b.upstream_sha FROM opening_catalogue_state s LEFT JOIN opening_import_batch b ON b.id=s.active_batch_id WHERE s.id=1');
                if (false === $row) {
                    throw new \RuntimeException('Catalogue schema is not initialised.');
                }
                $time = null === $row['last_updated_at'] ? null : new \DateTimeImmutable((string) $row['last_updated_at'], new \DateTimeZone('UTC'));

                return $read([
                    'batchId' => null === $row['active_batch_id'] ? null : (int) $row['active_batch_id'],
                    'catalogueRevision' => (int) $row['catalogue_revision'],
                    'sourceSha' => null === $row['upstream_sha'] ? null : (string) $row['upstream_sha'],
                    'updatedAt' => $time?->format('Y-m-d\TH:i:s.u\Z'),
                ]);
            });
        } finally {
            if ($changeIsolation) {
                $this->connection->setTransactionIsolation($previous);
            }
        }
    }

    /** @return array{catalogueRevision: int, sourceSha: ?string, updatedAt: ?string, entryCount: int} */
    public function status(): array
    {
        return $this->snapshot(function (array $snapshot): array {
            return [
                'catalogueRevision' => $snapshot['catalogueRevision'], 'sourceSha' => $snapshot['sourceSha'], 'updatedAt' => $snapshot['updatedAt'],
                'entryCount' => null === $snapshot['batchId'] ? 0 : (int) $this->connection->fetchOne('SELECT COUNT(*) FROM opening_entry WHERE batch_id=?', [$snapshot['batchId']]),
            ];
        });
    }

    /** @return array<string, mixed> */
    public function search(string $search = '', string $eco = '', int $page = 1, int $limit = 20): array
    {
        return $this->snapshot(function (array $snapshot) use ($search, $eco, $page, $limit): array {
            $where = 'batch_id=?';
            $parameters = [$snapshot['batchId']];
            if ('' !== $search) {
                $where .= " AND full_name LIKE ? ESCAPE '!'";
                $parameters[] = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            }
            if ('' !== $eco) {
                $where .= ' AND eco LIKE ?';
                $parameters[] = $eco.'%';
            }
            $total = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM (SELECT 1 FROM opening_entry WHERE '.$where.' GROUP BY BINARY full_name) grouped_names', $parameters);
            // Page/limit are validated integers; SQL data values remain bound parameters.
            $rows = $this->connection->fetchAllAssociative('SELECT e.id,e.source_uid,e.eco,e.full_name,g.sequence_count FROM opening_entry e JOIN (SELECT MIN(source_uid) AS source_uid,COUNT(*) AS sequence_count FROM opening_entry WHERE '.$where.' GROUP BY BINARY full_name) g ON g.source_uid=e.source_uid WHERE e.batch_id=? ORDER BY e.full_name,e.source_uid LIMIT '.$limit.' OFFSET '.(($page - 1) * $limit), [...$parameters, $snapshot['batchId']]);
            $items = [];
            foreach ($rows as $row) {
                $sequences = $this->connection->fetchAllAssociative('SELECT original_pgn,eco FROM opening_entry WHERE '.$where.' AND BINARY full_name=BINARY ? ORDER BY CHAR_LENGTH(original_pgn),source_uid LIMIT 3', [...$parameters, $row['full_name']]);
                $items[] = ['id' => (string) $row['id'], 'sourceId' => (string) $row['source_uid'], 'eco' => (string) $row['eco'], 'name' => (string) $row['full_name'], 'sequenceCount' => (int) $row['sequence_count'], 'sequences' => array_map(static fn (array $sequence): array => ['pgn' => (string) $sequence['original_pgn'], 'eco' => (string) $sequence['eco']], $sequences)];
            }

            return ['catalogueRevision' => $snapshot['catalogueRevision'], 'items' => $items, 'page' => $page, 'limit' => $limit, 'total' => $total];
        });
    }

    /** @return array<string, mixed> */
    public function operationalStatus(): array
    {
        return [
            'catalogue' => $this->status(),
            'lastCheck' => $this->connection->fetchAssociative('SELECT last_checked_at,last_check_status,last_error_summary,automatic_updates_held_at FROM opening_catalogue_state WHERE id=1'),
            'recentBatches' => $this->connection->fetchAllAssociative('SELECT id,upstream_sha,content_checksum,importer_version,status,created_at,validated_at,finished_at,error_summary FROM opening_import_batch ORDER BY id DESC LIMIT 10'),
        ];
    }
}
