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

        $startingYear = 2022;
        $startingQuarter = 1;

        $now = new \DateTime();
        $endingYear = intval($now->format('Y'));
        $endingQuarter = intval(ceil(intval($now->format('m')) / 3));

        // Add project returns...
        for($year=$startingYear; $year<=$endingYear; $year++) {
            $loopStartingQuarter = ($year === $startingYear) ? $startingQuarter : 1;
            $loopEndingQuarter = ($year === $endingYear) ? $endingQuarter : 4;

            for($quarter=$loopStartingQuarter; $quarter<=$loopEndingQuarter; $quarter++) {
                $projectReturns = [];

                $mustBeSignedOff = $year === $endingYear && $quarter === $endingQuarter;

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

                    if ($fund === Fund::CRSTS1) {
                        if (!$projectFund instanceof CrstsProjectFundDefinition) {
                            throw new \RuntimeException('ProjectFundDefinition / type mismatch');
                        }

                        if ($projectFund->isRetained() || $quarter === 1) {
                            $projectReturns[$project->getName()] = $this->createRandomCrstsProjectReturn($year, $quarter);
                        }
                    } else {
                        throw new \RuntimeException("Unsupported Project Return Type: ".$project::class);
                    }
                }

                $expenses = $this->createRandomCrstsExpenses(
                    ExpenseType::filterForFund($fund),
                    $year,
                    $quarter,
                );

                $returns[] = match($fund) {
                    Fund::CRSTS1 => new CrstsFundReturnDefinition(
                        $mustBeSignedOff ? null : $this->createRandomUser(), // No signoff user for the most recent return...
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
        $contact = $this->faker->boolean(95) ?
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
            $this->faker->randomElement(ActiveTravelElement::cases()),
            $this->faker->boolean(),
            $this->faker->boolean(),
            $projectId,
            $projectFunds,
        );
    }

    public function createRandomCrstsProjectFund(): CrstsProjectFundDefinition
    {
        $bcrType = $this->faker->randomElement(BenefitCostRatioType::cases());
        $bcrValue = $bcrType === BenefitCostRatioType::VALUE ?
            strval($this->faker->randomFloat(2, 0, 10)) :
            null;

        return new CrstsProjectFundDefinition(
            $this->faker->boolean(),
            $this->faker->boolean(),
            $this->faker->randomElement(FundedMostlyAs::class),
            $bcrType,
            $bcrValue,
        );
    }

    public function createRandomCrstsProjectReturn(int $returnYear, int $returnQuarter): CrstsProjectReturnDefinition
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

        $expenses = $this->createRandomCrstsExpenses(ExpenseType::filterForProject(Fund::CRSTS1), $returnYear, $returnQuarter);

        return new CrstsProjectReturnDefinition(
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
    public function createRandomCrstsExpenses(array $expenseTypes, int $returnYear, int $returnQuarter): array
    {
        // N.B. The logic here is also very simple and hence the forecast / actual expenses
        //      are *extremely* unlikely to be coherent from one year to the next :)

        $divisionConfigurations = CrstsHelper::getExpenseDivisionConfigurations($returnYear, $returnQuarter);

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
