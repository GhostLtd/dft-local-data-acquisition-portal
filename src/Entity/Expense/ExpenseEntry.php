<?php

namespace App\Entity\Expense;

use App\Entity\Traits\IdTrait;
use App\Repository\Expense\ExpenseEntryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseEntryRepository::class)]
class ExpenseEntry
{
    use IdTrait;

    #[ORM\Column(length: 255)]
    private ?string $description = null; // 2proj_exp (e.g. "2022/23 Q1 Actual" - the column headers from the "Programme expenditure" table)

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $value = null; // 2proj_exp (e.g. "£9,200,000" - the values entered into the "Programme expenditure" table)

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ExpenseSeries $series = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getSeries(): ?ExpenseSeries
    {
        return $this->series;
    }

    public function setSeries(?ExpenseSeries $series): static
    {
        $this->series = $series;

        return $this;
    }
}
