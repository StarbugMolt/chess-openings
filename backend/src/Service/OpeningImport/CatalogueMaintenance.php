<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

use App\Service\Migrations\CatalogueOperationLock;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Psr\Log\LoggerInterface;

final readonly class CatalogueMaintenance
{
    public function __construct(private Connection $connection, private CatalogueOperationLock $lock, private CataloguePromoter $promoter, private LoggerInterface $logger)
    {
    }

    public function rollback(int $batch): int
    {
        return $this->lock->run(function () use ($batch): int {
            $active = $this->connection->fetchOne('SELECT active_batch_id FROM opening_catalogue_state WHERE id=1');
            $revision = $this->promoter->activate($batch, null === $active || false === $active ? null : (int) $active, 'rollback');
            $this->log('notice', 'Opening catalogue rollback.', ['batchId' => $batch, 'catalogueRevision' => $revision]);

            return $revision;
        });
    }

    /** Called while the catalogue operation lock is held. Never removes shared chess facts. */
    public function cleanup(): void
    {
        $this->lock->assertHeld();
        $retained = $this->connection->fetchFirstColumn("SELECT id FROM opening_import_batch WHERE status='retired' ORDER BY finished_at DESC,id DESC LIMIT 2");
        $candidates = $this->connection->fetchFirstColumn("SELECT b.id FROM opening_import_batch b WHERE b.id NOT IN (SELECT active_batch_id FROM opening_catalogue_state WHERE active_batch_id IS NOT NULL) AND ((b.status IN ('failed','staging','ready') AND b.created_at<UTC_TIMESTAMP(6)-INTERVAL 7 DAY) OR b.status='retired') ORDER BY b.created_at,b.id LIMIT 25");
        $deleted = 0;
        foreach ($candidates as $candidate) {
            if (in_array((string) $candidate, array_map('strval', $retained), true)) {
                continue;
            }
            try {
                $this->connection->transactional(function () use ($candidate): void {
                    $this->connection->executeStatement('DELETE s FROM opening_entry_step s JOIN opening_entry e ON e.id=s.opening_entry_id WHERE e.batch_id=?', [$candidate]);
                    $this->connection->delete('opening_entry', ['batch_id' => $candidate]);
                    $this->connection->delete('opening_import_file', ['batch_id' => $candidate]);
                    $this->connection->delete('opening_import_batch', ['id' => $candidate]);
                });
                ++$deleted;
            } catch (ForeignKeyConstraintViolationException) {
                // A lesson/provenance FK retains the whole snapshot; the transaction restores children too.
                $this->log('info', 'Retained referenced opening snapshot.', ['batchId' => (int) $candidate]);
            }
            if ($deleted >= 1) {
                break;
            }
        }
    }

    /** @param array<string, mixed> $context */
    private function log(string $level, string $message, array $context): void
    {
        try {
            $this->logger->log($level, $message, $context);
        } catch (\Throwable) {
            // Operational logging cannot reverse an activation or alter retention guarantees.
        }
    }
}
