<?php

declare(strict_types=1);

namespace App\Service\Privacy;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PrivacyConfiguration
{
    public const BACKUP_DAYS = 30;
    public const LOG_DAYS = 14;
    public const SESSION_SECONDS = 28800;

    public function __construct(
        #[Autowire('%env(PRIVACY_PUBLISHER)%')] private string $publisher,
        #[Autowire('%env(PRIVACY_CONTACT_EMAIL)%')] private string $contact,
        #[Autowire('%env(PRIVACY_EMAIL_PROVIDER)%')] private string $emailProvider,
        #[Autowire('%kernel.environment%')] private string $environment,
    ) {
    }

    /** @return array<string, mixed> Explicit public settings only; no SMTP credentials or host secrets. */
    public function publicNotice(): array
    {
        $publisher = trim($this->publisher);
        $contact = trim($this->contact);
        $emailProvider = trim($this->emailProvider);
        $missing = [];
        if ('' === $publisher || strlen($publisher) > 200 || preg_match('/[\x00-\x1f]/', $publisher)) {
            $missing[] = 'publisher';
            $publisher = '';
        }
        if (strlen($contact) > 254 || !filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            $missing[] = 'contactEmail';
            $contact = '';
        }
        if ('' === $emailProvider || strlen($emailProvider) > 300 || preg_match('/[\x00-\x1f]/', $emailProvider) || str_contains($emailProvider, '://')) {
            $missing[] = 'emailProvider';
            $emailProvider = '';
        }

        return [
            'version' => 1,
            'publisher' => $publisher,
            'contactEmail' => $contact,
            'emailProvider' => $emailProvider,
            'hostingProvider' => 'o2switch',
            'hostingBackupDays' => self::BACKUP_DAYS,
            'deploymentBackupDays' => self::BACKUP_DAYS,
            'applicationLogDays' => self::LOG_DAYS,
            'unverifiedAccountDays' => 0,
            'development' => 'prod' !== $this->environment,
            'configured' => [] === $missing,
            'missing' => $missing,
        ];
    }
}
