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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Throwable;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

#[AsCommand(
    name: 'maintenance:disable',
    description: 'Bring the application out of maintenance mode.',
    aliases: ['maintenance:off', 'app:up', 'up']
)]
class DisableCommand extends Command
{
    public function __construct(
        private MaintenanceManagerInterface $maintenanceModeManager
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->maintenanceModeManager->disable();

            if ($this->maintenanceModeManager->isActive() === false) {
                $io->success('Maintenance mode has been disabled.');

                return Command::SUCCESS;
            }

            $io->error('Maintenance mode could not be disabled.');

            return Command::FAILURE;
        } catch (Throwable $throwable) {
            $io->error($throwable->getMessage());

            return Command::FAILURE;
        }
    }
}
