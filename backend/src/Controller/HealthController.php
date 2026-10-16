<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController
{
    public function __construct(
        private readonly Connection $database,
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDirectory,
    ) {
    }

    #[Route('/api/health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $headers = ['Cache-Control' => 'no-store'];
        $file = $this->projectDirectory.'/public/release.txt';
        if (is_file($file)) {
            $release = trim((string) file_get_contents($file));
            if (preg_match('/^[a-f0-9]{40}-[0-9]{14}-[a-f0-9]{8}$/D', $release)) {
                $headers['X-Release-Id'] = $release;
            }
        }

        try {
            if (1 !== (int) $this->database->fetchOne('SELECT 1')) {
                throw new \RuntimeException('Database readiness check failed.');
            }

            return new JsonResponse(['status' => 'ok'], headers: $headers);
        } catch (\Throwable $error) {
            $this->logger->error('Database readiness check failed.', ['errorType' => $error::class]);

            return new JsonResponse(['status' => 'unavailable'], 503, $headers);
        }
    }
}
