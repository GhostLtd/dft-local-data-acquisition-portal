<?php

namespace App\DataFixtures\Definition\Expense;

use App\Entity\Enum\ExpenseType;

class ExpenseDefinition
{
    public function __construct(
        protected ExpenseType $type,
        protected string      $division,
        protected string      $column,
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

    public function getColumn(): string
    {
        return $this->column;
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
