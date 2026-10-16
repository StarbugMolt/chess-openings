<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Admin\AdminGrants;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:admin:list', description: 'List administrator emails and grant times (UTC).')]
final class AdminListCommand extends Command
{
    public function __construct(private readonly AdminGrants $grants)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $admins = $this->grants->list();
        if ([] === $admins) {
            $output->writeln('No administrators.');

            return Command::SUCCESS;
        }
        $table = new Table($output);
        $table->setHeaders(['Email', 'Granted at (UTC)']);
        foreach ($admins as $admin) {
            $table->addRow([OutputFormatter::escape($admin['email']), $admin['grantedAt']]);
        }
        $table->render();

        return Command::SUCCESS;
    }
}
