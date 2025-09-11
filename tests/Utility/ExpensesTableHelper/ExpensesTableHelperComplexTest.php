<?php

namespace App\Tests\Utility\ExpensesTableHelper;

use App\Config\ExpenseDivision\TableConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\ExpenseRow\TotalConfiguration;
use App\Config\ExpenseRow\UngroupedConfiguration;
use App\Config\Table\Cell;
use App\Config\Table\TableBody;
use App\Entity\Enum\ExpenseCategory;
use App\Entity\Enum\ExpenseType;
use App\Utility\CrstsHelper;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Tests complex realistic scenarios for ExpensesTableHelper.
 *
 * These tests mirror the actual usage patterns found in CrstsHelper:
 * - getFundExpensesTable() pattern with multiple categories and grand totals
 * - Multiple division configurations (multi-year scenarios)
 * - Mixed configuration types in single tables
 *
 * Covers:
 * - Multiple categories with standalone totals
 * - Complex category configurations with subtotals
 * - Multiple division configurations
 * - Real-world CrstsHelper patterns
 */
class ExpensesTableHelperComplexTest extends ExpensesTableHelperTestBase
{
    public function testMultipleCategoriesWithStandaloneTotal(): void
    {
        // Mirrors getFundExpensesTable pattern: multiple categories + grand total
        $configuration = new TableConfiguration(
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

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        // Should have 3 bodies: 2 categories + 1 total
        $this->assertCount(3, $bodies);

        $bodyArray = array_values($bodies);

        // First category body
        $firstCategoryRows = $bodyArray[0]->getRows();
        $this->assertCount(1, $firstCategoryRows); // Single row category

        // Second category body
        $secondCategoryRows = $bodyArray[1]->getRows();
        $this->assertCount(1, $secondCategoryRows); // Single row category

        // Total body
        $totalRows = $bodyArray[2]->getRows();
        $this->assertCount(1, $totalRows);
        $totalCells = $totalRows[0]->getCells();
        $this->assertValidTotalCell($totalCells[1], ['fex', 'fre']);
    }

    public function testComplexFundExpensesTablePattern(): void
    {
        // Realistic pattern from getFundExpensesTable with subtotals
        $configuration = new TableConfiguration(
            [
                // Fund capital category with multiple expense types
                new CategoryConfiguration(
                    ExpenseCategory::FUND_CAPITAL,
                    [
                        ExpenseType::FUND_CAPITAL_EXPENDITURE,
                        ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE
                    ]
                ),
                // Local contributions category with subtotal (like real getFundExpensesTable)
                new CategoryConfiguration(
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
                ),
                // Other contributions category
                new CategoryConfiguration(
                    ExpenseCategory::OTHER_CAPTIAL_CONTRIBUTIONS,
                    [ExpenseType::FUND_CAPITAL_OTHER]
                ),
                // Grand total across all categories
                new TotalConfiguration(
                    'grand_total',
                    ['fex', 'flc', 'ftp', 'fot'],
                    new TranslatableMessage('forms.crsts.expenses.total_row')
                ),
                // Resource category (separate from capital)
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
        $this->assertValidTableStructure($table);

        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        // Should have 5 bodies: 4 categories + 1 grand total
        $this->assertCount(5, $bodies);

        $bodyArray = array_values($bodies);

        // Verify local contributions category has subtotal
        $localContribsBody = $bodyArray[1];
        $localContribsRows = $localContribsBody->getRows();
        $this->assertGreaterThan(3, count($localContribsRows)); // Group header + expenses + subtotal + baseline

        // Verify grand total references correct expense types
        $grandTotalBody = $bodyArray[3]; // Grand total comes before resource category
        $grandTotalCell = $grandTotalBody->getRows()[0]->getCells()[1];
        $this->assertEquals(['fex', 'flc', 'ftp', 'fot'], $grandTotalCell->getAttribute('total_rows_to_sum'));
    }

    public function testMultipleDivisionsTable(): void
    {
        // Realistic multi-year scenario
        $configuration = new TableConfiguration(
            [$this->createSimpleUngroupedConfiguration()],
            $this->createMultipleDivisions(),
            []
        );

        // Test first division
        $this->configureHelper($configuration, '2024-25');
        $table1 = $this->helper->getTable();
        $this->assertValidTableStructure($table1);

        // Test second division
        $this->configureHelper($configuration, '2025-26');
        $table2 = $this->helper->getTable();
        $this->assertValidTableStructure($table2);

        // Should be different table instances (different cache keys)
        $this->assertNotSame($table1, $table2);

        // Verify forecast flags are correct for each division
        $cells1 = $this->getFirstDataRow($table1);
        $cells2 = $this->getFirstDataRow($table2);

        // First division (2024-25) has non-forecast columns
        $this->assertFalse($cells1[0]->getAttribute('is_forecast')); // Q1
        $this->assertFalse($cells1[1]->getAttribute('is_forecast')); // Q2

        // Second division (2025-26) has forecast columns
        $this->assertTrue($cells2[0]->getAttribute('is_forecast')); // Q1
        $this->assertTrue($cells2[1]->getAttribute('is_forecast')); // Q2
    }

    public function testMixedConfigurationTypesInSingleTable(): void
    {
        // Mix of UngroupedConfiguration, CategoryConfiguration, and TotalConfiguration
        $configuration = new TableConfiguration(
            [
                // Start with ungrouped expenses
                $this->createSimpleUngroupedConfiguration(),
                // Add categorized expenses
                new CategoryConfiguration(
                    ExpenseCategory::FUND_RESOURCE,
                    [ExpenseType::FUND_RESOURCE_EXPENDITURE]
                ),
                // Intermediate total
                new TotalConfiguration(
                    'intermediate_total',
                    ['fex', 'fre'],
                    'Intermediate Total'
                ),
                // Another category
                new CategoryConfiguration(
                    ExpenseCategory::LOCAL_CAPITAL_CONTRIBUTIONS,
                    [ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION]
                ),
                // Final grand total
                new TotalConfiguration(
                    'grand_total',
                    ['fex', 'fre', 'flc'],
                    'Grand Total'
                )
            ],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        // Should have 5 bodies: ungrouped + category + total + category + total
        $this->assertCount(5, $bodies);

        $bodyArray = array_values($bodies);

        // Verify intermediate total references correct types
        $intermediateTotalCell = $bodyArray[2]->getRows()[0]->getCells()[1];
        $this->assertEquals(['fex', 'fre'], $intermediateTotalCell->getAttribute('total_rows_to_sum'));

        // Verify grand total references all types
        $grandTotalCell = $bodyArray[4]->getRows()[0]->getCells()[1];
        $this->assertEquals(['fex', 'fre', 'flc'], $grandTotalCell->getAttribute('total_rows_to_sum'));
    }

    public function testComplexTableWithMultipleColumnsAndBaselines(): void
    {
        // Complex scenario with multiple columns, baselines, and mixed configurations
        $configuration = new TableConfiguration(
            [
                new CategoryConfiguration(
                    ExpenseCategory::FUND_CAPITAL,
                    [
                        ExpenseType::FUND_CAPITAL_EXPENDITURE,
                        ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE
                    ]
                ),
                new TotalConfiguration(
                    'capital_total',
                    ['fex', 'feb'],
                    'Capital Total'
                )
            ],
            [$this->createMultiColumnDivision()],
            []
        );

        // Test with baselines disabled
        $this->helper
            ->setConfiguration($configuration)
            ->setDivisionKey('2024-25')
            ->setEditableBaselines(false);

        $table = $this->helper->getTable();
        $this->assertValidTableStructure($table);

        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);
        $categoryBody = array_values($bodies)[0];
        $categoryRows = $categoryBody->getRows();

        // Find baseline row (should be third row: group header + regular + baseline)
        $baselineRow = $categoryRows[2];
        $regularRow = $categoryRows[1];

        // Find the actual data cells (Cell objects, not Headers)
        $baselineDataCell = null;
        $regularDataCell = null;

        foreach ($baselineRow->getCells() as $cell) {
            if ($cell instanceof Cell) {
                $baselineDataCell = $cell;
                break;
            }
        }

        foreach ($regularRow->getCells() as $cell) {
            if ($cell instanceof Cell) {
                $regularDataCell = $cell;
                break;
            }
        }

        $this->assertNotNull($baselineDataCell, 'Should find baseline data cell');
        $this->assertNotNull($regularDataCell, 'Should find regular data cell');

        // Baseline should be disabled
        $this->assertTrue($baselineDataCell->getOption('disabled'));

        // Regular expense should be enabled
        $this->assertFalse($regularDataCell->getOption('disabled'));

        // Verify total row includes both expense types
        $totalBody = array_values($bodies)[1];
        $totalCell = $totalBody->getRows()[0]->getCells()[1]; // Skip header
        $this->assertEquals(['fex', 'feb'], $totalCell->getAttribute('total_rows_to_sum'));
    }

    public function testRealisticGetFundBaselinesTablePattern(): void
    {
        // Mirror getFundBaselinesTable: UngroupedConfiguration with multiple baseline types
        $configuration = new TableConfiguration(
            [new UngroupedConfiguration([
                ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE,
                ExpenseType::FUND_CAPITAL_EXPENDITURE_WITH_OVER_PROGRAMMING_BASELINE,
                ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION_BASELINE
            ])],
            [$this->createMultiColumnDivision()], // Multiple columns but with hideForecastAndActual
            []
        );

        $this->helper
            ->setConfiguration($configuration)
            ->setDivisionKey('2024-25')
            ->setEditableBaselines(true); // Baselines table typically allows editing

        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // Should have 3 rows for 3 baseline types
        $this->assertCount(3, $bodyRows);

        // All rows should have ungrouped class
        foreach ($bodyRows as $row) {
            $this->assertEquals('ungrouped', $row->getOption('classes'));
        }

        // All baseline cells should be enabled (editableBaselines = true)
        foreach ($bodyRows as $row) {
            $dataCell = $row->getCells()[1]; // Skip header
            $this->assertFalse($dataCell->getOption('disabled'));
        }
    }

    public function testRealisticGetSchemeExpensesTablePattern(): void
    {
        // Mirror getSchemeExpensesTable: UngroupedConfiguration with scheme expense types
        $configuration = new TableConfiguration(
            [new UngroupedConfiguration([
                ExpenseType::SCHEME_CAPITAL_SPEND_FUND,
                ExpenseType::SCHEME_CAPITAL_SPEND_ALL_SOURCES
            ])],
            [$this->createMultiColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();
        $bodyRows = $table->getHeadAndBodies()[1]->getRows();

        // Should have 2 rows for 2 scheme expense types
        $this->assertCount(2, $bodyRows);

        // Verify expense type values
        $firstRowCell = $bodyRows[0]->getCells()[1];
        $secondRowCell = $bodyRows[1]->getCells()[1];

        $this->assertEquals('ssp', $firstRowCell->getAttribute('row_key'));
        $this->assertEquals('ssa', $secondRowCell->getAttribute('row_key'));
    }

    public function testRealWorldGetFundExpensesTableFullIntegration(): void
    {
        // Using realistic return year/quarter that would generate forecast columns
        $returnYear = 2024;
        $returnQuarter = 3;

        $realConfiguration = CrstsHelper::getFundExpensesTable($returnYear, $returnQuarter);

        // Test with realistic division key from the generated configuration
        $divisions = $realConfiguration->getDivisionConfigurations();
        $this->assertNotEmpty($divisions, 'Real configuration should have division configurations');

        $firstDivisionKey = $divisions[0]->getKey();
        $this->configureHelper($realConfiguration, $firstDivisionKey);

        $table = $this->helper->getTable();
        $this->assertNotNull($table, 'Real-world configuration should generate valid table');

        // Verify complex structure matches expectation
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);
        $this->assertGreaterThan(4, count($bodies), 'Fund expenses table should have multiple categories + totals');

        // Test that forecast flags are properly set based on return year/quarter
        $firstBodyRows = array_values($bodies)[0]->getRows();
        if (count($firstBodyRows) > 0) {
            $dataCells = array_filter($firstBodyRows[0]->getCells(), fn($cell) => $cell instanceof Cell);
            if (count($dataCells) > 0) {
                $firstDataCell = array_values($dataCells)[0];
                $this->assertIsBool($firstDataCell->getAttribute('is_forecast'), 'Real configuration should set forecast flags');
            }
        }

        // Verify translation parameters are properly integrated
        $this->assertIsArray($realConfiguration->getExtraTranslationParameters(), 'Should have extra translation parameters');
    }

    public function testRealWorldGetFundBaselinesTableIntegration(): void
    {
        $realConfiguration = CrstsHelper::getFundBaselinesTable(2024, 2);

        $divisions = $realConfiguration->getDivisionConfigurations();
        $firstDivisionKey = $divisions[0]->getKey();
        $this->configureHelper($realConfiguration, $firstDivisionKey);

        $table = $this->helper->getTable();
        $this->assertNotNull($table, 'Baselines table configuration should generate valid table');

        // Verify all cells are baseline expense types and properly handled
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);
        $this->assertCount(1, $bodies, 'Baselines table should have single ungrouped body');

        $bodyRows = array_values($bodies)[0]->getRows();
        $this->assertGreaterThan(2, count($bodyRows), 'Should have multiple baseline expense types');

        // Test baseline editability behavior
        foreach ($bodyRows as $row) {
            $dataCells = array_filter($row->getCells(), fn($cell) => $cell instanceof Cell);
            foreach ($dataCells as $cell) {
                $expenseType = $cell->getAttribute('expense_type');
                if ($expenseType && str_contains($expenseType->value, 'BASELINE')) {
                    // Baseline cells behavior depends on editableBaselines setting
                    $this->assertIsBool($cell->getOption('disabled'), 'Baseline cells should have disabled state set');
                }
            }
        }
    }

    public function testMultiYearDivisionConfigurationBehavior(): void
    {
        // Test multiple realistic return year/quarter combinations
        $testScenarios = [
            ['year' => 2024, 'quarter' => 1, 'description' => 'Early 2024 return'],
            ['year' => 2024, 'quarter' => 4, 'description' => 'End of 2024 return'],
            ['year' => 2025, 'quarter' => 2, 'description' => 'Mid 2025 return'],
        ];

        foreach ($testScenarios as $scenario) {
            $configuration = CrstsHelper::getFundExpensesTable($scenario['year'], $scenario['quarter']);

            $divisions = $configuration->getDivisionConfigurations();
            $this->assertGreaterThan(3, count($divisions), "Should generate multiple years for {$scenario['description']}");

            // Test that divisions span from 2022 to at least 2026
            $divisionKeys = array_map(fn($div) => $div->getKey(), $divisions);
            $this->assertContains('2022-23', $divisionKeys, 'Should include 2022-23 division');
            $this->assertContains('2025-26', $divisionKeys, 'Should include 2025-26 division');

            // Test forecast flag logic across different divisions
            foreach ($divisions as $division) {
                $columns = $division->getColumnConfigurations();
                foreach ($columns as $column) {
                    $isForecast = $column->isForecast();
                    $this->assertIsBool($isForecast, "Forecast flag should be boolean for {$scenario['description']}");
                }
            }
        }
    }

    public function testLargeTableConfigurationPerformance(): void
    {
        $startTime = microtime(true);

        // Generate maximum realistic configuration (2026 return would include all years)
        $configuration = CrstsHelper::getFundExpensesTable(2026, 4);

        $divisions = $configuration->getDivisionConfigurations();
        $this->assertGreaterThan(4, count($divisions), 'Large configuration should have many divisions');

        // Test table generation performance for each division
        foreach ($divisions as $division) {
            $this->configureHelper($configuration, $division->getKey());

            $divisionStartTime = microtime(true);
            $table = $this->helper->getTable();
            $divisionEndTime = microtime(true);

            $this->assertNotNull($table, "Should generate table for division {$division->getKey()}");
            $this->assertLessThan(0.1, $divisionEndTime - $divisionStartTime, 'Individual division generation should be fast');
        }

        $totalTime = microtime(true) - $startTime;
        $this->assertLessThan(2.0, $totalTime, 'Full large configuration test should complete within reasonable time');
    }

    public function testSubtotalCalculationAccuracy(): void
    {
        $configuration = CrstsHelper::getFundExpensesTable(2024, 2);
        $divisions = $configuration->getDivisionConfigurations();
        $this->configureHelper($configuration, $divisions[0]->getKey());

        $table = $this->helper->getTable();
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);

        // Find the LOCAL_CAPITAL_CONTRIBUTIONS category body (contains subtotal)
        $subtotalBody = null;
        foreach ($bodies as $body) {
            $rows = $body->getRows();
            foreach ($rows as $row) {
                $cells = $row->getCells();
                foreach ($cells as $cell) {
                    if ($cell instanceof Cell &&
                        $cell->getAttribute('row_key') === 'SubTotal') {
                        $subtotalBody = $body;
                        break 3;
                    }
                }
            }
        }

        $this->assertNotNull($subtotalBody, 'Should find body containing SubTotal configuration');

        // Verify subtotal only references intended expense types
        $subtotalRow = null;
        foreach ($subtotalBody->getRows() as $row) {
            foreach ($row->getCells() as $cell) {
                if ($cell instanceof Cell &&
                    $cell->getAttribute('row_key') === 'SubTotal') {
                    $subtotalRow = $row;
                    break 2;
                }
            }
        }

        $this->assertNotNull($subtotalRow, 'Should find subtotal row');

        // Check that subtotal references correct expense types
        $subtotalCells = array_filter($subtotalRow->getCells(), fn($cell) => $cell instanceof Cell);
        foreach ($subtotalCells as $cell) {
            $rowsToSum = $cell->getAttribute('total_rows_to_sum');
            if ($rowsToSum) {
                $this->assertIsArray($rowsToSum, 'SubTotal should have array of rows to sum');
                $this->assertContains(ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION, $rowsToSum, 'SubTotal should include fund local contribution');
                $this->assertContains(ExpenseType::FUND_CAPITAL_THIRD_PARTY_CONTRIBUTION, $rowsToSum, 'SubTotal should include fund third party contribution');
                $this->assertNotContains(ExpenseType::FUND_CAPITAL_EXPENDITURE, $rowsToSum, 'SubTotal should NOT include fund capital expenditure');
            }
        }
    }
}