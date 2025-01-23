<?php

namespace App\DataFixtures;

use App\DataFixtures\Definition\FundAwardDefinition;
use App\DataFixtures\Definition\FundReturn\CrstsFundReturnDefinition;
use App\DataFixtures\Definition\UserDefinition;
use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\AuthorityDefinition;
use App\DataFixtures\Definition\MilestoneDefinition;
use App\DataFixtures\Definition\SchemeDefinition;
use App\DataFixtures\Definition\SchemeFund\CrstsSchemeFundDefinition;
use App\DataFixtures\Definition\SchemeReturn\CrstsSchemeReturnDefinition;
use App\DataFixtures\Generator\CouncilName;
use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\Fund;
use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\Enum\OnTrackRating;
use App\Entity\Enum\Rating;
use App\Entity\Enum\TransportMode;
use App\Utility\CrstsHelper;
use App\Utility\FinancialQuarter;
use Faker;

class RandomFixtureGenerator
{
    protected ?Faker\Generator $faker = null;
    protected ?int $seed = 12345; // Reproducibility
    protected array $existingContacts = [];

    public function setSeed(?int $seed): static {
        $this->seed = $seed;
        return $this;
    }

    protected function initialise(): void
    {
        if (!$this->faker) {
            $this->faker = Faker\Factory::create('en_GB');
            $this->faker->seed($this->seed);
        }
    }

    /**
     * @template T
     * @class-string $class<T>
     * @return array<T>
     */
    protected function repeat(string $class, int $min, int $max, \Closure $callback): array
    {
        if ($max < $min) {
            throw new \RuntimeException("repeat(): \$max must be larger than \$min");
        }

        $range = $max - $min;
        $random = mt_rand(0, $range);

        $data = [];

        for($i=$min; $i<=($min + $random); $i++) {
            $generated = $callback($i);

            $generatedClass = $generated::class;
            if ($generatedClass !== $class) {
                throw new \RuntimeException("repeat(): \$callback generated incorrect class type - expected {$class}, got {$generatedClass}");
            }

            $data[] = $generated;
        }

        return $data;
    }

    public function createAllAuthorityDefinitions(int $numberOfFixtures): array
    {
        $this->initialise();

        $authorities = array_map(
            fn($name) => new AuthorityDefinition($name, $this->createRandomUser(), [], []),
            CouncilName::COUNCIL_NAMES
        );
        $subset = $this->faker->randomElements($authorities, $numberOfFixtures);
        foreach ($subset as $authority) {
            $this->createSchemesAndFundsForAuthority($authority);
        }
        return $authorities;
    }

    protected function createSchemesAndFundsForAuthority(AuthorityDefinition $authority): void
    {
        /** @var array<SchemeDefinition> $schemes */
        $schemes = $this->repeat(SchemeDefinition::class, 2, 5, $this->createRandomScheme(...));

        // Find all funds used by all of the schemes
        $funds = [];
        foreach($schemes as $schemeDefinition) {
            foreach($schemeDefinition->getSchemeFunds() as $fundDefinition) {
                $fund = $fundDefinition->getFund();
                $funds[$fund->value] = $fund;
            }
        }

        $authority->setSchemes($schemes);
        $authority->setFundAwards(array_map(fn(Fund $fund) => $this->createRandomFundAward($fund, $schemes), $funds));
    }

    /**
     * @param array<SchemeDefinition> $schemes
     */
    public function createRandomFundAward(Fund $fund, array $schemes): FundAwardDefinition
    {
        $returns = [];

        $finalQuarter = FinancialQuarter::createFromDate(new \DateTime('3 months ago'));

        $leadContactUser = $this->createRandomUser();

        // Add scheme returns...
        foreach (FinancialQuarter::getRange(new FinancialQuarter(2022, 1), $finalQuarter) as $financialQuarter)
        {
                $schemeReturns = [];
                $mustBeSignedOff = $financialQuarter < $finalQuarter;

                foreach($schemes as $scheme) {
                    $schemeFund = null;
                    foreach($scheme->getSchemeFunds() as $loopSchemeFund) {
                        if ($loopSchemeFund->getFund() === $fund) {
                            $schemeFund = $loopSchemeFund;
                            break;
                        }
                    }

                    // This scheme isn't a recipient of this fund's funds...
                    if (!$schemeFund) {
                        continue;
                    }

                    if ($fund === Fund::CRSTS1) {
                        if (!$schemeFund instanceof CrstsSchemeFundDefinition) {
                            throw new \RuntimeException('SchemeFundDefinition / type mismatch');
                        }

                        if ($schemeFund->isRetained() || $finalQuarter->quarter === 4) {
                            $schemeReturns[$scheme->getName()] = $this->createRandomCrstsSchemeReturn($financialQuarter);
                        }
                    } else {
                        throw new \RuntimeException("Unsupported Scheme Return Type: ".$scheme::class);
                    }
                }

                $expenses = $this->createRandomCrstsExpenses(
                    ExpenseType::filterForFund($fund),
                    $financialQuarter
                );

                $quarterStartDate = $financialQuarter->getStartDate();

                // No signoff user for the most recent return...
                if ($mustBeSignedOff) {
                    $signoffDeadline = (clone $quarterStartDate)->modify('+3 months');

                    $signoffUser = $this->faker->boolean(90) ? $leadContactUser : $this->createRandomUser();
                    $signoffDatetime = $this->faker->dateTimeBetween($quarterStartDate, $signoffDeadline);
                } else {
                    $signoffUser = null;
                    $signoffDatetime = null;
                }

                $returns[] = match($fund) {
                    Fund::CRSTS1 => new CrstsFundReturnDefinition(
                        $signoffUser,
                        $signoffDatetime,
                        $financialQuarter->initialYear,
                        $financialQuarter->quarter,
                        $this->faker->text(),
                        $this->faker->text(),
                        $this->faker->randomElement(Rating::cases()),
                        $this->faker->text(),
                        $this->faker->randomElement(Rating::cases()),
                        $this->faker->text(),
                        $this->faker->text(),
                        $this->faker->text(),
                        $expenses,
                        $schemeReturns,
                    ),
                    default => throw new \RuntimeException("Unsupported FundReturnDefinition: {$fund->value}")
                };
        }

        return new FundAwardDefinition($leadContactUser, $fund, $returns);
    }

    public function createRandomUser(): UserDefinition
    {
        // Chance to re-use existing contact...
        $contact = $this->faker->boolean(20) ?
            $this->faker->randomElement($this->existingContacts) :
            null;

        if (!$contact) {
            $this->existingContacts[] = $contact = new UserDefinition(
                $this->faker->name(),
                $this->faker->randomElement(['Chief', 'Head of transportation', 'Bursar', 'Transport Manager', 'Fund Manager']),
                $this->faker->phoneNumber(),
                $this->faker->email(),
            );
        }

        return $contact;
    }

    public function createRandomScheme(): SchemeDefinition
    {
        // Since we currently only have CRSTS, we'll be having at most one fund
        $schemeFunds = [];

        if (mt_rand(0, 100) > 20) {
            $schemeFunds[] = $this->createRandomCrstsSchemeFund();
        }

        $schemeId = $this->faker->currencyCode() . $this->faker->numberBetween(1, 9999);

        return new SchemeDefinition(
            $this->faker->sentence(),
            $this->faker->text(),
            $this->faker->randomElement(TransportMode::cases()),
            $this->faker->randomElement(ActiveTravelElement::cases()),
            $this->faker->boolean(),
            $this->faker->boolean(),
            $schemeId,
            $schemeFunds,
        );
    }

    public function createRandomCrstsSchemeFund(): CrstsSchemeFundDefinition
    {
        $bcrType = $this->faker->randomElement(BenefitCostRatioType::cases());
        $bcrValue = $bcrType === BenefitCostRatioType::VALUE ?
            strval($this->faker->randomFloat(2, 0, 10)) :
            null;

        return new CrstsSchemeFundDefinition(
            $this->faker->boolean(),
            $this->faker->boolean(),
            $this->faker->randomElement(FundedMostlyAs::class),
            $bcrType,
            $bcrValue,
        );
    }

    public function createRandomCrstsSchemeReturn(FinancialQuarter $financialQuarter): CrstsSchemeReturnDefinition
    {
        $milestones = [];
        $milestoneEarliestDate = new \DateTime('2022-01-01');

        // N.B. This logic is very simple, and might not generate logically coherent dates
        //      for milestones or expense date ranges.

        foreach(MilestoneType::cases() as $milestoneType) {
            $milestone = $this->createRandomMilestone($milestoneType, $milestoneEarliestDate, new \DateTime("2028-01-01"));
            $milestoneEarliestDate = $milestone->getDate();
            $milestones[] = $milestone;
        }

        $expenses = $this->createRandomCrstsExpenses(ExpenseType::filterForScheme(Fund::CRSTS1), $financialQuarter);

        return new CrstsSchemeReturnDefinition(
            strval($this->faker->randomFloat(2, 1_000, 99_000_000)),
            strval($this->faker->numberBetween(1_000, 99_000_000)),
            strval($this->faker->numberBetween(1_000, 99_000_000)),
            $this->faker->randomElement(OnTrackRating::cases()),
            $this->faker->randomElement(BusinessCase::cases()),
            $this->faker->dateTime(),
            $this->faker->text(),
            $milestones,
            $expenses,
        );
    }

    public function createRandomMilestone(?MilestoneType $milestoneType, \DateTime $earliestDate, \DateTime $latestDate): MilestoneDefinition
    {
        $date = $this->faker->dateTimeBetween($earliestDate, $latestDate);
        return new MilestoneDefinition($milestoneType, $date);
    }

    /**
     * @param array<ExpenseType> $expenseTypes
     * @return array<ExpenseDefinition>
     */
    public function createRandomCrstsExpenses(array $expenseTypes, FinancialQuarter $financialQuarter): array
    {
        // N.B. The logic here is also very simple and hence the forecast / actual expenses
        //      are *extremely* unlikely to be coherent from one year to the next :)

        $divisionConfigurations = CrstsHelper::getExpenseDivisionConfigurations(...$financialQuarter->getAsArray());

        $expenses = [];

        foreach($expenseTypes as $expenseType) {
            foreach($divisionConfigurations as $divisionConfiguration) {
                foreach($divisionConfiguration->getColumnConfigurations() as $columnConfiguration) {
                    $expenses[] = new ExpenseDefinition(
                        $expenseType,
                        $divisionConfiguration->getKey(),
                        $columnConfiguration->getKey(),
                        $columnConfiguration->isForecast(),
                        $this->faker->numberBetween(1_000_000, 99_000_000)
                    );
                }
            }
        }

        return $expenses;
    }
}
