<?php

namespace App\Entity\Enum;

enum BenefitCostRatioType: string
{
    case NA = 'na';
    case TBC = 'tbc';
    case VALUE = 'value';
}
