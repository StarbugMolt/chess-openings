<?php

declare(strict_types=1);

namespace App\Tests\Service\Account;

use App\Entity\User;
use App\Service\Account\AccountData;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AccountDataTest extends TestCase
{
    public static function actions(): iterable
    {
        yield 'download' => ['export'];
        yield 'delete' => ['delete'];
    }

    private function account(): array
    {
        return ['id' => 7, 'email' => 'data@example.test', 'password' => 'fresh-database-hash', 'created_at' => '2026-10-15 08:00:00', 'verified_at' => '2026-10-15 09:00:00'];
    }

    #[DataProvider('actions')]
    public function testWrongConfirmationUsesTheLockedFreshHashAndDoesNotReadOrDeleteData(string $action): void
    {
        $db = $this->createMock(Connection::class);
        $db->method('transactional')->willReturnCallback(static fn (\Closure $work): mixed => $work());
        $db->expects(self::once())->method('fetchAssociative')->with(self::stringContains('FOR UPDATE'), [7])->willReturn($this->account());
        $db->expects(self::never())->method('iterateAssociative');
        $db->expects(self::never())->method('delete');
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::once())->method('isPasswordValid')->with(self::callback(static fn (User $user): bool => 'fresh-database-hash' === $user->getPassword()), 'entered-password')->willReturn(false);
        self::assertSame('export' === $action ? null : false, (new AccountData($db, $hasher))->{$action}(7, 'entered-password'));
    }

    #[DataProvider('actions')]
    public function testConcurrentlyDeletedAccountCannotBeConfirmed(string $action): void
    {
        $db = $this->createMock(Connection::class);
        $db->method('transactional')->willReturnCallback(static fn (\Closure $work): mixed => $work());
        $db->method('fetchAssociative')->willReturn(false);
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('isPasswordValid');
        $this->expectException(UnauthorizedHttpException::class);
        (new AccountData($db, $hasher))->{$action}(7, 'entered-password');
    }

    public function testDownloadDoesNotSilentlyTruncateLargeHistoryOrInferLegacyRecall(): void
    {
        $db = $this->createMock(Connection::class);
        $db->method('transactional')->willReturnCallback(static fn (\Closure $work): mixed => $work());
        $db->method('fetchAssociative')->willReturn($this->account());
        $db->expects(self::exactly(2))->method('iterateAssociative')->willReturnCallback(static function (string $sql, array $parameters): \Generator {
            self::assertSame([7], $parameters);
            if (str_contains($sql, 'account_email_token')) {
                yield ['purpose' => 'reset', 'created_at' => '2026-10-15 10:00:00', 'expires_at' => '2026-10-15 11:00:00'];

                return;
            }
            for ($i = 0; $i < 10000; ++$i) {
                yield ['attempt_id' => (string) $i, 'lesson_id' => 'italian', 'revision' => 1, 'line_id' => 'main', 'playing_color' => 'w', 'hint_count' => 0, 'completed_at' => '2026-10-15 10:00:00', 'assessment_json' => null, 'lesson_title' => 'Italian Game', 'line_title' => 'A retained line'];
            }
        });
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->method('isPasswordValid')->willReturn(true);
        $data = (new AccountData($db, $hasher))->export(7, 'entered-password');
        self::assertNotNull($data);
        self::assertCount(10000, $data['completions']);
        self::assertSame('9999', $data['completions'][9999]['attemptId']);
        self::assertNull($data['completions'][9999]['assessment']);
        self::assertSame('2026-10-15T10:00:00+00:00', $data['completions'][0]['completedAt']);
        self::assertSame(['id', 'email', 'createdAt', 'verifiedAt'], array_keys($data['account']));
        self::assertSame(['purpose', 'createdAt', 'expiresAt'], array_keys($data['emailLinks'][0]));
        self::assertStringNotContainsString('fresh-database-hash', json_encode($data, JSON_THROW_ON_ERROR));
    }
}
