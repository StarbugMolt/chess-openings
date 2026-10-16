<?php

declare(strict_types=1);

namespace App\Service\Account;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class EmailTokens
{
    /** @var list<Email> */
    private array $pendingEmails = [];

    public function __construct(private readonly Connection $connection, private readonly MailerInterface $mailer, #[Autowire('%env(DEFAULT_URI)%')] private readonly string $baseUri, #[Autowire('%env(MAIL_FROM)%')] private readonly string $from, private readonly LoggerInterface $logger)
    {
    }

    public function send(User $user, string $purpose, #[\SensitiveParameter] ?string $pendingPasswordHash = null): void
    {
        if (!in_array($purpose, ['verify', 'reset'], true)) {
            throw new \InvalidArgumentException('Unsupported email purpose.');
        }
        $token = bin2hex(random_bytes(32));
        $ttl = 'verify' === $purpose ? 86400 : 3600;
        $issued = $this->connection->transactional(function () use ($user, $purpose, $token, $ttl, $pendingPasswordHash): bool {
            $account = $this->connection->fetchAssociative('SELECT password, verified_at FROM user_account WHERE id = ? FOR UPDATE', [$user->getId()]);
            if (false === $account || ('verify' === $purpose) !== (null === $account['verified_at'])) {
                return false;
            }
            if ('verify' === $purpose) {
                if (null === $pendingPasswordHash) {
                    // Sequence order is independent of timestamp precision and clock adjustments.
                    $latest = $this->connection->fetchOne("SELECT pending_password_hash FROM account_email_token WHERE user_id = ? AND purpose = 'verify' ORDER BY issued_sequence DESC LIMIT 1", [$user->getId()]);
                    $pendingPasswordHash = is_string($latest) ? $latest : (string) $account['password'];
                }
            } else {
                // Reset links still replace one another; verification links keep their own candidates.
                $this->connection->executeStatement('DELETE FROM account_email_token WHERE user_id = ? AND purpose = ?', [$user->getId(), $purpose]);
            }
            $this->connection->insert('account_email_token', ['token_hash' => hash('sha256', $token), 'user_id' => $user->getId(), 'purpose' => $purpose, 'pending_password_hash' => 'verify' === $purpose ? $pendingPasswordHash : null, 'created_at' => gmdate('Y-m-d H:i:s'), 'expires_at' => gmdate('Y-m-d H:i:s', time() + $ttl)]);

            return true;
        });
        $this->connection->executeStatement('DELETE FROM account_email_token WHERE expires_at <= UTC_TIMESTAMP() LIMIT 100');
        if (!$issued) {
            return;
        }
        // Canonical configured URL, never a request Host header. Fragments avoid access-log tokens.
        $path = 'verify' === $purpose ? '/verify-email' : '/reset-password';
        $link = rtrim($this->baseUri, '/').$path.'#token='.$token;
        $subject = 'verify' === $purpose ? 'Verify your Chess Openings email' : 'Reset your Chess Openings password';
        $expiry = 'verify' === $purpose ? '24 hours' : 'one hour';
        $confirmation = 'verify' === $purpose ? 'Open this link and confirm your email, then sign in with the password you chose:' : 'Open this link and choose your new password:';
        $this->pendingEmails[] = (new Email())->from($this->from)->to($user->getEmail())->subject($subject)->text($subject."\n\n".$confirmation."\n".$link."\n\nThis link expires in ".$expiry." and can be used once. If you did not request it, ignore this email.\n");
    }

    public function registrationNotice(User $user): void
    {
        $this->pendingEmails[] = (new Email())->from($this->from)->to($user->getEmail())->subject('Your Chess Openings account already exists')->text("An account already exists for this address. Sign in, or request a password reset from the sign-in page.\n\nIf you did not request registration, ignore this email.\n");
    }

    /** SMTP runs after the response is sent; delivery is best effort, without durable retries. */
    public function deliver(): void
    {
        $emails = $this->pendingEmails;
        $this->pendingEmails = [];
        foreach ($emails as $email) {
            try {
                $this->mailer->send($email);
            } catch (TransportExceptionInterface) {
                $this->logger->error('Account email delivery failed. Check the configured transport.');
            }
        }
    }

    /**
     * Consume under a user-row lock, serialising concurrent issue/consume operations.
     *
     * @param (\Closure(): string)|null $passwordHash Invoked only for a valid reset token; verification never hashes
     */
    public function consume(#[\SensitiveParameter] string $token, string $purpose, #[\SensitiveParameter] ?\Closure $passwordHash = null): bool
    {
        return $this->connection->transactional(function () use ($token, $purpose, $passwordHash): bool {
            $hash = hash('sha256', $token);
            $id = $this->connection->fetchOne('SELECT user_id FROM account_email_token WHERE token_hash = ? AND purpose = ?', [$hash, $purpose]);
            if (false === $id) {
                return false;
            }
            $account = $this->connection->fetchAssociative('SELECT password, verified_at FROM user_account WHERE id = ? FOR UPDATE', [$id]);
            if (false === $account || ('verify' === $purpose && null !== $account['verified_at'])) {
                return false;
            }
            $row = $this->connection->fetchAssociative('SELECT user_id, pending_password_hash FROM account_email_token WHERE token_hash = ? AND purpose = ? AND expires_at > UTC_TIMESTAMP() FOR UPDATE', [$hash, $purpose]);
            if (false === $row) {
                return false;
            }
            if ('verify' === $purpose) {
                // Each registration link activates its own password; legacy nulls preserve the account hash.
                $boundHash = $row['pending_password_hash'] ?? $account['password'];
                $this->connection->executeStatement('UPDATE user_account SET password = ?, verified_at = UTC_TIMESTAMP() WHERE id = ?', [$boundHash, $id]);
            } elseif ('reset' === $purpose && null !== $passwordHash) {
                // Expiry and single-use checks run before the expensive password hash.
                $this->connection->executeStatement('UPDATE user_account SET password = ? WHERE id = ?', [$passwordHash(), $id]);
            } else {
                throw new \InvalidArgumentException('Unsupported token operation.');
            }
            $this->connection->executeStatement('DELETE FROM account_email_token WHERE user_id = ? AND purpose = ?', [$id, $purpose]);

            return true;
        });
    }
}
