<?php

namespace App\DataFixtures\Definition\SchemeReturn;

use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\TransportMode;

abstract class AbstractSchemeReturnDefinition
{
    public function __construct(
        protected string               $name,
        protected string               $description,
        protected string               $risks,
        protected ?TransportMode       $transportMode = null,
        protected ?ActiveTravelElement $activeTravelElement = null,
        protected ?bool                $includesCleanAirElements = null,
        protected ?bool                $includesChargingPoints = null,
        protected ?string              $schemeIdentifier = null,
    )
    {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRisks(): string
    {
        return $this->risks;
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
}
