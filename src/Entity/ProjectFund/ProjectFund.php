<?php

namespace App\Entity\ProjectFund;

use App\Entity\Enum\Fund;
use App\Entity\Project;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Entity\Traits\IdTrait;
use App\Repository\ProjectFund\ProjectFundRepository;
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


    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    // --------------------------------------------------------------------------------

    abstract public function getFund(): Fund;
    abstract public function isReturnRequiredFor(int $quarter): bool;

    /** @return Collection<int, ProjectReturn> */
    abstract public function getReturns(): Collection;
}
