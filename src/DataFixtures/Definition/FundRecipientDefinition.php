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

        protected string $leadContactName,
        protected string $leadContactPosition,
        protected string $leadContactPhone,
        protected string $leadContactEmail,
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

    public function getLeadContactName(): string
    {
        return $this->leadContactName;
    }

    public function getLeadContactPosition(): string
    {
        return $this->leadContactPosition;
    }

    public function getLeadContactPhone(): string
    {
        return $this->leadContactPhone;
    }

    public function getLeadContactEmail(): string
    {
        return $this->leadContactEmail;
    }
}
