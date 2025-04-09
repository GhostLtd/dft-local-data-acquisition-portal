<?php

namespace App\Command\Import;

use App\Entity\Enum\ExpenseType;
use App\Entity\ExpenseEntry;
use App\Entity\ExpensesContainerInterface;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Exp;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

class ExpenseEntrySheetImporter extends AbstractSheetImporter
{
    protected const array COLUMNS = [
        'type' => 'type',
        'division' => 'division',
        'column' => 'subDivision',
        'value' => 'value',
        'identifier' => 'name_location',
        'forecast' => 'forecast',
    ];

    protected function processRow(Row $row): void
    {
        $values = $this->getCellValues($row);
        $expenseIdentifier = $this->extractValueFromArray($values, 'identifier');
        $isSchemeExpense = stripos($expenseIdentifier, '_') !== false;
        /** @var ExpensesContainerInterface $parentEntity */
        $parentEntity = $isSchemeExpense
            ? $this->findCrstsSchemeReturnByName(...$this->getSchemeAndAuthorityNames($expenseIdentifier))
            : $this->findCrstsFundReturnByAuthorityName($expenseIdentifier);

        if (!$parentEntity) {
            return;
        }

        $values['division'] = $this->attemptToFormatAsExpenseDivision($values['division']);
        $values['type'] = $this->attemptToFormatAsExpenseType($values['type']);
        $values['value'] = $this->attemptToFormatAsFinancial($values['value']);

        if (!$values['division'] || !$values['type'] || !$values['value'] || !$values['column']) {
            return;
        }

        $values['forecast'] = 'Y' === $values['forecast'];

        // find existing expense entry, or add new one
        $newExpense = new ExpenseEntry();
        $this->setColumnValues($newExpense, $values);

        $existingExpense = $parentEntity?->getExpenses()?->findFirst(fn($k, ExpenseEntry $e) =>
            $e->getType() === $newExpense->getType()
            && $e->getDivision() === $newExpense->getDivision()
            && $e->getColumn() === $newExpense->getColumn()
        );
        if ($existingExpense) {
            $existingExpense->setValue($newExpense->getValue());
            return;
        }

        $parentEntity->addExpense($newExpense);
        $this->persist($newExpense);
    }

    protected function attemptToFormatAsExpenseDivision(?string $value): ?string
    {
        $value = strtolower($value);
        return match($value) {
            'post-26/27' => 'post-2026-27',
            'total' => null,
            default => str_replace(['/'], ['-'], $value),
        };
    }

    protected function attemptToFormatAsExpenseType(?string $value): ?ExpenseType
    {
        if (preg_match('/crsts|total/i', $value)) {
            return null;
        }
        $value = strtoupper($value);
        /** @var ExpenseType $enum */
        $enum = (new \ReflectionEnumBackedCase(ExpenseType::class, $value))->getValue();
        return $enum;
    }
}