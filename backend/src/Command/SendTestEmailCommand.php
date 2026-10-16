<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(name: 'app:dev:send-test-email', description: 'Send a local development message to Mailpit.')]
final class SendTestEmailCommand extends Command
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ('dev' !== $this->environment) {
            $output->writeln('<error>This command is available only in development.</error>');

            return Command::FAILURE;
        }

        $this->mailer->send((new Email())
            ->from('hello@chess-openings.local')
            ->to('learner@example.test')
            ->subject('Chess Openings: local email is working')
            ->text('Development emails stay in Mailpit.'));
        $output->writeln('Message sent to the configured development mail transport.');

        return Command::SUCCESS;
    }
}
