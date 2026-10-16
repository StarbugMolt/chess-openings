<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\Privacy\PrivacyConfiguration;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class PrivacyController
{
    #[Route('/api/privacy', methods: ['GET'])]
    public function notice(PrivacyConfiguration $privacy): JsonResponse
    {
        return new JsonResponse($privacy->publicNotice(), 200, ['Cache-Control' => 'public, max-age=0, must-revalidate', 'X-Content-Type-Options' => 'nosniff']);
    }
}
