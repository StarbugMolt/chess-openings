<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Security\AccountRateLimiter;
use App\Security\AccountRequest;
use App\Service\Admin\AdminStats;
use App\Service\Admin\AdminUsers;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;

/** Owner-approved admin API (docs/ADMIN_REVIEW.md §3/§8). Access control also requires ROLE_ADMIN. */
final class AdminController extends AbstractController
{
    private const MAX_PAGE = 9999;
    private const MAX_SEARCH_BYTES = 254;

    public function __construct(private readonly AdminUsers $users, private readonly AdminStats $stats)
    {
    }

    #[Route('/api/admin/users', methods: ['GET'])]
    public function users(Request $request): JsonResponse
    {
        $this->admin();
        $search = $request->query->all()['search'] ?? '';
        $page = $request->query->all()['page'] ?? '1';
        if (!is_string($search) || strlen($search) > self::MAX_SEARCH_BYTES || !mb_check_encoding($search, 'UTF-8')
            || !is_string($page) || 1 !== preg_match('/\A[1-9][0-9]{0,3}\z/', $page) || (int) $page > self::MAX_PAGE) {
            throw new BadRequestHttpException();
        }

        try {
            return $this->reply($this->users->list(mb_strtolower(trim($search)), (int) $page));
        } catch (DbalException $error) {
            throw new ServiceUnavailableHttpException(previous: $error);
        }
    }

    #[Route('/api/admin/users/{id}', requirements: ['id' => '[1-9][0-9]{0,9}'], methods: ['DELETE'])]
    public function delete(string $id, Request $request, AccountRequest $input, AccountRateLimiter $limits): JsonResponse
    {
        $adminId = $this->admin();
        try {
            $body = $input->read($request);
            if (['password'] !== array_keys($body)) {
                throw new BadRequestHttpException();
            }
            $password = $input->password($body);
            // The same persistent confirmation buckets as account download/deletion.
            $limits->consume('account-data-network', AccountRateLimiter::clientNetwork($request->getClientIp()), 30, 900);
            $limits->consume('account-data-user', (string) $adminId, 5, 900);

            $result = $this->users->delete($adminId, (int) $id, $password);
        } catch (DbalException $error) {
            throw new ServiceUnavailableHttpException(previous: $error);
        }

        return match ($result) {
            AdminUsers::DELETED => $this->reply(['deleted' => true]),
            AdminUsers::WRONG_PASSWORD => $this->reply(['error' => 'Check your current password and try again.'], 400),
            AdminUsers::UNKNOWN => $this->reply(['error' => 'This account no longer exists.'], 404),
            AdminUsers::REFUSED => $this->reply(['error' => 'Your own account and other administrators cannot be deleted here.'], 409),
        };
    }

    #[Route('/api/admin/stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        $this->admin();
        try {
            return $this->reply($this->stats->stats());
        } catch (DbalException $error) {
            throw new ServiceUnavailableHttpException(previous: $error);
        }
    }

    /** Defence in depth behind the ^/api/admin access_control rule. */
    private function admin(): int
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->getUser();
        if (!$user instanceof User || null === $id = $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        return $id;
    }

    /** @param array<string, mixed> $data */
    private function reply(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status, ['Cache-Control' => 'private, no-store', 'Referrer-Policy' => 'no-referrer']);
    }
}
