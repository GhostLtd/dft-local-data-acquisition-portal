<?php

namespace App\DataFixtures\Definition\SchemeReturn;

abstract class AbstractSchemeReturnDefinition
{
    public function __construct(
        protected ?string $risks,
    ) {}

    public function getRisks(): ?string
    {
        return $this->risks;
    }
}
