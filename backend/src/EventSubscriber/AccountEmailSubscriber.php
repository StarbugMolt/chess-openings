<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Service\Account\EmailTokens;
use Symfony\Component\DependencyInjection\Attribute\AutowireServiceClosure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Public catalogue requests never resolve account mail configuration or instantiate its services. */
final class AccountEmailSubscriber implements EventSubscriberInterface
{
    public const DELIVERY_REQUEST_ATTRIBUTE = '_account_mail_delivery';

    /** @param \Closure(): EmailTokens $tokens */
    public function __construct(#[AutowireServiceClosure(EmailTokens::class)] private readonly \Closure $tokens)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::TERMINATE => 'deliver'];
    }

    public function deliver(TerminateEvent $event): void
    {
        if (true === $event->getRequest()->attributes->get(self::DELIVERY_REQUEST_ATTRIBUTE)) {
            ($this->tokens)()->deliver();
        }
    }
}
