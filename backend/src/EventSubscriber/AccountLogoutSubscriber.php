<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class AccountLogoutSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => ['logout', -128]];
    }

    public function logout(LogoutEvent $event): void
    {
        $event->setResponse(new JsonResponse(['message' => 'Signed out.'], 200, ['Cache-Control' => 'private, no-store']));
    }
}
