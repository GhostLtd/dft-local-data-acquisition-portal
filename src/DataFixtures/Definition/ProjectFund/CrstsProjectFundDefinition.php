<?php

namespace App\DataFixtures\Definition\ProjectFund;

use App\Entity\Enum\Fund;

class CrstsProjectFundDefinition extends AbstractProjectFundDefinition
{
    public function __construct(
        protected ?bool       $retained = null,
    ) {}

    public function isRetained(): ?bool
    {
        return $this->retained;
    }

    public function getFund(): Fund
    {
        return Fund::CRSTS;
    }
}
