<?php

namespace App\Entity\ProjectFund;

use App\Entity\Enum\ActiveTravelElements;
use App\Entity\Enum\Fund;
use App\Entity\Enum\TransportMode;
use App\Entity\Project;
use App\Entity\Traits\IdTrait;
use App\Repository\ProjectFund\ProjectFundRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectFundRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::CRSTS->value => CrstsProjectFund::class,
])]
class ProjectFund
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
}
