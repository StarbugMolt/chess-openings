<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\OpeningImport\CatalogueReader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:openings:status', description: 'Show active source revision, last check and ten recent import batches')]
final class OpeningStatusCommand extends Command
{
    public function __construct(private readonly CatalogueReader $reader)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(json_encode($this->reader->operationalStatus(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
