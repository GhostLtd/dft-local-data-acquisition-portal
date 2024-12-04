<?php

namespace App\Entity;

use App\Entity\Enum\ExpenseType;
use App\Entity\Traits\IdTrait;
use App\Repository\ExpenseEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseEntryRepository::class)]
class ExpenseEntry
{
    use IdTrait;

    #[ORM\Column(enumType: ExpenseType::class)]
    private ?ExpenseType $type = null;

    #[ORM\Column(length: 16)]
    private ?string $division = null;

    #[ORM\Column(length: 16)]
    private ?string $subDivision = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 0, nullable: true)]
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

    public function getSubDivision(): ?string
    {
        return $this->subDivision;
    }

    public function setSubDivision(string $subDivision): static
    {
        $this->subDivision = $subDivision;
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
