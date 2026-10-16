<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['onException', -64]];
    }

    public function onException(ExceptionEvent $event): void
    {
        $path = $event->getRequest()->getPathInfo();
        if ('/api' !== $path && !str_starts_with($path, '/api/')) {
            return;
        }

        $error = $event->getThrowable();
        $status = $error instanceof HttpExceptionInterface ? $error->getStatusCode() : 500;
        $headers = $error instanceof HttpExceptionInterface ? $error->getHeaders() : [];
        $message = match ($status) {
            404 => 'Not found.',
            405 => 'Method not allowed.',
            400 => 'Check the supplied details and input limits.',
            403 => 'Refresh the page and try again.',
            429 => 'Too many requests. Please try again later.',
            default => 'Request could not be completed.',
        };
        $event->setResponse(new JsonResponse(['error' => $message], $status, [...$headers, 'Cache-Control' => 'no-store']));
    }
}
