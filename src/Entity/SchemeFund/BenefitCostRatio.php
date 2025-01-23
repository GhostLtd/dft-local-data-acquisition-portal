<?php

namespace App\Entity\SchemeFund;

use App\Entity\Enum\BenefitCostRatioType;
use Brick\Math\BigDecimal;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Validator\Constraint as AppAssert;

#[ORM\Embeddable]
#[AppAssert\BenefitCostRatio(precision: 10, scale: 2, groups: ['scheme_details'])]
class BenefitCostRatio
{
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $value = null;

    #[ORM\Column(length: 5, nullable: true, enumType: BenefitCostRatioType::class)]
    private ?BenefitCostRatioType $type = null;

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function getValueAsBigDecimal(): ?BigDecimal
    {
        return $this->value === null ? null : BigDecimal::of($this->value);
    }

    public function setValue(BigDecimal|string|null $value): static
    {
        if ($value instanceof BigDecimal) {
            $value = strval($value);
        }

        $this->value = $value;
        return $this;
    }

    public function getType(): ?BenefitCostRatioType
    {
        return $this->type;
    }

    public function setType(?BenefitCostRatioType $type): static
    {
        $this->type = $type;
        return $this;
    }
}
