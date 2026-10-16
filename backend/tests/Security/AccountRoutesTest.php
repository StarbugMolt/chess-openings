<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Security\AccountRoutes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class AccountRoutesTest extends TestCase
{
    #[DataProvider('paths')]
    public function testProtectedPrefixesAlsoReceiveAccountPrivacy(string $path, bool $authenticated, bool $private): void
    {
        $routes = new AccountRoutes();
        $request = Request::create($path);
        self::assertSame($authenticated, $routes->matches($request));
        self::assertSame($private, $routes->isPrivate($request));
    }

    public static function paths(): iterable
    {
        yield ['/api/me', true, true];
        yield ['/api/me/progress', true, true];
        yield ['/api/lessons/italian/graph', true, true];
        yield ['/api/training', true, true];
        yield ['/api/progress/1', true, true];
        yield ['/api/admin', true, true];
        yield ['/api/admin/users', true, true];
        yield ['/api/admin/users/12', true, true];
        yield ['/api/admin/stats', true, true];
        yield ['/api/auth/csrf', false, true];
        yield ['/api/openings', false, false];
        yield ['/api/lessons-extra', false, false];
        yield ['/api/auth-extra', false, false];
        yield ['/api/administrator', false, false];
    }
}
