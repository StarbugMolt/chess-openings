<?php

declare(strict_types=1);

namespace App\Tests\Service\Admin;

use App\Service\Admin\AdminStats;
use App\Service\Admin\AdminUsers;
use PHPUnit\Framework\TestCase;

final class AdminHelpersTest extends TestCase
{
    public function testSearchPatternTreatsWildcardsAndTheEscapeCharacterLiterally(): void
    {
        self::assertSame('%%', AdminUsers::likePattern(''));
        self::assertSame('%a!%b!_c!!d%', AdminUsers::likePattern('a%b_c!d'));
        self::assertSame('%plain@example.test%', AdminUsers::likePattern('plain@example.test'));
    }

    public function testWeeksStartOnMondayUtcAndEndWithTheCurrentWeek(): void
    {
        // Sunday 23:30 in Paris is still Sunday 21:30 UTC, so the current week began on Monday 12 October.
        $weeks = AdminStats::weekStarts(new \DateTimeImmutable('2026-10-18 23:30:00', new \DateTimeZone('Europe/Paris')));
        self::assertCount(12, $weeks);
        self::assertSame('2026-10-12', $weeks[11]);
        self::assertSame('2026-07-27', $weeks[0]);
        foreach ($weeks as $week) {
            self::assertSame('1', (new \DateTimeImmutable($week))->format('N'));
        }
        $monday = AdminStats::weekStarts(new \DateTimeImmutable('2026-10-19 00:00:00', new \DateTimeZone('UTC')));
        self::assertSame('2026-10-19', $monday[11]);
    }

    public function testDailySeriesHasThirtyUtcDatesEndingToday(): void
    {
        $days = AdminStats::days(new \DateTimeImmutable('2026-10-16 01:00:00', new \DateTimeZone('Asia/Tokyo')));
        self::assertCount(30, $days);
        self::assertSame('2026-10-15', $days[29]);
        self::assertSame('2026-09-16', $days[0]);
        self::assertSame(array_values(array_unique($days)), $days);
    }
}
