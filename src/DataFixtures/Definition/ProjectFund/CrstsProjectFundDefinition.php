<?php

namespace App\DataFixtures\Definition\ProjectFund;

use App\DataFixtures\Definition\Return\CrstsReturnDefinition;
use App\Entity\Enum\ActiveTravelElements;
use App\Entity\Enum\CrstsPhase;
use App\Entity\Enum\TransportMode;

class CrstsProjectFundDefinition extends AbstractProjectFundDefinition
{
    /**
     * @param array<ActiveTravelElements> $activeTravelElements
     * @param array<CrstsReturnDefinition> $returns
     */
    public function __construct(
        ?TransportMode $transportMode = null,
        array $activeTravelElements = [],
        ?bool $includesCleanAirElements = null,
        ?bool $includesChargingPoints = null,
        ?string $projectIdentifier = null,
        protected ?bool $retained = null,
        protected ?CrstsPhase $phase = null,
        protected array $returns = [],
    ) {
        parent::__construct($transportMode, $activeTravelElements, $includesCleanAirElements, $includesChargingPoints, $projectIdentifier);
    }

    public function isRetained(): ?bool
    {
        return $this->retained;
    }

    public function getPhase(): ?CrstsPhase
    {
        return $this->phase;
    }

    /**
     * @return array<CrstsReturnDefinition>
     */
    public function getReturns(): array
    {
        return $this->returns;
    }
}
