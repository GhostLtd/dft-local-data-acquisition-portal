<?php

namespace App\Utility\SpreadsheetCreator;

use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\FundedMostlyAs;
use App\Entity\Enum\MilestoneType;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Repository\SchemeRepository;
use App\Repository\SchemeReturn\SchemeReturnRepository;
use App\Utility\CrstsHelper;
use App\Utility\ExpensesTableHelper;
use App\Utility\SpreadsheetCreator\WorksheetHelper\WorksheetHelper;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Contracts\Translation\TranslatorInterface;

class SchemeWorksheetCreator extends AbstractWorksheetCreator
{
    const string CURRENCY_FORMAT = '#,##0.00_-';

    protected bool $moveExpensesHeaderToColumnC;
    protected WorksheetHelper $helper;

    public function __construct(
        protected ExpensesTableHelper $expensesTableHelper,
        protected SchemeRepository    $schemeRepository,
        protected TranslatorInterface $translator, private readonly SchemeReturnRepository $schemeReturnRepository,
    ) {
        parent::__construct();
    }

    public function addWorksheet(Worksheet $worksheet, CrstsFundReturn $fundReturn, bool $moveExpensesHeaderToColumnC=false): void
    {
        $this->worksheet = $worksheet->setTitle('Schemes');
        $this->helper = new WorksheetHelper($this->worksheet);
        $this->moveExpensesHeaderToColumnC = $moveExpensesHeaderToColumnC;

        $this->helper->cell($moveExpensesHeaderToColumnC ? 5 : 4, 3)
            ->freezePane();

        $this->expensesTableHelper->setConfiguration(CrstsHelper::getSchemeExpensesTable($fundReturn->getYear(), $fundReturn->getQuarter()));

        $totalColumns = $this->getTotalColumnCount();
        $this->writeRowHeaders($totalColumns);

        $schemeReturns = $this->schemeReturnRepository->findForSpreadsheetExport($fundReturn);
        $expenseHeaderX = $this->moveExpensesHeaderToColumnC ? 4 : 19;
        $offsetX = $this->moveExpensesHeaderToColumnC ? 1 : 0;

        $currentY = 3;
        $textColumns = [];
        foreach($schemeReturns as $schemeReturn) {
            $scheme = $schemeReturn->getScheme();

            $isCDEL = $scheme->getCrstsData()->getFundedMostlyAs() === FundedMostlyAs::CDEL;
            $milestoneTypes = MilestoneType::getNonBaselineCases($isCDEL);

            if ($schemeReturn->getDevelopmentOnly()) {
                $milestoneTypes = array_filter($milestoneTypes, fn(MilestoneType $t) => $t->isDevelopmentMilestone());
            }

            $milestonesCount = count($milestoneTypes);

            // General text column options
            $transportMode = $scheme->getTransportMode()?->getForDisplay();
            $activeTravelElement = $scheme->getActiveTravelElement()?->getForDisplay();
            $businessCase = $schemeReturn->getBusinessCase()?->value;
            $expectedBusinessCaseApproval = $schemeReturn->getExpectedBusinessCaseApproval();
            $onTrackRatingValue = $schemeReturn->getOnTrackRating()?->value;

            $textColumns = [
                1 => $scheme->getSchemeIdentifier(),
                2 => $scheme->getName(),
                3 => match($scheme->getCrstsData()->isRetained()) {
                    true => 'Y',
                    false => 'N',
                    null => '',
                },
                $offsetX + 4 => $scheme->getDescription(),
                $offsetX + 5 => $onTrackRatingValue ? $this->translator->trans("enum.on_track_rating.{$onTrackRatingValue}") : '',
                $offsetX + 6 => match ($schemeReturn->getDevelopmentOnly()) {
                    true => 'Y',
                    false => 'N',
                    null => '',
                },
                $offsetX + 9 => $schemeReturn->getProgressUpdate(),
                $offsetX + 10 => match ($scheme->getCrstsData()->isPreviouslyTcf()) {
                    true => 'Y',
                    false => 'N',
                    null => '',
                },
                $offsetX + 11 => $schemeReturn->getRisks(),
                $offsetX + 12 => $transportMode ? $transportMode->trans($this->translator) : '',
                $offsetX + 13 => $activeTravelElement ? $activeTravelElement->trans($this->translator) : '',
                $offsetX + 14 => $businessCase ? $this->translator->trans("enum.business_case.{$businessCase}") : '',
                $offsetX + 15 => $expectedBusinessCaseApproval ? Date::PHPToExcel($expectedBusinessCaseApproval) : '',
                $offsetX + 16 => match ($schemeReturn->getBenefitCostRatio()?->getType()) {
                    BenefitCostRatioType::NA => 'N/A',
                    BenefitCostRatioType::TBC => 'TBC',
                    BenefitCostRatioType::VALUE => $schemeReturn->getBenefitCostRatio()->getValue(),
                    null => '',
                },
                $offsetX + 17 => $schemeReturn->getTotalCost() ?? '',
                $offsetX + 18 => $schemeReturn->getAgreedFunding() ?? '',
            ];

            foreach($textColumns as $x => $text) {
                $this->helper->cell($x, $currentY)
                    ->setValue($text);

                $this->helper->range($x, $currentY, $x, $currentY + $milestonesCount - 1)
                    ->mergeCells();
            }

            // On-track rating
            $tagColour = $schemeReturn->getOnTrackRating()?->getTagColour();

            if ($tagColour) {
                $onTrackColours = $this->getTagColours($tagColour);
                $this->helper->cell($offsetX + 5, $currentY)
                    ->setColor($onTrackColours[0])
                    ->setFill($onTrackColours[1]);
            }

            // Milestone types
            foreach($milestoneTypes as $idx => $milestoneType) {
                $milestone = $schemeReturn->getMilestoneByType($milestoneType);
                $milestoneDate = $milestone?->getDate();

                $this->helper->cell($offsetX + 7, $currentY + $idx)
                    ->setValue($this->translator->trans("enum.milestone_type.{$milestoneType->value}"));

                $this->helper->cell($offsetX + 8, $currentY + $idx)
                    ->setValue($milestoneDate ? Date::PHPToExcel($milestoneDate) : '');
            }

            // Scheme's expense row headers...
            $totalY = $currentY + intval(floor($milestonesCount / 2));
            $this->helper->cell($expenseHeaderX, $currentY)
                ->setValue('CRSTS spend');

            $this->helper->cell($expenseHeaderX, $totalY)
                ->setValue('Total spend');

            $this->mergeExpenseCellsAndSetTopAndBottomBorders($this->moveExpensesHeaderToColumnC ? 4 : 19, $currentY, $totalY, $milestonesCount);

            // Expense data for this scheme...
            $currentX = 20;
            foreach($this->expensesTableHelper->getDivisionConfigurations() as $divisionConfiguration) {
                $columns = $divisionConfiguration->getColumnConfigurations();

                $startX = $currentX;
                foreach($columns as $column) {
                    $crstsExpense = $schemeReturn->getExpenseByDivisionColumnAndType($divisionConfiguration->getKey(), $column->getKey(), ExpenseType::SCHEME_CAPITAL_SPEND_FUND);
                    $totalExpense = $schemeReturn->getExpenseByDivisionColumnAndType($divisionConfiguration->getKey(), $column->getKey(), ExpenseType::SCHEME_CAPITAL_SPEND_ALL_SOURCES);

                    $this->helper->cell($currentX, $currentY)
                        ->setValue($crstsExpense?->getValue() ?? '');

                    $this->helper->cell($currentX, $totalY)
                        ->setValue($totalExpense?->getValue() ?? '');

                    $this->mergeExpenseCellsAndSetTopAndBottomBorders($currentX, $currentY, $totalY, $milestonesCount);
                    $currentX++;
                }

                if (count($columns) > 1) {
                    $startXletter = Coordinate::stringFromColumnIndex($startX);
                    $endXletter = Coordinate::stringFromColumnIndex($currentX - 1);

                    $this->helper->cell($currentX, $currentY)
                        ->setValue("=SUM({$startXletter}{$currentY}:{$endXletter}{$currentY})");

                    $this->helper->cell($currentX, $totalY)
                        ->setValue("=SUM({$startXletter}{$totalY}:{$endXletter}{$totalY})");

                    $this->mergeExpenseCellsAndSetTopAndBottomBorders($currentX, $currentY, $totalY, $milestonesCount);
                    $currentX++;
                }

                $this->helper->range($currentX, $currentY, $currentX, $currentY + $milestonesCount - 1)
                    ->mergeCells()
                    ->setValue($schemeReturn->getExpenseDivisionComment($divisionConfiguration->getKey()));

                $currentX++;
            }

            $this->helper->range(20, $currentY, $totalColumns - 2, $currentY)
                ->setNumberFormatCode(self::CURRENCY_FORMAT)
                ->setFill($this->cellShadeOdd); // odd

            $this->helper->range(20, $totalY, $totalColumns - 2, $totalY)
                ->setNumberFormatCode(self::CURRENCY_FORMAT)
                ->setFill($this->cellShadeEven); // even

            $this->sumExpensesRow($currentY, $totalY - 1, $this->expensesTableHelper);
            $this->sumExpensesRow($totalY, $currentY + $milestonesCount - 1, $this->expensesTableHelper/* , $this->black */);

            $this->helper->range(1, $currentY + $milestonesCount - 1, $totalColumns - 1, $currentY + $milestonesCount - 1)
                ->setBottomBorder($this->black);

            $currentY += $milestonesCount;
        }

        // On-track ratings
        $this->helper->range($offsetX + 5, 3, $offsetX + 5, $currentY - 1)
            ->setVerticalAlignment(Alignment::VERTICAL_CENTER)
            ->setHorizontalAlignment(Alignment::HORIZONTAL_CENTER);

        // Milestone dates
        $this->helper->range($offsetX + 8, 3, $offsetX + 8, $currentY - 1)
            ->setNumberFormatCode('mmm yyyy');

        // Expected business case approval
        $this->helper->range($offsetX + 15, 3, $offsetX + 15, $currentY - 1)
            ->setNumberFormatCode('mmm yyyy');

        // BCR
        $this->helper->range($offsetX + 16, 3, $offsetX + 16, $currentY - 1)
            ->setHorizontalAlignment(Alignment::HORIZONTAL_LEFT);

        // Total cost, agreed funding
        $this->helper->range($offsetX + 17, 3, $offsetX + 18, $currentY - 1)
            ->setNumberFormatCode(self::CURRENCY_FORMAT);

        foreach(array_keys($textColumns) as $x) {
            $this->helper->range($x, 3, $x, $currentY - 1)
                ->setWrapText(true)
                ->setVerticalAlignment(Alignment::VERTICAL_TOP);
        }

        $currentX = 20;
        foreach($this->expensesTableHelper->getDivisionConfigurations() as $divisionConfiguration) {
            $columnCount = $divisionConfiguration->getColumnCount();
            $currentX += $columnCount;

            if ($columnCount > 1) {
                // Set styles on total columns
                $this->helper->range($currentX, 2, $currentX, $currentY - 1)
                    ->setNumberFormatCode(self::NUMERIC_FORMAT_CODE)
                    ->setFill($this->lightBlue)
                    ->setLeftBorder($this->darkGray);

                $currentX++; // Total column
            }

            // Set styles on comments column
            $this->helper->range($currentX, 3, $currentX, $currentY - 1)
                ->setWrapText(true)
                ->setFill($this->white)
                ->setVerticalAlignment(Alignment::VERTICAL_TOP)
                ->setLeftBorder($this->darkGray)
                ->setRightBorder($this->darkGray, Border::BORDER_THICK);

            $currentX++; // Comments column
        }

        // Grand total
        $this->helper->range($currentX, 2, $currentX, $currentY - 1)
            ->setNumberFormatCode(self::NUMERIC_FORMAT_CODE)
            ->setFill($this->lightBlue)
            ->setRightBorder($this->darkGray, Border::BORDER_THICK);

        // Set expense row header column style
        $this->helper->range($expenseHeaderX, 1, $expenseHeaderX, $currentY - 1)
            ->setLeftBorder($this->darkGray, Border::BORDER_THICK)
            ->setRightBorder($this->darkGray, Border::BORDER_THICK)
            ->setFill($this->yellow);

        // Expense headers...
        $currentX = 20;
        foreach($this->expensesTableHelper->getDivisionConfigurations() as $divisionConfiguration) {
            $label = $divisionConfiguration->getLabel()->trans($this->translator);
            $columns = $divisionConfiguration->getColumnConfigurations();

            $this->helper->cell($currentX, 1)
                ->setValue($label)
                ->setLeftBorder($this->darkGray, Border::BORDER_THICK)
                ->setBottomBorder($this->black)
                ->setFill($this->blue)
                ->setColor($this->white)
                ->setBold(true);

            $hasTotalColumn = count($columns) > 1;

            // +1 for total column (if applicable), +1 for comments column
            $this->helper->range($currentX, 1, $currentX + count($columns) - 1 + ($hasTotalColumn ? 1 : 0) + 1, 1)
                ->mergeCells();

            $lastIdx = array_key_last($columns);
            foreach($columns as $idx => $column) {
                $columnLabel = str_replace("\n", " ", $column->getLabel()->trans($this->translator));

                $header = $this->helper->cell($currentX, 2)
                    ->setValue($columnLabel)
                    ->setWrapText(false)
                    ->setWidth(16)
                    ->setBold(true)
                    ->setFill($this->lightGray);

                if ($idx === 0) {
                    $header->setLeftBorder($this->darkGray, Border::BORDER_THICK);
                }

                if ($idx === $lastIdx && !$hasTotalColumn) {
                    $header->setRightBorder($this->darkGray);
                }

                $currentX++;
            }

            if ($hasTotalColumn) {
                // Add total column
                $this->helper->cell($currentX, 2)
                    ->setValue('Total (£)')
                    ->setWidth(16)
                    ->setBold(true);

                $currentX++;
            }

            // Add comments column
            $this->helper->cell($currentX, 2)
                ->setValue('Comments')
                ->setFill($this->lightGray)
                ->setWidth(16)
                ->setBold(true)
                ->setLeftBorder($this->darkGray)
                ->setRightBorder($this->darkGray, Border::BORDER_THICK);

            $currentX++;
        }

        $this->helper->range($currentX, 1, $currentX, 2)
            ->mergeCells()
            ->setValue('Grand total (£)')
            ->setFill($this->lightBlue)
            ->setWidth(18)
            ->setBold(true)
            ->setLeftBorder($this->darkGray, Border::BORDER_THICK)
            ->setRightBorder($this->darkGray, Border::BORDER_THICK);
    }

    protected function sumExpensesRow(int $y1, int $y2, ExpensesTableHelper $expensesTableHelper, Color $bottomBorderColor=null): void
    {
        $currentX = 20;
        $sumCells = [];

        foreach($expensesTableHelper->getDivisionConfigurations() as $divisionConfiguration) {
            $columnCount = count($divisionConfiguration->getColumnConfigurations());
            $currentX += $columnCount;

            if ($columnCount === 1) {
                $currentX--; // There's no "total" column - we need to take the number from the single column itself
            }

            $sumCells[] = $this->helper->cell($currentX, $y1)->getCoordinate();
            $currentX+=2; // One to skip the comments column, one to skip the totals column
        }

        $cell = $this->helper->range($currentX, $y1, $currentX, $y2)
            ->mergeCells()
            ->setValue("=SUM(".join(',',$sumCells).")");

        if ($bottomBorderColor) {
            $cell->setBottomBorder($bottomBorderColor);
        }
    }

    protected function writeRowHeaders(int $totalColumns): void
    {
        $this->helper->cell(1, 1)
            ->setValue('Scheme Details')
            ->setBold(true)
            ->setColor($this->white)
            ->setFill($this->blue);

        $this->helper->cell(1, 2)
            ->setValue('Identifier')
            ->setWidth(20);

        $this->helper->cell(2, 2)
            ->setValue('Scheme')
            ->setWidth(50);

        $this->helper->cell(3, 2)
            ->setValue('Ret?')
            ->setWidth(6);

        $offsetX = $this->moveExpensesHeaderToColumnC ? 1 : 0;

        $this->helper->cell($offsetX + 4, 2)
            ->setValue('Description')
            ->setWidth(60);

        $this->helper->cell($offsetX + 5, 2)
            ->setValue('On-track rating')
            ->setWrapText(true)
            ->setWidth(20);

        $this->helper->cell($offsetX + 6, 2)
            ->setValue('Development only?')
            ->setWidth(20);

        $this->helper->cell($offsetX + 7, 2)
            ->setValue('Milestone')
            ->setWidth(20);

        $this->helper->cell($offsetX + 8, 2)
            ->setValue('Current forecast / delivered date')
            ->setWrapText(true)
            ->setWidth(20);

        $this->helper->cell($offsetX + 9, 2)
            ->setValue('Progress update')
            ->setWidth(60);

        $this->helper->cell($offsetX + 10, 2)
            ->setValue('Transforming Cities Fund?')
            ->setWrapText(true)
            ->setWidth(20);

        $this->helper->cell($offsetX + 11, 2)
            ->setValue('Scheme risks')
            ->setWidth(60);

        $this->helper->cell($offsetX + 12, 2)
            ->setValue('Transport mode')
            ->setWidth(35);

        $this->helper->cell($offsetX + 13, 2)
            ->setValue('Active travel element')
            ->setWidth(30);

        $this->helper->cell($offsetX + 14, 2)
            ->setValue('Current business case')
            ->setWidth(25);

        $this->helper->cell($offsetX + 15, 2)
            ->setValue('Expected date of next approval gateway')
            ->setWrapText(true)
            ->setWidth(20);

        $this->helper->cell($offsetX + 16, 2)
            ->setValue('BCR')
            ->setWidth(10);

        $this->helper->cell($offsetX + 17, 2)
            ->setValue('Total cost')
            ->setWidth(15);

        $this->helper->cell($offsetX + 18, 2)
            ->setValue('Agreed funding')
            ->setWidth(15);

        $this->helper->range(1, 2, $totalColumns - 1, 2)
            ->setFill($this->lightGray)
            ->setBold(true);

        $expenditureX = $this->moveExpensesHeaderToColumnC ? 4 : 19;
        $this->helper->range($expenditureX,1, $expenditureX, 2)
            ->mergeCells()
            ->setValue('Expenditure')
            ->setBold(true)
            ->setWidth(15);

        if ($this->moveExpensesHeaderToColumnC) {
            $this->helper->range(1, 1, 3, 1)->mergeCells();
            $this->helper->range(5, 1, 19, 1)
                ->mergeCells()
                ->setFill($this->blue);

        } else {
            $this->helper->range(1, 1, 18, 1)->mergeCells();
        }
    }

    protected function mergeExpenseCellsAndSetTopAndBottomBorders(int $currentX, int $y1, int $y2, int $totalCount): void
    {
        $this->helper->range($currentX, $y1, $currentX, $y2 - 1)->mergeCells();
        $this->helper->range($currentX, $y2, $currentX, $y1 + $totalCount - 1)->mergeCells();

        $this->helper->cell($currentX, $y2 - 1)
            ->setBottomBorder($this->darkGray);
    }

    protected function getTotalColumnCount(): int
    {
        $totalColumns = 20 + 1; // 20 details, 1 grand total
        foreach($this->expensesTableHelper->getDivisionConfigurations() as $divisionConfiguration) {
            $columnCount = $divisionConfiguration->getColumnCount();
            $totalColumns += $columnCount + ($columnCount > 1 ? 1 : 0) + 1; // columns + total (maybe) + comments
        }
        return $totalColumns;
    }
}
