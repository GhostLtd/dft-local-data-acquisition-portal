<?php

namespace App\Tests\Utility\ExpensesTableHelper;

use App\Config\ExpenseDivision\TableConfiguration;
use App\Config\ExpenseRow\UngroupedConfiguration;
use App\Config\Table\Cell;
use App\Config\Table\Header;
use App\Entity\Enum\ExpenseType;

/**
 * Tests UngroupedConfiguration scenarios for ExpensesTableHelper.
 *
 * UngroupedConfiguration is used for simple expense tables like:
 * - Fund baselines table (getFundBaselinesTable)
 * - Scheme expenses table (getSchemeExpensesTable)
 *
 * Covers:
 * - Single row ungrouped configurations
 * - Multiple row ungrouped configurations
 * - Multiple columns with forecast flags
 * - Baseline editability behavior
 */
class ExpensesTableHelperUngroupedTest extends ExpensesTableHelperTestBase
{
    /**
     * Creates UngroupedConfiguration with multiple expense types (like getFundBaselinesTable).
     *
     * @return UngroupedConfiguration Contains regular and baseline capital expenditure types
     */
    private function createMultiRowUngroupedConfiguration(): UngroupedConfiguration
    {
        return new UngroupedConfiguration([
            ExpenseType::FUND_CAPITAL_EXPENDITURE,
            ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE
        ]);
    }
    public function testUngroupedConfigurationSingleRow(): void
    {
        $configuration = new TableConfiguration(
            [$this->createSimpleUngroupedConfiguration()],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $cells = $this->getFirstDataRow($table);

        // Single row + single column = only data cell (header combined with title)
        $this->assertCount(1, $cells, 'Single row ungrouped configuration should generate one data cell');
        $this->assertValidExpenseCell($cells[0], '2024-25', ExpenseType::FUND_CAPITAL_EXPENDITURE, 'Q1');
    }

    public function testUngroupedConfigurationMultipleRows(): void
    {
        $configuration = new TableConfiguration(
            [$this->createMultiRowUngroupedConfiguration()],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // Multiple rows = separate headers + data cells
        $this->assertCount(2, $bodyRows, 'Multi-row ungrouped configuration should generate separate rows for each expense type');

        // First row: header + data cell
        $firstRowCells = $bodyRows[0]->getCells();
        $this->assertCount(2, $firstRowCells, 'First row should have header and data cell');
        $this->assertInstanceOf(Header::class, $firstRowCells[0], 'First cell should be Header for expense type label');
        $this->assertValidExpenseCell($firstRowCells[1], '2024-25', ExpenseType::FUND_CAPITAL_EXPENDITURE, 'Q1');

        // Row should have ungrouped class
        $this->assertEquals('ungrouped', $bodyRows[0]->getOption('classes'), 'Ungrouped rows should have ungrouped CSS class');
    }

    public function testUngroupedConfigurationWithMultipleColumns(): void
    {
        $configuration = new TableConfiguration(
            [$this->createSimpleUngroupedConfiguration()],
            [$this->createMultiColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $cells = $this->getFirstDataRow($table);

        // Single row + 4 columns + total = 5 cells
        $this->assertCount(5, $cells, 'Single row with 4 columns should generate 4 data cells plus 1 total cell');

        // All should be Cell objects (data + total)
        foreach ($cells as $i => $cell) {
            $this->assertInstanceOf(Cell::class, $cell, "Cell at index {$i} should be Cell instance for data entry");
        }

        // Check forecast flags
        $this->assertFalse($cells[0]->getAttribute('is_forecast'), 'Q1 column should not be marked as forecast'); // Q1
        $this->assertFalse($cells[1]->getAttribute('is_forecast'), 'Q2 column should not be marked as forecast'); // Q2
        $this->assertTrue($cells[2]->getAttribute('is_forecast'), 'Q3 column should be marked as forecast');  // Q3
        $this->assertTrue($cells[3]->getAttribute('is_forecast'), 'Q4 column should be marked as forecast');  // Q4

        // Last cell should be total
        $this->assertTrue($cells[4]->getAttribute('is_row_total'), 'Last cell should be marked as row total');
    }

    public function testUngroupedConfigurationWithForecastColumns(): void
    {
        $configuration = new TableConfiguration(
            [$this->createMultiRowUngroupedConfiguration()],
            [$this->createMultiColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // Check first row data cells for forecast flags
        $firstRowCells = $bodyRows[0]->getCells();
        $this->assertCount(6, $firstRowCells, 'Multi-row with 4 columns should have header + 4 data cells + total'); // header + 4 data + total

        // Data cells should have correct forecast flags
        $this->assertFalse($firstRowCells[1]->getAttribute('is_forecast'), 'Q1 data cell should not be forecast'); // Q1
        $this->assertFalse($firstRowCells[2]->getAttribute('is_forecast'), 'Q2 data cell should not be forecast'); // Q2
        $this->assertTrue($firstRowCells[3]->getAttribute('is_forecast'), 'Q3 data cell should be forecast');  // Q3
        $this->assertTrue($firstRowCells[4]->getAttribute('is_forecast'), 'Q4 data cell should be forecast');  // Q4
    }

    public function testEditableBaselines(): void
    {
        $configuration = new TableConfiguration(
            [new UngroupedConfiguration([ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE])],
            [$this->createSingleColumnDivision()],
            []
        );

        // Test disabled baselines (default)
        $this->helper
            ->setConfiguration($configuration)
            ->setDivisionKey('2024-25')
            ->setEditableBaselines(false);

        $table = $this->helper->getTable();
        $dataCell = $this->getFirstDataRow($table)[0];
        $this->assertTrue($dataCell->getOption('disabled'), 'Baseline cells should be disabled when editableBaselines is false');

        // Test enabled baselines
        $this->helper->setEditableBaselines(true);
        $table2 = $this->helper->getTable();
        $dataCell2 = $this->getFirstDataRow($table2)[0];
        $this->assertFalse($dataCell2->getOption('disabled'), 'Baseline cells should be enabled when editableBaselines is true');
    }

    public function testNonBaselineExpenseTypesAlwaysEnabled(): void
    {
        $configuration = new TableConfiguration(
            [$this->createSimpleUngroupedConfiguration()], // Uses FUND_CAPITAL_EXPENDITURE (not baseline)
            [$this->createSingleColumnDivision()],
            []
        );

        // Even with editableBaselines = false, non-baseline types should be enabled
        $this->helper
            ->setConfiguration($configuration)
            ->setDivisionKey('2024-25')
            ->setEditableBaselines(false);

        $table = $this->helper->getTable();
        $dataCell = $this->getFirstDataRow($table)[0];
        $this->assertFalse($dataCell->getOption('disabled'), 'Non-baseline expense types should always be enabled regardless of editableBaselines setting');
    }

    public function testUngroupedWithMixedBaselineAndRegularTypes(): void
    {
        $configuration = new TableConfiguration(
            [new UngroupedConfiguration([
                ExpenseType::FUND_CAPITAL_EXPENDITURE,          // Regular
                ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE  // Baseline
            ])],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->helper
            ->setConfiguration($configuration)
            ->setDivisionKey('2024-25')
            ->setEditableBaselines(false);

        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // First row (regular type) should be enabled
        $regularCell = $bodyRows[0]->getCells()[1];
        $this->assertFalse($regularCell->getOption('disabled'), 'Regular expense type cells should be enabled');

        // Second row (baseline type) should be disabled
        $baselineCell = $bodyRows[1]->getCells()[1];
        $this->assertTrue($baselineCell->getOption('disabled'), 'Baseline expense type cells should be disabled when editableBaselines is false');
    }
}