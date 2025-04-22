<?php

namespace App\Command\Import;

use App\Entity\Enum\ExpenseType;
use App\Entity\ExpenseEntry;
use App\Entity\ExpensesContainerInterface;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Utility\FinancialQuarter;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Exp;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use Throwable;

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

        $values['division'] = $this->attemptToFormatAsExpenseDivision($values['division']);
        $values['type'] = $this->attemptToFormatAsExpenseType($values['type']);
        // we need to sometimes apply 1m multipliers, but only on scheme expenses.
        $values['value'] = $this->attemptToFormatAsFinancial($values['value'], $isSchemeExpense, ['isScheme' => $isSchemeExpense, 'id' => $expenseIdentifier]);

        if(!$values['division'] || !$values['type']) {
            return;
        }

        if ($values['division'] === 'post-2026-27') {
            $values['column'] = 'forecast';
        }

        if (strtolower($values['column']) === 'total') {
            $this->logger->debug("ignoring total column", [$expenseIdentifier, $values]);
            return;
        }

        if ($values['value'] === null || !$values['column']) {
            $this->logger->warning("invalid values for ExpenseEntry", [$expenseIdentifier, $values]);
            return;
        }

        if ($isSchemeExpense && !in_array($values['type'], [ExpenseType::SCHEME_CAPITAL_SPEND_FUND, ExpenseType::SCHEME_CAPITAL_SPEND_ALL_SOURCES])) {
            $this->logger->error("invalid ExpenseEntry type for scheme", [$expenseIdentifier, $values]);
            return;
        }

        if (!$isSchemeExpense && in_array($values['type'], [ExpenseType::SCHEME_CAPITAL_SPEND_FUND, ExpenseType::SCHEME_CAPITAL_SPEND_ALL_SOURCES])) {
            $this->logger->error("invalid ExpenseEntry type for return", [$expenseIdentifier, $values]);
            return;
        }


        /** @var ExpensesContainerInterface $parentEntity */
        $parentEntity = $isSchemeExpense
            ? $this->findCrstsSchemeReturnByName(...$this->getSchemeAndAuthorityNames($expenseIdentifier))
            : $this->findCrstsFundReturnByAuthorityName($expenseIdentifier);

        if (!$parentEntity) {
            $this->logger->warning("unable to find parent for ExpenseEntry: {$expenseIdentifier}", $values);
            return;
        }

        /** @var CrstsFundReturn $fundReturn */
        $fundReturn = $isSchemeExpense ? $parentEntity->getFundReturn() : $parentEntity;
        $returnQuarter = FinancialQuarter::createFromDivisionAndColumn("{$fundReturn->getYear()}-{$fundReturn->getNextYearAsTwoDigits()}", "Q{$fundReturn->getQuarter()}");

        try {
            $expenseQuarter = FinancialQuarter::createFromDivisionAndColumn($values['division'], "{$values['column']}");
            $values['forecast'] = $expenseQuarter->getStartDate() > $returnQuarter->getStartDate();
        } catch (Throwable $e) {
            $values['forecast'] = true;
        }

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
        $ignoredValues = ['total', 'comments', 'current date vs date from previous report'];
        $value = strtolower($value);
        return match(true) {
            $value === 'post-26/27' => 'post-2026-27',
            $value === 'total' => ($this->logger->info("ignored expense division", [$value]) ?? null),
            1 === preg_match('/^\d{4}\/\d{2}$/', $value) => str_replace(['/'], ['-'], $value),
            in_array(strtolower($value), $ignoredValues) => ($this->logger->info("ignored expense division", [$value]) ?? null),
            default => ($this->logger->error("invalid expense division", [$value]) ?? null),
        };
    }

    protected function attemptToFormatAsExpenseType(?string $value): ?ExpenseType
    {
        $subMap = [
            'FUND_RESOURCE_TOTAL' => ExpenseType::FUND_RESOURCE_EXPENDITURE,
            'CRSTS SPEND' => ExpenseType::SCHEME_CAPITAL_SPEND_FUND,
            'TOTAL SPEND' => ExpenseType::SCHEME_CAPITAL_SPEND_ALL_SOURCES,
        ];
        $value = strtoupper($value);
        if (array_key_exists($value, $subMap)) {
            $this->logger->info("replaced ExpenseEntry type", [$value, $subMap[$value]->name]);
            return $subMap[$value];
        }
        if (preg_match('/_total$/i', $value)) {
            $this->logger->info("ignored total ExpenseEntry Type", [$value]);
            return null;
        }
        try {
            /** @var ExpenseType $enum */
            $enum = (new \ReflectionEnumBackedCase(ExpenseType::class, $value))->getValue();
            return $enum;
        } catch (\Throwable $e) {
            $this->logger->error("invalid expense type", [$value, $e->getMessage()]);
            return null;
        }
    }
}