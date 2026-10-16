<?php

declare(strict_types=1);

namespace App\Service\OpeningImport;

use App\Service\Migrations\CatalogueOperationLock;
use Doctrine\DBAL\Connection;

final readonly class CataloguePromoter
{
    public function __construct(private Connection $connection, private CatalogueStager $stager, private CatalogueOperationLock $lock)
    {
    }

    public function activate(int $batch, ?int $expectedActive, string $operation = 'updated'): int
    {
        $this->lock->assertHeld();

        return $this->connection->transactional(function () use ($batch, $expectedActive, $operation): int {
            $state = $this->connection->fetchAssociative('SELECT active_batch_id,catalogue_revision FROM opening_catalogue_state WHERE id=1 FOR UPDATE');
            if (false === $state) {
                throw new \RuntimeException('Catalogue schema has not been initialised.');
            }
            $active = null === $state['active_batch_id'] ? null : (int) $state['active_batch_id'];
            if ($active !== $expectedActive) {
                throw new \RuntimeException('Active catalogue changed before activation; retry.');
            }
            if ($batch === $active) {
                return (int) $state['catalogue_revision'];
            }
            $target = $this->connection->fetchAssociative('SELECT status FROM opening_import_batch WHERE id=? FOR UPDATE', [$batch]);
            if (false === $target || !in_array($target['status'], ['ready', 'retired'], true)) {
                throw new \InvalidArgumentException('Only a retained validated ready/retired catalogue can be activated.');
            }
            $entries = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM opening_entry WHERE batch_id=?', [$batch]);
            $plies = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM opening_entry_step s JOIN opening_entry e ON e.id=s.opening_entry_id WHERE e.batch_id=?', [$batch]);
            $this->stager->validate($batch, $entries, $plies);
            $this->lock->assertHeld();
            if (null !== $active) {
                $this->connection->executeStatement("UPDATE opening_import_batch SET status='retired',finished_at=UTC_TIMESTAMP(6) WHERE id=?", [$active]);
            }
            $this->connection->executeStatement("UPDATE opening_import_batch SET status='active',finished_at=UTC_TIMESTAMP(6) WHERE id=?", [$batch]);
            $this->connection->executeStatement('UPDATE opening_catalogue_state SET active_batch_id=?,catalogue_revision=catalogue_revision+1,last_checked_at=UTC_TIMESTAMP(6),last_updated_at=UTC_TIMESTAMP(6),last_check_status=?,last_error_summary=NULL,automatic_updates_held_at=IF(?=\'rollback\',UTC_TIMESTAMP(6),NULL) WHERE id=1', [$batch, $operation, $operation]);

            return (int) $state['catalogue_revision'] + 1;
        });
    }
}
