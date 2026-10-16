<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\OpeningImport\OpeningGraphBuilder;
use App\Service\OpeningImport\OpeningSourceFetcher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:openings:validate', description: 'Replay five pinned opening TSVs in memory without writing to the database')]
final class ValidateOpeningSourceCommand extends Command
{
    public function __construct(private readonly OpeningGraphBuilder $builder, private readonly OpeningSourceFetcher $fetcher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('source-directory', null, InputOption::VALUE_REQUIRED, 'Local directory containing a.tsv through e.tsv')
            ->addOption('source-sha', null, InputOption::VALUE_REQUIRED, 'Download all five files at this immutable upstream SHA');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $directory = $input->getOption('source-directory');
        $sha = $input->getOption('source-sha');
        if ((null === $directory) === (null === $sha) || (null !== $directory && !is_string($directory)) || (null !== $sha && !is_string($sha))) {
            $output->writeln('<error>Choose exactly one of --source-directory or --source-sha.</error>');

            return Command::INVALID;
        }
        $temporary = null;
        $start = hrtime(true);
        try {
            if (is_string($sha)) {
                $temporary = sys_get_temp_dir().'/opening-spike-'.bin2hex(random_bytes(8));
                if (!mkdir($temporary, 0700)) {
                    throw new \RuntimeException('Cannot create private temporary directory');
                }
                $this->fetcher->fetch($sha, $temporary);
                $directory = $temporary;
            }
            if (!is_string($directory)) {
                throw new \InvalidArgumentException('Source directory is required');
            }
            $graph = $this->builder->build($this->builder->directory($directory));
            $checksums = [];
            foreach (range('a', 'e') as $letter) {
                $checksums[$letter.'.tsv'] = hash_file('sha256', $directory.'/'.$letter.'.tsv');
            }
            $output->writeln(json_encode([
                'sourceSha' => $sha,
                'files' => $checksums,
                'entries' => count($graph->paths),
                'plies' => $graph->plies,
                'positions' => count($graph->positions),
                'edges' => count($graph->edges),
                'graphChecksum' => $graph->checksum(),
                'elapsedSeconds' => round((hrtime(true) - $start) / 1e9, 3),
                'peakMemoryBytes' => memory_get_peak_usage(true),
                'databaseWrites' => 0,
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>'.OutputFormatter::escape($e->getMessage()).'</error>');

            return Command::FAILURE;
        } finally {
            if (null !== $temporary) {
                foreach (glob($temporary.'/*.tsv') ?: [] as $file) {
                    unlink($file);
                }
                rmdir($temporary);
            }
        }
    }
}
