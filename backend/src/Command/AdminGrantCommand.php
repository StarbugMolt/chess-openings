<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Admin\AdminGrants;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:admin:grant', description: 'Grant administrator access to an existing verified account.')]
final class AdminGrantCommand extends Command
{
    public function __construct(private readonly AdminGrants $grants)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email address of a verified account');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = AdminGrants::normaliseEmail((string) $input->getArgument('email'));
        $result = $this->grants->grant($email);
        $shown = OutputFormatter::escape($email);
        $output->writeln(match ($result) {
            AdminGrants::GRANTED => sprintf('Granted administrator access to %s. Its existing sessions are signed out on their next request; sign in again to use the admin area.', $shown),
            AdminGrants::ALREADY_ADMIN => sprintf('%s is already an administrator; nothing changed.', $shown),
            AdminGrants::UNKNOWN => sprintf('<error>No account uses %s; nothing changed.</error>', $shown),
            AdminGrants::UNVERIFIED => sprintf('<error>%s has not confirmed its email address; nothing changed.</error>', $shown),
        });

        return in_array($result, [AdminGrants::GRANTED, AdminGrants::ALREADY_ADMIN], true) ? Command::SUCCESS : Command::FAILURE;
    }
}
