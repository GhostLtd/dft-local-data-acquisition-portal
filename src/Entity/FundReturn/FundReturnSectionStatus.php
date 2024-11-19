<?php

namespace App\Entity\FundReturn;

use App\Entity\Enum\CompletionStatus;
use App\Entity\Traits\IdTrait;
use App\Repository\FundReturn\FundReturnSectionStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FundReturnSectionStatusRepository::class)]
#[ORM\UniqueConstraint(columns: ['name', 'status'])]
class FundReturnSectionStatus
{
    use IdTrait;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(enumType: CompletionStatus::class)]
    private ?CompletionStatus $status = null;

    #[ORM\ManyToOne(inversedBy: 'sectionStatuses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FundReturn $fundReturn = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getStatus(): ?CompletionStatus
    {
        return $this->status;
    }

    public function setStatus(CompletionStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getFundReturn(): ?FundReturn
    {
        return $this->fundReturn;
    }

    public function setFundReturn(?FundReturn $fundReturn): static
    {
        $this->fundReturn = $fundReturn;
        return $this;
    }
}
