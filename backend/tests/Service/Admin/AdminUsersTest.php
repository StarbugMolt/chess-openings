<?php

declare(strict_types=1);

namespace App\Tests\Service\Admin;

use App\Entity\User;
use App\Service\Admin\AdminUsers;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminUsersTest extends TestCase
{
    private const ADMIN = ['email' => 'admin@example.test', 'password' => 'fresh-admin-hash', 'admin_granted_at' => '2026-10-16 08:00:00'];
    private const LEARNER = ['email' => 'learner@example.test', 'password' => 'learner-hash', 'admin_granted_at' => null];

    /** @param array<int, array<string, mixed>> $rows */
    private function connection(array $rows, bool $deletes): Connection
    {
        $db = $this->createMock(Connection::class);
        $db->method('transactional')->willReturnCallback(static fn (\Closure $work): mixed => $work());
        $db->expects(self::once())->method('fetchAllAssociativeIndexed')->with(self::stringContains('ORDER BY id FOR UPDATE'))->willReturn($rows);
        $db->expects($deletes ? self::once() : self::never())->method('delete')->with('user_account', ['id' => 9]);

        return $db;
    }

    private function hasher(?bool $valid): UserPasswordHasherInterface
    {
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        if (null === $valid) {
            $hasher->expects(self::never())->method('isPasswordValid');
        } else {
            $hasher->expects(self::once())->method('isPasswordValid')->with(self::callback(static fn (User $user): bool => 'fresh-admin-hash' === $user->getPassword()), 'entered')->willReturn($valid);
        }

        return $hasher;
    }

    public function testSuccessfulDeletionLogsIdentifiersButNotTheEmail(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('info')->with(self::anything(), self::callback(static function (array $context): bool {
            self::assertSame(['adminId', 'deletedAccountId', 'deletedAt'], array_keys($context));
            self::assertSame([1, 9], [$context['adminId'], $context['deletedAccountId']]);
            self::assertStringNotContainsString('@', json_encode($context, JSON_THROW_ON_ERROR));

            return true;
        }));
        $users = new AdminUsers($this->connection([1 => self::ADMIN, 9 => self::LEARNER], true), $this->hasher(true), $logger);
        self::assertSame(AdminUsers::DELETED, $users->delete(1, 9, 'entered'));
    }

    public static function refusals(): iterable
    {
        yield 'wrong password is checked before the target' => [[1 => self::ADMIN, 9 => ['admin_granted_at' => '2026-10-16 08:00:00'] + self::LEARNER], false, AdminUsers::WRONG_PASSWORD];
        yield 'unknown target' => [[1 => self::ADMIN], true, AdminUsers::UNKNOWN];
        yield 'another administrator' => [[1 => self::ADMIN, 9 => ['admin_granted_at' => '2026-10-16 08:00:00'] + self::LEARNER], true, AdminUsers::REFUSED];
    }

    /** @param array<int, array<string, mixed>> $rows */
    #[DataProvider('refusals')]
    public function testRefusalsDeleteNothingAndLogNothing(array $rows, bool $valid, string $result): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method(self::anything());
        self::assertSame($result, (new AdminUsers($this->connection($rows, false), $this->hasher($valid), $logger))->delete(1, 9, 'entered'));
    }

    public function testSelfDeletionIsRefused(): void
    {
        $db = $this->createMock(Connection::class);
        $db->method('transactional')->willReturnCallback(static fn (\Closure $work): mixed => $work());
        $db->method('fetchAllAssociativeIndexed')->willReturn([1 => self::ADMIN]);
        $db->expects(self::never())->method('delete');
        self::assertSame(AdminUsers::REFUSED, (new AdminUsers($db, $this->hasher(true), $this->createMock(LoggerInterface::class)))->delete(1, 1, 'entered'));
    }

    public function testRevokedOrDeletedAdminCannotConfirm(): void
    {
        $revoked = new AdminUsers($this->connection([1 => ['admin_granted_at' => null] + self::ADMIN, 9 => self::LEARNER], false), $this->hasher(null), $this->createMock(LoggerInterface::class));
        try {
            $revoked->delete(1, 9, 'entered');
            self::fail('A revoked admin could confirm a deletion.');
        } catch (AccessDeniedHttpException) {
        }
        $gone = new AdminUsers($this->connection([9 => self::LEARNER], false), $this->hasher(null), $this->createMock(LoggerInterface::class));
        $this->expectException(UnauthorizedHttpException::class);
        $gone->delete(1, 9, 'entered');
    }
}
