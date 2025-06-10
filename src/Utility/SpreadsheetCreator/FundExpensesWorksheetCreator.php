<?php

namespace App\Utility\SpreadsheetCreator;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\ExpenseRow\TotalConfiguration;
use App\Config\LabelProviderInterface;
use App\Entity\Enum\ExpenseType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use App\Utility\SpreadsheetCreator\WorksheetHelper\WorksheetHelper;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

class FundExpensesWorksheetCreator extends AbstractWorksheetCreator
{
    protected WorksheetHelper $helper;

    public function __construct(
        protected ExpensesTableHelper $expensesTableHelper,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct();
    }

    public function addWorksheet(Worksheet $worksheet, CrstsFundReturn $fundReturn): void
    {
        $configuration = CrstsHelper::getFundExpensesTable($fundReturn->getYear(), $fundReturn->getQuarter());
        $divisionConfigurations = $configuration->getDivisionConfigurations();

        $this->expensesTableHelper->setConfiguration($configuration);

        $columnCount = array_sum(array_map(fn(DivisionConfiguration $d) => count($d->getColumnConfigurations()) + 1, $divisionConfigurations)) + 2;
        $rowCount = $this->expensesTableHelper->getRowCount();

        $this->worksheet = $worksheet;
        $this->worksheet->setTitle('Fund expenditure');
        
        $fundStr = $this->translator->trans("enum.fund.{$fundReturn->getFund()->value}");

        $this->helper = new WorksheetHelper($this->worksheet, $this->offsetX, $this->offsetY);

        $this->helper->cell(1,1)
            ->setValue('Fund expenditure')
            ->setWidth(30);

        $this->helper->cell(1,2)
            ->setValue('Quarter');

        $this->helper->cell(3, 3)
            ->freezePane();

        $this->helper->column(1)
            ->setBold(true);

        $this->helper->column(2)
            ->setWidth(50);

        // Headers
        $this->helper->range(1, 2, $columnCount, 2)
            ->setFill($this->lightGray);

        $this->writeRowHeaders($fundStr, $columnCount);
        $this->writeColumnHeaders($divisionConfigurations, $rowCount);

        // Fill out the values
        foreach($fundReturn->getExpenses() as $expense) {
            $columnIdx = $this->expensesTableHelper->getAbsoluteColumnIndexFor($divisionConfigurations, $expense->getDivision(), $expense->getColumn(), accountForTotalColumns: true);
            $rowIdx = $this->expensesTableHelper->getAbsoluteRowIndexFor($expense->getType());

            $this->helper->cell(3 + $columnIdx, 3 + $rowIdx)
                ->setValueExplicit($expense->getValue(), DataType::TYPE_NUMERIC)
                ->setNumberFormatCode(self::NUMERIC_FORMAT_CODE);
        }

        // Total rows
        $this->addRowTotals($divisionConfigurations);
        $this->addColumnTotals($divisionConfigurations);

        // Set comments
        $currentX = 3;
        foreach($divisionConfigurations as $divisionConfiguration) {
            $divisionColumnCount = $divisionConfiguration->getColumnCount();
            $hasTotalColumn = $divisionColumnCount > 1;
            $totalColumnCount = $divisionColumnCount + ($hasTotalColumn ? 1 : 0);

            $this->helper->range($currentX, $rowCount + 3, $currentX + $totalColumnCount - 1, $rowCount + 3)
                ->setBottomBorder($this->black)
                ->setRightBorder($this->darkGray, Border::BORDER_THICK)
                ->mergeCells()
                ->setValue($fundReturn->getExpenseDivisionComment($divisionConfiguration->getKey()) ?? '')
                ->setVerticalAlignment(Alignment::VERTICAL_TOP)
                ->setWrapText(true);

            $currentX += $totalColumnCount;
        }

        // Top-left corner bar
        $this->helper->range(1, 1, 2, 1)
            ->mergeCells()
            ->setAllBorders($this->black);
    }

    public function writeRowHeaders(string $fundStr, int $columnCount): void
    {
        $currentRow = 1;
        $oddRow = false;

        foreach($this->expensesTableHelper->getRowGroupConfigurations() as $rowGroup) {
            $currentY = 2 + $currentRow;

            $label = $rowGroup instanceof LabelProviderInterface ?
                $rowGroup->getLabel(['fund' => $fundStr])->trans($this->translator) :
                '';

            $this->helper->cell(1, $currentY)
                ->setValue($label);

            if ($rowGroup instanceof CategoryConfiguration) {
                foreach($rowGroup->getRowConfigurations() as $rowIdx => $type) {
                    $rowColour = $oddRow ? $this->cellShadeOdd : $this->cellShadeEven;

                    $label = $type->getLabel(['fund' => $fundStr])->trans($this->translator);
                    $cell = $this->helper->cell(2, $currentY + $rowIdx)
                        ->setValue($label);

                    $isBaseline = $type instanceof ExpenseType && $type->isBaseline();
                    if ($isBaseline) {
                        $cell->setItalic(true);
                    } else {
                        $cell->setBold(true);
                    }

                    if ($label === '') {
                        $this->helper->range(1, $currentY, 2, $currentY)
                            ->mergeCells();

                        $this->helper->range(3, $currentY + $rowIdx, $columnCount - 1, $currentY + $rowIdx)
                            ->setFill($rowColour);
                    } else {
                        $this->helper->range(2, $currentY + $rowIdx, 1 + $columnCount - 1, $currentY + $rowIdx)
                            ->setBottomBorder($this->darkGray)
                            ->setLeftBorder($this->black)
                            ->setFill($rowColour);
                    }

                    $currentRow++;
                    $oddRow = !$oddRow;
                }

                $this->helper->range(1, $currentY, $columnCount, $currentY + $rowGroup->rowCount() - 1)
                    ->setTopBorder($this->black)
                    ->setBottomBorder($this->black);
            } else {
                $rowColour = $oddRow ? $this->cellShadeOdd : $this->cellShadeEven;

                $this->helper->range(1, $currentY, 2, $currentY)
                    ->mergeCells()
                    ->setTopBorder($this->black)
                    ->setBottomBorder($this->black);

                $this->helper->range(3, $currentY, $columnCount - 1, $currentY)
                    ->setFill($rowColour);

                $currentRow++;
                $oddRow = !$oddRow;
            }
        }

        $this->helper->range(1, 2 + $currentRow, 2, 2 + $currentRow)
            ->mergeCells()
            ->setHeight(80)
            ->setVerticalAlignment(Alignment::VERTICAL_TOP)
            ->setValue('Comments')
            ->setBottomBorder($this->black);
    }

    public function writeColumnHeaders(array $divisionConfigurations, int $rowCount): void
    {
        $currentX = 3;
        foreach($divisionConfigurations as $divisionConfiguration) {
            $columnConfigurations = $divisionConfiguration->getColumnConfigurations();
            $columnCount = count($columnConfigurations);

            if ($columnCount > 1) {
                // +1 to account for a total column
                $this->helper->range($currentX, 1, $currentX + $columnCount - 1 + 1, 1)
                    ->mergeCells();
            }

            $this->helper->cell($currentX, 1)
                ->setValue($divisionConfiguration->getLabel()->trans($this->translator));

            $this->helper->range($currentX, 1, $currentX + $columnCount - 1 + (($columnCount > 1) ? 1 : 0), 2 + $rowCount)
                ->setRightBorder($this->darkGray, Border::BORDER_THICK);

            foreach($columnConfigurations as $columnConfiguration) {
                $label = $columnConfiguration->getLabel()->trans($this->translator);
                $label = str_replace("\n", "", $label);

                $this->helper->cell($currentX, 2)
                    ->setValue($label)
                    ->setWidth(16);

                $currentX++;
            }

            if ($columnCount === 1) {
                $cell = $this->helper->range($currentX, 1, $currentX, 2)
                    ->mergeCells()
                    ->setValue('Grand total (£)')
                    ->setRightBorder($this->darkGray, Border::BORDER_THICK);
            } else {
                $cell = $this->helper->cell($currentX, 2)
                    ->setValue('Total (£)');
            }

            $cell
                ->setFill($this->lightBlue)
                ->setWidth(16)
                ->setBold(true);

            $currentX++;
        }

        $this->helper->range(1, 1, $currentX - 1, 2)
            ->setBold(true);

        $this->helper->range(1, 1, $currentX - 2, 1)
            ->setFill($this->blue)
            ->setColor($this->white);
    }

    public function addRowTotals(array $divisionConfigurations): void
    {
        // e.g. "Local capital contributions -> sub-total" (summing particular cells vertically to make a total)
        $totalRows = iterator_to_array($this->expensesTableHelper->getRowConfigurationsByType(TotalConfiguration::class));
        foreach($divisionConfigurations as $divisionConfiguration) {
            foreach($divisionConfiguration->getColumnConfigurations() as $columnConfiguration) {
                $columnIdx = $this->expensesTableHelper->getAbsoluteColumnIndexFor($divisionConfigurations, $divisionConfiguration->getKey(), $columnConfiguration->getKey(), accountForTotalColumns: true);
                /** @var TotalConfiguration $totalRow */
                foreach($totalRows as $totalRow) {
                    $sumParts = array_map(function(ExpenseType $srcKey) use ($columnIdx) {
                        $srcRowIdx = $this->expensesTableHelper->getAbsoluteRowIndexForKey($srcKey->value);
                        return $this->helper->cell(3 + $columnIdx, 3 + $srcRowIdx)
                            ->getCoordinate();
                    }, $totalRow->getKeysOfRowsToSum());

                    $targetRowIdx = $this->expensesTableHelper->getAbsoluteRowIndexForKey($totalRow->getKey());

                    $this->helper->cell(3 + $columnIdx, 3 + $targetRowIdx)
                        ->setValue('=' . join('+', $sumParts))
                        ->setNumberFormatCode(self::NUMERIC_FORMAT_CODE);
                }
            }
        }
    }

    /**
     * @param array<int, DivisionConfiguration> $divisionConfigurations
     */
    public function addColumnTotals(array $divisionConfigurations): void
    {
        // e.g. Adding four "Total (excluding baselines and over-programming)" cells to make the "Total" of that
        //      (summing four cells horizontally to make a total in the fifth column)
        $columnIdx = 3;
        $rowCount = $this->expensesTableHelper->getRowCount();

        foreach($divisionConfigurations as $divisionConfiguration) {
            $columnCount = $divisionConfiguration->getColumnCount();

            $column = $this->helper->range($columnIdx + $columnCount, 3, $columnIdx + $columnCount, 3 + $rowCount - 1)
                ->setNumberFormatCode(self::NUMERIC_FORMAT_CODE)
                ->setLeftBorder($this->darkGray);

            if ($columnCount === 1) {
                $column->setRightBorder($this->darkGray, Border::BORDER_THICK);
            } else {
                $column->setFill($this->lightBlue);
            }

            for($i = 0; $i <$rowCount; $i++) {
                $row = 3 + $i;

                $cell = $this->helper->cell($columnIdx + $columnCount, $row)
                    ->setFill($this->lightBlue);

                if ($columnCount > 1) {
                    $range = $this->helper->range($columnIdx, $row, $columnIdx + $columnCount - 1, $row);
                    $cell->setValue("=SUM({$range->getCoordinate()})");
                } else {
                    $sumCells = [];
                    $currentX = 2;
                    foreach($divisionConfigurations as $innerDivisionConfiguration) {
                        $innerColumnCount = $innerDivisionConfiguration->getColumnCount();
                        if ($innerColumnCount > 1) {
                            $currentX += $innerColumnCount + 1;
                            $sumCells[] = $this->helper->cell($currentX, $row)->getCoordinate();
                        }
                    }

                    $cell->setValue("=SUM(".join(',',$sumCells).")");
                }
//                $styleHashes[$cell->getStyle()->getHashCode()] ??= $cell->getStyle()->exportArray();
            }

            $columnIdx++; // To account for the artificially-added total column
            $columnIdx = $columnIdx + $columnCount;
        }
    }
}
