<?php

namespace App\DataFixtures\Definition;

class RecipientDefinition
{
    /**
     * @param array<ProjectDefinition> $projects
     * @param array<FundAwardDefinition> $fundAwards
     */
    public function __construct(
        protected string         $name,
        protected UserDefinition $owner,
        protected array          $projects,
        protected array          $fundAwards,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getAdmin(): UserDefinition
    {
        return $this->owner;
    }

    /** @return array<ProjectDefinition> */
    public function getProjects(): array
    {
        return $this->projects;
    }

    public function getFundAwards(): array
    {
        return $this->fundAwards;
    }
}
