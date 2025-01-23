<?php

namespace App\DataFixtures\Definition;

use App\DataFixtures\Definition\SchemeFund\AbstractSchemeFundDefinition;
use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\TransportMode;

class SchemeDefinition
{
    /**
     * @param array<AbstractSchemeFundDefinition> $schemeFunds
     */
    public function __construct(
        protected string               $name,
        protected string               $description,
        protected ?TransportMode       $transportMode = null,
        protected ?ActiveTravelElement $activeTravelElement = null,
        protected ?bool                $includesCleanAirElements = null,
        protected ?bool                $includesChargingPoints = null,
        protected ?string              $schemeIdentifier = null,
        protected array                $schemeFunds = [],
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

    public function getActiveTravelElement(): ?ActiveTravelElement
    {
        return $this->activeTravelElement;
    }

    public function getIncludesCleanAirElements(): ?bool
    {
        return $this->includesCleanAirElements;
    }

    public function getIncludesChargingPoints(): ?bool
    {
        return $this->includesChargingPoints;
    }

    public function getSchemeIdentifier(): ?string
    {
        return $this->schemeIdentifier;
    }

    /**
     * @return array<AbstractSchemeFundDefinition>
     */
    public function getSchemeFunds(): array
    {
        return $this->schemeFunds;
    }
}
