<?php

namespace App\Command\MaintenanceMode;

use App\Utility\MaintenanceModeHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ldap:maintenance-mode:unlock', description: 'Unlock the website for maintenance')]
class UnlockCommand extends Command
{
    public function __construct(
        protected MaintenanceModeHelper $maintenanceModeHelper,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $this->maintenanceModeHelper->disableMaintenanceMode();
        }
        catch(\Exception $e) {
            $io->error("Failed to enable maintenance mode: {$e->getMessage()}");
            return Command::FAILURE;
        }

        $io->success('Maintenance mode is inactive.');
        return Command::SUCCESS;
    }
}
