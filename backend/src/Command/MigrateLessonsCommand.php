<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Migrations\CatalogueOperationLock;
use App\Service\Migrations\LessonMigrationFactory;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:lessons:migrate', description: 'Apply lesson data migrations only after schema and an active catalogue exist')]
final class MigrateLessonsCommand extends Command
{
    public function __construct(private readonly Connection $connection, private readonly LessonMigrationFactory $factory, private readonly CatalogueOperationLock $lock, #[Autowire(service: 'doctrine.migrations.dependency_factory')] private readonly DependencyFactory $schemaFactory)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return $this->lock->run(function () use ($output): int {
            if (count($this->schemaFactory->getMigrationStatusCalculator()->getNewMigrations()) > 0) {
                throw new \RuntimeException('Apply pending schema migrations with app:schema:migrate before lesson migrations.');
            }
            try {
                $active = $this->connection->fetchOne('SELECT b.id FROM opening_catalogue_state s JOIN opening_import_batch b ON b.id=s.active_batch_id WHERE s.id=1 AND b.status=? AND s.catalogue_revision>0', ['active']);
            } catch (DbalException $e) {
                throw new \RuntimeException('Apply schema migrations, then import/activate the pinned opening catalogue before lesson migrations.', 0, $e);
            }
            if (false === $active) {
                throw new \RuntimeException('Import and activate the pinned opening catalogue before lesson migrations.');
            }
            $command = new MigrateCommand($this->factory->create());
            $arguments = new ArrayInput(['version' => 'latest', '--allow-no-migration' => true]);
            $arguments->setInteractive(false);

            return $command->run($arguments, $output);
        });
    }
}
