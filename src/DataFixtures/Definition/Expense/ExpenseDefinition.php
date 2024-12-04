<?php

namespace App\DataFixtures\Definition\Expense;

use App\Entity\Enum\ExpenseType;

class ExpenseDefinition
{
    public function __construct(
        protected ExpenseType $type,
        protected string      $division,
        protected string      $subDivision,
        protected bool        $forecast,
        protected ?string     $value,
    ) {}

    public function getType(): ExpenseType
    {
        return $this->type;
    }

    public function getDivision(): string
    {
        return $this->division;
    }

    public function getSubDivision(): string
    {
        return $this->subDivision;
    }

    public function isForecast(): bool
    {
        return $this->forecast;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }
}
