<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/** One protected route set drives access control, session expiry and response privacy. */
final class AccountRoutes implements RequestMatcherInterface
{
    private const AUTHENTICATED = '~^/api/(?:me|lessons|training|progress)(?:/|$)~';

    public function matches(Request $request): bool
    {
        return 1 === preg_match(self::AUTHENTICATED, $request->getPathInfo());
    }

    public function isPrivate(Request $request): bool
    {
        $path = $request->getPathInfo();

        return '/api/auth' === $path || str_starts_with($path, '/api/auth/') || $this->matches($request);
    }
}
