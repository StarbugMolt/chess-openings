<?php

declare(strict_types=1);

namespace App\Tests\Service\Privacy;

use App\Service\Privacy\PrivacyConfiguration;
use PHPUnit\Framework\TestCase;

final class PrivacyConfigurationTest extends TestCase
{
    public function testOnlyExplicitPublicDetailsAndOwnerRetentionAreExposed(): void
    {
        $notice = (new PrivacyConfiguration('StarbugStone', 'chessopenings@starbugstone.com', 'o2switch', 'prod'))->publicNotice();
        self::assertTrue($notice['configured']);
        self::assertFalse($notice['development']);
        self::assertSame([], $notice['missing']);
        self::assertSame(30, $notice['hostingBackupDays']);
        self::assertSame(30, $notice['deploymentBackupDays']);
        self::assertSame(14, $notice['applicationLogDays']);
        self::assertSame(0, $notice['unverifiedAccountDays']);
        self::assertSame('chessopenings@starbugstone.com', $notice['contactEmail']);
    }

    public function testMissingFieldsRemainExplicitAndTransportCredentialsCannotBePublished(): void
    {
        $notice = (new PrivacyConfiguration('', 'not-an-email', 'smtp://secret:password@mail.private', 'dev'))->publicNotice();
        self::assertFalse($notice['configured']);
        self::assertTrue($notice['development']);
        self::assertSame(['publisher', 'contactEmail', 'emailProvider'], $notice['missing']);
        self::assertSame('', $notice['emailProvider']);
        self::assertStringNotContainsString('secret', json_encode($notice, JSON_THROW_ON_ERROR));
    }
}
