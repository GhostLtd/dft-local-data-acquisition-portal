<?php

namespace App\DataFixtures\Definition;

class AuthorityDefinition
{
    /**
     * @param array<ProjectDefinition> $projects
     * @param array<FundAwardDefinition> $fundAwards
     */
    public function __construct(
        protected string         $name,
        protected UserDefinition $admin,
        protected array          $projects,
        protected array          $fundAwards,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getAdmin(): UserDefinition
    {
        return $this->admin;
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
