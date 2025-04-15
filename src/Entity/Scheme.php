<?php

namespace App\Entity;

use App\Entity\Enum\ActiveTravelElement;
use App\Entity\Enum\Fund;
use App\Entity\Enum\TransportMode;
use App\Entity\Enum\TransportModeCategory;
use App\Entity\SchemeData\CrstsData;
use App\Entity\Traits\IdTrait;
use App\Repository\SchemeRepository;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: SchemeRepository::class)]
#[UniqueEntity(fields: ['authority', 'schemeIdentifier'], groups: ["scheme.add", "scheme.edit"])]
#[UniqueEntity(fields: ['authority', 'name'], groups: ["scheme.add", "scheme.edit"])]
class Scheme implements PropertyChangeLoggableInterface
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'schemes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Authority $authority = null;

    #[ORM\Column(length: 255)]
    #[Length(max: 255, groups: ["scheme.add", "scheme.edit"])]
    #[NotBlank(message: 'scheme.name.not_blank', groups: ["scheme.add", "scheme.edit"])]
    private ?string $name = null; // 1proj_info: Scheme name

    #[ORM\Column(type: Types::TEXT, length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, nullable: true)]
    #[Length(max: 16383, groups: ["scheme.add", "scheme.edit"])]
    #[NotBlank(message: 'scheme.description.not_blank', groups: ["scheme.add", "scheme.edit"])]
    private ?string $description = null; // 1proj_info: Scheme description

    #[ORM\Column(nullable: true, enumType: TransportMode::class)]
    #[NotNull(message: 'scheme.transport_mode.not_null', groups: ["scheme.add", "scheme.edit"])]
    private ?TransportMode $transportMode = null; // 1proj_info: Transport mode

    #[ORM\Column(nullable: true, enumType: ActiveTravelElement::class)]
    private ?ActiveTravelElement $activeTravelElement = null; // 1proj_info: Does this scheme have active travel elements?

    #[ORM\Column(length: 255, nullable: true)]
    #[Length(max: 255, groups: ["scheme.add", "scheme.edit"])]
    #[NotNull(message: 'scheme.identifier.not_blank', groups: ["scheme.add", "scheme.edit"])]
    private ?string $schemeIdentifier = null; // 1proj_info: Scheme ID

    #[ORM\Embedded]
    #[Valid(groups: ["scheme.crsts1.add", "scheme.crsts1.edit"])]
    private CrstsData $crstsData;

    #[ORM\Column]
    /** @var $funds array<int, string> */
    private array $funds = []; // e.g. CRSTS, BSIP

    #[Callback(groups: ["scheme.add", "scheme.edit"])]
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
        $this->crstsData = new CrstsData();
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

    public function getSchemeIdentifier(bool $numberPatOnly = false): ?string
    {
        if ($numberPatOnly) {
            return $this->schemeIdentifier;
        }
        $words = preg_split("/[\s\/,-]+/", $this->authority->getName());
        $prefix = join('', array_map(fn($n) => substr($n, 0, 1), $words));
        return $prefix . '-' . $this->schemeIdentifier;
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

    /**
     * @return array<int, Fund>
     */
    public function getFunds(): array
    {
        return array_map(fn(string $fund) => Fund::from($fund), $this->funds);
    }

    public function setFunds(array $funds): static
    {
        foreach(Fund::cases() as $fund) {
            if (in_array($fund, $funds)) {
                $this->addFund($fund);
            } else {
                $this->removeFund($fund);
            }
        }

        return $this;
    }

    public function addFund(Fund $fund): static
    {
        if (!in_array($fund->value, $this->funds)) {
            $this->funds[] = $fund->value;
        }

        return $this;
    }

    public function removeFund(Fund $fund): static
    {
        foreach($this->funds as $key => $fundValue) {
            if ($fundValue === $fund->value) {
                unset($this->funds[$key]);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    public function fundsAsString(): string
    {
        return empty($this->funds) ? '-' : join(', ', $this->funds);
    }

    public function hasFundByValue(string $fundValue): bool
    {
        return $this->hasFund(Fund::from($fundValue));
    }

    public function hasFund(Fund $fund): bool
    {
        return in_array($fund->value, $this->funds);
    }
}
