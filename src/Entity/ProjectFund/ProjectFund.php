<?php

namespace App\Entity\ProjectFund;

use App\Entity\Enum\ActiveTravelElements;
use App\Entity\Enum\Fund;
use App\Entity\Enum\TransportMode;
use App\Entity\Project;
use App\Entity\Traits\IdTrait;
use App\Repository\ProjectFund\ProjectFundRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectFundRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::CRSTS->value => CrstsProjectFund::class,
])]
class ProjectFund
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'projectFunds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(nullable: true, enumType: TransportMode::class)]
    private ?TransportMode $transportMode = null; // 1proj_info: Transport mode

    #[ORM\Column(type: Types::SIMPLE_ARRAY, enumType: ActiveTravelElements::class)]
    private array $activeTravelElements = []; // 1proj_info: Does this project have active travel elements?

    #[ORM\Column(nullable: true)]
    private ?bool $includesCleanAirElements = null; // 1proj_info: Will this project include clean air elements?

    #[ORM\Column(nullable: true)]
    private ?bool $includesChargingPoints = null; // 1proj_info: Will this project include charging points for electric vehicles?

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $projectIdentifier = null; // 1proj_info: Project ID

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getTransportMode(): ?TransportMode
    {
        return $this->transportMode;
    }

    public function setTransportMode(?TransportMode $transportMode): static
    {
        $this->transportMode = $transportMode;
        return $this;
    }

    /**
     * @return ActiveTravelElements[]
     */
    public function getActiveTravelElements(): array
    {
        return $this->activeTravelElements;
    }

    public function setActiveTravelElements(array $activeTravelElements): static
    {
        $this->activeTravelElements = $activeTravelElements;
        return $this;
    }

    public function includesCleanAirElements(): ?bool
    {
        return $this->includesCleanAirElements;
    }

    public function setIncludesCleanAirElements(?bool $includesCleanAirElements): static
    {
        $this->includesCleanAirElements = $includesCleanAirElements;
        return $this;
    }

    public function includesChargingPoints(): ?bool
    {
        return $this->includesChargingPoints;
    }

    public function setIncludesChargingPoints(?bool $includesChargingPoints): static
    {
        $this->includesChargingPoints = $includesChargingPoints;
        return $this;
    }

    public function getProjectIdentifier(): ?string
    {
        return $this->projectIdentifier;
    }

    public function setProjectIdentifier(?string $projectIdentifier): static
    {
        $this->projectIdentifier = $projectIdentifier;
        return $this;
    }
}
