<?php

namespace App\Entity;

use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\Fund;
use App\Entity\Enum\TransportMode;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\Traits\IdTrait;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Authority $authority = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null; // 1proj_info: Project name

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null; // 1proj_info: Project description

    #[ORM\Column(nullable: true, enumType: TransportMode::class)]
    private ?TransportMode $transportMode = null; // 1proj_info: Transport mode

    #[ORM\Column(nullable: true, enumType: ActiveTravelElement::class)]
    private ?ActiveTravelElement $activeTravelElement = null; // 1proj_info: Does this project have active travel elements?

    #[ORM\Column(nullable: true)]
    private ?bool $includesCleanAirElements = null; // 1proj_info: Will this project include clean air elements?

    #[ORM\Column(nullable: true)]
    private ?bool $includesChargingPoints = null; // 1proj_info: Will this project include charging points for electric vehicles?

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $projectIdentifier = null; // 1proj_info: Project ID

    /**
     * @var Collection<int, ProjectFund>
     */
    #[ORM\OneToMany(targetEntity: ProjectFund::class, mappedBy: 'project')]
    private Collection $projectFunds;

    public function __construct()
    {
        $this->projectFunds = new ArrayCollection();
    }

    public function getAuthority(): ?Authority
    {
        return $this->authority;
    }

    public function setAuthority(?Authority $authority): static
    {
        $this->authority = $authority;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
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

    public function getActiveTravelElement(): ?ActiveTravelElement
    {
        return $this->activeTravelElement;
    }

    public function setActiveTravelElement(?ActiveTravelElement $activeTravelElement): Project
    {
        $this->activeTravelElement = $activeTravelElement;
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

    /**
     * @return Collection<int, ProjectFund>
     */
    public function getProjectFunds(): Collection
    {
        return $this->projectFunds;
    }

    public function addProjectFund(ProjectFund $projectFund): static
    {
        if (!$this->projectFunds->contains($projectFund)) {
            $this->projectFunds->add($projectFund);
            $projectFund->setProject($this);
        }

        return $this;
    }

    public function removeProjectFund(ProjectFund $projectFund): static
    {
        if ($this->projectFunds->removeElement($projectFund)) {
            // set the owning side to null (unless already changed)
            if ($projectFund->getProject() === $this) {
                $projectFund->setProject(null);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    public function getProjectFundForFund(Fund $fund): ?ProjectFund
    {
        $matchingProjectFunds = $this->getProjectFunds()->filter(
            fn(ProjectFund $pf) => $pf->getFund() === $fund
        );

        return $matchingProjectFunds->isEmpty() ?
            null :
            $matchingProjectFunds->first();
    }
}
