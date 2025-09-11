<?php

namespace App\Tests\Utility\ExpensesTableHelper;

use App\Config\ExpenseDivision\ColumnConfiguration;
use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Config\ExpenseDivision\TableConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\ExpenseRow\TotalConfiguration;
use App\Config\ExpenseRow\UngroupedConfiguration;
use App\Config\Table\Cell;
use App\Config\Table\Header;
use App\Config\Table\Table;
use App\Config\Table\TableBody;
use App\Config\Table\TableHead;
use App\Entity\Enum\ExpenseCategory;
use App\Entity\Enum\ExpenseType;
use App\Utility\ExpensesTableHelper;
use PHPUnit\Framework\TestCase;

/**
 * Abstract base class for ExpensesTableHelper tests.
 *
 * Provides common helper methods and setup for testing various
 * ExpensesTableHelper scenarios with realistic configurations.
 */
abstract class ExpensesTableHelperTestBase extends TestCase
{
    protected ExpensesTableHelper $helper;

    protected function setUp(): void
    {
        $this->helper = new ExpensesTableHelper();
    }

    // =================================================
    // DIVISION CONFIGURATION HELPERS
    // =================================================

    /**
     * Creates a single column division (typical for baselines or simple scenarios).
     *
     * @return DivisionConfiguration Division with single Q1 column, non-forecast
     */
    protected function createSingleColumnDivision(): DivisionConfiguration
    {
        return new DivisionConfiguration(
            '2024-25',
            [new ColumnConfiguration('Q1', false)],
            '2024-25 Label'
        );
    }

    /**
     * Creates a multi-column division with forecast flags (typical for full expense tables).
     *
     * @return DivisionConfiguration Division with Q1-Q4 columns, Q3-Q4 marked as forecast
     */
    protected function createMultiColumnDivision(): DivisionConfiguration
    {
        return new DivisionConfiguration(
            '2024-25',
            [
                new ColumnConfiguration('Q1', false),
                new ColumnConfiguration('Q2', false),
                new ColumnConfiguration('Q3', true),
                new ColumnConfiguration('Q4', true),
            ],
            '2024-25 Label'
        );
    }

    /**
     * Creates multiple divisions representing different years (realistic multi-year scenario).
     *
     * @return array<DivisionConfiguration> Array with 2024-25 (non-forecast) and 2025-26 (forecast) divisions
     */
    protected function createMultipleDivisions(): array
    {
        return [
            new DivisionConfiguration(
                '2024-25',
                [
                    new ColumnConfiguration('Q1', false),
                    new ColumnConfiguration('Q2', false),
                ],
                '2024-25'
            ),
            new DivisionConfiguration(
                '2025-26',
                [
                    new ColumnConfiguration('Q1', true),
                    new ColumnConfiguration('Q2', true),
                ],
                '2025-26'
            ),
        ];
    }

    // =================================================
    // ROW CONFIGURATION HELPERS
    // =================================================

    /**
     * Creates a simple UngroupedConfiguration with single expense type.
     *
     * @return UngroupedConfiguration Contains single FUND_CAPITAL_EXPENDITURE
     */
    protected function createSimpleUngroupedConfiguration(): UngroupedConfiguration
    {
        return new UngroupedConfiguration([ExpenseType::FUND_CAPITAL_EXPENDITURE]);
    }


    /**
     * Creates a CategoryConfiguration with single expense type.
     *
     * @return CategoryConfiguration FUND_CAPITAL category with single expense type
     */
    protected function createSimpleCategoryConfiguration(): CategoryConfiguration
    {
        return new CategoryConfiguration(
            ExpenseCategory::FUND_CAPITAL,
            [ExpenseType::FUND_CAPITAL_EXPENDITURE]
        );
    }

    // =================================================
    // TABLE CONFIGURATION HELPERS
    // =================================================

    /**
     * Creates a simple table configuration for basic testing
     */
    protected function createSimpleTableConfiguration(): TableConfiguration
    {
        return new TableConfiguration(
            [$this->createSimpleUngroupedConfiguration()],
            [$this->createSingleColumnDivision()],
            []
        );
    }

    /**
     * Creates a complex table configuration like getFundExpensesTable
     */
    protected function createComplexTableConfiguration(): TableConfiguration
    {
        return new TableConfiguration(
            [
                new CategoryConfiguration(
                    ExpenseCategory::FUND_CAPITAL,
                    [ExpenseType::FUND_CAPITAL_EXPENDITURE]
                ),
                new CategoryConfiguration(
                    ExpenseCategory::FUND_RESOURCE,
                    [ExpenseType::FUND_RESOURCE_EXPENDITURE]
                ),
                new TotalConfiguration(
                    'grand_total',
                    ['fex', 'fre'],
                    'Grand Total'
                )
            ],
            [$this->createSingleColumnDivision()],
            []
        );
    }

    // =================================================
    // ASSERTION HELPERS
    // =================================================

    protected function assertValidTableStructure(Table $table): void
    {
        $this->assertEquals('expenses', $table->getOption('classes'), 'Table should have expenses CSS class');

        $headAndBodies = $table->getHeadAndBodies();
        $this->assertGreaterThanOrEqual(2, count($headAndBodies), 'Table should have at least header and one body');
        $this->assertInstanceOf(TableHead::class, $headAndBodies[0], 'First element should be TableHead');

        // All other elements should be TableBody
        for ($i = 1; $i < count($headAndBodies); $i++) {
            $this->assertInstanceOf(TableBody::class, $headAndBodies[$i], "Element at index {$i} should be TableBody");
        }
    }

    protected function assertValidExpenseCell(Cell $cell, string $expectedDivisionKey, ExpenseType $expectedExpenseType, string $expectedColumnKey): void
    {
        $expectedKey = "expense__{$expectedDivisionKey}__{$expectedExpenseType->value}__{$expectedColumnKey}";
        $this->assertEquals($expectedKey, $cell->getOption('key'), 'Cell key should follow expected format');

        $this->assertEquals($expectedDivisionKey, $cell->getAttribute('division'), 'Division attribute should match');
        $this->assertEquals($expectedExpenseType, $cell->getAttribute('expense_type'), 'Expense type attribute should match');
        $this->assertEquals($expectedExpenseType->value, $cell->getAttribute('row_key'), 'Row key should match expense type value');
        $this->assertEquals($expectedColumnKey, $cell->getAttribute('col_key'), 'Column key attribute should match');
        $this->assertTrue($cell->getAttribute('is_data_cell'), 'Should be marked as data cell');
    }

    protected function assertValidTotalCell(Cell $cell, ?array $expectedRowsToSum = null): void
    {
        $this->assertTrue($cell->getOption('disabled'), 'Total cells should be disabled');

        if ($expectedRowsToSum !== null) {
            $this->assertEquals($expectedRowsToSum, $cell->getAttribute('total_rows_to_sum'), 'Total rows to sum should match expected');
        }
    }

    protected function configureHelper(TableConfiguration $configuration, string $divisionKey = '2024-25'): void
    {
        $this->helper
            ->setConfiguration($configuration)
            ->setDivisionKey($divisionKey);
    }

    protected function getFirstDataRow(Table $table): array
    {
        $tableBody = $table->getHeadAndBodies()[1];
        $firstRow = $tableBody->getRows()[0];
        return $firstRow->getCells();
    }

}