<?php

namespace App\Form\Type\FundReturn\Crsts;

use App\Config\Table\Cell;
use App\Config\Table\Table;
use App\Config\Table\TableBody;
use App\Config\Table\TableHead;
use App\Entity\ExpensesContainerInterface;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class ExpensesTableCalculator
{
    public function calculate(Table $table, ExpensesContainerInterface $expensesContainer): Table
    {
        $cellValues = [];

        foreach($expensesContainer->getExpenses() as $expense) {
            $divKey = $expense->getDivision();
            $expenseType = $expense->getType()->value;

            $key = "expense__{$divKey}__{$expenseType}__{$expense->getColumn()}";
            $cellValues[$key] = $expense->getValue();
        }

        $cellMap = [];

        foreach($table->getRows() as $row) {
            foreach($row->getCells() as $cell) {
                if ($cell instanceof Cell) {
                    $key = $cell->getOption('key');

                    if ($cell->getAttribute('is_data_cell')) {
                        $value = $cellValues[$key] ?? null;

                        if ($value !== null) {
                            $value = BigDecimal::of($value);
                        }

                        $cell = $this->getCalculatedCell($cell, $value);
                    }

                    $row = $cell->getAttribute('row_key');
                    $col = $cell->getAttribute('col_key');

                    $cellMap[$row] ??= [];
                    $cellMap[$row][$col] = $cell;
                }
            }
        }

        $completed = true;

        do {
            foreach($cellMap as $rowKey => $row) {
                foreach($row as $colKey => $col) {
                    if (!$this->calculateCell($rowKey, $colKey, $cellMap)) {
                        $completed = false;
                    }
                }
            }
        } while(!$completed);

        foreach($cellMap as $rowKey => $row) {
            foreach($row as $colKey => $col) {
                if ($col instanceof Cell) {
                    $value = $col->getOption('text');

                    if ($value !== '') {
                        $roundedValue = $this->parseDecimalValue($value);

                        $cellMap[$rowKey][$colKey] = new Cell(
                            array_merge($col->getOptions(), ['text' => $this->formatValue($roundedValue, 0)]),
                            $col->getOptions()
                        );
                    }
                }
            }
        }

        // Now construct the new table...
        $tableHeadAndBodies = [];

        foreach($table->getHeadAndBodies() as $headOrBody) {
            if ($headOrBody instanceof TableHead) {
                $tableHeadAndBodies[] = $headOrBody;
            } else {
                $tableRows = [];

                foreach($headOrBody->getRows() as $row) {
                    $tableRow = [];

                    foreach($row->getCells() as $cell) {
                        if ($cell instanceof Cell) {
                            $rowKey = $cell->getAttribute('row_key');
                            $colKey = $cell->getAttribute('col_key');
                            $tableRow[] = $cellMap[$rowKey][$colKey];
                        } else {
                            $tableRow[] = $cell;
                        }
                    }

                    $tableRows[] = new ($row::class)($tableRow, $row->getOptions(), $row->getAttributes());
                }

                $tableHeadAndBodies[] = new TableBody($tableRows);
            }
        }

        $mergedClasses = trim($table->getOption('classes', '').' calculated-expenses');
        return new Table($tableHeadAndBodies, array_merge($table->getOptions(), ['classes' => $mergedClasses]));
    }

    protected function calculateCell(string $row, string $col, array &$cellMap): bool
    {
        $cell = $cellMap[$row][$col];

        if ($cell->getAttribute('calculated')) {
            return true;
        }

        $rowsToSum = $cell->getAttribute('total_rows_to_sum');
        $totalRow = $cell->getAttribute('is_row_total');

        if ($totalRow) {
            $total = BigDecimal::zero();
            foreach($cellMap[$row] as $rowCell) {
                if ($rowCell === $cell) {
                    continue;
                }

                if (!$rowCell->getAttribute('calculated')) {
                    return false;
                }

                $value = $rowCell->getOption('text');
                if ($value !== '') {
                    $decimalValue = $this->parseDecimalValue($value);
                    $total = $total->plus($decimalValue);
                }
            }

            $cellMap[$row][$col] = $this->getCalculatedCell($cell, $total);
        }

        if ($rowsToSum) {
            $total = BigDecimal::zero();
            foreach($rowsToSum as $expenseType) {
                $rowToSum = $cellMap[$expenseType->value];

                $rowCell = $rowToSum[$col];

                if (!$rowCell->getAttribute('calculated')) {
                    return false;
                }

                $value = $rowCell->getOption('text');
                if ($value !== '') {
                    $decimalValue = $this->parseDecimalValue($value);
                    $total = $total->plus($decimalValue);
                }
            }

            $cellMap[$row][$col] = $this->getCalculatedCell($cell, $total);
        }

        return true;
    }

    protected function getCalculatedCell(mixed $cell, ?BigDecimal $value): Cell
    {
        return new Cell(
            array_merge($cell->getOptions(), ['text' => $this->formatValue($value)]),
            array_merge($cell->getAttributes(), ['calculated' => true])
        );
    }

    protected function parseDecimalValue($value): BigDecimal
    {
        return BigDecimal::of(str_replace(['£', ','], ['', ''], $value));
    }

    public function formatValue(?BigDecimal $value, int $scale = 2): string
    {
        if ($value) {
            $value = $value->toScale($scale, RoundingMode::HALF_UP);

            $integralPart = number_format($value->getIntegralPart());
            return $scale === 0 ?
                $integralPart :
                "{$integralPart}.{$value->getFractionalPart()}";
        } else {
            return '';
        }
    }
}
