<?php

namespace App\Validator\Constraint;

use Ghost\GovUkCoreBundle\Validator\Constraint\Decimal;

#[\Attribute(\Attribute::TARGET_CLASS)]
class BenefitCostRatio extends Decimal
{
    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT];
    }
}
