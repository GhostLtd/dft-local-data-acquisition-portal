<?php

namespace App\Command;

use App\DataFixtures\FixtureHelper;
use App\DataFixtures\RandomFixtureGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        protected RandomFixtureGenerator $fixtureGenerator, private readonly RandomFixtureGenerator $randomFixtureGenerator,
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
        $this
            ->addOption('number-of-fixtures', null, InputOption::VALUE_REQUIRED, 'Number of fixtures to add', 1)
            ->addOption('initial-seed', null, InputOption::VALUE_REQUIRED, 'Initial seed', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->fixtureHelper->setEntityManager($this->entityManager);

        $initialSeed = $input->getOption('initial-seed');
        if ($initialSeed) {
            $this->randomFixtureGenerator->setSeed($initialSeed);
        }

        $numberOfFixtures = $input->getOption('number-of-fixtures');
        for($i=0; $i<$numberOfFixtures; $i++) {
            $this->fixtureHelper->createFundRecipient($this->fixtureGenerator->createRandomRecipient());
        }

        $this->entityManager->flush();

        $io->success('Success!');

        return Command::SUCCESS;
    }
}

