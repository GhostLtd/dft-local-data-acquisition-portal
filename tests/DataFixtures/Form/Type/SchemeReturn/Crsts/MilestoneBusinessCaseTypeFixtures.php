<?php

namespace App\Tests\DataFixtures\Form\Type\SchemeReturn\Crsts;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class MilestoneBusinessCaseTypeFixtures extends AbstractFixture
{
    public const CRSTS_SCHEME_RETURN = 'crsts_scheme_return';

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
            ->setState('submitted')
            ->setFundAward($fundAward);

        $manager->persist($fundReturn);

        $scheme = (new Scheme())
            ->setName('Test Scheme')
            ->setAuthority($authority);

        $manager->persist($scheme);

        $schemeReturn = (new CrstsSchemeReturn())
            ->setFundReturn($fundReturn)
            ->setScheme($scheme);

        $manager->persist($schemeReturn);

        $this->addReference(self::CRSTS_SCHEME_RETURN, $schemeReturn);

        $manager->flush();
    }
}