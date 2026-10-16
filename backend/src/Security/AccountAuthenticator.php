<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class AccountAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    public function __construct(private readonly AccountRequest $input, private readonly AccountRateLimiter $limits, private readonly EntityManagerInterface $em)
    {
    }

    public function supports(Request $request): bool
    {
        return '/api/auth/login' === $request->getPathInfo() && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $data = $this->input->read($request);
        $email = $this->input->email($data);
        $password = $this->input->password($data);
        $remember = $data['rememberMe'] ?? false;
        if (!is_bool($remember)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException();
        }
        $network = AccountRateLimiter::clientNetwork($request->getClientIp());
        $this->limits->consume('login-ip', $network, 60, 900);
        $this->limits->consume('login-email-ip', $email."\0".$network, 10, 900);

        return new Passport(new UserBadge($email, function (string $email): User {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($user instanceof User) {
                return $user;
            }
            // A real hash check also runs for unknown addresses. This is a public dummy hash.
            $dummy = new User($email);
            $dummy->setPassword('$argon2id$v=19$m=65536,t=4,p=1$TlY3TmpRcnpEUnNuaUwvcQ$3gx2kmI/WDjVQZI8Rs0PyWoyYPSeSDBUMMgbz52sb74');

            return $dummy;
        }), new PasswordCredentials($password), [new RememberMeBadge(['rememberMe' => $remember])]);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): Response
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Unexpected authenticated user.');
        }
        $this->limits->reset('login-email-ip', $user->getEmail()."\0".AccountRateLimiter::clientNetwork($request->getClientIp()));

        return new JsonResponse(['user' => $user->toArray()], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        return new JsonResponse(['error' => 'Unable to sign in. Check your credentials and verify your email.'], 401, ['Cache-Control' => 'private, no-store']);
    }

    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        return new JsonResponse(['error' => 'Sign in to continue.'], 401, ['Cache-Control' => 'private, no-store']);
    }
}
