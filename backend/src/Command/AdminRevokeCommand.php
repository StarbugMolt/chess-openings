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

#[AsCommand(name: 'app:admin:revoke', description: 'Revoke administrator access from an account.')]
final class AdminRevokeCommand extends Command
{
    public function __construct(private readonly AdminGrants $grants)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email address of the administrator account');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = AdminGrants::normaliseEmail((string) $input->getArgument('email'));
        $result = $this->grants->revoke($email);
        $shown = OutputFormatter::escape($email);
        $output->writeln(match ($result) {
            AdminGrants::REVOKED => sprintf('Revoked administrator access from %s. Its existing sessions, including remembered browsers, are signed out on their next request.', $shown),
            AdminGrants::NOT_ADMIN => sprintf('%s is not an administrator; nothing changed.', $shown),
            AdminGrants::UNKNOWN => sprintf('<error>No account uses %s; nothing changed.</error>', $shown),
        });

        return AdminGrants::UNKNOWN === $result ? Command::FAILURE : Command::SUCCESS;
    }
}
