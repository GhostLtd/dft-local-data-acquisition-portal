<?php

namespace App\DataFixtures\Definition\SchemeFund;

use App\Entity\Enum\Fund;

abstract class AbstractSchemeFundDefinition
{
    abstract public function getFund(): Fund;
}
