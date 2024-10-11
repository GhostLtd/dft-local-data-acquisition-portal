<?php

namespace App\Entity;

use App\Entity\Enum\MilestoneType;
use App\Entity\Return\CrstsReturn;
use App\Entity\Traits\IdTrait;
use App\Repository\MilestoneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MilestoneRepository::class)]
class Milestone
{
    use IdTrait;

    #[ORM\Column(enumType: MilestoneType::class)]
    private ?MilestoneType $type = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'milestones')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CrstsReturn $return = null;

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

    public function getReturn(): ?CrstsReturn
    {
        return $this->return;
    }

    public function setReturn(?CrstsReturn $return): static
    {
        $this->return = $return;
        return $this;
    }
}
