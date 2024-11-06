<?php

namespace App\DataFixtures\Definition;

use App\DataFixtures\Definition\FundReturn\CrstsFundReturnDefinition;
use App\Entity\Enum\Fund;
use App\Entity\FundReturn\FundReturn;

class FundAwardDefinition
{
    /**
     * @param array<FundReturn> $returns
     */
    public function __construct(
        protected Fund $fund,
        protected array $returns = []
    ) {
        $expectedReturnType = match($fund) {
            Fund::CRSTS => CrstsFundReturnDefinition::class,
        };

        foreach($returns as $return) {
            $actualReturnType = $return::class;
            if ($actualReturnType !== $expectedReturnType) {
                throw new \RuntimeException("Invalid FundAward({$fund->name}); expected all returns to be of type {$expectedReturnType}, but found an instance of {$actualReturnType}");
            }
        }
    }

    public function getFund(): Fund
    {
        return $this->fund;
    }

    public function getReturns(): array
    {
        return $this->returns;
    }
}
