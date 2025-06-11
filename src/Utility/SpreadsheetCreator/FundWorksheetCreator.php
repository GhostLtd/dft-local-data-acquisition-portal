<?php

namespace App\Utility\SpreadsheetCreator;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\ExpenseRow\TotalConfiguration;
use App\Entity\Enum\ExpenseType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

class FundWorksheetCreator
{
    const string NUMERIC_FORMAT_CODE = '#,##0;[Red]-#,##0';

    protected Worksheet $worksheet;
    protected int $originX = 2;
    protected int $originY = 2;

    protected Color $cellShadeEven;
    protected Color $cellShadeOdd;

    protected Color $black;
    protected Color $blue;
    protected Color $darkGray;
    protected Color $lightGray;
    protected Color $white;

    public function __construct(
        protected ExpensesTableHelper $expensesTableHelper,
        protected TranslatorInterface $translator,
    ) {
        $this->blue = new Color('ff2f5597');
        $this->black = new Color(Color::COLOR_BLACK);
        $this->darkGray = new Color('ff888888');
        $this->white = new Color(Color::COLOR_WHITE);
        $this->lightGray = new Color('ffcccccc');

        $this->cellShadeEven = new Color('ffeeeeee');
        $this->cellShadeOdd = new Color('ffdddddd');
    }

    public function setBold(int $x, int $y): void
    {
        $this->worksheet->getStyle([$x, $y])->getFont()->setBold(true);
    }

    public function setItalic(int $x, int $y): void
    {
        $this->worksheet->getStyle([$x, $y])->getFont()->setItalic(true);
    }

    public function addFundWorksheet(Worksheet $worksheet, CrstsFundReturn $fundReturn): void
    {
        $this->expensesTableHelper
            ->setRowGroupConfigurations(CrstsHelper::getFundExpenseRowsConfiguration())
            ->setFund($fundReturn->getFund());

        $this->worksheet = $worksheet;
        $this->worksheet->setTitle('Fund expenses');
        
        $fundStr = $this->translator->trans("enum.fund.{$fundReturn->getFund()->value}");

        $this->worksheet
            ->setCellValue([$this->originX, $this->originY], 'Fund expenditure')
            ->setCellValue([$this->originX, $this->originY + 1], 'Year')
            ->setCellValue([$this->originX, $this->originY + 2], 'Quarter');

        $this->setBold($this->originX, $this->originY + 1);
        $this->setBold($this->originX, $this->originY + 2);

        $firstColumn = Coordinate::stringFromColumnIndex($this->originX);

        $this->worksheet
            ->getColumnDimension($firstColumn)
            ->setWidth(30);

        $this->worksheet
            ->getColumnDimension(Coordinate::stringFromColumnIndex($this->originX + 1))
            ->setWidth(50);

        // Set first column as bold
        $this->worksheet
            ->getStyle("{$firstColumn}:{$firstColumn}")
            ->getFont()
            ->setBold(true);

        $divisionConfigurations = CrstsHelper::getExpenseDivisionConfigurations($fundReturn->getYear(), $fundReturn->getQuarter());

        $columnCount = array_sum(array_map(fn(DivisionConfiguration $d) => count($d->getColumnConfigurations()) + 1, $divisionConfigurations)) + 2;
        $rowCount = $this->expensesTableHelper->getRowCount();

        $lastColumn = Coordinate::stringFromColumnIndex($this->originX + $columnCount - 1);

        $this->writeRowHeaders($fundStr, $columnCount);
        $this->writeColumnHeaders($divisionConfigurations, $rowCount);

        // Fill out the values
        foreach($fundReturn->getExpenses() as $expense) {
            $columnIdx = $this->expensesTableHelper->getAbsoluteColumnIndexFor($divisionConfigurations, $expense->getDivision(), $expense->getColumn(), accountForTotalColumns: true);
            $cell = $this->worksheet->getCell([$this->originX + 2 + $columnIdx, $this->originY + 3 + $this->expensesTableHelper->getAbsoluteRowIndexFor($expense->getType())]);
            $cell->setValueExplicit($expense->getValue(), DataType::TYPE_NUMERIC);
            $cell->getStyle()->getNumberFormat()->setFormatCode(self::NUMERIC_FORMAT_CODE);
        }

        // Total rows
        $this->addRowTotals($divisionConfigurations);
        $this->addColumnTotals($divisionConfigurations);

        // Styles

        // Top bar
        $this->worksheet->mergeCells("{$firstColumn}{$this->originY}:{$lastColumn}{$this->originY}");
        $style = $this->worksheet->getStyle("{$firstColumn}{$this->originY}");
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($this->blue);
        $style->getFont()->setColor($this->white);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Headers
        $style = $this->worksheet->getStyle([$this->originX, $this->originY + 1, $this->originX + $columnCount - 1, $this->originY + 2]);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($this->lightGray);

        $style = $this->worksheet->getStyle([$this->originX, $this->originY + 2, $this->originX + $columnCount - 1, $this->originY + 2]);
        $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK)->setColor($this->black);

        // Footer
        $style = $this->worksheet->getStyle([$this->originX, $this->originY + 2 + $rowCount, $this->originX + $columnCount - 1, $this->originY + 2 + $rowCount]);
        $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);

        // Outer box
        $style = $this->worksheet->getStyle([$this->originX, $this->originY, $this->originX + $columnCount - 1, $this->originY + 2 + $rowCount]);
        $borders = $style->getBorders();
        $borders->getLeft()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
        $borders->getRight()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
    }

    public function writeRowHeaders(string $fundStr, int $columnCount): void
    {
        $currentRow = 0;
        $oddRow = false;

        foreach($this->expensesTableHelper->getRowGroupConfigurations() as $rowGroup) {
            $currentY = $this->originY + 3 + $currentRow;

            $this->worksheet->setCellValue([$this->originX, $currentY], $rowGroup->getLabel(['fund' => $fundStr])->trans($this->translator));
            $this->setBold($this->originX, $currentY);

            if ($rowGroup instanceof CategoryConfiguration) {
                foreach($rowGroup->getRowConfigurations() as $rowIdx => $type) {
                    $label = $type->getLabel(['fund' => $fundStr])->trans($this->translator);
                    $this->worksheet->setCellValue([$this->originX + 1, $currentY + $rowIdx], $label);

                    $isBaseline = $type instanceof ExpenseType && $type->isBaseline();
                    if ($isBaseline) {
                        $this->setItalic($this->originX + 1, $currentY + $rowIdx);
                    } else {
                        $this->setBold($this->originX + 1, $currentY + $rowIdx);
                    }

                    if ($label === '') {
                        $this->worksheet->mergeCells([$this->originX, $currentY, $this->originX + 1, $currentY]);
                    } else {
                        $style = $this->worksheet->getStyle([$this->originX + 1, $currentY + $rowIdx, $this->originX + 1 + $columnCount - 2, $currentY + $rowIdx]);
                        $borders = $style->getBorders();
                        $borders->getBottom()->setBorderStyle(Border::BORDER_THIN)->setColor($this->darkGray);
                        $borders->getLeft()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);

                        $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($oddRow ? $this->cellShadeOdd : $this->cellShadeEven);
                    }

                    $currentRow++;
                    $oddRow = !$oddRow;
                }

                $borders = $this->worksheet->getStyle([$this->originX, $currentY, $this->originX + $columnCount - 1, $currentY + $rowGroup->rowCount() - 1])->getBorders();
                $borders->getTop()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
                $borders->getBottom()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
            } else {
                $this->worksheet->mergeCells([$this->originX, $currentY, $this->originX + 1, $currentY]);

                $style = $this->worksheet->getStyle([$this->originX, $currentY]);
                $borders = $style->getBorders();
                $borders->getTop()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
                $borders->getBottom()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);

                $style = $this->worksheet->getStyle([$this->originX + 2, $currentY, $this->originX + 2 + $columnCount - 3, $currentY]);
                $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($oddRow ? $this->cellShadeOdd : $this->cellShadeEven);

                $this->setBold($this->originX, $currentY);

                $currentRow++;
                $oddRow = !$oddRow;
            }
        }
    }

    public function writeColumnHeaders(array $divisionConfigurations, int $rowCount): void
    {
        $currentX = $this->originX + 2;
        foreach($divisionConfigurations as $divisionConfiguration) {
            $this->expensesTableHelper->setDivisionConfiguration($divisionConfiguration);

            $columnConfigurations = $divisionConfiguration->getColumnConfigurations();
            $columnCount = count($columnConfigurations);

            if ($columnCount > 1) {
                $this->worksheet->mergeCells([$currentX, $this->originY + 1, $currentX + $columnCount - 1, $this->originY + 1]);
            }

            $this->worksheet->setCellValue([$currentX, $this->originY + 1], $divisionConfiguration->getLabel()->trans($this->translator));
            $style = $this->worksheet->getStyle([$currentX, $this->originY + 1]);
            $style->getFont()->setBold(true);
            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $style = $this->worksheet->getStyle([$currentX, $this->originY + 1, $currentX + $columnCount - 1, $this->originY + 1 + 1 + $rowCount]);
            $borders = $style->getBorders();
            $borders->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $borders->getRight()->setBorderStyle(Border::BORDER_THIN);

            foreach($columnConfigurations as $columnConfiguration) {
                $this->worksheet->setCellValue([$currentX, $this->originY + 2], $columnConfiguration->getLabel()->trans($this->translator));
                $this->worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($currentX))->setWidth(15);
                $currentX++;
            }

            $this->worksheet->setCellValue([$currentX, $this->originY + 2], (function () use ($columnCount) {
                $text = new RichText();
                $run = $text->createTextRun($columnCount > 1 ? 'Total' : 'TOTAL');
                $run->getFont()->setBold(true);
                $text->createTextRun("\n(£)");
                return $text;
            })());
            $this->worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($currentX))->setWidth(15);
            $style = $this->worksheet->getStyle([$currentX, $this->originY + 2]);
            $style->getFont()->setBold(true);
            $style->getAlignment()->setWrapText(true);
            $currentX++;
        }
    }

    public function addRowTotals(array $divisionConfigurations): void
    {
        $totalRows = iterator_to_array($this->expensesTableHelper->getRowConfigurationsByType(TotalConfiguration::class));
        foreach($divisionConfigurations as $divisionConfiguration) {
            foreach($divisionConfiguration->getColumnConfigurations() as $columnConfiguration) {
                $columnIdx = $this->expensesTableHelper->getAbsoluteColumnIndexFor($divisionConfigurations, $divisionConfiguration->getKey(), $columnConfiguration->getKey(), accountForTotalColumns: true);
                /** @var TotalConfiguration $totalRow */
                foreach($totalRows as $totalRow) {
                    $sumParts = [];
                    foreach($totalRow->getKeysOfRowsToSum() as $srcKey) {
                        $srcRowIdx = $this->expensesTableHelper->getAbsoluteRowIndexForKey($srcKey->value);
                        $sumParts[] = Coordinate::stringFromColumnIndex($this->originX + 2 + $columnIdx) . ($this->originY + 4 + $srcRowIdx);
                    }

                    $targetRowIdx = $this->expensesTableHelper->getAbsoluteRowIndexForKey($totalRow->getKey());
                    $cell = $this->worksheet->getCell([$this->originX + 2 + $columnIdx, $this->originY + 3 + $targetRowIdx]);
                    $cell->setValue('=' . join('+', $sumParts));
                    $cell->getStyle()->getNumberFormat()->setFormatCode(self::NUMERIC_FORMAT_CODE);
                }
            }
        }
    }

    /**
     * @param array<int, DivisionConfiguration> $divisionConfigurations
     */
    public function addColumnTotals(array $divisionConfigurations): void
    {
        $tintBlue = fn(Color $rgb) => match($rgb) {
            $this->cellShadeOdd => new Color('ffddddff'),
            default => new Color('ffccccee'),
        };

        $columnIdx = $this->originX + 2;
        $rowCount = $this->expensesTableHelper->getRowCount();

        foreach($divisionConfigurations as $divisionConfiguration) {
            $columnCount = $divisionConfiguration->getColumnCount();

            for($i = 0; $i < $rowCount; $i++) {
                $startColumn = Coordinate::stringFromColumnIndex($columnIdx);
                $endColumn = Coordinate::stringFromColumnIndex($columnIdx + $columnCount - 1);
                $row = $this->originY + 3 + $i;

                if ($columnCount > 1) {
                    $cell = $this->worksheet->getCell([$columnIdx + $columnCount, $row]);
                    $cell->setValue("=SUM({$startColumn}{$row}:{$endColumn}{$row})");
                    $cell->getStyle()->getNumberFormat()->setFormatCode(self::NUMERIC_FORMAT_CODE);
                } else {
                    $sumCells = [];
                    $currentX = $this->originX + 2 - 1;
                    foreach($divisionConfigurations as $innerDivisionConfiguration) {
                        $currentX += $innerDivisionConfiguration->getColumnCount() + 1;
                        $sumCells[] = Coordinate::stringFromColumnIndex("{$currentX}").$row;
                    }

                    $cell = $this->worksheet->getCell([$columnIdx + $columnCount, $row]);
                    $cell->setValue("=SUM(".join(',',$sumCells).")");
                    $cell->getStyle()->getNumberFormat()->setFormatCode(self::NUMERIC_FORMAT_CODE);
                }

                $fill = $this->worksheet->getStyle([$columnIdx + $columnCount, $row])->getFill();
                $fill
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor($tintBlue($i % 2 ? $this->cellShadeEven : $this->cellShadeOdd));
            }

            $columnIdx++; // To account for the artificially-added total column

            $columnIdx = $columnIdx + $columnCount;
        }
    }
}
