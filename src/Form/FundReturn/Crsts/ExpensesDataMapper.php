<?php

namespace App\Form\FundReturn\Crsts;

use App\Entity\Enum\ExpenseType;
use App\Entity\ExpenseEntry;
use App\Entity\ExpensesContainerInterface;
use App\Utility\ExpensesTableHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormInterface;

class ExpensesDataMapper implements DataMapperInterface
{
    protected ?ExpensesTableHelper $tableHelper = null;

    public function __construct(
        protected EntityManagerInterface $entityManager,
    ) {}

    public function setTableHelper(ExpensesTableHelper $tableHelper): ExpensesDataMapper
    {
        $this->tableHelper = $tableHelper;
        return $this;
    }

    public function mapDataToForms(mixed $viewData, \Traversable $forms): void
    {
        $this->checkCorrectlyInitialised();

        if (!$viewData instanceof ExpensesContainerInterface) {
            throw new UnexpectedTypeException($viewData, ExpensesContainerInterface::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $collection = $viewData->getExpenses();

        foreach($collection as $expense) {
            $divKey = $expense->getDivision();
            $expenseValue = $expense->getType()->value;

            $key = "expense__{$divKey}__{$expenseValue}__{$expense->getColumn()}";

            if (isset($forms[$key])) {
                $forms[$key]->setData($expense->getValue());
            }
        }
    }

    public function mapFormsToData(\Traversable $forms, mixed &$viewData): void
    {
        $this->checkCorrectlyInitialised();

        if (!$viewData instanceof ExpensesContainerInterface) {
            throw new UnexpectedTypeException($viewData, ExpensesContainerInterface::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        $touchedExpenses = [];

        $divKey = $this->tableHelper->getDivisionConfiguration()->getKey();

        // Update the expenses collection based upon the form data
        foreach($this->tableHelper->getAllCells() as $cell) {
            ['key' => $key] = $cell->getOptions();

            if (!$cell->getAttribute('is_data_cell')) {
                continue;
            }

            ['sub_division' => $subKey, 'expense_type' => $expenseType, 'is_forecast' => $isForecast] = $cell->getAttributes();
            $expenseEntry = $this->findExpenseEntry($viewData, $divKey, $subKey, $expenseType);

            $value = $forms[$key]?->getData();

            if (!$value) {
                if ($expenseEntry) {
                    $viewData->removeExpense($expenseEntry);
                    $this->entityManager->remove($expenseEntry);
                }
            } else {
                if (!$expenseEntry) {
                    $expenseEntry = (new ExpenseEntry())
                        ->setDivision($divKey)
                        ->setColumn($subKey)
                        ->setType($expenseType);

                    $viewData->addExpense($expenseEntry);
                    $this->entityManager->persist($expenseEntry);
                }

                // Remove commas, which get inserted by the javascript to enhance readability
                $value = str_replace(',', '', $value);

                $expenseEntry
                    ->setValue($value)
                    ->setForecast($isForecast);
            }

            $touchedExpenses[$key] = true;
        }

        // Remove any expenseEntries for this div (e.g. 2022/23) that we haven't updated
        // (another way: any expenseEntries that aren't expected base upon the given divisionConfiguration + types)
        foreach($viewData->getExpenses() as $expense) {
            if ($expense->getDivision() !== $divKey) {
                continue;
            }

            $key = "expense__{$divKey}__{$expense->getType()->value}__{$expense->getColumn()}";
            if (!isset($touchedExpenses[$key])) {
                $viewData->removeExpense($expense);
            }
        }
    }

    protected function findExpenseEntry(ExpensesContainerInterface $viewData, string $divKey, string $column, ExpenseType $expenseType): ?ExpenseEntry
    {
        foreach($viewData->getExpenses() as $expense) {
            if ($expense->getDivision() === $divKey &&
                $expense->getColumn() === $column &&
                $expense->getType() === $expenseType
            ) {
                return $expense;
            }
        }

        return null;
    }

    protected function checkCorrectlyInitialised(): void
    {
        if (!$this->tableHelper) {
            throw new \RuntimeException('DataMapper not correctly initialised - setTableMapper must be called before use');
        }
    }
}
