<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\AccountController;
use App\Security\AccountRateLimiter;
use App\Security\AccountRequest;
use App\Service\Account\EmailTokens;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AccountVerificationTest extends TestCase
{
    public function testTokenOnlyVerificationNeverCallsThePasswordHasher(): void
    {
        $csrf = $this->createMock(CsrfTokenManagerInterface::class);
        $csrf->method('isTokenValid')->willReturn(true);
        $limits = $this->createMock(Connection::class);
        $limits->method('transactional')->willReturnCallback(static fn (\Closure $operation): mixed => $operation());
        $limits->method('fetchAssociative')->willReturn(['attempts' => 1, 'expires_at' => time() + 900]);
        $database = $this->createMock(Connection::class);
        $database->method('transactional')->willReturnCallback(static fn (\Closure $operation): bool => $operation());
        $database->method('fetchOne')->willReturn(1);
        $database->method('fetchAssociative')->willReturnOnConsecutiveCalls(['password' => 'previous-hash', 'verified_at' => null], ['user_id' => 1, 'pending_password_hash' => 'registration-hash']);
        $tokens = new EmailTokens($database, $this->createMock(MailerInterface::class), 'https://example.test', 'no-reply@example.test', new NullLogger());
        $hasher = $this->createMock(UserPasswordHasherInterface::class);
        $hasher->expects(self::never())->method('hashPassword');
        $controller = new AccountController($this->createMock(EntityManagerInterface::class), new AccountRequest($csrf), new AccountRateLimiter($limits, 'test-secret'), $tokens, $hasher);
        $request = Request::create('/api/auth/verify-email', 'POST', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => 'csrf-value'], content: json_encode(['token' => str_repeat('a', 64)], JSON_THROW_ON_ERROR));
        self::assertSame(200, $controller->verify($request)->getStatusCode());
    }
}
