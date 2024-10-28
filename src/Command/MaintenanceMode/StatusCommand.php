<?php

namespace App\Command\MaintenanceMode;

use App\Repository\Utility\MaintenanceLockRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'ldap:maintenance-mode:status', description: 'check the maintenance status')]
class StatusCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(protected readonly MaintenanceLockRepository $maintenanceLockRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->status();

        return Command::SUCCESS;
    }

    protected function status(): void
    {
        [$isActive, $whiteListedIPs] = $this->maintenanceLockRepository->getIsActiveAndWhitelistedIps();
        if (!$isActive) {
            $this->io->success('Maintenance mode is NOT active.');
        } else {
            $this->io->success(sprintf('Maintenance mode is active. Whitelisted IPs: %s', implode(', ', $whiteListedIPs)));
        }
    }
}
