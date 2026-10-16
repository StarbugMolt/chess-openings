<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Security\AccountAuthenticator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class AccountLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly CsrfTokenManagerInterface $csrf)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'login'];
    }

    public function login(LoginSuccessEvent $event): void
    {
        $session = $event->getRequest()->getSession();
        $session->set('account_started', time());
        $session->set('account_active', time());
        if ($event->getAuthenticator() instanceof AccountAuthenticator) {
            $this->csrf->refreshToken('account');
        }
    }
}
