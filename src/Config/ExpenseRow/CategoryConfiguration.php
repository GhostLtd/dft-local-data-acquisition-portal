<?php

namespace App\Config\ExpenseRow;

use App\Config\LabelProviderInterface;
use App\Entity\Enum\ExpenseCategory;
use App\Entity\Enum\ExpenseType;
use Symfony\Component\Translation\TranslatableMessage;

class CategoryConfiguration extends AbstractRowContainer implements LabelProviderInterface
{
    /**
     * @param array<int, ExpenseType|TotalConfiguration> $rowConfigurations
     */
    public function __construct(
        protected ExpenseCategory $category,
        protected array           $rowConfigurations,
    ) {
        parent::__construct($rowConfigurations);
    }

    public function getCategory(): ExpenseCategory
    {
        return $this->category;
    }

    public function getLabel(array $extraParameters=[]): string|TranslatableMessage
    {
        return new TranslatableMessage("enum.expense_category.{$this->category->value}", $extraParameters);
    }
}
