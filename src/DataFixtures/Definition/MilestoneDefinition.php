<?php

namespace App\DataFixtures\Definition;

use App\Entity\Enum\MilestoneType;

class MilestoneDefinition
{
    public function __construct(
        protected MilestoneType $type,
        protected \DateTimeInterface $date,
    ) {}

    public function getType(): MilestoneType
    {
        return $this->type;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }
}
