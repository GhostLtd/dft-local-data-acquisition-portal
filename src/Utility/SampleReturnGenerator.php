<?php

namespace App\Utility;

use App\DataFixtures\FixtureHelper;
use App\DataFixtures\RandomFixtureGenerator;
use App\Entity\Authority;
use App\Entity\FundReturn\CrstsFundReturn;
use Doctrine\ORM\EntityManagerInterface;

class SampleReturnGenerator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RandomFixtureGenerator $randomFixtureGenerator,
        private readonly FixtureHelper          $fixtureHelper,
    ) {}

    public function createAssetsForNewAuthority(Authority $authority): void
    {
        $this->randomFixtureGenerator->setSeed(\random_int(0, PHP_INT_MAX));
        $this->fixtureHelper->setEntityManager($this->entityManager);

        $returnQuarter = FinancialQuarter::createFromDate(new \DateTime('6 months ago'));
        [$schemes, $fundAwards] = $this->randomFixtureGenerator
            ->createSchemeAndFundAwardDefinitions($returnQuarter, $returnQuarter);
        $this->fixtureHelper->processSchemeAndFundDefinitions($authority, $schemes, $fundAwards);

        // sign off return
        /** @var CrstsFundReturn $existingReturn */
        $existingReturn = $authority->getFundAwards()->first()->getReturns()->first();
        $existingReturn->signoff($authority->getAdmin());

        // create new return for following quarter, from existing one.
        $nextReturn = $existingReturn->createFundReturnForNextQuarter();
        $this->entityManager->persist($nextReturn);

    }
}
