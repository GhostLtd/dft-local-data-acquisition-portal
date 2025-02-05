<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;

interface ExpensesContainerInterface
{
    /**
     * @return Collection<int, ExpenseEntry>
     */
    public function getExpenses(): Collection;
    public function addExpense(ExpenseEntry $expense): static;
    public function removeExpense(ExpenseEntry $expense): static;

    public function setExpenseDivisionComment(string $divKey, string $comment): static;

    public function getExpenseDivisionComment(string $divKey): ?string;
}
