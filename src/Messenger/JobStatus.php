<?php

namespace App\Messenger;

use App\Entity\Enum\JobState;

class JobStatus
{
    protected \DateTime $timestamp;

    public function __construct(
        protected JobState $state,
        protected ?string  $errorMessage = null,
        protected array    $context = []
    ) {
        $this->timestamp = new \DateTime();
    }

    public function getState(): JobState
    {
        return $this->state;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
