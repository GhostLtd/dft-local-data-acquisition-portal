<?php

namespace App\Validator\Constraint;

use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\ProjectFund\BenefitCostRatio as BenefitCostRatioEntity;
use Ghost\GovUkCoreBundle\Validator\Constraint\Decimal;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class BenefitCostRatioValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof BenefitCostRatio) {
            throw new UnexpectedTypeException($constraint, BenefitCostRatio::class);
        }

        if (!$value) {
            return;
        }

        if (!$value instanceof BenefitCostRatioEntity) {
            throw new UnexpectedValueException($value, BenefitCostRatioEntity::class);
        }

        $val = $value->getValue();
        $type = $value->getType();

        if ($val === null && $type === null) {
            return;
        }

        $validator = $this->context->getValidator()->inContext($this->context);

        // If "Value known" is selected, then validate that value...
        if ($type === BenefitCostRatioType::VALUE) {
            $validators = [
                new NotNull(['message' => "common.number.not-null"]),
                new Decimal(precision: 10, scale: 2),
            ];
            $validator->atPath('value')->validate($val, $validators, ['Default']);
        }
    }
}
