<?php

namespace App\Entity\ProjectFund;

use App\Entity\Enum\Fund;
use App\Entity\Project;
use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Entity\Traits\IdTrait;
use App\Repository\ProjectFund\ProjectFundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectFundRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::BSIP->value => BsipProjectFund::class,
    Fund::CRSTS1->value => CrstsProjectFund::class,
])]
abstract class ProjectFund
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'projectFunds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    /**
     * @var Collection<int, ProjectReturn>
     */
    #[ORM\OneToMany(targetEntity: ProjectReturn::class, mappedBy: 'projectFund', orphanRemoval: true)]
    private Collection $returns;

    public function __construct()
    {
        $this->returns = new ArrayCollection();
    }


    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    /**
     * @return Collection<int, ProjectReturn>
     */
    public function getReturns(): Collection
    {
        return $this->returns;
    }

    public function addReturn(ProjectReturn $return): static
    {
        if (!$this->returns->contains($return)) {
            $this->returns->add($return);
            $return->setProjectFund($this);
        }

        return $this;
    }

    public function removeReturn(ProjectReturn $return): static
    {
        if ($this->returns->removeElement($return)) {
            // set the owning side to null (unless already changed)
            if ($return->getProjectFund() === $this) {
                $return->setProjectFund(null);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    abstract public function getFund(): Fund;
    abstract public function isReturnRequiredFor(int $quarter): bool;
}
