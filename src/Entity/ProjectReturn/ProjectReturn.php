<?php

namespace App\Entity\ProjectReturn;

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
    Fund::CRSTS->value => CrstsProjectReturn::class,
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

    public function getProjectReturnSectionStatusForSection(ExpenseType|ProjectLevelSection $enum): ?ProjectReturnSectionStatus
    {
        return $this->sectionStatuses->findFirst(fn(int $idx, ProjectReturnSectionStatus $status) => $status->getName() === $enum->name);
    }

    public function getStatusForSection(
        ExpenseType|ProjectLevelSection $enum,
        CompletionStatus             $default = CompletionStatus::NOT_STARTED
    ): CompletionStatus
    {
        $projectReturnSectionStatus = $this->getProjectReturnSectionStatusForSection($enum);

        return $projectReturnSectionStatus ?
            $projectReturnSectionStatus->getStatus() :
            $default;
    }

    abstract public function getFund(): Fund;
    abstract public function getFundReturn(): ?FundReturn;
    abstract public function getProjectFund(): ?ProjectFund;
}
