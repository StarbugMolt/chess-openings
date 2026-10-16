<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Migrations\CatalogueOperationLock;
use App\Service\OpeningImport\CatalogueSynchronizer;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:database:prepare', description: 'Apply schema, bootstrap the first pinned source catalogue, then apply lesson data migrations')]
final class PrepareDatabaseCommand extends Command
{
    public function __construct(private readonly CatalogueOperationLock $lock, private readonly Connection $connection, private readonly CatalogueSynchronizer $synchronizer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('source-ref', null, InputOption::VALUE_REQUIRED, 'Override the first-install pinned SHA explicitly', CatalogueSynchronizer::BOOTSTRAP_SHA)
            ->addOption('source-directory', null, InputOption::VALUE_REQUIRED, 'First-install offline source fixture/checkout')
            ->addOption('lock-timeout', null, InputOption::VALUE_REQUIRED, 'Wait 0–600 seconds for another database operation', '120');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timeout = $input->getOption('lock-timeout');
        if (!is_string($timeout) || !preg_match('/\A(?:0|[1-9][0-9]{0,2})\z/', $timeout) || (int) $timeout > 600) {
            throw new \InvalidArgumentException('--lock-timeout must be an integer between 0 and 600 seconds.');
        }

        return $this->lock->run(function () use ($input, $output): int {
            $application = $this->getApplication();
            if (null === $application) {
                throw new \LogicException('Database preparation requires the Console application.');
            }
            $arguments = new ArrayInput([]);
            $arguments->setInteractive(false);
            $code = $application->find('app:schema:migrate')->run($arguments, $output);
            if (Command::SUCCESS !== $code) {
                return $code;
            }
            $this->lock->assertHeld();
            $active = $this->connection->fetchOne('SELECT active_batch_id FROM opening_catalogue_state WHERE id=1');
            if (false === $active) {
                throw new \RuntimeException('Catalogue state row is missing; repair the schema before database preparation.');
            }
            if (null === $active) {
                $sha = $input->getOption('source-ref');
                $directory = $input->getOption('source-directory');
                if (!is_string($sha) || (null !== $directory && !is_string($directory))) {
                    throw new \InvalidArgumentException('Bootstrap source options must be strings.');
                }
                $summary = $this->synchronizer->sync($sha, $directory);
                $output->writeln(json_encode($summary, JSON_THROW_ON_ERROR));
            } else {
                $output->writeln('Existing catalogue retained; deployment does not run a weekly refresh.');
            }
            $arguments = new ArrayInput([]);
            $arguments->setInteractive(false);

            return $application->find('app:lessons:migrate')->run($arguments, $output);
        }, (int) $timeout);
    }
}
