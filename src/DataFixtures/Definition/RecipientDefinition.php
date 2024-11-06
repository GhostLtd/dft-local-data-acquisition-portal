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
        protected UserDefinition $leadContact,
        protected array          $projects,
        protected array          $fundAwards,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getLeadContact(): UserDefinition
    {
        return $this->leadContact;
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
