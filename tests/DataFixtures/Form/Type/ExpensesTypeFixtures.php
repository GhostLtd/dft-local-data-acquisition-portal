<?php

namespace App\Tests\DataFixtures\Form\Type;

use App\Entity\Authority;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\Fund;
use App\Entity\ExpenseEntry;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class ExpensesTypeFixtures extends AbstractFixture
{
    public const string CRSTS_FUND_RETURN = 'crsts_fund_return';
    public const string AUTHORITY = 'authority';

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

        // Create FundAward first
        $fundAward = (new FundAward())
            ->setType(Fund::CRSTS1)
            ->setAuthority($authority);

        $manager->persist($fundAward);

        // Create a CrstsFundReturn with some existing expenses
        $crstsFundReturn = (new CrstsFundReturn())
            ->setYear(2024)
            ->setQuarter(1)
            ->setState('open')
            ->setFundAward($fundAward);

        // Add some existing expense entries
        $expense1 = (new ExpenseEntry())
            ->setDivision('2024_25')
            ->setColumn('q1')
            ->setType(ExpenseType::FUND_CAPITAL_EXPENDITURE)
            ->setValue('100000')
            ->setForecast(false);

        $expense2 = (new ExpenseEntry())
            ->setDivision('2024_25')
            ->setColumn('q2')
            ->setType(ExpenseType::FUND_CAPITAL_EXPENDITURE)
            ->setValue('150000')
            ->setForecast(true);

        $crstsFundReturn->addExpense($expense1);
        $crstsFundReturn->addExpense($expense2);

        $manager->persist($crstsFundReturn);
        $manager->persist($expense1);
        $manager->persist($expense2);

        $this->addReference(self::CRSTS_FUND_RETURN, $crstsFundReturn);
        $this->addReference(self::AUTHORITY, $authority);

        $manager->flush();
    }
}