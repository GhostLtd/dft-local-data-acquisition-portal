<?php

namespace App\Entity\FundReturn;

use App\Entity\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\Fund;
use App\Entity\Enum\CompletionStatus;
use App\Entity\Enum\FundLevelSection;
use App\Entity\FundAward;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Entity\Traits\IdTrait;
use App\Entity\User;
use App\Repository\FundReturn\FundReturnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FundReturnRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::CRSTS1->value => CrstsFundReturn::class,
])]
abstract class FundReturn
{
    use IdTrait;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $quarter = null;

    #[ORM\ManyToOne(inversedBy: 'returns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FundAward $fundAward = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signoffEmail = null; // top_signoff

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $signoffUser = null; // top_signoff

    /**
     * @var Collection<int, FundReturnSectionStatus>
     */
    #[ORM\OneToMany(targetEntity: FundReturnSectionStatus::class, mappedBy: 'fundReturn', orphanRemoval: true)]
    private Collection $sectionStatuses;

    public function __construct()
    {
        $this->sectionStatuses = new ArrayCollection();
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getQuarter(): ?int
    {
        return $this->quarter;
    }

    public function setQuarter(?int $quarter): static
    {
        $this->quarter = $quarter;
        return $this;
    }

    public function getFundAward(): ?FundAward
    {
        return $this->fundAward;
    }

    public function setFundAward(?FundAward $fundAward): static
    {
        $this->fundAward = $fundAward;
        return $this;
    }

    public function getSignoffEmail(): ?string
    {
        return $this->signoffEmail;
    }

    public function setSignoffEmail(?string $signoffEmail): static
    {
        $this->signoffEmail = $signoffEmail;
        return $this;
    }

    public function getSignoffUser(): ?User
    {
        return $this->signoffUser;
    }

    public function setSignoffUser(?User $signoffUser): static
    {
        $this->signoffUser = $signoffUser;
        return $this;
    }

    /**
     * @return Collection<int, FundReturnSectionStatus>
     */
    public function getSectionStatuses(): Collection
    {
        return $this->sectionStatuses;
    }

    public function addSectionStatus(FundReturnSectionStatus $sectionStatus): static
    {
        if (!$this->sectionStatuses->contains($sectionStatus)) {
            $this->sectionStatuses->add($sectionStatus);
            $sectionStatus->setFundReturn($this);
        }

        return $this;
    }

    public function removeSectionStatus(FundReturnSectionStatus $sectionStatus): static
    {
        if ($this->sectionStatuses->removeElement($sectionStatus)) {
            // set the owning side to null (unless already changed)
            if ($sectionStatus->getFundReturn() === $this) {
                $sectionStatus->setFundReturn(null);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    public function getFundReturnSectionStatusForName(string $name): ?FundReturnSectionStatus
    {
        return $this->sectionStatuses->findFirst(fn(int $idx, FundReturnSectionStatus $status) => $status->getName() === $name);
    }

    public function getStatusForSection(
        DivisionConfiguration|FundLevelSection $section,
        CompletionStatus                       $default = CompletionStatus::NOT_STARTED
    ): CompletionStatus
    {
        $name = match($section::class) {
            DivisionConfiguration::class => $section->getKey(),
            FundLevelSection::class => $section->name,
        };

        $fundReturnSectionStatus = $this->getFundReturnSectionStatusForName($name);

        return $fundReturnSectionStatus ?
            $fundReturnSectionStatus->getStatus() :
            $default;
    }

    abstract public function getFund(): Fund;

    /** @return Collection<int, ProjectReturn> */
    abstract public function getProjectReturns(): Collection;

    /** @return array<int, DivisionConfiguration> */
    abstract public function getDivisionConfigurations(): array;

    public function getProjectReturnForProjectFund(ProjectFund $projectFund): ?ProjectReturn
    {
        foreach($this->getProjectReturns() as $projectReturn) {
            if ($projectReturn->getProjectFund() === $projectFund) {
                return $projectReturn;
            }
        }

        return null;
    }

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
