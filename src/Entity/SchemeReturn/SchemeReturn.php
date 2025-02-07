<?php

namespace App\Entity\SchemeReturn;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\CompletionStatus;
use App\Entity\Enum\Fund;
use App\Entity\Enum\SchemeLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeFund\SchemeFund;
use App\Entity\Traits\IdTrait;
use App\Repository\SchemeReturn\SchemeReturnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Valid;

#[ORM\Entity(repositoryClass: SchemeReturnRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::CRSTS1->value => CrstsSchemeReturn::class,
])]
abstract class SchemeReturn
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'returns')]
    #[ORM\JoinColumn(nullable: false)]
    #[Valid(groups: ['scheme_details', 'scheme_elements', 'scheme_transport_mode'])]
    private ?SchemeFund $schemeFund = null;

    #[ORM\ManyToOne(inversedBy: 'schemeReturns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FundReturn $fundReturn = null;

    /**
     * @var Collection<int, SchemeReturnSectionStatus>
     */
    #[ORM\OneToMany(targetEntity: SchemeReturnSectionStatus::class, mappedBy: 'schemeReturn', orphanRemoval: true)]
    private Collection $sectionStatuses;

    #[ORM\Column]
    private bool $readyForSignoff = false;

    public function __construct()
    {
        $this->sectionStatuses = new ArrayCollection();
    }

    public function getSchemeFund(): ?SchemeFund
    {
        return $this->schemeFund;
    }

    public function setSchemeFund(?SchemeFund $schemeFund): static
    {
        $this->schemeFund = $schemeFund;
        return $this;
    }

    public function getFundReturn(): ?FundReturn
    {
        return $this->fundReturn;
    }

    public function setFundReturn(?FundReturn $fundReturn): static
    {
        $this->fundReturn = $fundReturn;
        return $this;
    }


    /**
     * @return Collection<int, SchemeReturnSectionStatus>
     */
    public function getSectionStatuses(): Collection
    {
        return $this->sectionStatuses;
    }

    public function getReadyForSignoff(): bool
    {
        return $this->readyForSignoff;
    }

    public function setReadyForSignoff(bool $readyForSignoff): static
    {
        $this->readyForSignoff = $readyForSignoff;
        return $this;
    }

    public function addSectionStatus(SchemeReturnSectionStatus $sectionStatus): static
    {
        if (!$this->sectionStatuses->contains($sectionStatus)) {
            $this->sectionStatuses->add($sectionStatus);
            $sectionStatus->setSchemeReturn($this);
        }

        return $this;
    }

    public function removeSectionStatus(SchemeReturnSectionStatus $sectionStatus): static
    {
        if ($this->sectionStatuses->removeElement($sectionStatus)) {
            // set the owning side to null (unless already changed)
            if ($sectionStatus->getSchemeReturn() === $this) {
                $sectionStatus->setSchemeReturn(null);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    public function getSchemeReturnSectionStatusForName(string $name): ?SchemeReturnSectionStatus
    {
        return $this->sectionStatuses->findFirst(fn(int $idx, SchemeReturnSectionStatus $status) => $status->getName() === $name);
    }

    public function getOrCreateSchemeReturnSectionStatus(DivisionConfiguration|SchemeLevelSection $section): SchemeReturnSectionStatus
    {
        $name = self::getSectionName($section);

        $status = $this->getSchemeReturnSectionStatusForName($name);

        if (!$status) {
            $status = (new SchemeReturnSectionStatus())
                ->setStatus(CompletionStatus::NOT_STARTED)
                ->setName($name);

            $this->addSectionStatus($status);
        }

        return $status;
    }


    public function getStatusForSection(
        DivisionConfiguration|SchemeLevelSection $section,
        CompletionStatus                         $default = CompletionStatus::NOT_STARTED
    ): CompletionStatus
    {
        $schemeReturnSectionStatus = $this->getSchemeReturnSectionStatusForName(self::getSectionName($section));

        return $schemeReturnSectionStatus ?
            $schemeReturnSectionStatus->getStatus() :
            $default;
    }

    public static function getSectionName(DivisionConfiguration|SchemeLevelSection $section): string
    {
        return match($section::class) {
            DivisionConfiguration::class => $section->getKey(),
            SchemeLevelSection::class => $section->name,
        };
    }

    abstract public function getFund(): Fund;

    /** @return array<int, DivisionConfiguration> */
    abstract public function getDivisionConfigurations(): array;

    public function findDivisionConfigurationByKey(string $key): ?DivisionConfiguration
    {
        foreach($this->getDivisionConfigurations() as $divisionConfiguration) {
            if ($divisionConfiguration->getKey() === $key) {
                return $divisionConfiguration;
            }
        }

        return null;
    }
}
