<?php

namespace App\Entity;

use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\Fund;
use App\Entity\Enum\TransportMode;
use App\Entity\SchemeFund\SchemeFund;
use App\Entity\Traits\IdTrait;
use App\Repository\SchemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchemeRepository::class)]
class Scheme
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'schemes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Authority $authority = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null; // 1proj_info: Scheme name

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null; // 1proj_info: Scheme description

    #[ORM\Column(nullable: true, enumType: TransportMode::class)]
    private ?TransportMode $transportMode = null; // 1proj_info: Transport mode

    #[ORM\Column(nullable: true, enumType: ActiveTravelElement::class)]
    private ?ActiveTravelElement $activeTravelElement = null; // 1proj_info: Does this scheme have active travel elements?

    #[ORM\Column(nullable: true)]
    private ?bool $includesCleanAirElements = null; // 1proj_info: Will this scheme include clean air elements?

    #[ORM\Column(nullable: true)]
    private ?bool $includesChargingPoints = null; // 1proj_info: Will this scheme include charging points for electric vehicles?

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $schemeIdentifier = null; // 1proj_info: Scheme ID

    /**
     * @var Collection<int, SchemeFund>
     */
    #[ORM\OneToMany(targetEntity: SchemeFund::class, mappedBy: 'scheme')]
    private Collection $schemeFunds;

    public function __construct()
    {
        $this->schemeFunds = new ArrayCollection();
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

    public function setActiveTravelElement(?ActiveTravelElement $activeTravelElement): Scheme
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

    public function getSchemeIdentifier(): ?string
    {
        return $this->schemeIdentifier;
    }

    public function setSchemeIdentifier(?string $schemeIdentifier): static
    {
        $this->schemeIdentifier = $schemeIdentifier;
        return $this;
    }

    /**
     * @return Collection<int, SchemeFund>
     */
    public function getSchemeFunds(): Collection
    {
        return $this->schemeFunds;
    }

    public function addSchemeFund(SchemeFund $schemeFund): static
    {
        if (!$this->schemeFunds->contains($schemeFund)) {
            $this->schemeFunds->add($schemeFund);
            $schemeFund->setScheme($this);
        }

        return $this;
    }

    public function removeSchemeFund(SchemeFund $schemeFund): static
    {
        if ($this->schemeFunds->removeElement($schemeFund)) {
            // set the owning side to null (unless already changed)
            if ($schemeFund->getScheme() === $this) {
                $schemeFund->setScheme(null);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    public function getSchemeFundForFund(Fund $fund): ?SchemeFund
    {
        $matchingSchemeFunds = $this->getSchemeFunds()->filter(
            fn(SchemeFund $pf) => $pf->getFund() === $fund
        );

        return $matchingSchemeFunds->isEmpty() ?
            null :
            $matchingSchemeFunds->first();
    }
}
