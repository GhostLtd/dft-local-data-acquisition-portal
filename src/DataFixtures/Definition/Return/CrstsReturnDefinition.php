<?php

namespace App\DataFixtures\Definition\Return;

use App\DataFixtures\Definition\ContactDefinition;
use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\MilestoneDefinition;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\OnTrackRating;
use App\Entity\Enum\Rating;

class CrstsReturnDefinition
{
    /**
     * @param array<MilestoneDefinition> $milestones
     * @param array<ExpenseDefinition> $expenses
     */
    public function __construct(
        protected ?int $year = null,
        protected ?int $quarter = null,
        protected ?string $progressSummary = null,
        protected ?string $deliveryConfidence = null,
        protected ?Rating $overallConfidence = null,
        protected ?string $ragProgressSummary = null,
        protected ?Rating $ragProgressRating = null,
        protected ?string $localContribution = null,
        protected ?string $resourceFunding = null,
        protected ?string $comments = null,
        protected ?string $totalCost = null,
        protected ?string $agreeFunding = null,
        protected ?string $spendToDate = null,
        protected ?OnTrackRating $onTrackRating = null,
        protected ?BusinessCase $businessCase = null,
        protected ?\DateTimeInterface $expectedBusinessCaseApproval = null,
        protected ?string $progressUpdate = null,
        protected ?ContactDefinition $signoffContact = null,
        protected array $milestones = [],
        protected array $expenses = [],
    ) {}

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function getQuarter(): ?int
    {
        return $this->quarter;
    }

    public function getProgressSummary(): ?string
    {
        return $this->progressSummary;
    }

    public function getDeliveryConfidence(): ?string
    {
        return $this->deliveryConfidence;
    }

    public function getOverallConfidence(): ?Rating
    {
        return $this->overallConfidence;
    }

    public function getRagProgressSummary(): ?string
    {
        return $this->ragProgressSummary;
    }

    public function getRagProgressRating(): ?Rating
    {
        return $this->ragProgressRating;
    }

    public function getLocalContribution(): ?string
    {
        return $this->localContribution;
    }

    public function getResourceFunding(): ?string
    {
        return $this->resourceFunding;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function getTotalCost(): ?string
    {
        return $this->totalCost;
    }

    public function getAgreeFunding(): ?string
    {
        return $this->agreeFunding;
    }

    public function getSpendToDate(): ?string
    {
        return $this->spendToDate;
    }

    public function getOnTrackRating(): ?OnTrackRating
    {
        return $this->onTrackRating;
    }

    public function getBusinessCase(): ?BusinessCase
    {
        return $this->businessCase;
    }

    public function getExpectedBusinessCaseApproval(): ?\DateTimeInterface
    {
        return $this->expectedBusinessCaseApproval;
    }

    public function getProgressUpdate(): ?string
    {
        return $this->progressUpdate;
    }

    public function getSignoffContact(): ?ContactDefinition
    {
        return $this->signoffContact;
    }

    /** @return array<MilestoneDefinition> */
    public function getMilestones(): array
    {
        return $this->milestones;
    }

    /** @return array<ExpenseDefinition> */
    public function getExpenses(): array
    {
        return $this->expenses;
    }
}
