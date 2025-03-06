<?php

namespace App\DataFixtures;

use App\DataFixtures\Definition\FundAwardDefinition;
use App\DataFixtures\Definition\FundReturn\CrstsFundReturnDefinition;
use App\DataFixtures\Definition\UserDefinition;
use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\AuthorityDefinition;
use App\DataFixtures\Definition\MilestoneDefinition;
use App\DataFixtures\Definition\SchemeDefinition;
use App\DataFixtures\Definition\SchemeReturn\CrstsSchemeReturnDefinition;
use App\DataFixtures\Generator\CouncilName;
use App\DataFixtures\Generator\SchemeName;
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
use App\Entity\Scheme;
use App\Entity\SchemeData\CrstsData;
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
            $this->faker->addProvider(new CouncilName($this->faker));
            $this->faker->addProvider(new SchemeName($this->faker));
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

        $num = $this->faker->numberBetween($min, $max);
        $data = [];

        for($i=0; $i<$num; $i++) {
            $generated = $callback($i);

            $generatedClass = $generated::class;
            if ($generatedClass !== $class) {
                throw new \RuntimeException("repeat(): \$callback generated incorrect class type - expected {$class}, got {$generatedClass}");
            }

            $data[] = $generated;
        }

        return $data;
    }

    public function createAuthorityDefinitions(int $numberOfFixtures): array
    {
        $this->initialise();

        $authorities = [];

        $numberOfFixtures = min($numberOfFixtures, count(CouncilName::COUNCIL_NAMES)); // Can only have as many as we have

        for($i=0; $i<$numberOfFixtures; $i++) {
            do {
                $name = $this->faker->council_name();
            } while(array_key_exists($name, $authorities));

            [$schemes, $fundAwards] = $this->createSchemeAndFundAwardDefinitions();

            $authorities[$name] = new AuthorityDefinition($name, $this->createRandomUser(), $schemes, $fundAwards);
        }

        return $authorities;
    }

    public function createSchemeAndFundAwardDefinitions(FinancialQuarter $initialQuarter = null, FinancialQuarter $finalQuarter = null): array
    {
        $this->initialise();

        /** @var array<SchemeDefinition> $schemes */
        $schemes = $this->repeat(SchemeDefinition::class, 4, 8, $this->createRandomScheme(...));

        // Find all funds used by all schemes
        $funds = [
            Fund::CRSTS1->value => Fund::CRSTS1,
        ];

        $fundAwards = array_map(fn(Fund $fund) => $this->createRandomFundAward($fund, $schemes, $initialQuarter, $finalQuarter), $funds);

        return [$schemes, $fundAwards];
    }

    /**
     * @param array<SchemeDefinition> $schemes
     */
    public function createRandomFundAward(Fund $fund, array $schemes, FinancialQuarter $initialQuarter = null, FinancialQuarter $finalQuarter = null): FundAwardDefinition
    {
        $returns = [];

        $initialQuarter = $initialQuarter ?? new FinancialQuarter(2022, 1);
        $finalQuarter = $finalQuarter ?? FinancialQuarter::createFromDate(new \DateTime('3 months ago'));

        // Add scheme returns...
        foreach (
            FinancialQuarter::getRange(
                $initialQuarter,
                $finalQuarter
            ) as $financialQuarter
        ) {
            $schemeReturns = [];
            $mustBeSignedOff = $financialQuarter < $finalQuarter;

            foreach($schemes as $scheme) {
                if ($fund === Fund::CRSTS1) {
                    $schemeReturns[$scheme->getName()] = $this->createRandomCrstsSchemeReturn($financialQuarter, $scheme);
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

                $signoffUser = $this->createRandomUser();
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
                    $this->faker->text(),
                    $this->faker->text(),
                    $expenses,
                    $schemeReturns,
                ),
                default => throw new \RuntimeException("Unsupported FundReturnDefinition: {$fund->value}")
            };
        }

        return new FundAwardDefinition($fund, $returns);
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

    public function createRandomScheme(int $num): SchemeDefinition
    {
        $schemeId = $this->faker->currencyCode() . $this->faker->numberBetween(1, 9999);

        $crstsData = (new CrstsData())
            ->setRetained($num < 2 || $this->faker->boolean())
            ->setPreviouslyTcf($this->faker->boolean());

        return new SchemeDefinition(
            $crstsData,
            $this->faker->scheme_name(),
            $this->faker->text(),
            $this->faker->randomElement(TransportMode::cases()),
            $this->faker->randomElement(ActiveTravelElement::cases()),
            $schemeId,
            [Fund::CRSTS1],
        );
    }

    public function createRandomCrstsSchemeReturn(FinancialQuarter $financialQuarter, SchemeDefinition $scheme): CrstsSchemeReturnDefinition
    {
        $milestones = [];
        $milestoneEarliestDate = new \DateTime('2022-01-01');

        // N.B. This logic is very simple, and might not generate logically coherent dates
        //      for milestones or expense date ranges.

        $isDevelopmentOnly = $this->faker->boolean(20);

        foreach(MilestoneType::cases() as $milestoneType) {
            if ($isDevelopmentOnly && !$milestoneType->isDevelopmentMilestone()) {
                continue;
            }

            $milestone = $this->createRandomMilestone($milestoneType, $milestoneEarliestDate, new \DateTime("2028-01-01"));
            $milestoneEarliestDate = $milestone->getDate();
            $milestones[] = $milestone;
        }

        $expenses = $this->createRandomCrstsExpenses(ExpenseType::filterForScheme(Fund::CRSTS1), $financialQuarter);

        $bcrType = $this->faker->randomElement(BenefitCostRatioType::cases());
        $bcrValue = $bcrType === BenefitCostRatioType::VALUE ?
            strval($this->faker->randomFloat(2, 0, 10)) :
            null;

        return new CrstsSchemeReturnDefinition(
            $this->faker->text(),
            $scheme,
            $bcrType,
            $bcrValue,
            strval($this->faker->randomFloat(2, 1_000, 99_000_000)),
            strval($this->faker->numberBetween(1_000, 99_000_000)),
            $this->faker->randomElement(OnTrackRating::cases()),
            $this->faker->randomElement(BusinessCase::cases()),
            $this->faker->dateTime(),
            $this->faker->text(),
            $this->faker->boolean(20),
            $isDevelopmentOnly,
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
