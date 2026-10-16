<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Security\AccountRequest;
use App\Security\AccountRoutes;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Bound authenticated sessions without starting sessions for public catalogue responses. */
final class AccountSessionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AccountRequest $input, private readonly AccountRoutes $routes)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::REQUEST => ['expire', 9], KernelEvents::RESPONSE => ['privateResponse', -8]];
    }

    public function expire(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$event->isMainRequest()) {
            return;
        }
        if ('/api/auth/logout' === $request->getPathInfo() && $request->isMethod('POST')) {
            $this->input->read($request);
            $request->request->set('_csrf_token', $request->headers->get('X-CSRF-Token'));
        }
        if (!$this->routes->isPrivate($request) || !$request->hasPreviousSession()) {
            return;
        }
        $session = $request->getSession();
        $started = $session->get('account_started');
        $active = $session->get('account_active');
        if (is_int($started) && is_int($active)) {
            if (time() - $started >= 28800 || time() - $active >= 7200) {
                $session->invalidate();
            } else {
                $session->set('account_active', time());
            }
        }
    }

    public function privateResponse(ResponseEvent $event): void
    {
        if ($this->routes->isPrivate($event->getRequest())) {
            $event->getResponse()->headers->set('Cache-Control', 'private, no-store, no-transform');
            $event->getResponse()->headers->set('Referrer-Policy', 'no-referrer');
        }
    }
}
