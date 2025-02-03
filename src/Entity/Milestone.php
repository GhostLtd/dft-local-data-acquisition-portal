<?php

namespace App\Entity;

use App\Entity\Enum\MilestoneType;
use App\Entity\Traits\IdTrait;
use App\Repository\MilestoneRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\NotNull;

#[ORM\Entity(repositoryClass: MilestoneRepository::class)]
class Milestone
{
    use IdTrait;

    #[ORM\Column(enumType: MilestoneType::class)]
    private ?MilestoneType $type = null; // 4proj_milestones (one of "Start development", "End development" etc - see enum)

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[NotNull(message: 'common.date.not_null', groups: ["milestone_dates"])]
    private ?\DateTimeInterface $date = null; // 4proj_milestone (the value for the chosen field)

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
}
