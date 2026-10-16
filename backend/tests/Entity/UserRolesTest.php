<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

final class UserRolesTest extends TestCase
{
    public function testOnlyAGrantTimeAddsTheAdministratorRole(): void
    {
        $user = new User('learner@example.test');
        self::assertFalse($user->isAdmin());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertFalse($user->toArray()['admin']);

        (new \ReflectionProperty(User::class, 'adminGrantedAt'))->setValue($user, new \DateTimeImmutable('2026-10-16 08:00:00', new \DateTimeZone('UTC')));
        self::assertTrue($user->isAdmin());
        self::assertSame(['ROLE_USER', 'ROLE_ADMIN'], $user->getRoles());
        self::assertSame(['id', 'email', 'verified', 'createdAt', 'admin'], array_keys($user->toArray()));
        self::assertTrue($user->toArray()['admin']);
    }

    public function testSessionSerialisationStillReplacesThePasswordHashWithAChecksum(): void
    {
        $user = new User('learner@example.test');
        $user->setPassword('secret-hash');
        $data = $user->__serialize();
        self::assertSame(hash('crc32c', 'secret-hash'), $data["\0".User::class."\0password"]);
        self::assertArrayHasKey("\0".User::class."\0adminGrantedAt", $data);
        self::assertNotContains('secret-hash', $data);
    }
}
