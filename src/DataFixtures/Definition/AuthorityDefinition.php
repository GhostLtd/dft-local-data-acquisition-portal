<?php

namespace App\DataFixtures\Definition;

class AuthorityDefinition
{
    /**
     * @param array<SchemeDefinition> $schemes
     * @param array<FundAwardDefinition> $fundAwards
     */
    public function __construct(
        protected string         $name,
        protected UserDefinition $admin,
        protected array          $schemes,
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

    /** @return array<SchemeDefinition> */
    public function getSchemes(): array
    {
        return $this->schemes;
    }

    public function getFundAwards(): array
    {
        return $this->fundAwards;
    }

    public function setSchemes(array $schemes): static
    {
        $this->schemes = $schemes;
        return $this;
    }

    public function setFundAwards(array $fundAwards): static
    {
        $this->fundAwards = $fundAwards;
        return $this;
    }
}
