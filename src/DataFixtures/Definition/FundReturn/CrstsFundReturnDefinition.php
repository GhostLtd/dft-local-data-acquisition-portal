<?php

namespace App\DataFixtures\Definition\FundReturn;

use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\SchemeReturn\CrstsSchemeReturnDefinition;
use App\DataFixtures\Definition\UserDefinition;
use App\Entity\Enum\Rating;

class CrstsFundReturnDefinition extends AbstractFundReturnDefinition
{
    /**
     * @param array<ExpenseDefinition> $expenses
     *
     * N.B. the string is the scheme name
     * @param array<string, CrstsSchemeReturnDefinition> $schemeReturns
     */
    public function __construct(
        ?UserDefinition   $signoffUser = null,
        ?\DateTime        $signoffDate = null,
        protected ?int    $year = null,
        protected ?int    $quarter = null,
        protected ?string $progressSummary = null,
        protected ?string $deliveryConfidence = null,
        protected ?Rating $overallConfidence = null,
        protected ?string $ragProgressSummary = null,
        protected ?Rating $ragProgressRating = null,
        protected ?string $localContribution = null,
        protected ?string $resourceFunding = null,
        protected ?string $comments = null,
        protected array   $expenses = [],
        protected array   $schemeReturns = [],
    )
    {
        parent::__construct($signoffUser, $signoffDate);

        foreach($this->schemeReturns as $name => $_scheme) {
            if (!is_string($name)) {
                throw new \RuntimeException('CrstsFundReturnDefinition() - schemeReturns must be indexed by scheme name');
            }
        }
    }

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

    public function getExpenses(): array
    {
        return $this->expenses;
    }

    public function getSchemeReturns(): array
    {
        return $this->schemeReturns;
    }
}
