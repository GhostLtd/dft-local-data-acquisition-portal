<?php

namespace App\DataFixtures\Definition;

use App\DataFixtures\Definition\ProjectFund\AbstractProjectFundDefinition;

class ProjectDefinition
{
    /**
     * @param array<AbstractProjectFundDefinition> $projectFunds
     */
    public function __construct(
        protected string $name,
        protected string $description,
        protected array $projectFunds,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return array<AbstractProjectFundDefinition>
     */
    public function getProjectFunds(): array
    {
        return $this->projectFunds;
    }
}
