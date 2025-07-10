<?php

namespace App\Tests\Enum;

use App\Entity\Enum\MilestoneType;
use PHPUnit\Framework\TestCase;

class MilestoneTypeTest extends TestCase
{
    public function testBaselineCasesAll(): void
    {
        $this->assertEquals([
            MilestoneType::BASELINE_START_DEVELOPMENT,
            MilestoneType::BASELINE_END_DEVELOPMENT,
            MilestoneType::BASELINE_START_CONSTRUCTION,
            MilestoneType::BASELINE_END_CONSTRUCTION,
            MilestoneType::BASELINE_START_DELIVERY,
            MilestoneType::BASELINE_END_DELIVERY,
            MilestoneType::BASELINE_FINAL_DELIVERY,
        ], MilestoneType::getBaselineCases());
    }

    public function testBaselineCasesCDEL(): void
    {
        $this->assertEquals([
            MilestoneType::BASELINE_START_DEVELOPMENT,
            MilestoneType::BASELINE_END_DEVELOPMENT,
            MilestoneType::BASELINE_START_CONSTRUCTION,
            MilestoneType::BASELINE_END_CONSTRUCTION,
            MilestoneType::BASELINE_FINAL_DELIVERY,
        ], MilestoneType::getBaselineCases(isCDEL: true));
    }

    public function testBaselineCasesRDEL(): void
    {
        $this->assertEquals([
            MilestoneType::BASELINE_START_DEVELOPMENT,
            MilestoneType::BASELINE_END_DEVELOPMENT,
            MilestoneType::BASELINE_START_DELIVERY,
            MilestoneType::BASELINE_END_DELIVERY,
        ], MilestoneType::getBaselineCases(isCDEL: false));
    }

    public function testNonBaselineCasesAll(): void
    {
        $this->assertEquals([
            MilestoneType::START_DEVELOPMENT,
            MilestoneType::END_DEVELOPMENT,
            MilestoneType::START_CONSTRUCTION,
            MilestoneType::END_CONSTRUCTION,
            MilestoneType::START_DELIVERY,
            MilestoneType::END_DELIVERY,
            MilestoneType::FINAL_DELIVERY,
        ], MilestoneType::getNonBaselineCases());
    }

    public function testNonBaselineCasesCDEL(): void
    {
        $this->assertEquals([
            MilestoneType::START_DEVELOPMENT,
            MilestoneType::END_DEVELOPMENT,
            MilestoneType::START_CONSTRUCTION,
            MilestoneType::END_CONSTRUCTION,
            MilestoneType::FINAL_DELIVERY,
        ], MilestoneType::getNonBaselineCases(isCDEL: true));
    }

    public function testNonBaselineCasesRDEL(): void
    {
        $this->assertEquals([
            MilestoneType::START_DEVELOPMENT,
            MilestoneType::END_DEVELOPMENT,
            MilestoneType::START_DELIVERY,
            MilestoneType::END_DELIVERY,
        ], MilestoneType::getNonBaselineCases(isCDEL: false));
    }
}
