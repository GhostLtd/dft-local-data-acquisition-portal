<?php

namespace App\Utility;

use App\DataFixtures\FixtureHelper;
use App\DataFixtures\RandomFixtureGenerator;
use App\Entity\Authority;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Features;
use Doctrine\ORM\EntityManagerInterface;

class SampleReturnGenerator
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected string                 $environment,
        protected Features               $features,
        protected FixtureHelper          $fixtureHelper,
        protected RandomFixtureGenerator $randomFixtureGenerator,
    ) {}

    public function createAssetsForNewAuthority(Authority $authority): void
    {
        if (
            $this->environment !== 'dev' ||
            !$this->features->isEnabled(Features::FEATURE_DEV_MCA_FIXTURES)
        ) {
            return;
        }

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
