<?php

namespace App\Command;

use App\Utility\FundReturnCreator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ldap:cron:create-returns',
    description: 'Create any required fund-returns',
)]
class LdapCronCreateReturnsCommand extends Command
{
    public function __construct(protected FundReturnCreator $fundReturnCreator)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
//        $io = new SymfonyStyle($input, $output);

        $this->fundReturnCreator->createRequiredFundReturns();

        return Command::SUCCESS;
    }
}
