<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\OpeningImport\CatalogueMaintenance;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:openings:rollback', description: 'Activate a retained validated source batch with a new monotonic revision')]
final class RollbackOpeningsCommand extends Command
{
    public function __construct(private readonly CatalogueMaintenance $maintenance)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('batch', InputArgument::REQUIRED, 'Retained batch ID shown by app:openings:status');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $batch = $input->getArgument('batch');
        if (!is_string($batch) || !preg_match('/\A[1-9][0-9]{0,17}\z/', $batch)) {
            throw new \InvalidArgumentException('Batch ID must be a positive integer.');
        }
        $revision = $this->maintenance->rollback((int) $batch);
        $output->writeln(json_encode(['batchId' => (int) $batch, 'catalogueRevision' => $revision], JSON_THROW_ON_ERROR));

        return Command::SUCCESS;
    }
}
