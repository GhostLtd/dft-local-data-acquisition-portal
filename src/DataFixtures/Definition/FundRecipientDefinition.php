<?php

namespace App\DataFixtures\Definition;

class FundRecipientDefinition
{
    /**
     * @param array<ProjectDefinition> $projects
     */
    public function __construct(
        protected string $name,
        protected array  $projects,
        protected ContactDefinition $contact,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<ProjectDefinition> */
    public function getProjects(): array
    {
        return $this->projects;
    }

    public function getContact(): ContactDefinition
    {
        return $this->contact;
    }
}
