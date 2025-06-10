<?php

namespace App\Entity;

use App\Entity\Enum\ExpenseType;
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

    public function createExpensesForNextQuarter(Collection $sourceExpenses, FinancialQuarter $copyUpToAndIncluding): Collection
    {
        return $sourceExpenses
            ->map(function (ExpenseEntry $e) use ($copyUpToAndIncluding) {
                if ($e->getType()->isBaseline()) {
                    $isCopyValue = true;
                } else if ($e->getDivision() === 'post-2026-27') {
                    $isCopyValue = false; // We shouldn't be issuing returns past this date in any case
                } else {
                    $entryFQ = FinancialQuarter::createFromDivisionAndColumn($e->getDivision(), $e->getColumn());
                    $isCopyValue = $entryFQ <= $copyUpToAndIncluding;
                }

                return (new ExpenseEntry())
                    ->setDivision($e->getDivision())
                    ->setColumn($e->getColumn())
                    ->setType($e->getType())
                    ->setValue($isCopyValue ? $e->getValue() : null)
                    ->setForecast(!$isCopyValue);
            });
    }

    public function getExpenseWithSameDivisionTypeAndColumnAs(ExpenseEntry $other): ?ExpenseEntry
    {
        foreach($this->expenses as $expense) {
            if ($expense->hasSameDivisionTypeAndColumnAs($other)) {
                return $expense;
            }
        }

        return null;
    }

    public function getExpenseByDivisionColumnAndType(string $divisionKey, string $columnKey, ExpenseType $type): ?ExpenseEntry
    {
        foreach($this->expenses as $expense) {
            if (
                $expense->getDivision() === $divisionKey
                && $expense->getColumn() === $columnKey
                && $expense->getType() === $type
            ) {
                return $expense;
            }
        }

        return null;
    }
}
