<?php

namespace App\Validator;

use App\Entity\ExpensesContainerInterface;
use Ghost\GovUkCoreBundle\Validator\Constraint\Decimal;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ExpensesValidator
{
    /**
     * This validator essentially helps any errors be correctly matched with their corresponding field.
     *
     * Since this form uses a DataMapper, simply using a Decimal constraint directly on ExpenseEntry->value
     * does not work correctly, as the collection is indexed using numbers, whereas the field names are not.
     *
     * error_mapping is not a viable solution, since it expects a concrete array of mappings, whereas we need
     * to figure the mappings out based on the data and the expenses_table_helper (i.e. it doesn't allow passing
     * a callback)
     */
    public static function validate(mixed $value, ExecutionContext $context, mixed $payload): void
    {
        if (!$value instanceof ExpensesContainerInterface) {
            throw new UnexpectedValueException($value, ExpensesContainerInterface::class);
        }

        foreach($value->getExpenses() as $expense) {
            $division = $expense->getDivision();
            $expenseType = $expense->getType()->value;
            $column = $expense->getColumn();

            $context->getValidator()
                ->inContext($context)
                ->atPath("expense__{$division}__{$expenseType}__{$column}")
                ->validate($expense->getValue(), [new Decimal(precision: 12, scale: 0)], ['Default']);
        }
    }
}
