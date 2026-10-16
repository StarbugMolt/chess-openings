<?php

declare(strict_types=1);

namespace App\Service\Migrations;

use Doctrine\DBAL\Connection;

/** Same-connection advisory lock shared by sync, rollback and database preparation. */
final class CatalogueOperationLock
{
    private int $depth = 0;
    private ?string $name = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    /** @template T
     * @param callable(): T $operation
     *
     * @return T
     */
    public function run(callable $operation, int $timeout = 0): mixed
    {
        if ($timeout < 0 || $timeout > 600) {
            throw new \InvalidArgumentException('Database lock timeout must be between 0 and 600 seconds.');
        }
        if ($this->depth > 0) {
            $this->assertHeld();
            ++$this->depth;
            try {
                return $operation();
            } finally {
                --$this->depth;
            }
        }
        // Scope to this database, so isolated CI databases do not contend with development.
        $this->name = 'chess.catalogue.'.substr(hash('sha256', (string) $this->connection->getDatabase()), 0, 40);
        if (1 !== (int) $this->connection->fetchOne('SELECT GET_LOCK(?, ?)', [$this->name, $timeout])) {
            throw new \RuntimeException('Another catalogue or migration operation holds the database lock. Retry after it finishes.');
        }
        $this->depth = 1;
        try {
            return $operation();
        } finally {
            $this->depth = 0;
            try {
                $this->connection->fetchOne('SELECT RELEASE_LOCK(?)', [$this->name]);
            } catch (\Throwable) {
                // A dead connection has already released the lock. Preserve the operation's result/error.
            }
        }
    }

    public function assertHeld(): void
    {
        if ($this->depth < 1 || 1 !== (int) $this->connection->fetchOne('SELECT IS_USED_LOCK(?) = CONNECTION_ID()', [$this->name])) {
            throw new \RuntimeException('Catalogue database lock ownership was lost; stop and retry the operation.');
        }
    }
}
