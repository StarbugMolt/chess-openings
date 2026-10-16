<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/** Restrict ORM schema tools to ORM-owned tables without hiding migration history during execution. */
final class ManagedSchemaSubscriber implements EventSubscriberInterface
{
    public const FILTER = '~\A(?!(?:chess_position|chess_move|opening_import_batch|opening_import_file|opening_entry|opening_entry_step|opening_catalogue_state|account_email_token|account_request_limit|authored_chess_position|authored_chess_move|lesson|lesson_revision|lesson_line|lesson_line_step|user_line_completion|doctrine_schema_versions|doctrine_lesson_versions)\z)~';

    private bool $active = false;
    private ?\Closure $previousFilter = null;

    public function __construct(private readonly Connection $connection)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => 'filter', ConsoleEvents::TERMINATE => 'restore', ConsoleEvents::ERROR => 'restore'];
    }

    public function filter(ConsoleCommandEvent $event): void
    {
        $name = $event->getCommand()?->getName();
        if (!in_array($name, ['doctrine:migrations:diff', 'doctrine:schema:update', 'doctrine:schema:drop', 'doctrine:schema:validate'], true)) {
            return;
        }
        $previous = $this->connection->getConfiguration()->getSchemaAssetsFilter();
        $this->previousFilter = \Closure::fromCallable($previous);
        $this->active = true;
        if ('doctrine:migrations:diff' === $name) {
            // Doctrine checks metadata before applying this expression inside DiffGenerator.
            $requested = $event->getInput()->getOption('filter-expression');
            if (null !== $requested && self::FILTER !== $requested) {
                throw new \InvalidArgumentException('Schema diffs must retain the project filter protecting DBAL-managed catalogue and migration tables.');
            }
            // Command::run binds argv again after console.command; set the definition's default too.
            $event->getCommand()->getDefinition()->getOption('filter-expression')->setDefault(self::FILTER);
            $event->getInput()->setOption('filter-expression', self::FILTER);
        } else {
            $this->connection->getConfiguration()->setSchemaAssetsFilter(static fn (string $name): bool => 1 === preg_match(self::FILTER, $name));
        }
    }

    public function restore(ConsoleEvent $event): void
    {
        if ($this->active && null !== $this->previousFilter) {
            $this->connection->getConfiguration()->setSchemaAssetsFilter($this->previousFilter);
            $this->active = false;
        }
    }
}
