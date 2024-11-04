<?php

namespace App\DataFixtures;

use App\DataFixtures\Definition\ContactDefinition;
use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\FundRecipientDefinition;
use App\DataFixtures\Definition\MilestoneDefinition;
use App\DataFixtures\Definition\ProjectDefinition;
use App\DataFixtures\Definition\ProjectFund\CrstsProjectFundDefinition;
use App\DataFixtures\Definition\Return\CrstsReturnDefinition;
use App\Entity\Contact;
use App\Entity\Expense\ExpenseEntry;
use App\Entity\Expense\ExpenseSeries;
use App\Entity\FundRecipient;
use App\Entity\Milestone;
use App\Entity\Project;
use App\Entity\ProjectFund\CrstsProjectFund;
use App\Entity\Return\CrstsReturn;
use Doctrine\ORM\EntityManagerInterface;

class FixtureHelper
{
    protected ?EntityManagerInterface $entityManager = null;
    protected ?array $contacts = [];

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

    public function createFundRecipient(FundRecipientDefinition $definition): FundRecipient
    {
        $leadContact = $this->createContact($definition->getContact());

        $fundRecipient = (new FundRecipient())
            ->setName($definition->getName())
            ->setLeadContact($leadContact);

        $this->persist([$fundRecipient]);

        foreach($definition->getProjects() as $projectDefinition) {
            $fundRecipient->addProject($this->createProject($projectDefinition));
        }

        return $fundRecipient;
    }

    public function createContact(ContactDefinition $definition): Contact
    {
        $email = $definition->getEmail();

        $existingContact = $this->contacts[$email] ?? null;

        if ($existingContact) {
            if (
                $definition->getName() !== $existingContact->getName() ||
                $definition->getPhone() !== $existingContact->getPhone() ||
                $definition->getPosition() !== $existingContact->getPosition()
            ) {
                throw new \RuntimeException('Contact with given email already exists, but with different details');
            }

            return $existingContact;
        }

        $contact = (new Contact())
            ->setName($definition->getName())
            ->setPosition($definition->getPosition())
            ->setPhone($definition->getPhone())
            ->setEmail($email);

        $this->contacts[$email] = $contact;

        $this->persist([$contact]);

        return $contact;
    }

    public function createProject(ProjectDefinition $definition): Project
    {
        $project = (new Project())
            ->setName($definition->getName())
            ->setDescription($definition->getDescription());

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
            ->setPhase($definition->getPhase())
            ->setProjectIdentifier($definition->getProjectIdentifier())
            ->setActiveTravelElements($definition->getActiveTravelElements())
            ->setIncludesChargingPoints($definition->getIncludesChargingPoints())
            ->setIncludesCleanAirElements($definition->getIncludesCleanAirElements())
            ->setTransportMode($definition->getTransportMode());

        $this->persist([$projectFund]);

        foreach($definition->getReturns() as $returnDefinition) {
            $projectFund->addReturn($this->createCrstsReturn($returnDefinition));
        }

        return $projectFund;
    }

    public function createCrstsReturn(CrstsReturnDefinition $definition): CrstsReturn
    {
        $signoffContactDefinition = $definition->getSignoffContact();
        $signoffContact = $signoffContactDefinition ?
            $this->createContact($signoffContactDefinition) :
            null;

        $signoffBy = $signoffContact ? $signoffContact->getEmail() : null;

        $return = (new CrstsReturn())
            ->setComments($definition->getComments())
            ->setBusinessCase($definition->getBusinessCase())
            ->setAgreedFunding($definition->getAgreeFunding())
            ->setDeliveryConfidence($definition->getDeliveryConfidence())
            ->setLocalContribution($definition->getLocalContribution())
            ->setYear($definition->getYear())
            ->setQuarter($definition->getQuarter())
            ->setExpectedBusinessCaseApproval($definition->getExpectedBusinessCaseApproval())
            ->setOnTrackRating($definition->getOnTrackRating())
            ->setOverallConfidence($definition->getOverallConfidence())
            ->setProgressSummary($definition->getProgressSummary())
            ->setProgressUpdate($definition->getProgressUpdate())
            ->setRagProgressRating($definition->getRagProgressRating())
            ->setRagProgressSummary($definition->getRagProgressSummary())
            ->setResourceFunding($definition->getResourceFunding())
            ->setSignoffContact($signoffContact)
            ->setSignoffBy($signoffBy)
            ->setSpendToDate($definition->getSpendToDate())
            ->setTotalCost($definition->getTotalCost());

        $this->persist([$return]);

        foreach($definition->getMilestones() as $milestoneDefinition) {
            $return->addMilestone($this->createMilestone($milestoneDefinition));
        }

        foreach($definition->getExpenses() as $expenseDefinition) {
            $return->addExpense($this->createExpenseSeries($expenseDefinition));
        }

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
