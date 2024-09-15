<?php

declare(strict_types=1);

namespace Kefisu\Bundle\MaintenanceBundle\Command;

use Kefisu\Bundle\MaintenanceBundle\Contract\MaintenanceManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'maintenance:status',
    description: 'Check the status of the maintenance mode.',
    aliases: ['app:status']
)]
class StatusCommand extends Command
{
    public function __construct(
        private MaintenanceManagerInterface $maintenanceModeManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $status = $this->maintenanceModeManager->isActive();

            $io->success($status ? 'Maintenance mode is active.' : 'Maintenance mode is not active.');

            return Command::SUCCESS;
        } catch (Throwable $throwable) {
            $io->error($throwable->getMessage());

            return Command::FAILURE;
        }
    }
}
