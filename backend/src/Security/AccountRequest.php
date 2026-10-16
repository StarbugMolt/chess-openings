<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AccountRequest
{
    public function __construct(private readonly CsrfTokenManagerInterface $csrf)
    {
    }

    /** @return array<string, mixed> */
    public function read(Request $request): array
    {
        if ((int) $request->headers->get('Content-Length', '0') > 4096 || strlen($request->getContent()) > 4096 || 'json' !== $request->getContentTypeFormat()) {
            throw new BadRequestHttpException();
        }
        if (!$this->csrf->isTokenValid(new CsrfToken('account', $request->headers->get('X-CSRF-Token')))) {
            throw new AccessDeniedHttpException();
        }
        try {
            $data = json_decode($request->getContent(), true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new BadRequestHttpException();
        }
        if (!is_array($data) || array_is_list($data) && [] !== $data) {
            throw new BadRequestHttpException();
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    public function email(array $data): string
    {
        $email = $data['email'] ?? null;
        if (!is_string($email) || strlen($email) > 254 || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException();
        }

        return strtolower(trim($email));
    }

    /** @param array<string, mixed> $data */
    public function password(#[\SensitiveParameter] array $data, bool $new = false): string
    {
        $password = $data['password'] ?? null;
        if (!is_string($password) || strlen($password) > 256 || mb_strlen($password) < ($new ? 12 : 1)) {
            throw new BadRequestHttpException();
        }

        return $password;
    }

    /** @param array<string, mixed> $data */
    public function token(#[\SensitiveParameter] array $data): string
    {
        $token = $data['token'] ?? null;
        if (!is_string($token) || 1 !== preg_match('/\A[a-f0-9]{64}\z/', $token)) {
            throw new BadRequestHttpException();
        }

        return $token;
    }
}
