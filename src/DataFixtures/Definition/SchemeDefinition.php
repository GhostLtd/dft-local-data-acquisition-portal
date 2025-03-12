<?php

namespace App\DataFixtures\Definition;

use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\TransportMode;
use App\Entity\SchemeData\CrstsData;

class SchemeDefinition
{
    public function __construct(
        protected CrstsData            $crstsData,
        protected string               $name,
        protected string               $description,
        protected string               $risks,
        protected ?TransportMode       $transportMode = null,
        protected ?ActiveTravelElement $activeTravelElement = null,
        protected ?bool                $includesCleanAirElements = null,
        protected ?bool                $includesChargingPoints = null,
        protected ?string              $schemeIdentifier = null,
    ) {}

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

    public function getCrstsData(): CrstsData
    {
        return $this->crstsData;
    }
}
