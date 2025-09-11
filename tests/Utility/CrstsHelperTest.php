<?php

namespace App\Tests\Utility;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Utility\CrstsHelper;
use PHPUnit\Framework\TestCase;

class CrstsHelperTest extends TestCase
{
    public function testGetNextYear(): void
    {
        $reflection = new \ReflectionClass(CrstsHelper::class);
        $method = $reflection->getMethod('getNextYear');

        $this->assertEquals('23', $method->invoke(null, 2022));
        $this->assertEquals('24', $method->invoke(null, 2023));
        $this->assertEquals('25', $method->invoke(null, 2024));
        $this->assertEquals('26', $method->invoke(null, 2025));
        $this->assertEquals('27', $method->invoke(null, 2026));
    }

    public function testGetDivisionConfigurationKey(): void
    {
        $this->assertEquals('2022-23', CrstsHelper::getDivisionConfigurationKey(2022));
        $this->assertEquals('2023-24', CrstsHelper::getDivisionConfigurationKey(2023));
        $this->assertEquals('2024-25', CrstsHelper::getDivisionConfigurationKey(2024));
        
        $this->assertEquals('post-2022-23', CrstsHelper::getDivisionConfigurationKey(2022, true));
        $this->assertEquals('post-2025-26', CrstsHelper::getDivisionConfigurationKey(2025, true));
    }

    public function testGetExpenseDivisionConfigurationsStructure(): void
    {
        $divisions = CrstsHelper::getExpenseDivisionConfigurations(2024, 2);
        
        $this->assertIsArray($divisions);
        $this->assertNotEmpty($divisions);
        
        foreach ($divisions as $division) {
            $this->assertInstanceOf(DivisionConfiguration::class, $division);
        }
        
        $this->assertGreaterThanOrEqual(5, count($divisions));
    }
}