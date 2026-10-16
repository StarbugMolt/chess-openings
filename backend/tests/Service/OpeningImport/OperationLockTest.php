<?php

declare(strict_types=1);

namespace App\Tests\Service\OpeningImport;

use App\Service\Migrations\CatalogueOperationLock;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class OperationLockTest extends TestCase
{
    public function testReleaseFailurePreservesOriginalFailure(): void
    {
        $lock = $this->lockWithBrokenRelease();
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('original operation failure');
        $lock->run(static fn () => throw new \DomainException('original operation failure'));
    }

    public function testReleaseFailurePreservesCommittedResult(): void
    {
        self::assertSame('committed', $this->lockWithBrokenRelease()->run(static fn () => 'committed'));
    }

    private function lockWithBrokenRelease(): CatalogueOperationLock
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabase')->willReturn('isolated_lock_test');
        $connection->method('fetchOne')->willReturnCallback(static function (string $sql): int {
            if (str_contains($sql, 'RELEASE_LOCK')) {
                throw new \RuntimeException('lost connection during release');
            }

            return 1;
        });

        return new CatalogueOperationLock($connection);
    }
}
