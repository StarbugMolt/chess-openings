<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Migrations\CatalogueOperationLock;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:schema:migrate', description: 'Apply schema migrations under the shared catalogue/deployment database lock')]
final class MigrateSchemaCommand extends Command
{
    public function __construct(#[Autowire(service: 'doctrine.migrations.dependency_factory')] private readonly DependencyFactory $factory, private readonly CatalogueOperationLock $lock)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->lock->run(function () use ($output): int {
            $arguments = new ArrayInput(['version' => 'latest', '--allow-no-migration' => true]);
            $arguments->setInteractive(false);

            return (new MigrateCommand($this->factory))->run($arguments, $output);
        });
    }
}
