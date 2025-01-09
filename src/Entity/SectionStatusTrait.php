<?php

namespace App\Entity;

use App\Entity\Enum\CompletionStatus;
use Doctrine\ORM\Mapping as ORM;

trait SectionStatusTrait
{
    #[ORM\Column(enumType: CompletionStatus::class)]
    private ?CompletionStatus $status = null;

    public function getStatus(): ?CompletionStatus
    {
        return $this->status;
    }

    public function setStatus(CompletionStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getStatusAsString(): ?string
    {
        return $this->status->value;
    }

    public function setStatusAsString(string $status): static
    {
        $this->status = CompletionStatus::from($status);
        return $this;
    }

}