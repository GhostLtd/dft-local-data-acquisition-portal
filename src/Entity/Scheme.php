<?php

namespace App\Entity;

use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\TransportMode;
use App\Entity\Enum\TransportModeCategory;
use App\Entity\SchemeData\CrstsData;
use App\Entity\Traits\IdTrait;
use App\Repository\SchemeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: SchemeRepository::class)]
class Scheme
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'schemes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Authority $authority = null;

    #[ORM\Column(length: 255)]
    #[NotBlank(message: 'scheme.name.not_blank', groups: ["scheme_details"])]
    private ?string $name = null; // 1proj_info: Scheme name

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[NotBlank(message: 'scheme.description.not_blank', groups: ["scheme_details"])]
    private ?string $description = null; // 1proj_info: Scheme description

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[NotBlank(message: 'scheme.description.not_blank', groups: ["scheme_details"])]
    private ?string $risks = null; // josh_4/2: Scheme level risks

    #[ORM\Column(nullable: true, enumType: TransportMode::class)]
    #[NotNull(message: 'scheme.transport_mode.not_null', groups: ["scheme_transport_mode"])]
    private ?TransportMode $transportMode = null; // 1proj_info: Transport mode

    #[ORM\Column(nullable: true, enumType: ActiveTravelElement::class)]
    private ?ActiveTravelElement $activeTravelElement = null; // 1proj_info: Does this scheme have active travel elements?

    #[ORM\Column(nullable: true)]
    #[NotNull(message: 'scheme.includes_clean_air_elements.not_null', groups: ["scheme_transport_mode"])]
    private ?bool $includesCleanAirElements = null; // 1proj_info: Will this scheme include clean air elements?

    #[ORM\Column(nullable: true)]
    #[NotNull(message: 'scheme.includes_charging_points.not_null', groups: ["scheme_transport_mode"])]
    private ?bool $includesChargingPoints = null; // 1proj_info: Will this scheme include charging points for electric vehicles?

    #[ORM\Column(length: 255, nullable: true)]
    #[NotBlank(message: 'scheme.identifier.not_blank', groups: ["scheme_details"])]
    private ?string $schemeIdentifier = null; // 1proj_info: Scheme ID

    #[ORM\Embedded]
    #[Valid(groups: ["scheme_details"])]
    private CrstsData $crstsData; // 1proj_info

    #[Callback(groups: ['scheme_transport_mode'])]
    public function validateActiveTravel(ExecutionContextInterface $context): void
    {
        if (!$this->transportMode ||
            $this->transportMode->category() !== TransportModeCategory::ACTIVE_TRAVEL
        ) {
            if ($this->activeTravelElement === null) {
                $context
                    ->buildViolation('scheme.active_travel_element.not_null')
                    ->atPath('hasActiveTravelElements')
                    ->addViolation();
            }
        }
    }

    public function __construct()
    {
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

    public function setName(?string $name): static
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

    public function getRisks(): ?string
    {
        return $this->risks;
    }

    public function setRisks(?string $risks): static
    {
        $this->risks = $risks;
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

    public function getCrstsData(): CrstsData
    {
        return $this->crstsData;
    }

    public function setCrstsData(CrstsData $crstsData): Scheme
    {
        $this->crstsData = $crstsData;
        return $this;
    }

    // --------------------------------------------------------------------------------
}
