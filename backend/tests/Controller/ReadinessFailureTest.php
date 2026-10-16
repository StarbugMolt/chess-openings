<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\HealthController;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ReadinessFailureTest extends TestCase
{
    public function testAnOutageIsGenericAndStillIdentifiesTheExecutingRelease(): void
    {
        $directory = sys_get_temp_dir().'/chess-health-'.bin2hex(random_bytes(8));
        mkdir($directory.'/public', 0700, true);
        $release = str_repeat('a', 40).'-20261015060000-12345678';
        file_put_contents($directory.'/public/release.txt', $release);
        $database = $this->createMock(Connection::class);
        $database->method('fetchOne')->willThrowException(new \RuntimeException('private database credentials'));

        try {
            $response = (new HealthController($database, new NullLogger(), $directory))();
            self::assertSame(503, $response->getStatusCode());
            self::assertSame('{"status":"unavailable"}', $response->getContent());
            self::assertSame($release, $response->headers->get('X-Release-Id'));
        } finally {
            unlink($directory.'/public/release.txt');
            rmdir($directory.'/public');
            rmdir($directory);
        }
    }
}
