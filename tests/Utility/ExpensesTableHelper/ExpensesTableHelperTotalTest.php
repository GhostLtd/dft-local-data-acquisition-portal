<?php

namespace App\Tests\Utility\ExpensesTableHelper;

use App\Config\ExpenseDivision\TableConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\ExpenseRow\TotalConfiguration;
use App\Config\Table\Cell;
use App\Config\Table\Header;
use App\Config\Table\TableBody;
use App\Entity\Enum\ExpenseCategory;
use App\Entity\Enum\ExpenseType;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Tests TotalConfiguration scenarios for ExpensesTableHelper.
 *
 * TotalConfiguration is used for totals and subtotals in expense tables:
 * - Standalone totals (like grand totals in getFundExpensesTable)
 * - Subtotals within categories (like SubTotal in getFundExpensesTable)
 *
 * Covers:
 * - Standalone total configurations
 * - Total configurations within categories
 * - Total row attributes and calculations
 * - Multiple total scenarios
 */
class ExpensesTableHelperTotalTest extends ExpensesTableHelperTestBase
{
    /**
     * Creates a CategoryConfiguration with TotalConfiguration (like getFundExpensesTable subtotals).
     *
     * @return CategoryConfiguration LOCAL_CAPITAL_CONTRIBUTIONS category with subtotal
     */
    private function createCategoryWithSubtotalConfiguration(): CategoryConfiguration
    {
        return new CategoryConfiguration(
            ExpenseCategory::LOCAL_CAPITAL_CONTRIBUTIONS,
            [
                ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION,
                ExpenseType::FUND_CAPITAL_THIRD_PARTY_CONTRIBUTION,
                new TotalConfiguration(
                    'subtotal',
                    ['flc', 'ftp'],
                    new TranslatableMessage('forms.crsts.expenses.sub_total')
                )
            ]
        );
    }

    /**
     * Creates a standalone TotalConfiguration.
     *
     * @return TotalConfiguration Grand total summing FUND_CAPITAL_EXPENDITURE
     */
    private function createStandaloneTotalConfiguration(): TotalConfiguration
    {
        return new TotalConfiguration(
            'grand_total',
            ['fex'],
            'Grand Total'
        );
    }
    public function testStandaloneTotalConfiguration(): void
    {
        $configuration = new TableConfiguration(
            [
                $this->createSimpleUngroupedConfiguration(),
                $this->createStandaloneTotalConfiguration()
            ],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        // Should have 2 bodies: ungrouped + total
        $this->assertCount(2, $bodies, 'Standalone total should generate separate table body');

        $totalBody = array_values($bodies)[1];
        $totalRow = $totalBody->getRows()[0];
        $totalCells = $totalRow->getCells();

        $this->assertCount(2, $totalCells, 'Total row should have header and data cell');
        $this->assertInstanceOf(Header::class, $totalCells[0], 'First cell should be Header for total label');
        $this->assertEquals(2, $totalCells[0]->getOption('colspan'), 'Total header should span 2 columns');

        $this->assertValidTotalCell($totalCells[1], ['fex']);
    }

    public function testTotalConfigurationWithinCategory(): void
    {
        $configuration = new TableConfiguration(
            [$this->createCategoryWithSubtotalConfiguration()],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // Group header + 2 expense rows + 1 total row = 4 rows
        $this->assertCount(4, $bodyRows, 'Category with subtotal should have group header + 2 expense rows + 1 total row');

        // Last row should be the total within category
        $totalRow = $bodyRows[3];
        $totalCells = $totalRow->getCells();

        // TotalConfiguration within category: spacer + row title + data cell
        $this->assertCount(3, $totalCells, 'Total within category should have spacer + title + data cell');

        $this->assertInstanceOf(Header::class, $totalCells[0], 'First cell should be spacer Header'); // Spacer
        $this->assertInstanceOf(Header::class, $totalCells[1], 'Second cell should be row title Header'); // Row title
        $this->assertValidTotalCell($totalCells[2], ['flc', 'ftp']);
    }

    public function testTotalConfigurationWithMultipleColumns(): void
    {
        $configuration = new TableConfiguration(
            [
                $this->createSimpleUngroupedConfiguration(),
                $this->createStandaloneTotalConfiguration()
            ],
            [$this->createMultiColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        $totalBody = array_values($bodies)[1];
        $totalRow = $totalBody->getRows()[0];
        $totalCells = $totalRow->getCells();

        // Total with multiple columns: header + 4 data cells + total = 6 cells
        $this->assertCount(6, $totalCells, 'Total with 4 columns should have header + 4 data + row total');

        // Header
        $this->assertInstanceOf(Header::class, $totalCells[0], 'First cell should be total header');
        $this->assertEquals(2, $totalCells[0]->getOption('colspan'), 'Total header should span 2 columns');

        // Data cells (all should be totals, disabled)
        for ($i = 1; $i <= 4; $i++) {
            $this->assertValidTotalCell($totalCells[$i], ['fex']);
            $this->assertFalse($totalCells[$i]->getAttribute('is_data_cell'), "Total cell {$i} should not be marked as data cell");
        }

        // Final total cell
        $this->assertTrue($totalCells[5]->getAttribute('is_row_total'), 'Final cell should be marked as row total');
    }

    public function testTotalConfigurationKeyGeneration(): void
    {
        $configuration = new TableConfiguration(
            [
                $this->createSimpleUngroupedConfiguration(),
                new TotalConfiguration('custom_total', ['fex'], 'Custom Total')
            ],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        $totalBody = array_values($bodies)[1];
        $totalCell = $totalBody->getRows()[0]->getCells()[1];

        // Key should include custom total key
        $this->assertEquals('expense__custom_total__Q1', $totalCell->getOption('key'), 'Total cell key should include custom total identifier');
        $this->assertEquals('custom_total', $totalCell->getAttribute('row_key'), 'Total cell row_key should match configuration key');
    }

    public function testTotalConfigurationWithTranslatableMessage(): void
    {
        $configuration = new TableConfiguration(
            [$this->createCategoryWithSubtotalConfiguration()], // Uses TranslatableMessage for label
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // Get the total row
        $totalRow = $bodyRows[3];
        $totalCells = $totalRow->getCells();

        // The total cell should have correct attributes
        $totalCell = $totalCells[2];
        $this->assertEquals('subtotal', $totalCell->getAttribute('row_key'), 'Total cell should have correct row key from configuration');
        $this->assertEquals(['flc', 'ftp'], $totalCell->getAttribute('total_rows_to_sum'), 'Total cell should reference correct rows to sum');
    }

    public function testMultipleTotalConfigurations(): void
    {
        $configuration = new TableConfiguration(
            [
                $this->createSimpleUngroupedConfiguration(),
                new TotalConfiguration('subtotal', ['fex'], 'Subtotal'),
                new TotalConfiguration('grand_total', ['fex'], 'Grand Total')
            ],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        // Should have 3 bodies: ungrouped + 2 totals
        $this->assertCount(3, $bodies, 'Multiple totals should each generate separate table bodies');

        $bodyArray = array_values($bodies);

        // First total
        $subtotalCell = $bodyArray[1]->getRows()[0]->getCells()[1];
        $this->assertEquals('subtotal', $subtotalCell->getAttribute('row_key'), 'First total should have subtotal key');

        // Second total
        $grandTotalCell = $bodyArray[2]->getRows()[0]->getCells()[1];
        $this->assertEquals('grand_total', $grandTotalCell->getAttribute('row_key'), 'Second total should have grand_total key');
    }

    public function testTotalConfigurationDisabledState(): void
    {
        $configuration = new TableConfiguration(
            [
                $this->createSimpleUngroupedConfiguration(),
                $this->createStandaloneTotalConfiguration()
            ],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        $totalCell = array_values($bodies)[1]->getRows()[0]->getCells()[1];

        // Total cells should always be disabled (non-editable)
        $this->assertTrue($totalCell->getOption('disabled'), 'Total cells should always be disabled for editing');
        $this->assertFalse($totalCell->getAttribute('is_data_cell'), 'Total cells should not be marked as data cells');
    }

    public function testCategoryWithMixedRowsAndTotals(): void
    {
        // Create a category similar to getFundExpensesTable pattern
        $categoryConfig = new CategoryConfiguration(
            ExpenseCategory::LOCAL_CAPITAL_CONTRIBUTIONS,
            [
                ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION,
                ExpenseType::FUND_CAPITAL_THIRD_PARTY_CONTRIBUTION,
                new TotalConfiguration(
                    'subtotal',
                    ['flc', 'ftp'],
                    new TranslatableMessage('forms.crsts.expenses.sub_total')
                ),
                ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION_BASELINE
            ]
        );

        $configuration = new TableConfiguration(
            [$categoryConfig],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // Group header + 2 expenses + 1 total + 1 baseline = 5 rows
        $this->assertCount(5, $bodyRows, 'Category with mixed rows and totals should have all row types');

        // Row 3 should be the subtotal
        $totalRow = $bodyRows[3];
        $totalCells = $totalRow->getCells();
        $this->assertValidTotalCell($totalCells[2], ['flc', 'ftp']);

        // Row 4 should be the baseline expense
        $baselineRow = $bodyRows[4];
        $baselineCells = $baselineRow->getCells();

        // Find the data cell (should be Cell object, not Header)
        $baselineDataCell = null;
        foreach ($baselineCells as $cell) {
            if ($cell instanceof Cell) {
                $baselineDataCell = $cell;
                break;
            }
        }

        $this->assertNotNull($baselineDataCell, 'Should find baseline data cell');
        $this->assertInstanceOf(Cell::class, $baselineDataCell, 'Baseline should be data cell, not total cell'); // Data cell, not total
    }

    public function testTotalConfigurationWithEmptyRowsToSum(): void
    {
        $configuration = new TableConfiguration(
            [
                $this->createSimpleUngroupedConfiguration(),
                new TotalConfiguration('empty_total', [], 'Empty Total') // Empty rows to sum
            ],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        $this->assertCount(2, $bodies, 'Should still create total body even with empty rows to sum');
        $totalBody = array_values($bodies)[1];
        $totalCell = $totalBody->getRows()[0]->getCells()[1];

        $this->assertValidTotalCell($totalCell, []);
        $this->assertEquals([], $totalCell->getAttribute('total_rows_to_sum'), 'Total should have empty array for rows to sum');
    }

    public function testTotalConfigurationWithNonExistentRowKeys(): void
    {
        $configuration = new TableConfiguration(
            [
                $this->createSimpleUngroupedConfiguration(), // Creates 'fex' key
                new TotalConfiguration('invalid_total', ['nonexistent', 'missing'], 'Invalid Total')
            ],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();

        $this->assertNotNull($table, 'Should handle total with non-existent row keys gracefully');
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);
        $totalCell = array_values($bodies)[1]->getRows()[0]->getCells()[1];

        $this->assertEquals(['nonexistent', 'missing'], $totalCell->getAttribute('total_rows_to_sum'),
            'Should preserve original row keys even if they don\'t exist in table');
    }

    public function testTotalConfigurationWithEmptyKey(): void
    {
        $configuration = new TableConfiguration(
            [
                $this->createSimpleUngroupedConfiguration(),
                new TotalConfiguration('', ['fex'], 'Empty Key Total') // Empty key
            ],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);
        $totalCell = array_values($bodies)[1]->getRows()[0]->getCells()[1];

        $this->assertEquals('expense____Q1', $totalCell->getOption('key'), 'Empty total key should result in double underscore in cell key');
        $this->assertEquals('', $totalCell->getAttribute('row_key'), 'Row key should remain empty string');
    }
}