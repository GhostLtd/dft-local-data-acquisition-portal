<?php

namespace App\Tests\Enum;

use App\Entity\Enum\MilestoneType;
use PHPUnit\Framework\TestCase;

class MilestoneTypeTest extends TestCase
{
    public function testBaselineCasesCDEL(): void
    {
        $this->assertEquals([
            MilestoneType::BASELINE_START_DEVELOPMENT,
            MilestoneType::BASELINE_END_DEVELOPMENT,
            MilestoneType::BASELINE_START_CONSTRUCTION,
            MilestoneType::BASELINE_END_CONSTRUCTION,
            MilestoneType::BASELINE_FINAL_DELIVERY,
        ], MilestoneType::getBaselineCases(true));
    }

    public function testBaselineCasesRDEL(): void
    {
        $this->assertEquals([
            MilestoneType::BASELINE_START_DEVELOPMENT,
            MilestoneType::BASELINE_END_DEVELOPMENT,
            MilestoneType::BASELINE_START_DELIVERY,
            MilestoneType::BASELINE_END_DELIVERY,
        ], MilestoneType::getBaselineCases(false));
    }

    public function testNonBaselineCasesCDEL(): void
    {
        $this->assertEquals([
            MilestoneType::START_DEVELOPMENT,
            MilestoneType::END_DEVELOPMENT,
            MilestoneType::START_CONSTRUCTION,
            MilestoneType::END_CONSTRUCTION,
            MilestoneType::FINAL_DELIVERY,
        ], MilestoneType::getNonBaselineCases(true));
    }

    public function testNonBaselineCasesRDEL(): void
    {
        $this->assertEquals([
            MilestoneType::START_DEVELOPMENT,
            MilestoneType::END_DEVELOPMENT,
            MilestoneType::START_DELIVERY,
            MilestoneType::END_DELIVERY,
        ], MilestoneType::getNonBaselineCases(false));
    }
}
