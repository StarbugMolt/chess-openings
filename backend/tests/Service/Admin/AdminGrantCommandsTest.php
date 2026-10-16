<?php

declare(strict_types=1);

namespace App\Tests\Service\Admin;

use App\Command\AdminGrantCommand;
use App\Command\AdminListCommand;
use App\Command\AdminRevokeCommand;
use App\Service\Admin\AdminGrants;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class AdminGrantCommandsTest extends TestCase
{
    /** @param array<string, mixed>|false $account */
    private function connection(array|false $account, ?string $expectedUpdate): Connection
    {
        $db = $this->createMock(Connection::class);
        $db->method('transactional')->willReturnCallback(static fn (\Closure $work): mixed => $work());
        $db->expects(self::once())->method('fetchAssociative')->with(self::stringContains('FOR UPDATE'), ['admin@example.test'])->willReturn($account);
        if (null === $expectedUpdate) {
            $db->expects(self::never())->method('executeStatement');
        } else {
            $db->expects(self::once())->method('executeStatement')->with(self::stringContains($expectedUpdate))->willReturn(1);
        }

        return $db;
    }

    public static function grants(): iterable
    {
        yield 'verified account is granted' => [['id' => 4, 'verified_at' => '2026-10-15 09:00:00', 'admin_granted_at' => null], 'SET admin_granted_at = ?', Command::SUCCESS, 'Granted'];
        yield 'existing grant is idempotent' => [['id' => 4, 'verified_at' => '2026-10-15 09:00:00', 'admin_granted_at' => '2026-10-16 08:00:00'], null, Command::SUCCESS, 'already an administrator'];
        yield 'unknown account is refused' => [false, null, Command::FAILURE, 'No account uses'];
        yield 'unverified account is refused' => [['id' => 4, 'verified_at' => null, 'admin_granted_at' => null], null, Command::FAILURE, 'has not confirmed'];
    }

    /** @param array<string, mixed>|false $account */
    #[DataProvider('grants')]
    public function testGrantNormalisesEmailAndOnlyGrantsVerifiedAccounts(array|false $account, ?string $update, int $status, string $message): void
    {
        $command = new CommandTester(new AdminGrantCommand(new AdminGrants($this->connection($account, $update))));
        self::assertSame($status, $command->execute(['email' => "  Admin@Example.TEST \n"]));
        self::assertStringContainsString($message, $command->getDisplay());
        self::assertStringContainsString('admin@example.test', $command->getDisplay());
    }

    public static function revocations(): iterable
    {
        yield 'admin is revoked' => [['id' => 4, 'admin_granted_at' => '2026-10-16 08:00:00'], 'SET admin_granted_at = NULL', Command::SUCCESS, 'Revoked'];
        yield 'non-admin is idempotent' => [['id' => 4, 'admin_granted_at' => null], null, Command::SUCCESS, 'not an administrator'];
        yield 'unknown account fails' => [false, null, Command::FAILURE, 'No account uses'];
    }

    /** @param array<string, mixed>|false $account */
    #[DataProvider('revocations')]
    public function testRevokeIsIdempotentAndRejectsUnknownAccounts(array|false $account, ?string $update, int $status, string $message): void
    {
        $command = new CommandTester(new AdminRevokeCommand(new AdminGrants($this->connection($account, $update))));
        self::assertSame($status, $command->execute(['email' => 'ADMIN@example.test']));
        self::assertStringContainsString($message, $command->getDisplay());
    }

    public function testListShowsEmailsAndUtcGrantTimesWithoutSecrets(): void
    {
        $db = $this->createMock(Connection::class);
        $db->expects(self::exactly(2))->method('fetchAllAssociative')->with(self::logicalAnd(self::stringContains('admin_granted_at IS NOT NULL'), self::logicalNot(self::stringContains('password'))))
            ->willReturnOnConsecutiveCalls([['email' => 'first@example.test', 'admin_granted_at' => '2026-10-16 08:00:00'], ['email' => '<b>x@example.test', 'admin_granted_at' => '2026-10-16 09:00:00']], []);
        $command = new CommandTester(new AdminListCommand(new AdminGrants($db)));
        self::assertSame(Command::SUCCESS, $command->execute([]));
        self::assertStringContainsString('first@example.test', $command->getDisplay());
        self::assertStringContainsString('2026-10-16T08:00:00+00:00', $command->getDisplay());
        self::assertStringContainsString('<b>x@example.test', $command->getDisplay());
        self::assertSame(Command::SUCCESS, $command->execute([]));
        self::assertStringContainsString('No administrators.', $command->getDisplay());
    }
}
