<?php

namespace App\Command\Import\ReimportExpenses;

use App\Command\Import\AbstractSheetImporter;
use App\Command\Import\ExpenseEntrySheetImporter as BaseExpenseEntrySheetImporter;
use App\Entity\Enum\ExpenseType;
use App\Entity\ExpenseEntry;
use App\Entity\ExpensesContainerInterface;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\PropertyChangeLog;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Utility\FinancialQuarter;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Exp;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use Throwable;

class ExpenseEntrySheetImporter extends BaseExpenseEntrySheetImporter
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
        $pcRepository = $this->entityManager->getRepository(PropertyChangeLog::class);

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

        $existingExpense = $parentEntity?->getExpenses()?->findFirst(fn($k, ExpenseEntry $e) =>
            $e->getType() === $values['type']
            && $e->getDivision() === $values['division']
            && $e->getColumn() === $values['column']
        );
        if ($existingExpense) {
            if (number_format($values['value'], 2, '.', '') === $existingExpense->getValue()) {
                $this->logger->info("ignoring unchanged expense value", [$existingExpense->getValue(), $values['value'], $expenseIdentifier, $values]);
                return;
            }
            // lookup change log
            $changeLogs = $pcRepository->findBy([
                'entityClass' => get_class($parentEntity),
                'entityId' => $parentEntity->getId(),
                'propertyName' => $existingExpense->getPropertyChangeLogPropertyName(),
            ]);
            if (!empty($changeLogs)) {
                $this->logger->warning("expense already edited", [$expenseIdentifier, $values]);
                return;
            }

            $this->logger->warning("replacing value", [$existingExpense->getValue(), $values['value'], $expenseIdentifier, $values]);
            $existingExpense->setValue($values['value']);
        } else {
            $this->logger->error("missing expense", [$expenseIdentifier, $values]);
        }
    }
}