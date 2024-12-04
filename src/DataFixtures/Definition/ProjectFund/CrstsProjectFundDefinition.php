<?php

namespace App\DataFixtures\Definition\ProjectFund;

use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\Enum\Fund;
use App\Entity\Enum\FundedMostlyAs;

class CrstsProjectFundDefinition extends AbstractProjectFundDefinition
{
    public function __construct(
        protected ?bool                 $retained = null,
        protected ?bool                 $previouslyTcf = null,
        protected ?FundedMostlyAs       $fundedMostlyAs = null,
        protected ?BenefitCostRatioType $benefitCostRatioType = null,
        protected ?string               $benefitCostRatioValue = null,
    ) {}

    public function isRetained(): ?bool
    {
        return $this->retained;
    }

    public function getFund(): Fund
    {
        return Fund::CRSTS1;
    }

    public function getPreviouslyTcf(): ?bool
    {
        return $this->previouslyTcf;
    }

    public function getFundedMostlyAs(): ?FundedMostlyAs
    {
        return $this->fundedMostlyAs;
    }

    public function getBenefitCostRatioValue(): ?string
    {
        return $this->benefitCostRatioValue;
    }

    public function getBenefitCostRatioType(): ?BenefitCostRatioType
    {
        return $this->benefitCostRatioType;
    }
}
