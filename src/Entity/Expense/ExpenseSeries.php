<?php

namespace App\Entity\Expense;

use App\Entity\Enum\ExpenseType;
use App\Entity\Traits\IdTrait;
use App\Repository\Expense\ExpenseSeriesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseSeriesRepository::class)]
class ExpenseSeries
{
    use IdTrait;

    /**
     * @var Collection<int, ExpenseEntry>
     */
    #[ORM\OneToMany(targetEntity: ExpenseEntry::class, mappedBy: 'series', orphanRemoval: true)]
    private Collection $entries;

    #[ORM\Column(enumType: ExpenseType::class)]
    private ?ExpenseType $type = null;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    /**
     * @return Collection<int, ExpenseEntry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(ExpenseEntry $entry): static
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setSeries($this);
        }

        return $this;
    }

    public function removeEntry(ExpenseEntry $entry): static
    {
        if ($this->entries->removeElement($entry)) {
            // set the owning side to null (unless already changed)
            if ($entry->getSeries() === $this) {
                $entry->setSeries(null);
            }
        }

        return $this;
    }

    public function getType(): ?ExpenseType
    {
        return $this->type;
    }

    public function setType(ExpenseType $type): static
    {
        $this->type = $type;

        return $this;
    }
}
