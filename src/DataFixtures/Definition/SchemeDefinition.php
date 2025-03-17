<?php

namespace App\DataFixtures\Definition;

use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\Fund;
use App\Entity\Enum\TransportMode;
use App\Entity\SchemeData\CrstsData;

class SchemeDefinition
{
    public function __construct(
        protected CrstsData            $crstsData,
        protected string               $name,
        protected string               $description,
        protected ?TransportMode       $transportMode = null,
        protected ?ActiveTravelElement $activeTravelElement = null,
        protected ?string              $schemeIdentifier = null,
        protected array                $funds = [],
    ) {
        foreach($funds as $fund) {
            if (!$fund instanceof Fund) {
                throw new \InvalidArgumentException('Funds must be instance of '.Fund::class);
            }
        }
    }

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

    public function getSchemeIdentifier(): ?string
    {
        return $this->schemeIdentifier;
    }

    public function getCrstsData(): CrstsData
    {
        return $this->crstsData;
    }

    /**
     * @return array<int, Fund>
     */
    public function getFunds(): array
    {
        return $this->funds;
    }
}
