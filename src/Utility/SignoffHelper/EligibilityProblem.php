<?php

namespace App\Utility\SignoffHelper;

readonly class EligibilityProblem
{
    public function __construct(
        public EligibilityProblemType $type,
        public string                 $message,
        public array                  $messageParameters = [],
        public string                 $messageDomain = 'messages',
        public ?string                $url = null,
    ) {}
}
