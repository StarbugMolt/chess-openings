<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\EventSubscriber\AccountEmailSubscriber;
use App\Security\AccountRateLimiter;
use App\Security\AccountRequest;
use App\Service\Account\EmailTokens;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

final class AccountController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly AccountRequest $input, private readonly AccountRateLimiter $limits, private readonly EmailTokens $tokens, private readonly UserPasswordHasherInterface $hasher)
    {
    }

    #[Route('/api/auth/csrf', methods: ['GET'])]
    public function csrf(CsrfTokenManagerInterface $csrf): JsonResponse
    {
        return $this->reply(['csrfToken' => $csrf->getToken('account')->getValue()]);
    }

    #[Route('/api/auth/register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $this->input->read($request);
        $email = $this->input->email($data);
        $password = $this->input->password($data, true);
        $this->mailLimits($request, $email);
        // Hash even if already registered; the response never reveals account existence.
        $candidate = new User($email);
        $candidate->setPassword($this->hasher->hashPassword($candidate, $password));
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            try {
                $this->em->persist($candidate);
                $this->em->flush();
                $user = $candidate;
            } catch (UniqueConstraintViolationException) {
                return $this->accepted();
            }
        }
        if (!$user->isVerified()) {
            $this->tokens->send($user, 'verify', $candidate->getPassword());
        } else {
            $this->tokens->registrationNotice($user);
        }

        return $this->accepted();
    }

    #[Route('/api/auth/request-reset', methods: ['POST'])]
    public function requestReset(Request $request): JsonResponse
    {
        $data = $this->input->read($request);
        $email = $this->input->email($data);
        $this->mailLimits($request, $email);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user instanceof User && $user->isVerified()) {
            $this->tokens->send($user, 'reset');
        }

        return $this->accepted();
    }

    #[Route('/api/auth/request-verification', methods: ['POST'])]
    public function requestVerification(Request $request): JsonResponse
    {
        $data = $this->input->read($request);
        $email = $this->input->email($data);
        $this->mailLimits($request, $email);
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($user instanceof User && !$user->isVerified()) {
            $this->tokens->send($user, 'verify');
        }

        return $this->accepted();
    }

    #[Route('/api/auth/verify-email', methods: ['POST'])]
    public function verify(Request $request): JsonResponse
    {
        $data = $this->input->read($request);
        $this->limits->consume('verify-ip', AccountRateLimiter::clientNetwork($request->getClientIp()), 30, 900);

        return $this->tokenResult($this->tokens->consume($this->input->token($data), 'verify'));
    }

    #[Route('/api/auth/reset-password', methods: ['POST'])]
    public function reset(Request $request): JsonResponse
    {
        $data = $this->input->read($request);
        $this->limits->consume('reset-ip', AccountRateLimiter::clientNetwork($request->getClientIp()), 30, 900);
        $password = $this->input->password($data, true);

        return $this->tokenResult($this->tokens->consume($this->input->token($data), 'reset', fn (): string => $this->hasher->hashPassword(new User('unused@example.test'), $password)));
    }

    #[Route('/api/auth/login', methods: ['POST'])]
    public function login(): JsonResponse
    {
        throw new \LogicException('The authenticator handles this route.');
    }

    #[Route('/api/auth/logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        throw new \LogicException('The firewall handles this route.');
    }

    #[Route('/api/me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->reply(['user' => $user->toArray()]);
    }

    private function mailLimits(Request $request, string $email): void
    {
        $this->limits->consume('mail-ip', AccountRateLimiter::clientNetwork($request->getClientIp()), 20, 3600);
        $this->limits->consume('mail-email', $email, 5, 3600);
        $this->limits->consume('mail-email-day', $email, 10, 86400);
        $request->attributes->set(AccountEmailSubscriber::DELIVERY_REQUEST_ATTRIBUTE, true);
    }

    private function accepted(): JsonResponse
    {
        $response = $this->reply(['message' => 'If this address is eligible, an email will arrive shortly. Check your inbox and spam folder.'], 202);
        // Clients can finish this fixed response before SMTP termination on runtimes without finish_request().
        $response->headers->set('Content-Length', (string) strlen((string) $response->getContent()));
        $response->headers->addCacheControlDirective('no-transform');

        return $response;
    }

    private function tokenResult(bool $success): JsonResponse
    {
        return $this->reply($success ? ['message' => 'Done. You can now sign in.'] : ['error' => 'This link is invalid or expired. Request a new email.'], $success ? 200 : 400);
    }

    /** @param array<string, mixed> $data */
    private function reply(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status, ['Cache-Control' => 'private, no-store', 'Referrer-Policy' => 'no-referrer']);
    }
}
