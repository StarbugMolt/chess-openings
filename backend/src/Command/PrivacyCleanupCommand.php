<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Privacy\PrivacyCleanup;
use App\Service\Privacy\PrivacyConfiguration;
use App\Service\Privacy\RetentionFiles;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:privacy:cleanup', description: 'Report expired private records/files; --apply performs bounded cleanup.')]
final class PrivacyCleanupCommand extends Command
{
    public function __construct(
        private readonly PrivacyCleanup $cleanup,
        private readonly PrivacyConfiguration $privacy,
        private readonly RetentionFiles $files,
        #[Autowire('%kernel.project_dir%')] private readonly string $project,
        #[Autowire('%kernel.logs_dir%')] private readonly string $logs,
        #[Autowire('%kernel.environment%')] private readonly string $environment,
        #[Autowire('%env(PRIVACY_BACKUP_DIRECTORY)%')] private readonly string $backups,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('apply', null, InputOption::VALUE_NONE, 'Remove eligible records/files; omitted means dry run.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum removals per category per run (1–10000).', '1000')
            ->addOption('check-config', null, InputOption::VALUE_NONE, 'Check public notice fields without reading/deleting private data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $notice = $this->privacy->publicNotice();
        if ($input->getOption('check-config')) {
            $output->writeln(json_encode(['configured' => $notice['configured'], 'missing' => $notice['missing']], JSON_THROW_ON_ERROR));

            return $notice['configured'] ? Command::SUCCESS : Command::FAILURE;
        }
        $limit = $input->getOption('limit');
        if (!is_string($limit) || !preg_match('/\A[1-9]\d{0,4}\z/', $limit) || (int) $limit > 10000) {
            $output->writeln('<error>--limit must be between 1 and 10000.</error>');

            return Command::INVALID;
        }
        $apply = (bool) $input->getOption('apply');
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $backups = $this->backups;
        if ('' === $backups && 'prod' === $this->environment && is_link($this->project.'/.env.local')) {
            $configuration = realpath($this->project.'/.env.local');
            if (false !== $configuration) {
                $backups = dirname($configuration).'/backups';
            }
        }
        $directories = ['logs' => $this->logs, 'sessions' => $this->project.'/var/sessions/'.$this->environment];
        if ('' !== $backups) {
            $directories['backups'] = $backups;
        }
        // Resolve configured root symlinks (shared sessions/logs), but never prune public files.
        $public = realpath($this->project.'/public');
        foreach ($directories as $directory) {
            $real = realpath($directory);
            if (false !== $public && false !== $real && ($real === $public || str_starts_with($real, $public.DIRECTORY_SEPARATOR))) {
                $output->writeln('<error>Retention directories must be outside the webroot.</error>');

                return Command::INVALID;
            }
        }
        $result = ['dryRun' => !$apply, 'at' => $now->format(DATE_ATOM), 'limit' => (int) $limit, 'database' => $this->cleanup->run($apply, (int) $limit, $now), 'files' => [], 'deploymentBackupsConfigured' => '' !== $backups];
        foreach ($directories as $category => $directory) {
            $result['files'][$category] = $this->files->run($directory, $category, $apply, (int) $limit, $now->getTimestamp());
        }
        $output->writeln(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
