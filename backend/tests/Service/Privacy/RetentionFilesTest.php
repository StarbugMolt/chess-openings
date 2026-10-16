<?php

declare(strict_types=1);

namespace App\Tests\Service\Privacy;

use App\Service\Privacy\RetentionFiles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class RetentionFilesTest extends TestCase
{
    private string $root;
    private int $now = 1800000000;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/chess-retention-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0700);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->root);
    }

    private function file(string $name, int $age): string
    {
        $path = $this->root.'/'.$name;
        file_put_contents($path, 'private disposable test data');
        touch($path, $this->now - $age);

        return $path;
    }

    public function testDryRunBoundaryAndLimitDoNotLoseNewOrUnrelatedSessions(): void
    {
        $old = $this->file('sess_old', 28801);
        $boundary = $this->file('sess_boundary', 28800);
        $new = $this->file('sess_new', 28799);
        $unknown = $this->file('account-export.json', 90000);
        $files = new RetentionFiles();
        self::assertSame(['eligible' => 2, 'removed' => 0, 'skipped' => 0], $files->run($this->root, 'sessions', false, 1, $this->now));
        self::assertFileExists($old);
        self::assertSame(1, $files->run($this->root, 'sessions', true, 1, $this->now)['removed']);
        self::assertFileDoesNotExist($old);
        self::assertFileExists($boundary);
        self::assertSame(1, $files->run($this->root, 'sessions', true, 10, $this->now)['removed']);
        self::assertFileExists($new);
        self::assertFileExists($unknown);
    }

    public function testBusySessionAndChildSymlinkArePreserved(): void
    {
        $path = $this->file('sess_busy', 30000);
        $outside = $this->file('outside-private', 90000);
        symlink($outside, $this->root.'/sess_link');
        $handle = fopen($path, 'rb');
        flock($handle, LOCK_EX);
        try {
            self::assertSame(['eligible' => 1, 'removed' => 0, 'skipped' => 1], (new RetentionFiles())->run($this->root, 'sessions', true, 10, $this->now));
            self::assertFileExists($path);
            self::assertFileExists($outside);
            self::assertTrue(is_link($this->root.'/sess_link'));
        } finally {
            fclose($handle);
        }
        self::assertSame(1, (new RetentionFiles())->run($this->root, 'sessions', true, 10, $this->now)['removed']);
    }

    public function testOnlyValidDatedApplicationLogsExpireAndSharedRootSymlinksWork(): void
    {
        $expired = $this->file('prod-2026-01-01.log', 15 * 86400);
        $recent = $this->file('openings-2026-01-02.log', 13 * 86400);
        $unknown = $this->file('access-2026-01-01.log', 90 * 86400);
        $badDate = $this->file('prod-2026-99-99.log', 90 * 86400);
        symlink($this->root, $this->root.'-shared');
        try {
            self::assertSame(1, (new RetentionFiles())->run($this->root.'-shared', 'logs', true, 10, $this->now)['removed']);
        } finally {
            unlink($this->root.'-shared');
        }
        self::assertFileDoesNotExist($expired);
        self::assertFileExists($recent);
        self::assertFileExists($unknown);
        self::assertFileExists($badDate);
    }

    public function testOnlyCompleteManagedSnapshotsExpireAfterThirtyDays(): void
    {
        $old = $this->file('20260101-120000-123456-aabbccdd.sql', 30 * 86400);
        $recent = $this->file('20260102-120000-123456-aabbccdd.sql', 29 * 86400);
        $manual = $this->file('manual-export.sql', 90 * 86400);
        $partial = $this->file('.dump-private', 90 * 86400);
        self::assertSame(1, (new RetentionFiles())->run($this->root, 'backups', true, 10, $this->now)['removed']);
        self::assertFileDoesNotExist($old);
        self::assertFileExists($recent);
        self::assertFileExists($manual);
        self::assertFileExists($partial);
    }
}
