<?php

declare(strict_types=1);

namespace App\Tests\Service\Account;

use App\Entity\User;
use App\Service\Account\AccountEmailText;
use App\Service\Account\EmailTokens;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class AccountEmailTextTest extends TestCase
{
    public function testEmailsUseTheRequestedLanguageAndKeepTokensInFragments(): void
    {
        foreach (['en' => 'Verify your', 'fr' => 'Confirmez votre', 'es' => 'Verifica tu'] as $locale => $prefix) {
            foreach (['verify', 'reset'] as $purpose) {
                $connection = $this->createMock(Connection::class);
                $connection->method('transactional')->willReturnCallback(static fn (\Closure $operation): bool => $operation());
                $connection->method('fetchAssociative')->willReturn(['password' => 'hash', 'verified_at' => 'verify' === $purpose ? null : '2026-10-16 00:00:00']);
                $stored = [];
                $connection->method('insert')->willReturnCallback(static function (string $table, array $row) use (&$stored): int {
                    $stored = $row;

                    return 1;
                });
                $mailer = $this->createMock(MailerInterface::class);
                $mailer->expects(self::once())->method('send')->willReturnCallback(static function (Email $email) use ($locale, $purpose, $prefix, &$stored): void {
                    $text = $email->getTextBody() ?? '';
                    self::assertStringContainsString('verify' === $purpose ? $prefix : ['en' => 'Reset your', 'fr' => 'Réinitialisez votre', 'es' => 'Restablece tu'][$locale], $email->getSubject() ?? '');
                    $path = 'verify' === $purpose ? '/verify-email' : '/reset-password';
                    $query = 'en' === $locale ? '' : '?lang='.$locale;
                    self::assertMatchesRegularExpression('~'.preg_quote('https://example.test'.$path.$query, '~').'#token=([a-f0-9]{64})~', $text);
                    preg_match('/#token=([a-f0-9]{64})/', $text, $matches);
                    self::assertSame(hash('sha256', $matches[1]), $stored['token_hash']);
                    self::assertStringNotContainsString('?token=', $text);
                });
                $tokens = new EmailTokens($connection, $mailer, 'https://example.test', 'no-reply@example.test', new NullLogger());
                $tokens->send(new User('language@example.test'), $purpose, 'hash', $locale);
                $tokens->deliver();
            }
        }
    }

    public function testUnsupportedLocalesUseEnglishAndExistingAccountNoticesAreTranslated(): void
    {
        self::assertSame(AccountEmailText::link('verify', 'en', 'https://example.test'), AccountEmailText::link('verify', 'unsupported', 'https://example.test'));
        foreach (['en' => 'already exists', 'fr' => 'existe déjà', 'es' => 'ya existe'] as $locale => $subject) {
            $mailer = $this->createMock(MailerInterface::class);
            $mailer->expects(self::once())->method('send')->willReturnCallback(static function (Email $email) use ($subject): void { self::assertStringContainsString($subject, $email->getSubject() ?? ''); });
            $tokens = new EmailTokens($this->createMock(Connection::class), $mailer, 'https://example.test', 'no-reply@example.test', new NullLogger());
            $tokens->registrationNotice(new User('language@example.test'), $locale);
            $tokens->deliver();
        }
    }
}
