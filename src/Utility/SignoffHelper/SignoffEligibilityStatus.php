<?php

namespace App\Utility\SignoffHelper;

readonly class SignoffEligibilityStatus
{
    public function __construct(
        public bool $isEligible,

        /** @var array<EligibilityProblem> */
        public array $problems = [],
    ) {}

    public function getProblemsByType(): array
    {
        $byType = [];

        foreach($this->problems as $problem) {
            $byType[$problem->type->value] ??= [];
            $byType[$problem->type->value][] = $problem;
        }

        return $byType;
    }
}
