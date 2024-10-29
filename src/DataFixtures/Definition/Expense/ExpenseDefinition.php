<?php

namespace App\DataFixtures\Definition\Expense;

use App\Entity\Enum\ExpenseType;

class ExpenseDefinition
{

    /**
     * @param array<string, string> $entries
     */
    public function __construct(
        protected ExpenseType $type,
        protected array $entries,
    ) {}

    public function getType(): ExpenseType
    {
        return $this->type;
    }

    public function getEntries(): array
    {
        return $this->entries;
    }
}