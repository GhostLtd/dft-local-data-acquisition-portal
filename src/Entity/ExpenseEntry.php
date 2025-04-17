<?php

namespace App\Entity;

use App\Entity\Enum\ExpenseType;
use App\Entity\Traits\IdTrait;
use App\Repository\ExpenseEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ghost\GovUkCoreBundle\Validator\Constraint\Decimal;

#[ORM\Entity(repositoryClass: ExpenseEntryRepository::class)]
class ExpenseEntry implements PropertyChangeLoggableInterface
{
    use IdTrait;

    #[ORM\Column(enumType: ExpenseType::class)]
    private ?ExpenseType $type = null;

    #[ORM\Column(length: 16)]
    private ?string $division = null;

    #[ORM\Column(name: 'col', length: 16)]
    private ?string $column = null;

    // N.B. See ExpenseValidator
    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    private ?string $value = null;

    #[ORM\Column]
    private ?bool $forecast = null;

    public function getType(): ?ExpenseType
    {
        return $this->type;
    }

    public function setType(ExpenseType $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getDivision(): ?string
    {
        return $this->division;
    }

    public function setDivision(string $division): static
    {
        $this->division = $division;
        return $this;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }

    public function setColumn(string $column): static
    {
        $this->column = $column;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function isForecast(): ?bool
    {
        return $this->forecast;
    }

    public function setForecast(bool $forecast): static
    {
        $this->forecast = $forecast;
        return $this;
    }
}
