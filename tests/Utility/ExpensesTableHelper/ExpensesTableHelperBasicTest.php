<?php

namespace App\Tests\Utility\ExpensesTableHelper;

use App\Config\ExpenseDivision\TableConfiguration;
use App\Config\ExpenseRow\UngroupedConfiguration;
use App\Config\Table\Table;
use App\Config\Table\TableHead;
use App\Config\Table\TableBody;
use App\Entity\Enum\ExpenseType;

/**
 * Tests basic functionality and validation for ExpensesTableHelper.
 *
 * Covers:
 * - Invalid division key handling
 * - Basic table structure validation
 * - Caching behavior
 * - Helper method functionality
 */
class ExpensesTableHelperBasicTest extends ExpensesTableHelperTestBase
{
    public function testGetTableReturnsNullForInvalidDivisionKey(): void
    {
        $configuration = $this->createSimpleTableConfiguration();
        $this->configureHelper($configuration, 'invalid-key');

        $this->assertNull($this->helper->getTable(), 'Should return null when division key does not exist in configuration');
    }

    public function testGetTableReturnsValidTable(): void
    {
        $configuration = $this->createSimpleTableConfiguration();
        $this->configureHelper($configuration);

        $table = $this->helper->getTable();

        $this->assertNotNull($table, 'Should return a valid Table instance for valid configuration');
        $this->assertValidTableStructure($table);
    }

    public function testTableStructure(): void
    {
        $configuration = $this->createSimpleTableConfiguration();
        $this->configureHelper($configuration);

        $table = $this->helper->getTable();
        $headAndBodies = $table->getHeadAndBodies();

        $this->assertCount(2, $headAndBodies, 'Simple table should have exactly 2 parts: head and body');
        $this->assertInstanceOf(TableHead::class, $headAndBodies[0], 'First element should be TableHead for column headers');
        $this->assertInstanceOf(TableBody::class, $headAndBodies[1], 'Second element should be TableBody for data rows');
    }

    public function testCachingBehavior(): void
    {
        $configuration = $this->createSimpleTableConfiguration();
        $this->configureHelper($configuration);

        $table1 = $this->helper->getTable();
        $table2 = $this->helper->getTable();

        $this->assertSame($table1, $table2, 'Subsequent calls with same configuration should return cached instance');
    }

    public function testCachingWithDifferentDivisionKeys(): void
    {
        $configuration = new TableConfiguration(
            [$this->createSimpleUngroupedConfiguration()],
            $this->createMultipleDivisions(),
            []
        );

        // First division
        $this->configureHelper($configuration, '2024-25');
        $table1 = $this->helper->getTable();

        // Second division
        $this->configureHelper($configuration, '2025-26');
        $table2 = $this->helper->getTable();

        // Should be different table instances (different cache keys)
        $this->assertNotSame($table1, $table2, 'Different division keys should generate different table instances');
        $this->assertNotNull($table1, 'First division (2024-25) should generate valid table');
        $this->assertNotNull($table2, 'Second division (2025-26) should generate valid table');
    }

    public function testCachingWithEditableBaselines(): void
    {
        $configuration = new TableConfiguration(
            [new UngroupedConfiguration([ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE])],
            [$this->createSingleColumnDivision()],
            []
        );

        // Test with editableBaselines = false
        $this->helper
            ->setConfiguration($configuration)
            ->setDivisionKey('2024-25')
            ->setEditableBaselines(false);
        $table1 = $this->helper->getTable();

        // Test with editableBaselines = true (should create different cache entry)
        $this->helper->setEditableBaselines(true);
        $table2 = $this->helper->getTable();

        $this->assertNotSame($table1, $table2, 'Different editableBaselines settings should generate different cached instances');
    }

    public function testGetRowGroupConfigurations(): void
    {
        $rowConfig = $this->createSimpleUngroupedConfiguration();
        $configuration = new TableConfiguration(
            [$rowConfig],
            [$this->createSingleColumnDivision()],
            []
        );

        $this->helper->setConfiguration($configuration);
        $rowGroups = $this->helper->getRowGroupConfigurations();

        $this->assertIsArray($rowGroups, 'getRowGroupConfigurations should return array of configurations');
        $this->assertCount(1, $rowGroups, 'Should return exactly one row group configuration');
        $this->assertSame($rowConfig, $rowGroups[0], 'Should return the same configuration instance that was set');
    }

    public function testTableWithEmptyRowGroupConfigurations(): void
    {
        $configuration = new TableConfiguration(
            [], // Empty row configurations
            [$this->createSingleColumnDivision()],
            []
        );

        $this->configureHelper($configuration);
        $table = $this->helper->getTable();

        $this->assertNotNull($table, 'Should handle empty row configurations gracefully');
        $bodies = array_filter($table->getHeadAndBodies(), fn($item) => $item instanceof TableBody);
        $this->assertCount(0, $bodies, 'Empty row configuration should result in no table bodies');
    }

    public function testTableWithEmptyDivisionConfigurations(): void
    {
        $configuration = new TableConfiguration(
            [$this->createSimpleUngroupedConfiguration()],
            [], // Empty division configurations
            []
        );

        $this->helper->setConfiguration($configuration);
        $this->helper->setDivisionKey('any-key');

        $table = $this->helper->getTable();
        $this->assertNull($table, 'Should return null when no divisions are configured');
    }

    public function testGetTableWithEmptyDivisionKey(): void
    {
        $configuration = $this->createSimpleTableConfiguration();
        $this->configureHelper($configuration, '');

        $this->assertNull($this->helper->getTable(), 'Should return null for empty division key');
    }

    public function testGetTableWithWhitespaceDivisionKey(): void
    {
        $configuration = $this->createSimpleTableConfiguration();
        $this->configureHelper($configuration, '   ');

        $this->assertNull($this->helper->getTable(), 'Should return null for whitespace-only division key');
    }
}