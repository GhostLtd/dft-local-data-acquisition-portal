<?php

namespace App\Entity;

use App\Entity\Enum\CompletionStatus;

interface SectionStatusInterface
{
    public function getStatus(): ?CompletionStatus;
    public function setStatus(CompletionStatus $status): static;

    public function getStatusAsString(): ?string;
    public function setStatusAsString(string $status): static;

}