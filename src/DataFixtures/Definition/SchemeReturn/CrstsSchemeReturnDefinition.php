<?php

namespace App\DataFixtures\Definition\SchemeReturn;

use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\MilestoneDefinition;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\OnTrackRating;

class CrstsSchemeReturnDefinition
{
    /**
     * @param array<MilestoneDefinition> $milestones
     * @param array<ExpenseDefinition> $expenses
     */
    public function __construct(
        protected ?string             $totalCost = null,
        protected ?string             $agreeFunding = null,
        protected ?OnTrackRating      $onTrackRating = null,
        protected ?BusinessCase       $businessCase = null,
        protected ?\DateTimeInterface $expectedBusinessCaseApproval = null,
        protected ?string             $progressUpdate = null,
        protected ?bool               $readyForSignoff = null,
        protected array               $milestones = [],
        protected array               $expenses = [],
    ) {}

    public function getTotalCost(): ?string
    {
        return $this->totalCost;
    }

    public function getAgreeFunding(): ?string
    {
        return $this->agreeFunding;
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

    public function getReadyForSignoff(): ?bool
    {
        return $this->readyForSignoff;
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
