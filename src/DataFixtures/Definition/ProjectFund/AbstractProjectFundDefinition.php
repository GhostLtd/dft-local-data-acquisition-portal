<?php

namespace App\DataFixtures\Definition\ProjectFund;

use App\Entity\Enum\Fund;

abstract class AbstractProjectFundDefinition
{
    abstract public function getFund(): Fund;
}
