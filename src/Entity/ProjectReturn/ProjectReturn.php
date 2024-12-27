<?php

namespace App\Entity\ProjectReturn;

use App\Entity\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\CompletionStatus;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\Fund;
use App\Entity\Enum\ProjectLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\Traits\IdTrait;
use App\Repository\ProjectReturn\ProjectReturnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectReturnRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::CRSTS1->value => CrstsProjectReturn::class,
])]
abstract class ProjectReturn
{
    use IdTrait;

    /**
     * @var Collection<int, ProjectReturnSectionStatus>
     */
    #[ORM\OneToMany(targetEntity: ProjectReturnSectionStatus::class, mappedBy: 'projectReturn', orphanRemoval: true)]
    private Collection $sectionStatuses;

    public function __construct()
    {
        $this->sectionStatuses = new ArrayCollection();
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

    public function getStatusForSection(
        DivisionConfiguration|ProjectLevelSection $section,
        CompletionStatus                          $default = CompletionStatus::NOT_STARTED
    ): CompletionStatus
    {
        $name = match($section::class) {
            DivisionConfiguration::class => $section->getKey(),
            ProjectLevelSection::class => $section->name,
        };

        $projectReturnSectionStatus = $this->getProjectReturnSectionStatusForName($name);

        return $projectReturnSectionStatus ?
            $projectReturnSectionStatus->getStatus() :
            $default;
    }

    abstract public function getFund(): Fund;
    abstract public function getFundReturn(): ?FundReturn;
    abstract public function getProjectFund(): ?ProjectFund;

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
