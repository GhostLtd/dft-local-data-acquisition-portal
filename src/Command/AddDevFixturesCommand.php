<?php

namespace App\Command;

use App\DataFixtures\FixtureHelper;
use App\DataFixtures\RandomFixtureGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'ldap:dev:add-fixtures',
    description: 'Add development fixture entities to the database',
)]
class AddDevFixturesCommand extends Command
{
    public function __construct(
        protected string                 $appEnvironment,
        protected EntityManagerInterface $entityManager,
        protected FixtureHelper          $fixtureHelper,
        protected RandomFixtureGenerator $fixtureGenerator,
    )
    {
        parent::__construct();
    }

    public function isEnabled(): bool
    {
        return $this->appEnvironment === 'dev';
    }

    protected function configure(): void
    {
        $this->addArgument('number-of-entries-to-add', InputArgument::OPTIONAL, 'Number of fixtures', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->fixtureHelper->setEntityManager($this->entityManager);

        $numberOfEntries = $input->getArgument('number-of-entries-to-add');
        for($i=0; $i<$numberOfEntries; $i++) {
            $this->fixtureHelper->createFundRecipient($this->fixtureGenerator->createRandomFundRecipient());
        }

        $this->entityManager->flush();

        $io->success('Success!');

        return Command::SUCCESS;
    }
}
