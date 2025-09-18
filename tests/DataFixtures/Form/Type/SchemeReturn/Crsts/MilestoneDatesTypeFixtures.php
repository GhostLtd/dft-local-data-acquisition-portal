<?php

namespace App\Tests\DataFixtures\Form\Type\SchemeReturn\Crsts;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Milestone;
use App\Entity\Scheme;
use App\Entity\SchemeData\CrstsData;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class MilestoneDatesTypeFixtures extends AbstractFixture
{
    public const CRSTS_SCHEME_RETURN_CDEL = 'crsts_scheme_return_cdel';
    public const CRSTS_SCHEME_RETURN_RDEL = 'crsts_scheme_return_rdel';
    public const AUTHORITY = 'authority';

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setName('Mr Test')
            ->setEmail('test@example.com');

        $manager->persist($admin);

        $authority = (new Authority())
            ->setName('Test Authority')
            ->setAdmin($admin);

        $manager->persist($authority);

        $fundAward = (new FundAward())
            ->setType(Fund::CRSTS1)
            ->setAuthority($authority);

        $manager->persist($fundAward);

        $fundReturn = (new CrstsFundReturn())
            ->setYear(2024)
            ->setQuarter(1)
            ->setState('open')
            ->setFundAward($fundAward);

        $manager->persist($fundReturn);

        // Create CDEL scheme with existing milestones
        $cdelScheme = (new Scheme())
            ->setName('Test CDEL Scheme')
            ->setAuthority($authority);

        $cdelCrstsData = (new CrstsData())
            ->setFundedMostlyAs(FundedMostlyAs::CDEL);
        $cdelScheme->setCrstsData($cdelCrstsData);

        $manager->persist($cdelScheme);

        $cdelSchemeReturn = (new CrstsSchemeReturn())
            ->setFundReturn($fundReturn)
            ->setScheme($cdelScheme)
            ->setDevelopmentOnly(false);

        // Add existing milestones for CDEL
        $startDevMilestone = (new Milestone())
            ->setType(MilestoneType::START_DEVELOPMENT)
            ->setDate(new \DateTime('2024-01-01'));

        $endDevMilestone = (new Milestone())
            ->setType(MilestoneType::END_DEVELOPMENT)
            ->setDate(new \DateTime('2024-06-01'));

        $cdelSchemeReturn->addMilestone($startDevMilestone);
        $cdelSchemeReturn->addMilestone($endDevMilestone);

        $manager->persist($cdelSchemeReturn);
        $manager->persist($startDevMilestone);
        $manager->persist($endDevMilestone);

        // Create RDEL scheme
        $rdelScheme = (new Scheme())
            ->setName('Test RDEL Scheme')
            ->setAuthority($authority);

        $rdelCrstsData = (new CrstsData())
            ->setFundedMostlyAs(FundedMostlyAs::RDEL);
        $rdelScheme->setCrstsData($rdelCrstsData);

        $manager->persist($rdelScheme);

        $rdelSchemeReturn = (new CrstsSchemeReturn())
            ->setFundReturn($fundReturn)
            ->setScheme($rdelScheme)
            ->setDevelopmentOnly(true);

        $manager->persist($rdelSchemeReturn);

        $this->addReference(self::CRSTS_SCHEME_RETURN_CDEL, $cdelSchemeReturn);
        $this->addReference(self::CRSTS_SCHEME_RETURN_RDEL, $rdelSchemeReturn);
        $this->addReference(self::AUTHORITY, $authority);

        $manager->flush();
    }
}