<?php

namespace App\DataFixtures\Definition\ProjectFund;

use App\Entity\Enum\Fund;
use App\Entity\Enum\FundedMostlyAs;

class CrstsProjectFundDefinition extends AbstractProjectFundDefinition
{
    public function __construct(
        protected ?bool           $retained = null,
        protected ?bool           $previouslyTcf = null,
        protected ?FundedMostlyAs $fundedMostlyAs = null,
    ) {}

    public function isRetained(): ?bool
    {
        return $this->retained;
    }

    public function getFund(): Fund
    {
        return Fund::CRSTS;
    }

    public function getPreviouslyTcf(): ?bool
    {
        return $this->previouslyTcf;
    }

    public function getFundedMostlyAs(): ?FundedMostlyAs
    {
        return $this->fundedMostlyAs;
    }
}
