<?php

namespace App\Entity\Config\ExpenseRow;

use App\Entity\Enum\ExpenseCategory;
use App\Entity\Enum\ExpenseType;

class CategoryConfiguration implements RowGroupInterface
{
    /**
     * @param array<int, ExpenseType|TotalConfiguration> $rowConfigurations
     */
    public function __construct(
        protected ExpenseCategory $category,
        protected array           $rowConfigurations,
    ) {}

    public function getCategory(): ExpenseCategory
    {
        return $this->category;
    }

    /**
     * @return array<int, ExpenseType|TotalConfiguration>
     */
    public function getRowConfigurations(): array
    {
        return $this->rowConfigurations;
    }

    public function rowCount(): int
    {
        return count($this->rowConfigurations);
    }

    /**
     * @return array<int, ExpenseType>
     */
    public function getExpenseTypes(): array
    {
        return array_values(array_filter($this->rowConfigurations, fn(ExpenseType|TotalConfiguration $e) => $e instanceof ExpenseType));
    }
}
