<?php

namespace App\DataFixtures\Definition;

use App\DataFixtures\Definition\ProjectFund\AbstractProjectFundDefinition;
use App\Entity\Enum\ActiveTravelElements;
use App\Entity\Enum\TransportMode;

class ProjectDefinition
{
    /**
     * @param array<AbstractProjectFundDefinition> $projectFunds
     * @param array<ActiveTravelElements> $activeTravelElements
     */
    public function __construct(
        protected string $name,
        protected string $description,
        protected ?TransportMode $transportMode = null,
        protected array $activeTravelElements = [],
        protected ?bool $includesCleanAirElements = null,
        protected ?bool $includesChargingPoints = null,
        protected ?string $projectIdentifier = null,
        protected array $projectFunds = [],
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

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

    /**
     * @return array<AbstractProjectFundDefinition>
     */
    public function getProjectFunds(): array
    {
        return $this->projectFunds;
    }
}
