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
use App\Entity\Enum\Fund;
use App\Entity\Expense\ExpenseEntry;
use App\Entity\Expense\ExpenseSeries;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Recipient;
use App\Entity\Milestone;
use App\Entity\Project;
use App\Entity\ProjectFund\CrstsProjectFund;
use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Entity\User;
use App\Entity\UserRecipientRole;
use Doctrine\ORM\EntityManagerInterface;

class FixtureHelper
{
    protected ?EntityManagerInterface $entityManager = null;
    protected array $users = [];

    public function setEntityManager(EntityManagerInterface $entityManager): static
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * @param array<mixed> $object
     */
    protected function persist(array $objects): void
    {
        if (!$this->entityManager) {
            return;
        }

        foreach($objects as $object) {
            $this->entityManager->persist($object);
        }
    }

    // ----------------------------------------------------------------------------------------------------

    public function createFundRecipient(RecipientDefinition $definition): Recipient
    {
        $recipient = (new Recipient())
            ->setName($definition->getName());

        $leadContact = $this->createUser($definition->getLeadContact(), $recipient);

        $recipient
            ->setLeadContact($leadContact);

        $this->persist([$recipient]);

        foreach($definition->getProjects() as $projectDefinition) {
            $recipient->addProject($this->createProject($projectDefinition));
        }

        $projects = $recipient->getProjects()->toArray();
        foreach($definition->getFundAwards() as $fundAwardDefinition) {
            $recipient->addFundAward($this->createFundAward($fundAwardDefinition, $projects));
        }

        return $recipient;
    }

    public function createUser(UserDefinition $definition, ?Recipient $recipient=null): User
    {
        $email = $definition->getEmail();

        $existingUser = $this->users[$email] ?? null;

        if ($existingUser) {
            if (
                $definition->getName() !== $existingUser->getName() ||
                $definition->getPhone() !== $existingUser->getPhone() ||
                $definition->getPosition() !== $existingUser->getPosition()
            ) {
                throw new \RuntimeException('User with given email already exists, albeit with different details');
            }

            return $existingUser;
        }

        $user = (new User())
            ->setName($definition->getName())
            ->setPosition($definition->getPosition())
            ->setPhone($definition->getPhone())
            ->setEmail($email);

        if ($recipient) {
            $recipientRole = (new UserRecipientRole())
                ->setRecipient($recipient)
                ->setRole('ACCESS'); // TODO - we'll later have more granular/specific roles to add here

            $user->addRecipientRole($recipientRole);

            $this->persist([$recipientRole]);
        }

        $this->users[$email] = $user;
        $this->persist([$user]);
        return $user;
    }

    /**
     * @param array<Project> $projects
     */
    public function createFundAward(FundAwardDefinition $fundAwardDefinition, array $projects=[]): FundAward
    {
        $fund = $fundAwardDefinition->getFund();

        $fundAward = (new FundAward())
            ->setType($fund);

        $this->persist([$fundAward]);

        foreach($fundAwardDefinition->getReturns() as $returnDefinition) {
            if ($fund === Fund::CRSTS && $returnDefinition instanceof CrstsFundReturnDefinition) {
                $return = $this->createCrstsFundReturn($returnDefinition, $projects);
            } else {
                throw new \RuntimeException("Unsupported returnDefinition type for fund {$fund->value}: ".$returnDefinition::class);
            }

            $signoffUserDefinition = $returnDefinition->getSignoffUser();
            $signoffUser = $signoffUserDefinition ?
                $this->createUser($signoffUserDefinition) :
                null;

            $return
                ->setSignoffUser($signoffUser)
                ->setSignoffEmail($signoffUser?->getEmail());

            $fundAward->addReturn($return);
        }

        return $fundAward;
    }

    public function createProject(ProjectDefinition $definition): Project
    {
        $project = (new Project())
            ->setName($definition->getName())
            ->setDescription($definition->getDescription())
            ->setProjectIdentifier($definition->getProjectIdentifier())
            ->setActiveTravelElements($definition->getActiveTravelElements())
            ->setIncludesChargingPoints($definition->getIncludesChargingPoints())
            ->setIncludesCleanAirElements($definition->getIncludesCleanAirElements())
            ->setTransportMode($definition->getTransportMode());

        $this->persist([$project]);

        foreach($definition->getProjectFunds() as $projectFundDefinition) {
            $class = $projectFundDefinition::class;

            $projectFund = match($class) {
                CrstsProjectFundDefinition::class => $this->createCrstsProjectFund($projectFundDefinition),
                default => throw new \RuntimeException("Unsupported ProjectFund definition class - {$class}"),
            };

            $project->addProjectFund($projectFund);
        }

        return $project;
    }

    public function createCrstsProjectFund(CrstsProjectFundDefinition $definition): CrstsProjectFund
    {
        $projectFund = (new CrstsProjectFund())
            ->setRetained($definition->isRetained())
            ->setPhase($definition->getPhase());

        $this->persist([$projectFund]);

        return $projectFund;
    }

    /**
     * @param array<Project> $projects
     */
    public function createCrstsFundReturn(CrstsFundReturnDefinition $definition, array $projects=[]): CrstsFundReturn
    {
        $return = (new CrstsFundReturn())
            ->setComments($definition->getComments())
            ->setDeliveryConfidence($definition->getDeliveryConfidence())
            ->setLocalContribution($definition->getLocalContribution())
            ->setYear($definition->getYear())
            ->setQuarter($definition->getQuarter())
            ->setOverallConfidence($definition->getOverallConfidence())
            ->setProgressSummary($definition->getProgressSummary())
            ->setRagProgressRating($definition->getRagProgressRating())
            ->setRagProgressSummary($definition->getRagProgressSummary())
            ->setResourceFunding($definition->getResourceFunding())
        ;

        foreach($definition->getExpenses() as $expenseDefinition) {
            $return->addExpense($this->createExpenseSeries($expenseDefinition));
        }

        foreach($definition->getProjectReturns() as $projectName => $projectReturnDefinition) {
            $project = null;
            foreach($projects as $loopProject) {
                if ($projectName === $loopProject->getName()) {
                    $project = $loopProject;
                    break;
                }
            }

            if (!$project) {
                throw new \RuntimeException("Project referenced by return not found: {$projectName}");
            }

            $projectFund = null;
            foreach($project->getProjectFunds() as $loopFund) {
                $projectFund = match(true) {
                    $loopFund instanceof CrstsProjectFund => $loopFund,
                    default => null,
                };

                if ($projectFund) {
                    break;
                }
            }

            if (!$projectFund) {
                throw new \RuntimeException("Project referenced by return is not funded by CRSTS: {$projectName}");
            }

            $return->addProjectReturn($this->createCrstsProjectReturn($projectReturnDefinition, $projectFund));
        }

        $this->persist([$return]);

        return $return;
    }

    public function createCrstsProjectReturn(CrstsProjectReturnDefinition $definition, CrstsProjectFund $projectFund): CrstsProjectReturn
    {
        $return = (new CrstsProjectReturn())
            ->setProjectFund($projectFund)
            ->setTotalCost($definition->getTotalCost())
            ->setAgreedFunding($definition->getAgreeFunding())
            ->setSpendToDate($definition->getSpendToDate())
            ->setOnTrackRating($definition->getOnTrackRating())
            ->setBusinessCase($definition->getBusinessCase())
            ->setExpectedBusinessCaseApproval($definition->getExpectedBusinessCaseApproval())
            ->setProgressUpdate($definition->getProgressUpdate());

        foreach($definition->getMilestones() as $milestoneDefinition) {
            $return->addMilestone($this->createMilestone($milestoneDefinition));
        }

        foreach($definition->getExpenses() as $expenseDefinition) {
            $return->addExpense($this->createExpenseSeries($expenseDefinition));
        }

        $this->persist([$return]);

        return $return;
    }

    public function createMilestone(MilestoneDefinition $definition): Milestone
    {
        $milestone = (new Milestone())
            ->setDate($definition->getDate())
            ->setType($definition->getType());

        $this->persist([$milestone]);

        return $milestone;
    }

    public function createExpenseSeries(ExpenseDefinition $definition): ExpenseSeries
    {
        $type = $definition->getType();

        $expenseSeries = (new ExpenseSeries())
            ->setType($type);

        $this->persist([$expenseSeries]);

        foreach($definition->getEntries() as $description => $value) {
            $entry = (new ExpenseEntry())
                ->setDescription($description)
                ->setValue($value);

            $expenseSeries->addEntry($entry);
            $this->persist([$entry]);
        }

        return $expenseSeries;
    }
}
