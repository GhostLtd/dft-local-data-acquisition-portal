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
}