<?php

namespace App\Tests\Utility;


use App\Utility\FinancialQuarter;
use PHPUnit\Framework\TestCase;

class FinancialQuarterTest extends TestCase
{
    public function fromDateProvider(): array
    {
        return [
            ['2025-03-31', 2024, 4],
            ['2025-01-01', 2024, 4],
            ['2024-12-31', 2024, 3],
            ['2024-10-01', 2024, 3],
            ['2024-09-30', 2024, 2],
            ['2024-07-01', 2024, 2],
            ['2024-06-30', 2024, 1],
            ['2024-04-01', 2024, 1],
        ];
    }

    /**
     * @dataProvider fromDateProvider
     */
    public function testFromDate($dateString, $year, $quarter)
    {
        $fq = FinancialQuarter::createFromDate(new \DateTime($dateString));
        $this->assertEquals($year, $fq->initialYear);
        $this->assertEquals($quarter, $fq->quarter);
    }

    public function startDateProvider(): array
    {
        return [
            [2024, 4, '2025-01-01'],
            [2024, 3, '2024-10-01'],
            [2024, 2, '2024-07-01'],
            [2024, 1, '2024-04-01'],
        ];
    }

    /**
     * @dataProvider startDateProvider
     */
    public function testStartDate($year, $quarter, $expectedDate): void
    {
        $fq = new FinancialQuarter($year, $quarter);
        $this->assertEquals($expectedDate, $fq->getStartDate()->format('Y-m-d'));
    }

    public function quarterComparisonDataProvider(): array
    {
        return [
            [2024, 1, 'lt'],
            [2024, 1, 'lte'],
            [2024, 2, 'lte'],
            [2024, 2, 'eq'],
            [2024, 2, 'gte'],
            [2024, 3, 'gt'],
            [2024, 3, 'gte'],

            [2024, 1, 'eq', false],
            [2024, 1, 'gt', false],
            [2024, 1, 'gte', false],

            [2024, 2, 'lt', false],
            [2024, 2, 'gt', false],

            [2024, 3, 'lt', false],
            [2024, 3, 'lte', false],
            [2024, 3, 'eq', false],

            [2023, 1, 'lt'],
            [2023, 1, 'lte'],
            [2023, 1, 'eq', false],
            [2023, 1, 'gt', false],
            [2023, 1, 'gte', false],
            [2023, 4, 'lt'],
            [2023, 4, 'lte'],
            [2023, 4, 'eq', false],
            [2023, 4, 'gt', false],
            [2023, 4, 'gte', false],

            [2025, 1, 'gt'],
            [2025, 1, 'gte'],
            [2025, 1, 'eq', false],
            [2025, 1, 'lt', false],
            [2025, 1, 'lte', false],
            [2025, 4, 'gt'],
            [2025, 4, 'gte'],
            [2025, 4, 'eq', false],
            [2025, 4, 'lt', false],
            [2025, 4, 'lte', false],

        ];
    }

    /**
     * @dataProvider quarterComparisonDataProvider
     */
    public function testQuarterComparison($year, $quarter, $comparison, $expectedResult = true): void
    {
        $fq = new FinancialQuarter($year, $quarter);
        $compareTo = new FinancialQuarter(2024, 2);
        switch ($comparison) {
            case 'eq' :
                $this->assertEquals($expectedResult, $fq == $compareTo);
                break;
            case 'gt' :
                $this->assertEquals($expectedResult, $fq > $compareTo);
                break;
            case 'gte' :
                $this->assertEquals($expectedResult, $fq >= $compareTo);
                break;
            case 'lt' :
                $this->assertEquals($expectedResult, $fq < $compareTo);
                break;
            case 'lte' :
                $this->assertEquals($expectedResult, $fq <= $compareTo);
                break;

            default:
                throw new \RuntimeException("Comparison not supported");
        }
    }

    public function divisionAndColumnDataProvider(): array
    {
        return [
            ['2023-24', 'Q1', 2023, 1],
            ['2023-24', 'Q2', 2023, 2],
            ['2023-24', 'Q3', 2023, 3],
            ['2023-24', 'Q4', 2023, 4],

            ['2023-2', 'Q1', null, null],
            ['2023-2', 'Q5', null, null],
            ['2023-2', 'Q0', null, null],
            ['2023-24', '1', null, null],
            ['2023-24', 'A1', null, null],
        ];
    }

    /**
     * @dataProvider divisionAndColumnDataProvider
     */
    public function testFromDivisionAndColumn(string $division, string $column, ?int $expectedYear, ?int $expectedQuarter): void
    {
        if ($expectedYear === null && $expectedQuarter === null) {
            $this->expectException(\RuntimeException::class);
        }
        $fq = FinancialQuarter::createFromDivisionAndColumn($division, $column);
        $this->assertEquals($expectedYear, $fq->initialYear);
        $this->assertEquals($expectedQuarter, $fq->quarter);
    }
}