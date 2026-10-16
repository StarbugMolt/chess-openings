<?php

declare(strict_types=1);

namespace App\Tests\Service\Account;

use App\Entity\User;
use App\Service\Account\EmailTokens;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;

final class EmailTokensTest extends TestCase
{
    public function testRegistrationNoticeIsDeliveredOnlyAfterTheResponse(): void
    {
        $mail = $this->createMock(MailerInterface::class);
        $count = 0;
        $mail->expects(self::once())->method('send')->willReturnCallback(static function () use (&$count): void { ++$count; });
        $tokens = new EmailTokens($this->createMock(Connection::class), $mail, 'https://example.test', 'no-reply@example.test', new NullLogger());
        $tokens->registrationNotice(new User('private@example.test'));
        self::assertSame(0, $count);
        $tokens->deliver();
        self::assertSame(1, $count);
        $tokens->deliver();
        self::assertSame(1, $count);
    }

    public function testDeliveryFailureDoesNotLogRecipientsTokensOrExceptionDetails(): void
    {
        $mail = $this->createMock(MailerInterface::class);
        $mail->method('send')->willThrowException(new TransportException('private@example.test secret-token smtp-password'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error')->with('Account email delivery failed. Check the configured transport.');
        $tokens = new EmailTokens($this->createMock(Connection::class), $mail, 'https://example.test', 'no-reply@example.test', $logger);
        $tokens->registrationNotice(new User('private@example.test'));
        $tokens->deliver();
    }

    public function testMissingOrExpiredEmailTokenNeverHashesThePassword(): void
    {
        foreach (['verify', 'reset'] as $purpose) {
            foreach ([false, 1] as $id) {
                $connection = $this->createMock(Connection::class);
                $connection->method('transactional')->willReturnCallback(static fn (\Closure $operation): bool => $operation());
                $connection->method('fetchOne')->willReturn($id);
                $connection->method('fetchAssociative')->willReturnOnConsecutiveCalls(['password' => 'existing-hash', 'verified_at' => 'verify' === $purpose ? null : '2026-10-15 00:00:00'], false);
                $tokens = new EmailTokens($connection, $this->createMock(MailerInterface::class), 'https://example.test', 'no-reply@example.test', new NullLogger());
                self::assertFalse($tokens->consume(str_repeat('a', 64), $purpose, static function (): string { self::fail('Invalid token reached the password hasher.'); }));
            }
        }
    }

    public function testVerificationInstallsItsBindingOrLegacyHashWithoutInvokingTheHasher(): void
    {
        foreach (['registration-hash', null] as $boundHash) {
            $connection = $this->createMock(Connection::class);
            $connection->method('transactional')->willReturnCallback(static fn (\Closure $operation): bool => $operation());
            $connection->method('fetchOne')->willReturn(1);
            $connection->method('fetchAssociative')->willReturnOnConsecutiveCalls(['password' => 'legacy-account-hash', 'verified_at' => null], ['user_id' => 1, 'pending_password_hash' => $boundHash]);
            $updates = [];
            $connection->method('executeStatement')->willReturnCallback(static function (string $sql, array $params) use (&$updates): int {
                if (str_starts_with($sql, 'UPDATE user_account')) {
                    $updates[] = $params;
                }

                return 1;
            });
            $tokens = new EmailTokens($connection, $this->createMock(MailerInterface::class), 'https://example.test', 'no-reply@example.test', new NullLogger());
            self::assertTrue($tokens->consume(str_repeat('a', 64), 'verify', static function (): string { self::fail('Verification must not compute a password hash.'); }));
            self::assertSame([[$boundHash ?? 'legacy-account-hash', 1]], $updates);
        }
    }
}
