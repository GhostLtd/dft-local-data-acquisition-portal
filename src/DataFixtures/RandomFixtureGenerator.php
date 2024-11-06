<?php

namespace App\DataFixtures;

use App\DataFixtures\Definition\FundAwardDefinition;
use App\DataFixtures\Definition\FundReturn\CrstsFundReturnDefinition;
use App\DataFixtures\Definition\UserDefinition;
use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\RecipientDefinition;
use App\DataFixtures\Definition\MilestoneDefinition;
use App\DataFixtures\Definition\ProjectDefinition;
use App\DataFixtures\Definition\ProjectFund\CrstsProjectFundDefinition;
use App\DataFixtures\Definition\ProjectReturn\CrstsProjectReturnDefinition;
use App\DataFixtures\Generator\CouncilName;
use App\Entity\Enum\ActiveTravelElements;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\CrstsPhase;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\Fund;
use App\Entity\Enum\MilestoneType;
use App\Entity\Enum\OnTrackRating;
use App\Entity\Enum\Rating;
use App\Entity\Enum\TransportMode;
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

    public function createRandomRecipient(): RecipientDefinition
    {
        $this->initialise();

        /** @var array<ProjectDefinition> $projects */
        $projects = $this->repeat(ProjectDefinition::class, 2, 5, $this->createRandomProject(...));

        // Find all funds used by all of the projects
        $funds = [];
        foreach($projects as $projectDefinition) {
            foreach($projectDefinition->getProjectFunds() as $fundDefinition) {
                $fund = $fundDefinition->getFund();
                $funds[$fund->value] = $fund;
            }
        }

        return new RecipientDefinition(
            CouncilName::generate(),
            $this->createRandomUser(),
            $projects,
            array_map(fn(Fund $fund) => $this->createRandomFundAward($fund, $projects), $funds),
        );
    }

    /**
     * @param array<ProjectDefinition> $projects
     */
    public function createRandomFundAward(Fund $fund, array $projects): FundAwardDefinition
    {
        $returns = [];

        $startingYear = $this->faker->numberBetween(2018, 2024);
        $startingQuarter = $this->faker->numberBetween(1, 4);

        $endingYear = $startingYear + $this->faker->numberBetween(1, 8);
        $endingQuarter = ($startingQuarter + $this->faker->numberBetween(1, 4) % 4) + 1;

        $expenses = $this->createRandomExpenses(
            ExpenseType::filterForFund(),
            $startingYear,
            $startingYear + $this->faker->numberBetween(3, 8)
        );

        // Add project returns...
        for($year=$startingYear; $year<=$endingYear; $year++) {
            $loopStartingQuarter = ($year === $startingYear) ? $startingQuarter : 1;
            $loopEndingQuarter = ($year === $endingYear) ? $endingQuarter : 4;

            for($quarter=$loopStartingQuarter; $quarter<=$loopEndingQuarter; $quarter++) {
                $projectReturns = [];

                foreach($projects as $project) {
                    $projectFund = null;
                    foreach($project->getProjectFunds() as $loopProjectFund) {
                        if ($loopProjectFund->getFund() === $fund) {
                            $projectFund = $loopProjectFund;
                            break;
                        }
                    }

                    // This project isn't a recipient of this fund's funds...
                    if (!$projectFund) {
                        continue;
                    }

                    if ($fund === Fund::CRSTS) {
                        if (!$projectFund instanceof CrstsProjectFundDefinition) {
                            throw new \RuntimeException('ProjectFundDefinition / type mismatch');
                        }

                        if (!$projectFund->isRetained() || $quarter === 1) {
                            $projectReturns[$project->getName()] = $this->createRandomCrstsProjectReturn($startingYear, $startingQuarter);
                        }
                    } else {
                        throw new \RuntimeException("Unsupported Project Return Type: ".$project::class);
                    }
                }

                $returns[] = match($fund) {
                    Fund::CRSTS => new CrstsFundReturnDefinition(
                        $this->createRandomUser(),
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
                        $expenses,
                        $projectReturns,
                    ),
                    default => throw new \RuntimeException("Unsupported FundReturnDefinition: {$fund->value}")
                };
            }
        }

        return new FundAwardDefinition($fund, $returns);
    }

    public function createRandomUser(): UserDefinition
    {
        // Chance to re-use existing contact...
        $contact = $this->faker->boolean(30) ?
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

    public function createRandomProject(): ProjectDefinition
    {
        // Since we currently only have CRSTS, we'll be having at most one fund
        $projectFunds = [];

        if (mt_rand(0, 100) > 20) {
            $projectFunds[] = $this->createRandomCrstsProjectFund();
        }

        $projectId = $this->faker->currencyCode() . $this->faker->numberBetween(1, 9999);

        return new ProjectDefinition(
            $this->faker->sentence(),
            $this->faker->text(),
            $this->faker->randomElement(TransportMode::cases()),
            $this->faker->randomElements(ActiveTravelElements::cases(), null),
            $this->faker->boolean(),
            $this->faker->boolean(),
            $projectId,
            $projectFunds,
        );
    }

    public function createRandomCrstsProjectFund(): CrstsProjectFundDefinition
    {
        return new CrstsProjectFundDefinition(
            $this->faker->boolean(),
            $this->faker->randomElement(CrstsPhase::cases()),
        );
    }

    public function createRandomCrstsProjectReturn(int $startingYear, int $startingQuarter): CrstsProjectReturnDefinition
    {
        $milestones = [];
        $earliestDate = new \DateTime("{$startingYear}-".(($startingQuarter-1)*3)."-01");

        // N.B. This logic is very simple, and might not generate logically coherent dates
        //      for milestones or expense date ranges.

        foreach(MilestoneType::cases() as $milestoneType) {
            $milestone = $this->createRandomMilestone($milestoneType, $earliestDate);
            $earliestDate = $milestone->getDate();
            $milestones[] = $milestone;
        }

        $expenses = $this->createRandomExpenses(
            ExpenseType::filterForProject(),
            $startingYear,
            $startingYear + $this->faker->numberBetween(3, 8)
        );

        return new CrstsProjectReturnDefinition(
            '£'.$this->faker->numberBetween(1_000, 99_000_000),
            '£'.$this->faker->numberBetween(1_000, 99_000_000),
            '£'.$this->faker->numberBetween(1_000, 99_000_000),
            $this->faker->randomElement(OnTrackRating::cases()),
            $this->faker->randomElement(BusinessCase::cases()),
            $this->faker->dateTime(),
            $this->faker->text(),
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
     * @param array<ExpenseType> $expenseTypes
     * @return array<ExpenseDefinition>
     */
    public function createRandomExpenses(array $expenseTypes, int $startYear, int $endYear): array
    {
        // N.B. The logic here is also very simple and hence the forecast / actual expenses
        //      are *extremely* unlikely to be coherent from one year to the next :)

        $expenses = [];

        foreach($expenseTypes as $expenseType) {
            $entries = [];
            for($expenseYear = $startYear; $expenseYear <= $endYear; $expenseYear++) {
                $expenseNextYear = $expenseYear + 1;
                $key = "{$expenseYear}/{$expenseNextYear}";

                for($quarter=1; $quarter<=4; $quarter++) {
                    $entries["{$key} {$quarter}"] = $this->faker->numberBetween('1_000_000', '99_000_000');
                }
            }

            $expenses[] = new ExpenseDefinition($expenseType, $entries);
        }

        return $expenses;
    }
}
