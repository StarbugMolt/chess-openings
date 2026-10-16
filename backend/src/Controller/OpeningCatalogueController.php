<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\OpeningImport\CatalogueReader;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class OpeningCatalogueController
{
    public function __construct(private CatalogueReader $reader)
    {
    }

    #[Route('/api/openings/catalogue-status', methods: ['GET'])]
    public function status(Request $request): JsonResponse
    {
        try {
            return $this->response($request, $this->reader->status());
        } catch (DbalException $error) {
            throw new ServiceUnavailableHttpException(previous: $error);
        }
    }

    #[Route('/api/openings', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $search = $request->query->getString('search', '');
        $eco = $request->query->getString('eco', '');
        $page = $request->query->getString('page', '1');
        $limit = $request->query->getString('limit', '20');
        if (!mb_check_encoding($search, 'UTF-8') || mb_strlen($search) > 100 || preg_match('/[\x00-\x1f\x7f]/', $search)
            || ('' !== $eco && !preg_match('/\A[A-E][0-9]{0,2}\z/', $eco))
            || !preg_match('/\A[1-9][0-9]{0,3}\z/', $page)
            || !preg_match('/\A[1-9][0-9]?\z/', $limit) || (int) $limit > 50) {
            throw new BadRequestHttpException('Invalid catalogue query.');
        }
        try {
            return $this->response($request, $this->reader->search(trim($search), $eco, (int) $page, (int) $limit));
        } catch (DbalException $error) {
            throw new ServiceUnavailableHttpException(previous: $error);
        }
    }

    /** @param array<string, mixed> $payload */
    private function response(Request $request, array $payload): JsonResponse
    {
        $response = new JsonResponse($payload);
        $response->setPublic()->setMaxAge(0)->headers->addCacheControlDirective('must-revalidate');
        $response->setEtag(hash('sha256', $response->getContent() ?: ''));
        $response->isNotModified($request);

        return $response;
    }
}
