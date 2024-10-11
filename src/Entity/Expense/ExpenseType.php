<?php

namespace App\Entity\Expense;

use App\Entity\Traits\IdTrait;
use App\Repository\Expense\ExpenseTypeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseTypeRepository::class)]
class ExpenseType
{
    use IdTrait;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }
}
