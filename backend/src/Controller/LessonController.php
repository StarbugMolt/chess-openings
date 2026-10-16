<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Security\AccountRateLimiter;
use App\Security\AccountRequest;
use App\Service\Lesson\CompletionRecorder;
use App\Service\Lesson\LessonReader;
use App\Service\OpeningImport\CatalogueReader;
use App\Service\Practice\PracticeReader;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class LessonController extends AbstractController
{
    public function __construct(private readonly LessonReader $lessons, private readonly CatalogueReader $catalogue)
    {
    }

    #[Route('/api/courses', methods: ['GET'])]
    public function courses(): JsonResponse
    {
        try {
            return new JsonResponse(['courses' => $this->lessons->courses()], 200, ['Cache-Control' => 'public, max-age=0, must-revalidate']);
        } catch (DbalException $error) {
            throw new ServiceUnavailableHttpException(previous: $error);
        }
    }

    #[Route('/api/lessons/{id}', requirements: ['id' => '[a-z0-9-]{1,48}'], methods: ['GET'])]
    public function lesson(string $id): JsonResponse
    {
        $user = $this->user();
        try {
            $data = $this->catalogue->snapshot(fn (array $snapshot): array => ['lesson' => $this->lessons->lesson($id), 'catalogueRevision' => $snapshot['catalogueRevision'], 'progress' => $this->lessons->progress($user->getId() ?? throw new \LogicException())]);

            return new JsonResponse($data, 200, ['Cache-Control' => 'private, no-store']);
        } catch (DbalException $error) {
            throw new ServiceUnavailableHttpException(previous: $error);
        }
    }

    #[Route('/api/me/completions', methods: ['POST'])]
    public function completion(Request $request, AccountRequest $input, CompletionRecorder $recorder, AccountRateLimiter $limits): JsonResponse
    {
        $user = $this->user();
        try {
            $data = $input->read($request);
            $limits->consume('completion-user', (string) $user->getId(), 60, 900);
            $recorder->record($user->getId() ?? throw new BadRequestHttpException(), $data);
        } catch (DbalException $error) {
            throw new ServiceUnavailableHttpException(previous: $error);
        }

        return new JsonResponse(['saved' => true], 200, ['Cache-Control' => 'private, no-store']);
    }

    #[Route('/api/me/progress', methods: ['GET'])]
    public function progress(): JsonResponse
    {
        try {
            return new JsonResponse(['progress' => $this->lessons->progress($this->user()->getId() ?? throw new \LogicException())], 200, ['Cache-Control' => 'private, no-store']);
        } catch (DbalException $error) {
            throw new ServiceUnavailableHttpException(previous: $error);
        }
    }

    #[Route('/api/me/practice', methods: ['GET'])]
    public function practice(PracticeReader $practice): JsonResponse
    {
        try {
            return new JsonResponse($practice->read($this->user()->getId() ?? throw new \LogicException()), 200, ['Cache-Control' => 'private, no-store']);
        } catch (DbalException $error) {
            throw new ServiceUnavailableHttpException(previous: $error);
        }
    }

    private function user(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
