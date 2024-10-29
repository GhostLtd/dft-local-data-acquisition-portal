<?php

namespace App\DataFixtures\Definition\ProjectFund;

use App\Entity\Enum\ActiveTravelElements;
use App\Entity\Enum\TransportMode;

abstract class AbstractProjectFundDefinition
{
    /**
     * @param array<ActiveTravelElements> $activeTravelElements
     */
    public function __construct(
        protected ?TransportMode $transportMode = null,
        protected array $activeTravelElements = [],
        protected ?bool $includesCleanAirElements = null,
        protected ?bool $includesChargingPoints = null,
        protected ?string $projectIdentifier = null,
    ) {}

    public function getTransportMode(): ?TransportMode
    {
        return $this->transportMode;
    }

    public function getActiveTravelElements(): array
    {
        return $this->activeTravelElements;
    }

    public function getIncludesCleanAirElements(): ?bool
    {
        return $this->includesCleanAirElements;
    }

    public function getIncludesChargingPoints(): ?bool
    {
        return $this->includesChargingPoints;
    }

    public function getProjectIdentifier(): ?string
    {
        return $this->projectIdentifier;
    }
}
