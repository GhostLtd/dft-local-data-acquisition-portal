<?php

namespace App\DataFixtures\Definition;

use App\Entity\Enum\MilestoneType;

class MilestoneDefinition
{
    public function __construct(
        protected ?MilestoneType $type = null,
        protected ?\DateTimeInterface $date = null,
    ) {}

    public function getType(): ?MilestoneType
    {
        return $this->type;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }
}
