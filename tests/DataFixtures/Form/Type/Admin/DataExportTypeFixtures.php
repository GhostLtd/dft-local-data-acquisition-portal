<?php

namespace App\Tests\DataFixtures\Form\Type\Admin;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class DataExportTypeFixtures extends AbstractFixture
{
    protected FundAward $fundAward;
    protected ObjectManager $manager;

    public function load(ObjectManager $manager): void
    {
        $this->manager = $manager;

        $admin = (new User())
            ->setName('Mr Best')
            ->setEmail('best-west-test@example.com');

        $manager->persist($admin);

        $authority = (new Authority())
            ->setName('West Test Combined Authority')
            ->setAdmin($admin);

        $manager->persist($authority);

        $this->fundAward = (new FundAward())
            ->setType(Fund::CRSTS1)
            ->setAuthority($authority);

        $manager->persist($this->fundAward);

        $this->addCrstsFundReturn(2024, 1, FundReturn::STATE_SUBMITTED);

        $this->addCrstsFundReturn(2024, 2, FundReturn::STATE_SUBMITTED);
        $this->addCrstsFundReturn(2024, 2, FundReturn::STATE_SUBMITTED); // Test "distinct"

        $this->addCrstsFundReturn(2024, 3, FundReturn::STATE_OPEN);
        $this->addCrstsFundReturn(2024, 4, FundReturn::STATE_INITIAL);

        $this->addCrstsFundReturn(2025, 1, FundReturn::STATE_SUBMITTED); // Test "distinct"

        $manager->flush();
    }

    private function addCrstsFundReturn(int $year, int $quarter, string $state): void
    {
        $fundReturn = (new CrstsFundReturn())
            ->setYear($year)
            ->setQuarter($quarter)
            ->setState($state)
            ->setFundAward($this->fundAward);

        $this->manager->persist($fundReturn);
    }
}
