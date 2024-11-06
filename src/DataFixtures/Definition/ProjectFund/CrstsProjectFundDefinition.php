<?php

namespace App\DataFixtures\Definition\ProjectFund;

use App\DataFixtures\Definition\ProjectReturn\CrstsProjectReturnDefinition;
use App\DataFixtures\Definition\UserDefinition;
use App\Entity\Enum\ActiveTravelElements;
use App\Entity\Enum\CrstsPhase;
use App\Entity\Enum\Fund;
use App\Entity\Enum\TransportMode;

class CrstsProjectFundDefinition extends AbstractProjectFundDefinition
{
    public function __construct(
        protected ?bool       $retained = null,
        protected ?CrstsPhase $phase = null,
    ) {}

    public function isRetained(): ?bool
    {
        return $this->retained;
    }

    public function getPhase(): ?CrstsPhase
    {
        return $this->phase;
    }

    public function getFund(): Fund
    {
        return Fund::CRSTS;
    }
}
