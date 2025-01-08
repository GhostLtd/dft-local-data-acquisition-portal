<?php

namespace App\Entity\ProjectReturn;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\CompletionStatus;
use App\Entity\Enum\Fund;
use App\Entity\Enum\ProjectLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\Traits\IdTrait;
use App\Repository\ProjectReturn\ProjectReturnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Valid;

#[ORM\Entity(repositoryClass: ProjectReturnRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::CRSTS1->value => CrstsProjectReturn::class,
])]
abstract class ProjectReturn
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'returns')]
    #[ORM\JoinColumn(nullable: false)]
    #[Valid(groups: ['project_details'])]
    private ?ProjectFund $projectFund = null;

    #[ORM\ManyToOne(inversedBy: 'projectReturns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FundReturn $fundReturn = null;

    /**
     * @var Collection<int, ProjectReturnSectionStatus>
     */
    #[ORM\OneToMany(targetEntity: ProjectReturnSectionStatus::class, mappedBy: 'projectReturn', orphanRemoval: true)]
    private Collection $sectionStatuses;

    public function __construct()
    {
        $this->sectionStatuses = new ArrayCollection();
    }

    public function getProjectFund(): ?ProjectFund
    {
        return $this->projectFund;
    }

    public function setProjectFund(?ProjectFund $projectFund): static
    {
        $this->projectFund = $projectFund;
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
     * @return Collection<int, ProjectReturnSectionStatus>
     */
    public function getSectionStatuses(): Collection
    {
        return $this->sectionStatuses;
    }

    public function addSectionStatus(ProjectReturnSectionStatus $sectionStatus): static
    {
        if (!$this->sectionStatuses->contains($sectionStatus)) {
            $this->sectionStatuses->add($sectionStatus);
            $sectionStatus->setProjectReturn($this);
        }

        return $this;
    }

    public function removeSectionStatus(ProjectReturnSectionStatus $sectionStatus): static
    {
        if ($this->sectionStatuses->removeElement($sectionStatus)) {
            // set the owning side to null (unless already changed)
            if ($sectionStatus->getProjectReturn() === $this) {
                $sectionStatus->setProjectReturn(null);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    public function getProjectReturnSectionStatusForName(string $name): ?ProjectReturnSectionStatus
    {
        return $this->sectionStatuses->findFirst(fn(int $idx, ProjectReturnSectionStatus $status) => $status->getName() === $name);
    }

    public function getOrCreateProjectReturnSectionStatus(DivisionConfiguration|ProjectLevelSection $section): ProjectReturnSectionStatus
    {
        $name = self::getSectionName($section);

        $status = $this->getProjectReturnSectionStatusForName($name);

        if (!$status) {
            $status = (new ProjectReturnSectionStatus())
                ->setStatus(CompletionStatus::NOT_STARTED)
                ->setName($name);

            $this->addSectionStatus($status);
        }

        return $status;
    }


    public function getStatusForSection(
        DivisionConfiguration|ProjectLevelSection $section,
        CompletionStatus                          $default = CompletionStatus::NOT_STARTED
    ): CompletionStatus
    {
        $projectReturnSectionStatus = $this->getProjectReturnSectionStatusForName(self::getSectionName($section));

        return $projectReturnSectionStatus ?
            $projectReturnSectionStatus->getStatus() :
            $default;
    }

    public static function getSectionName(DivisionConfiguration|ProjectLevelSection $section): string
    {
        return match($section::class) {
            DivisionConfiguration::class => $section->getKey(),
            ProjectLevelSection::class => $section->name,
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
