<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\OpeningImport\CatalogueSynchronizer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:openings:sync', description: 'Fetch, legally replay, stage and atomically activate the local opening catalogue')]
final class SyncOpeningsCommand extends Command
{
    public function __construct(private readonly CatalogueSynchronizer $synchronizer)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('source-ref', null, InputOption::VALUE_REQUIRED, 'Immutable 40-character upstream SHA; omitted resolves upstream master once')
            ->addOption('source-directory', null, InputOption::VALUE_REQUIRED, 'Read five local TSVs instead of downloading; requires --source-ref')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Validate and report without persistent DB writes')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Explicitly accept reviewed count/removal safeguards; cannot bypass invalid chess/source data');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sha = $input->getOption('source-ref');
        $directory = $input->getOption('source-directory');
        if ((null !== $sha && !is_string($sha)) || (null !== $directory && !is_string($directory))) {
            throw new \InvalidArgumentException('Source options must be strings.');
        }
        $summary = $this->synchronizer->sync($sha, $directory, (bool) $input->getOption('dry-run'), (bool) $input->getOption('force'));
        $output->writeln(json_encode($summary, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
