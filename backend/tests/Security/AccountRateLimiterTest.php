<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\AccountRateLimiter;
use PHPUnit\Framework\TestCase;

final class AccountRateLimiterTest extends TestCase
{
    public function testIpv6PrivacyAddressesShareTheirNetworkBucket(): void
    {
        self::assertSame('2001:db8:1234:5678::/64', AccountRateLimiter::clientNetwork('2001:db8:1234:5678:abcd:ffff:0:1'));
        self::assertSame(AccountRateLimiter::clientNetwork('2001:db8:1234:5678::1'), AccountRateLimiter::clientNetwork('2001:0db8:1234:5678:eeee::2'));
        self::assertNotSame(AccountRateLimiter::clientNetwork('2001:db8:1234:5678::1'), AccountRateLimiter::clientNetwork('2001:db8:1234:5679::1'));
        self::assertSame('192.0.2.1', AccountRateLimiter::clientNetwork('::ffff:192.0.2.1'));
        self::assertSame('192.0.2.1', AccountRateLimiter::clientNetwork('192.0.2.1'));
        self::assertSame('unknown', AccountRateLimiter::clientNetwork(null));
    }
}
