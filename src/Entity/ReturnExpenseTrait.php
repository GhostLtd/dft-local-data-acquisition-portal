<?php

namespace App\Entity;

use App\Utility\FinancialQuarter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait ReturnExpenseTrait
{
    public function __expenseConstruct(): void
    {
        $this->expenses = new ArrayCollection();
    }

    /**
     * @var Collection<int, ExpenseEntry>
     */
    #[ORM\ManyToMany(targetEntity: ExpenseEntry::class, cascade: ['persist'])]
    private Collection $expenses;

    /**
     * @var array<string>
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $expenseDivisionComments = [];

    /**
     * @return Collection<int, ExpenseEntry>
     */
    public function getExpenses(): Collection
    {
        return $this->expenses;
    }

    public function addExpense(ExpenseEntry $expense): static
    {
        if (!$this->expenses->contains($expense)) {
            $this->expenses->add($expense);
        }

        return $this;
    }

    public function removeExpense(ExpenseEntry $expense): static
    {
        $this->expenses->removeElement($expense);
        return $this;
    }

    public function setExpenseDivisionComment(string $divKey, ?string $comment): static
    {
        if (empty($comment)) {
            unset($this->expenseDivisionComments[$divKey]);
        } else {
            $this->expenseDivisionComments[$divKey] = $comment;
        }
        return $this;
    }

    public function getExpenseDivisionComment(string $divKey): ?string
    {
        return $this->expenseDivisionComments[$divKey] ?? null;
    }

    public function createExpensesForNextQuarter(Collection $sourceExpenses, int $currentYear, int $currentQuarter): Collection
    {
        $currentFQ = new FinancialQuarter($currentYear, $currentQuarter);
        return $sourceExpenses
            ->map(function(ExpenseEntry $e) use ($currentFQ) {
                $isCopyValue = $e->getColumn() !== 'forecast'
                    && (FinancialQuarter::createFromDivisionAndColumn($e->getDivision(), $e->getColumn())) <= $currentFQ;
                return (new ExpenseEntry())
                    ->setDivision($e->getDivision())
                    ->setColumn($e->getColumn())
                    ->setType($e->getType())
                    ->setValue($isCopyValue ? $e->getValue() : null)
                    ->setForecast(!$isCopyValue);
            });
    }
}