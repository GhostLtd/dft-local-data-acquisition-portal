<?php

namespace App\DataFixtures;

use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\FundRecipientDefinition;
use App\DataFixtures\Definition\MilestoneDefinition;
use App\DataFixtures\Definition\ProjectDefinition;
use App\DataFixtures\Definition\ProjectFund\CrstsProjectFundDefinition;
use App\DataFixtures\Definition\Return\CrstsReturnDefinition;
use App\DataFixtures\Generator\CouncilName;
use App\Entity\Enum\ActiveTravelElements;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\CrstsPhase;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\MilestoneType;
use App\Entity\Enum\OnTrackRating;
use App\Entity\Enum\Rating;
use App\Entity\Enum\TransportMode;
use Faker;

class RandomFixtureGenerator
{
    protected ?Faker\Generator $faker = null;
    protected ?int $seed = 12345; // Reproducibility

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

    public function createRandomFundRecipient(): FundRecipientDefinition
    {
        $this->initialise();

        $projects = $this->repeat(ProjectDefinition::class, 0, 5, $this->createRandomProject(...));

        return new FundRecipientDefinition(
            CouncilName::generate(),
            $projects,
            $this->faker->name(),
            $this->faker->randomElement(['Chief', 'Head of transportation', 'Bursar', 'Transport Manager', 'Fund Manager']),
            $this->faker->phoneNumber(),
            $this->faker->email(),
        );
    }

    public function createRandomProject(): ProjectDefinition
    {
        // Since we currently only have CRSTS, we'll be having at most one fund
        $projectFunds = [];

        if (mt_rand(0, 100) > 20) {
            $projectFunds[] = $this->createRandomCrstsProjectFund();
        }

        return new ProjectDefinition(
            $this->faker->sentence(),
            $this->faker->text(),
            $projectFunds,
        );
    }

    public function createRandomCrstsProjectFund(): CrstsProjectFundDefinition
    {
        $retained = $this->faker->boolean();

        $year = $this->faker->numberBetween(2018, 2024);
        $quarter = $retained ? $this->faker->numberBetween(1, 4) : null;

        // If retained, we do quarterly returns, otherwise yearly
        $returns = $this->repeat(CrstsReturnDefinition::class, 1, 8, function() use (&$year, &$quarter, $retained) {
            $return = $this->createRandomCrstsReturn($retained, $year, $quarter);

            $year++;
            if ($retained) {
                $quarter++;
            }

            return $return;
        });

        $projectId = $this->faker->currencyCode() . $this->faker->numberBetween(1, 9999);

        return new CrstsProjectFundDefinition(
            $this->faker->randomElement(TransportMode::cases()),
            $this->faker->randomElements(ActiveTravelElements::cases(), null),
            $this->faker->boolean(),
            $this->faker->boolean(),
            $projectId,
            $retained,
            $this->faker->randomElement(CrstsPhase::cases()),
            $returns,
        );
    }

    public function createRandomCrstsReturn(bool $isRetained, int $year, ?int $quarter): CrstsReturnDefinition
    {
        $milestones = [];
        $earliestDate = null;

        // N.B. This logic is very simple, and might not generate logically coherent dates
        //      for milestones or expense date ranges.

        foreach(MilestoneType::cases() as $milestoneType) {
            $milestone = $this->createRandomMilestone($milestoneType, $earliestDate);
            $earliestDate = $milestone->getDate();
            $milestones[] = $milestone;
        }

        $expenseStartYear = intval($this->faker->dateTimeBetween('-10 years')->format('Y'));
        $expenseEndYear = intval($this->faker->dateTimeBetween(new \DateTime("{$expenseStartYear}-01-01"))->format('Y'));

        $expenses = $this->createRandomExpenses($isRetained, $expenseStartYear, $expenseEndYear);

        return new CrstsReturnDefinition(
            $year,
            $quarter,
            $this->faker->text(),
            $this->faker->text(),
            $this->faker->randomElement(Rating::cases()),
            $this->faker->text(),
            $this->faker->randomElement(Rating::cases()),
            $this->faker->text(),
            $this->faker->text(),
            $this->faker->text(),
            '£'.$this->faker->numberBetween(1_000, 99_000_000),
            '£'.$this->faker->numberBetween(1_000, 99_000_000),
            '£'.$this->faker->numberBetween(1_000, 99_000_000),
            $this->faker->randomElement(OnTrackRating::cases()),
            $this->faker->randomElement(BusinessCase::cases()),
            $this->faker->dateTime(),
            $this->faker->text(),
            $this->faker->email(),
            $milestones,
            $expenses,
        );
    }

    public function createRandomMilestone(?MilestoneType $milestoneType=null, \DateTime $earliestDate=null): MilestoneDefinition
    {
        if (!$milestoneType) {
            $milestoneType = $this->faker->randomElement(MilestoneType::cases());
        }

        $date = $this->faker->dateTimeBetween($earliestDate ?? '-10 years');

        return new MilestoneDefinition($milestoneType, $date);
    }

    /**
     * @return array<ExpenseDefinition>
     */
    public function createRandomExpenses(bool $isRetained, int $startYear, int $endYear): array
    {
        // N.B. The logic here is also very simple and hence the forecast / actual expenses
        //      are *extremely* unlikely to be coherent from one year to the next :)

        $expenses = [];

        foreach(ExpenseType::cases() as $expenseType) {
            $entries = [];
            for($expenseYear = $startYear; $expenseYear <= $endYear; $expenseYear++) {
                $expenseNextYear = $expenseYear + 1;
                $key = "{$expenseYear}/{$expenseNextYear}";

                if ($isRetained) {
                    for($quarter=1; $quarter<=4; $quarter++) {
                        $entries["{$key} {$quarter}"] = $this->faker->numberBetween('1_000_000', '99_000_000');
                    }
                } else {
                    $entries[$key] = $this->faker->numberBetween('1_000_000', '99_000_000');
                }
            }

            $expenses[] = new ExpenseDefinition($expenseType, $entries);
        }

        return $expenses;
    }
}
