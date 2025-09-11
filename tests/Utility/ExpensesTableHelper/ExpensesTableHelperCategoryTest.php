<?php

namespace App\Tests\Utility\ExpensesTableHelper;

use App\Config\ExpenseDivision\TableConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\Table\Cell;
use App\Config\Table\Header;
use App\Config\Table\TableBody;
use App\Entity\Enum\ExpenseCategory;
use App\Entity\Enum\ExpenseType;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Tests CategoryConfiguration scenarios for ExpensesTableHelper.
 *
 * CategoryConfiguration is used for grouped expense tables like:
 * - Fund expenses table (getFundExpensesTable) with multiple categories
 *
 * Covers:
 * - Single row categories (combined headers)
 * - Multiple row categories (group headers + spacers)
 * - Category labels and styling
 * - Cell accessibility titles
 */
class ExpensesTableHelperCategoryTest extends ExpensesTableHelperTestBase
{
    /**
     * Creates a CategoryConfiguration with multiple expense types.
     *
     * @return CategoryConfiguration FUND_CAPITAL category with regular and baseline types
     */
    private function createMultiRowCategoryConfiguration(): CategoryConfiguration
    {
        return new CategoryConfiguration(
            ExpenseCategory::FUND_CAPITAL,
            [
                ExpenseType::FUND_CAPITAL_EXPENDITURE,
                ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE
            ]
        );
    }
    public function testCategoryConfigurationSingleRow(): void
    {
        $configuration = new TableConfiguration(
            [$this->createSimpleCategoryConfiguration()],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $cells = $this->getFirstDataRow($table);

        // Single row category: combined header + data cell
        $this->assertCount(2, $cells, 'Single row category should have combined header and data cell');
        $this->assertInstanceOf(Header::class, $cells[0], 'First cell should be Header for category/expense type label');
        $this->assertEquals(2, $cells[0]->getOption('colspan'), 'Combined header should span 2 columns'); // Combined header
        $this->assertValidExpenseCell($cells[1], '2024-25', ExpenseType::FUND_CAPITAL_EXPENDITURE, 'Q1');
    }

    public function testCategoryConfigurationMultipleRows(): void
    {
        $configuration = new TableConfiguration(
            [$this->createMultiRowCategoryConfiguration()],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // Multiple rows: group header + data rows with spacers
        $this->assertCount(3, $bodyRows, 'Multi-row category should have group header + 2 data rows');

        // Group header row
        $groupHeaderCells = $bodyRows[0]->getCells();
        $this->assertCount(1, $groupHeaderCells, 'Group header row should have single spanning cell');
        $this->assertInstanceOf(Header::class, $groupHeaderCells[0], 'Group header should be Header cell');
        $this->assertEquals('group-header', $groupHeaderCells[0]->getOption('classes'), 'Group header should have group-header CSS class');

        // Data rows have spacers
        $firstDataRow = $bodyRows[1];
        $spacerCell = $firstDataRow->getCells()[0];
        $this->assertInstanceOf(Header::class, $spacerCell, 'First cell of data row should be spacer Header');
        $this->assertEquals('spacer', $spacerCell->getOption('classes'), 'Spacer cell should have spacer CSS class');
    }

    public function testCategoryWithMultipleColumns(): void
    {
        $configuration = new TableConfiguration(
            [$this->createSimpleCategoryConfiguration()],
            [$this->createMultiColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $cells = $this->getFirstDataRow($table);

        // Single row category with multiple columns: header + 4 data + total
        $this->assertCount(6, $cells, 'Single row category with 4 columns should have header + 4 data + total');

        // First cell is combined header
        $this->assertInstanceOf(Header::class, $cells[0], 'First cell should be combined header');
        $this->assertEquals(2, $cells[0]->getOption('colspan'), 'Combined header should span 2 columns');

        // Data cells with forecast flags
        $this->assertFalse($cells[1]->getAttribute('is_forecast'), 'Q1 data cell should not be forecast'); // Q1
        $this->assertFalse($cells[2]->getAttribute('is_forecast'), 'Q2 data cell should not be forecast'); // Q2
        $this->assertTrue($cells[3]->getAttribute('is_forecast'), 'Q3 data cell should be forecast');  // Q3
        $this->assertTrue($cells[4]->getAttribute('is_forecast'), 'Q4 data cell should be forecast');  // Q4

        // Total cell
        $this->assertTrue($cells[5]->getAttribute('is_row_total'), 'Last cell should be marked as row total');
    }

    public function testCategoryConfigurationGroupHeaderSpan(): void
    {
        $configuration = new TableConfiguration(
            [$this->createMultiRowCategoryConfiguration()],
            [$this->createMultiColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // Group header should span all columns (2 category columns + 4 data columns + 1 total = 7)
        $groupHeaderCell = $bodyRows[0]->getCells()[0];
        $this->assertEquals(7, $groupHeaderCell->getOption('colspan'), 'Group header should span all table columns (2 category + 4 data + 1 total)');
    }

    public function testCellAccessibilityTitles(): void
    {
        $configuration = new TableConfiguration(
            [$this->createSimpleCategoryConfiguration()],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $dataCell = $this->getFirstDataRow($table)[1]; // Skip header, get data cell

        $cellTitle = $dataCell->getOption('text');
        $this->assertInstanceOf(TranslatableMessage::class, $cellTitle, 'Cell title should be TranslatableMessage for accessibility');
        $this->assertEquals('forms.crsts.expenses.cell_title', $cellTitle->getMessage(), 'Cell title should use standard accessibility message key');

        $parameters = $cellTitle->getParameters();
        $this->assertArrayHasKey('group', $parameters, 'Cell title should include group parameter for screen readers');
        $this->assertArrayHasKey('row', $parameters, 'Cell title should include row parameter for screen readers');
        $this->assertArrayHasKey('column', $parameters, 'Cell title should include column parameter for screen readers');
    }

    public function testMultipleCategoriesInSameTable(): void
    {
        $configuration = new TableConfiguration(
            [
                $this->createSimpleCategoryConfiguration(), // FUND_CAPITAL
                new CategoryConfiguration(
                    ExpenseCategory::FUND_RESOURCE,
                    [ExpenseType::FUND_RESOURCE_EXPENDITURE]
                )
            ],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        // Should have 2 separate bodies for each category
        $this->assertCount(2, $bodies, 'Multiple categories should generate separate table bodies');

        $bodyArray = array_values($bodies);

        // First category
        $firstCategoryRows = $bodyArray[0]->getRows();
        $this->assertCount(1, $firstCategoryRows, 'First category should have single row'); // Single row category

        // Second category
        $secondCategoryRows = $bodyArray[1]->getRows();
        $this->assertCount(1, $secondCategoryRows, 'Second category should have single row'); // Single row category
    }

    public function testCategoryWithBaselineExpenseTypes(): void
    {
        $configuration = new TableConfiguration(
            [new CategoryConfiguration(
                ExpenseCategory::FUND_CAPITAL,
                [
                    ExpenseType::FUND_CAPITAL_EXPENDITURE,
                    ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE
                ]
            )],
            [$this->createSingleColumnDivision()],
            []
        );

        // Test with editableBaselines = false
        $this->helper
            ->setConfiguration($configuration)
            ->setDivisionKey('2024-25')
            ->setEditableBaselines(false);

        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // Skip group header, check data rows
        $regularRow = $bodyRows[1]; // First expense type
        $baselineRow = $bodyRows[2]; // Baseline expense type

        $regularCells = $regularRow->getCells();
        $baselineCells = $baselineRow->getCells();

        // Find the data cells (should be Cell objects, not Headers)
        $regularCell = null;
        $baselineCell = null;

        foreach ($regularCells as $cell) {
            if ($cell instanceof Cell) {
                $regularCell = $cell;
                break;
            }
        }

        foreach ($baselineCells as $cell) {
            if ($cell instanceof Cell) {
                $baselineCell = $cell;
                break;
            }
        }

        $this->assertNotNull($regularCell, 'Should find regular data cell');
        $this->assertNotNull($baselineCell, 'Should find baseline data cell');
        $this->assertFalse($regularCell->getOption('disabled'), 'Regular expense type should be enabled');
        $this->assertTrue($baselineCell->getOption('disabled'), 'Baseline expense type should be disabled when editableBaselines is false');
    }

    public function testCategoryConfigurationWithEmptyRowConfigurations(): void
    {
        $configuration = new TableConfiguration(
            [new CategoryConfiguration(
                ExpenseCategory::FUND_CAPITAL,
                [] // Empty row configurations
            )],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();

        $this->assertNotNull($table, 'Should handle category with empty row configurations');
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        if (count($bodies) > 0) {
            $bodyRows = array_values($bodies)[0]->getRows();
            $this->assertCount(0, $bodyRows, 'Empty category should generate no rows (category is skipped entirely)');
        } else {
            $this->assertCount(0, $bodies, 'Empty category should not create table body');
        }
    }

    public function testCellTitleWithAccessibilityParameters(): void
    {
        $configuration = new TableConfiguration(
            [$this->createSimpleCategoryConfiguration()],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $dataCell = $this->getFirstDataRow($table)[1];

        $cellTitle = $dataCell->getOption('text');
        $this->assertInstanceOf(TranslatableMessage::class, $cellTitle, 'Cell title should be TranslatableMessage for accessibility');

        $parameters = $cellTitle->getParameters();
        $this->assertNotEmpty($parameters['group'] ?? '', 'Group parameter should not be empty for screen readers');
        $this->assertNotEmpty($parameters['row'] ?? '', 'Row parameter should not be empty for screen readers');
        $this->assertNotEmpty($parameters['column'] ?? '', 'Column parameter should not be empty for screen readers');
    }
}