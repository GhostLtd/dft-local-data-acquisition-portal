<?php

namespace App\DataFixtures\Definition\FundReturn;

use App\DataFixtures\Definition\UserDefinition;

abstract class AbstractFundReturnDefinition
{
    public function __construct(
        protected ?UserDefinition $signoffUser = null,
        protected ?\DateTime      $signoffDate = null,
    ) {}

    public function getSignoffUser(): ?UserDefinition
    {
        return $this->signoffUser;
    }

    public function getSignoffDate(): ?\DateTime
    {
        return $this->signoffDate;
    }
}
