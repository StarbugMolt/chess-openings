<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/** Doctrine's direct write commands bypass the shared database operation lock. */
final class MigrationEntrypointSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => ['guard', 100]];
    }

    public function guard(ConsoleCommandEvent $event): void
    {
        if (in_array($event->getCommand()?->getName(), ['doctrine:migrations:migrate', 'doctrine:migrations:execute'], true)) {
            throw new \RuntimeException('Use app:schema:migrate or app:database:prepare to preserve the shared lock and schema/import/lesson ordering.');
        }
    }
}
