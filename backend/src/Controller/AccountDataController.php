<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Security\AccountRateLimiter;
use App\Security\AccountRequest;
use App\Service\Account\AccountData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class AccountDataController extends AbstractController
{
    public function __construct(private readonly AccountData $data, private readonly AccountRequest $input, private readonly AccountRateLimiter $limits)
    {
    }

    #[Route('/api/me/data-export', methods: ['POST'])]
    public function export(Request $request): JsonResponse
    {
        [$userId, $password] = $this->confirmation($request);
        $data = $this->data->export($userId, $password);
        if (null === $data) {
            return $this->wrongPassword();
        }
        $response = new JsonResponse($data);
        $response->setEncodingOptions(JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition('attachment', 'chess-openings-data-'.gmdate('Y-m-d').'.json'));
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }

    #[Route('/api/me', methods: ['DELETE'])]
    public function delete(Request $request, Security $security): Response
    {
        [$userId, $password] = $this->confirmation($request);
        if (!$this->data->delete($userId, $password)) {
            return $this->wrongPassword();
        }
        // The request was already CSRF-validated. Use configured logout listeners for
        // session invalidation and remember-cookie clearing, after the DB commit.
        $response = $security->logout(false) ?? new JsonResponse();
        $response->setContent('{"deleted":true}');
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /** @return array{int, string} */
    private function confirmation(Request $request): array
    {
        $body = $this->input->read($request);
        if (['password'] !== array_keys($body)) {
            throw new BadRequestHttpException();
        }
        $password = $this->input->password($body);
        $user = $this->getUser();
        if (!$user instanceof User || null === $id = $user->getId()) {
            throw $this->createAccessDeniedException();
        }
        $this->limits->consume('account-data-network', AccountRateLimiter::clientNetwork($request->getClientIp()), 30, 900);
        // Shared across both actions/browsers; success also counts toward the window.
        $this->limits->consume('account-data-user', (string) $id, 5, 900);

        return [$id, $password];
    }

    private function wrongPassword(): JsonResponse
    {
        return new JsonResponse(['error' => 'Check your current password and try again.'], 400);
    }
}
