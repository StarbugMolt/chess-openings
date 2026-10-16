<?php

declare(strict_types=1);

namespace App\Tests\Service\Privacy;

use App\Command\PrivacyCleanupCommand;
use App\Service\Privacy\PrivacyCleanup;
use App\Service\Privacy\PrivacyConfiguration;
use App\Service\Privacy\RetentionFiles;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;

final class PrivacyCleanupCommandTest extends TestCase
{
    public function testConfigurationCheckDoesNotReadPrivateData(): void
    {
        $db = $this->createMock(Connection::class);
        $db->expects(self::never())->method('fetchOne');
        $db->expects(self::never())->method('executeStatement');
        foreach (['O2Switch' => 0, '' => 1] as $provider => $status) {
            $command = new CommandTester(new PrivacyCleanupCommand(new PrivacyCleanup($db), new PrivacyConfiguration('Publisher', 'public@example.test', $provider, 'prod'), new RetentionFiles(), '/absent', '/absent/log', 'prod', ''));
            self::assertSame($status, $command->execute(['--check-config' => true]));
            self::assertSame(['configured' => 0 === $status, 'missing' => $status ? ['emailProvider'] : []], json_decode($command->getDisplay(), true));
            self::assertStringNotContainsString('public@example.test', $command->getDisplay());
        }
    }

    public function testInvalidLimitOrPublicDirectoryFailsBeforeDatabaseMutation(): void
    {
        $root = sys_get_temp_dir().'/chess-privacy-command-'.bin2hex(random_bytes(6));
        mkdir($root.'/public', 0700, true);
        $db = $this->createMock(Connection::class);
        $db->expects(self::never())->method('fetchOne');
        $db->expects(self::never())->method('executeStatement');
        $command = new CommandTester(new PrivacyCleanupCommand(new PrivacyCleanup($db), new PrivacyConfiguration('Publisher', 'public@example.test', 'O2Switch', 'prod'), new RetentionFiles(), $root, $root.'/public', 'prod', ''));
        try {
            foreach (['0', '-1', '10001', '1; DELETE', ''] as $limit) {
                self::assertSame(2, $command->execute(['--apply' => true, '--limit' => $limit]));
            }
            self::assertSame(2, $command->execute(['--apply' => true]));
            self::assertStringContainsString('outside the webroot', $command->getDisplay());
        } finally {
            (new Filesystem())->remove($root);
        }
    }
}
