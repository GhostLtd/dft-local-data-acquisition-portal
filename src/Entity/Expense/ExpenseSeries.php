<?php

namespace App\Entity\Expense;

use App\Entity\Traits\IdTrait;
use App\Repository\Expense\ExpenseSeriesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseSeriesRepository::class)]
class ExpenseSeries
{
    use IdTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?ExpenseType $type = null;

    public function getType(): ?ExpenseType
    {
        return $this->type;
    }

    public function setType(?ExpenseType $type): static
    {
        $this->type = $type;
        return $this;
    }
}
