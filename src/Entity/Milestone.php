<?php

namespace App\Entity;

use App\Entity\Enum\MilestoneType;
use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Entity\Traits\IdTrait;
use App\Repository\MilestoneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MilestoneRepository::class)]
class Milestone
{
    use IdTrait;

    #[ORM\Column(enumType: MilestoneType::class)]
    private ?MilestoneType $type = null; // 4proj_milestones (one of "Start development", "End development" etc - see enum)

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null; // 4proj_milestone (the value for the chosen field)

    #[ORM\ManyToOne(inversedBy: 'milestones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CrstsProjectReturn $return = null;

    public function getType(): ?MilestoneType
    {
        return $this->type;
    }

    public function setType(MilestoneType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(?\DateTimeInterface $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getReturn(): ?CrstsProjectReturn
    {
        return $this->return;
    }

    public function setReturn(?CrstsProjectReturn $return): static
    {
        $this->return = $return;
        return $this;
    }
}
