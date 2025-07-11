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

class FundWorksheetCreator extends AbstractWorksheetCreator
{
    public function __construct(
        protected ExpensesTableHelper $expensesTableHelper,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct();

        $this->originX = 2;
        $this->originY = 2;
    }

    public function addWorksheet(Worksheet $worksheet, CrstsFundReturn $fundReturn): void
    {
        $configuration = CrstsHelper::getFundExpensesTable($fundReturn->getYear(), $fundReturn->getQuarter());
        $divisionConfigurations = $configuration->getDivisionConfigurations();

        $this->expensesTableHelper->setConfiguration($configuration);

        $columnCount = array_sum(array_map(fn(DivisionConfiguration $d) => count($d->getColumnConfigurations()) + 1, $divisionConfigurations)) + 2;
        $rowCount = $this->expensesTableHelper->getRowCount();

        $lastColumn = Coordinate::stringFromColumnIndex($this->relX($columnCount - 1));

        $this->worksheet = $worksheet;
        $this->worksheet->setTitle('Fund expenditure');
        
        $fundStr = $this->translator->trans("enum.fund.{$fundReturn->getFund()->value}");

        $this->worksheet
            ->setCellValue($this->relXY(0, 0), 'Fund expenditure')
            ->setCellValue($this->relXY(0, 1), 'Year')
            ->setCellValue($this->relXY(0, 2), 'Quarter');

        $this->setBold($this->relX(0), $this->relY(1));
        $this->setBold($this->relX(0), $this->relY(2));

        $firstColumn = Coordinate::stringFromColumnIndex($this->relX(0));

        $this->worksheet
            ->getColumnDimension($firstColumn)
            ->setWidth(30);

        $this->worksheet
            ->getColumnDimension(Coordinate::stringFromColumnIndex($this->relX(1)))
            ->setWidth(50);

        // Make the first column bold
        $this->worksheet
            ->getStyle("{$firstColumn}:{$firstColumn}")
            ->getFont()
            ->setBold(true);

        $this->writeRowHeaders($fundStr, $columnCount);
        $this->writeColumnHeaders($divisionConfigurations, $rowCount);

        // Fill out the values
        foreach($fundReturn->getExpenses() as $expense) {
            $columnIdx = $this->expensesTableHelper->getAbsoluteColumnIndexFor($divisionConfigurations, $expense->getDivision(), $expense->getColumn(), accountForTotalColumns: true);
            $rowIdx = $this->expensesTableHelper->getAbsoluteRowIndexFor($expense->getType());
            $cell = $this->worksheet->getCell($this->relXY(2 + $columnIdx, 3 + $rowIdx));
            $cell->setValueExplicit($expense->getValue(), DataType::TYPE_NUMERIC);
            $cell->getStyle()->getNumberFormat()->setFormatCode(self::NUMERIC_FORMAT_CODE);
        }

        // Total rows
        $this->addRowTotals($divisionConfigurations);
        $this->addColumnTotals($divisionConfigurations);

        // Styles

        // Top bar
        $this->worksheet->mergeCells("{$firstColumn}{$this->relY(0)}:{$lastColumn}{$this->relY(0)}");
        $style = $this->worksheet->getStyle("{$firstColumn}{$this->relY(0)}");
        $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($this->blue);
        $style->getFont()->setColor($this->white);
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Headers
        $style = $this->worksheet->getStyle($this->relXYXY(0, 1, $columnCount - 1, 2));
        $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($this->lightGray);

        $style = $this->worksheet->getStyle($this->relXYXY(0, 2, $columnCount - 1, 2));
        $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK)->setColor($this->black);

        // Footer
        $style = $this->worksheet->getStyle($this->relXYXY(0, 2 + $rowCount, $columnCount - 1, 2 + $rowCount));
        $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);

        // Outer box
        $style = $this->worksheet->getStyle($this->relXYXY(0, 0, $columnCount - 1, 2 + $rowCount));
        $borders = $style->getBorders();
        $borders->getLeft()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
        $borders->getRight()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
    }

    public function writeRowHeaders(string $fundStr, int $columnCount): void
    {
        $currentRow = 0;
        $oddRow = false;

        foreach($this->expensesTableHelper->getRowGroupConfigurations() as $rowGroup) {
            $currentY = 3 + $currentRow;

            $this->worksheet->setCellValue($this->relXY(0, $currentY), $rowGroup->getLabel(['fund' => $fundStr])->trans($this->translator));
            $this->setBold(...$this->relXY(0, $currentY));

            if ($rowGroup instanceof CategoryConfiguration) {
                foreach($rowGroup->getRowConfigurations() as $rowIdx => $type) {
                    $label = $type->getLabel(['fund' => $fundStr])->trans($this->translator);

                    $cellCoords = $this->relXY(1, $currentY + $rowIdx);
                    $this->worksheet->setCellValue($cellCoords, $label);

                    $isBaseline = $type instanceof ExpenseType && $type->isBaseline();
                    if ($isBaseline) {
                        $this->setItalic(...$cellCoords);
                    } else {
                        $this->setBold(...$cellCoords);
                    }

                    if ($label === '') {
                        $this->worksheet->mergeCells($this->relXYXY(0, $currentY, 1, $currentY));

                        $style = $this->worksheet->getStyle($this->relXYXY(2, $currentY + $rowIdx, $columnCount - 2, $currentY + $rowIdx));
                        $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($oddRow ? $this->cellShadeOdd : $this->cellShadeEven);
                    } else {
                        $style = $this->worksheet->getStyle($this->relXYXY(1, $currentY + $rowIdx, 1 + $columnCount - 2, $currentY + $rowIdx));
                        $borders = $style->getBorders();
                        $borders->getBottom()->setBorderStyle(Border::BORDER_THIN)->setColor($this->darkGray);
                        $borders->getLeft()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);

                        $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($oddRow ? $this->cellShadeOdd : $this->cellShadeEven);
                    }

                    $currentRow++;
                    $oddRow = !$oddRow;
                }

                $borders = $this->worksheet->getStyle($this->relXYXY(0, $currentY, $columnCount - 1, $currentY + $rowGroup->rowCount() - 1))->getBorders();
                $borders->getTop()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
                $borders->getBottom()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
            } else {
                $this->worksheet->mergeCells($this->relXYXY(0, $currentY, 1, $currentY));
                $style = $this->worksheet->getStyle($this->relXY(0, $currentY));
                $borders = $style->getBorders();
                $borders->getTop()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);
                $borders->getBottom()->setBorderStyle(Border::BORDER_THIN)->setColor($this->black);

                $style = $this->worksheet->getStyle($this->relXYXY(2, $currentY, 2 + $columnCount - 3, $currentY));
                $style->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($oddRow ? $this->cellShadeOdd : $this->cellShadeEven);

                $this->setBold(...$this->relXY(0, $currentY));

                $currentRow++;
                $oddRow = !$oddRow;
            }
        }
    }

    public function writeColumnHeaders(array $divisionConfigurations, int $rowCount): void
    {
        $currentX = 2;
        foreach($divisionConfigurations as $divisionConfiguration) {
            $columnConfigurations = $divisionConfiguration->getColumnConfigurations();
            $columnCount = count($columnConfigurations);

            if ($columnCount > 1) {
                $this->worksheet->mergeCells($this->relXYXY($currentX, 1, $currentX + $columnCount - 1, 1));
            }

            $this->worksheet->setCellValue($this->relXY($currentX, 1), $divisionConfiguration->getLabel()->trans($this->translator));
            $style = $this->worksheet->getStyle($this->relXY($currentX, 1));
            $style->getFont()->setBold(true);
            $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $style = $this->worksheet->getStyle($this->relXYXY($currentX, 1, $currentX + $columnCount - 1, 1 + 1 + $rowCount));
            $borders = $style->getBorders();
            $borders->getLeft()->setBorderStyle(Border::BORDER_THIN);
            $borders->getRight()->setBorderStyle(Border::BORDER_THIN);

            foreach($columnConfigurations as $columnConfiguration) {
                $this->worksheet->setCellValue($this->relXY($currentX, 2), $columnConfiguration->getLabel()->trans($this->translator));
                $this->worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($this->relX($currentX)))->setWidth(16);
                $currentX++;
            }

            $this->worksheet->setCellValue($this->relXY($currentX, 2), (function () use ($columnCount) {
                $text = new RichText();
                $run = $text->createTextRun($columnCount > 1 ? 'Total' : 'TOTAL');
                $run->getFont()->setBold(true);
                $text->createTextRun("\n(£)");
                return $text;
            })());

            $this->worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($this->relX($currentX)))->setWidth(18);
            $style = $this->worksheet->getStyle($this->relXY($currentX, 2));
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
                        $sumParts[] = Coordinate::stringFromColumnIndex($this->relX(2 + $columnIdx)).($this->relY(3 + $srcRowIdx));
                    }

                    $targetRowIdx = $this->expensesTableHelper->getAbsoluteRowIndexForKey($totalRow->getKey());
                    $cell = $this->worksheet->getCell($this->relXY(2 + $columnIdx, 3 + $targetRowIdx));
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

        $columnIdx = $this->relX(2);
        $rowCount = $this->expensesTableHelper->getRowCount();

        foreach($divisionConfigurations as $divisionConfiguration) {
            $columnCount = $divisionConfiguration->getColumnCount();

            for($i = 0; $i < $rowCount; $i++) {
                $startColumn = Coordinate::stringFromColumnIndex($columnIdx);
                $endColumn = Coordinate::stringFromColumnIndex($columnIdx + $columnCount - 1);
                $row = $this->relY(3 + $i);

                if ($columnCount > 1) {
                    $cell = $this->worksheet->getCell([$columnIdx + $columnCount, $row]);
                    $cell->setValue("=SUM({$startColumn}{$row}:{$endColumn}{$row})");
                    $cell->getStyle()->getNumberFormat()->setFormatCode(self::NUMERIC_FORMAT_CODE);
                } else {
                    $sumCells = [];
                    $currentX = $this->relX(1);
                    foreach($divisionConfigurations as $innerDivisionConfiguration) {
                        $innerColumnCount = $innerDivisionConfiguration->getColumnCount();
                        if ($innerColumnCount > 1) {
                            $currentX += $innerColumnCount + 1;
                            $sumCells[] = Coordinate::stringFromColumnIndex("{$currentX}") . $row;
                        }
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
