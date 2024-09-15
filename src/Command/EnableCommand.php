<?php

declare(strict_types=1);

namespace Kefisu\Bundle\MaintenanceBundle\Command;

use Kefisu\Bundle\MaintenanceBundle\Contract\MaintenanceManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'maintenance:enable',
    description: 'Put the application into maintenance mode.',
    aliases: ['maintenance:on', 'app:down', 'down']
)]
class EnableCommand extends Command
{
    private const DURATION_KEY = 'duration';
    private const STATUS_KEY = 'status';

    public function __construct(
        private MaintenanceManagerInterface $maintenanceModeManager
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->addOption(
            name: self::DURATION_KEY,
            shortcut: 'd',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The duration of the maintenance in minutes.',
        );

        $this->addOption(
            name: self::STATUS_KEY,
            shortcut: 's',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'The status code that should be used when returning the maintenance mode response.',
            default: 503
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $duration = $input->getOption(self::DURATION_KEY);
            $statusCode = $input->getOption(self::STATUS_KEY);

            if (!is_numeric($statusCode)) {
                $io->error('The status code must be a number.');

                return Command::FAILURE;
            }

            if (!is_numeric($duration) && $duration !== null) {
                $io->error('The duration must be a number.');

                return Command::FAILURE;
            }

            $this->maintenanceModeManager->enable(
                duration: is_numeric($duration) ? (int) $duration : null,
                statusCode: (int) $statusCode
            );

            if ($this->maintenanceModeManager->isActive()) {
                $io->success('Application is now in maintenance mode.');

                $secret = $this->maintenanceModeManager->getSecret();
                if (is_string($secret)) {
                    $io->success(sprintf("You may bypass maintenance mode via [%s].", $secret));
                }

                return Command::SUCCESS;
            }

            $io->error('Failed to activate maintenance mode.');

            return Command::FAILURE;
        } catch (Throwable $throwable) {
            $io->error($throwable->getMessage());

            return Command::FAILURE;
        }
    }
}
