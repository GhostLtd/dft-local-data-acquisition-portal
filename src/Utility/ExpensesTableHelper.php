<?php

namespace App\Utility;

use App\Entity\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Config\ExpenseRow\CategoryConfiguration;
use App\Entity\Config\ExpenseRow\RowGroupInterface;
use App\Entity\Config\ExpenseRow\TotalConfiguration;
use App\Entity\Config\Table\Cell;
use App\Entity\Config\Table\Header;
use App\Entity\Config\Table\Row;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\Fund;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Creates the logical structure for an expenses table based upon the rowGroupConfiguration (row specification) and
 * the divisionConfiguration (column specification)
 *
 * This can then be passed to ExpensesTable to create the corresponding form, twig to create the corresponding HTML
 * representation, and ExpensesDataMapper to correctly map forms<->entities (all whilst keeping the complex logic
 * in one place)
 */
class ExpensesTableHelper
{
    /**
     * @var array<int, RowGroupInterface>
     */
    protected array $rowGroupConfigurations;

    protected DivisionConfiguration $divisionConfiguration;
    protected Fund $fund;

    protected array $cache = [];

    public function __construct(
        protected TranslatorInterface $translator,
    ) {}

    /**
     * @param array<int, RowGroupInterface> $rowGroupConfigurations
     */
    public function setRowGroupConfigurations(array $rowGroupConfigurations): static
    {
        $this->rowGroupConfigurations = $rowGroupConfigurations;
        return $this;
    }

    public function setDivisionConfiguration(DivisionConfiguration $divisionConfiguration): static
    {
        $this->divisionConfiguration = $divisionConfiguration;
        return $this;
    }

    public function setFund(Fund $fund): ExpensesTableHelper
    {
        $this->fund = $fund;
        return $this;
    }

    /**
     * @return array<int, Row>
     */
    public function getTableRows(): array
    {
        $cacheKey = $this->fund->value.'-'.$this->divisionConfiguration->getSlug();
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $tableRows = [];

        $divSlug = $this->divisionConfiguration->getSlug();

        $fundName = $this->translator->trans("enum.fund.{$this->fund->value}");
        $actualOrForecast = [
            false => $this->translator->trans('forms.crsts.expenses.actual'),
            true => $this->translator->trans('forms.crsts.expenses.forecast'),
        ];
        $totalTitle = $this->translator->trans('forms.crsts.expenses.total');

        // Table header row...
        // --------------------------------------------------------------------------------

        $cells = [
            new Header(['colspan' => 2])
        ];

        foreach($this->divisionConfiguration->getSubDivisionConfigurations() as $subDiv) {
            $forecastOrActual = $subDiv->isForecast() ? 'forecast' : 'actual';
            $cells[] = new Header([
                'text' => $subDiv->getTitle(),
            ], [
                'add_actual_or_forecast' => true,
                'text' => $this->translator->trans("forms.crsts.expenses.{$forecastOrActual}")
            ]);
        }

        if ($this->divisionConfiguration->shouldHaveTotal()) {
            $cells[] = new Header(['text' => $totalTitle]);
        }

        $tableRows[] = new Row($cells, ['classes' => 'header_row']);

        // Table data rows...
        // --------------------------------------------------------------------------------
        foreach($this->rowGroupConfigurations as $group) {
            $cells = [];

            // groups comprise either categories (e.g. "CRSTS Capital") or totals
            if ($group instanceof CategoryConfiguration) {
                foreach($group->getRowConfigurations() as $idx => $row) {
                    if ($idx === 0) {
                        $cells[] = new Header([
                            'text' => $this->translator->trans("enum.expense_category.{$group->getCategory()->value}", [
                                'fund' => $fundName,
                            ]),
                            'rowspan' => $group->rowCount(),
                        ]);
                    }

                    // categories comprise rows of either expenses (e.g. "Q1 Actual") or totals
                    if ($row instanceof TotalConfiguration) {
                        $rowSlug = $row->getSlug();
                        $title = $this->translator->trans($row->getTitle());
                        $disabled = true;
                        $attributes = [
                            'total_rows_to_sum' => $row->getSlugsOfRowsToSum(),
                            'row_slug' => $rowSlug,
                        ];
                        $isPossiblyADataCell = false;
                    } else if ($row instanceof ExpenseType) {
                        $rowSlug = $row->value;
                        $title = $this->translator->trans("enum.expense_type.{$row->value}", ['fund' => $fundName]);
                        $disabled = $row->isBaseline();
                        $attributes = [
                            'division' => $divSlug,
                            'expense_type' => $row,
                            'row_slug' => $row->value,
                        ];
                        $isPossiblyADataCell = true;
                    } else {
                        // Not actually possible, but catch to keep PHPStorm happy
                        throw new \RuntimeException('Unexpected row configuration');
                    }

                    $subDivisionConfigurations = $this->divisionConfiguration->getSubDivisionConfigurations();

                    $cells[] = new Header([
                        'text' => $title,
                    ]);

                    foreach($subDivisionConfigurations as $subDiv) {
                        $colSlug = $subDiv->getSlug();

                        $cells[] = new Cell([
                            'disabled' => $disabled,
                            'key' => "expense__{$divSlug}__{$rowSlug}__{$colSlug}",
                            'text' => "{$title} {$subDiv->getTitle()} ({$actualOrForecast[$subDiv->isForecast()]})",
                        ], array_merge($attributes, [
                            'sub_division' => $colSlug,
                            'is_forecast' => $subDiv->isForecast(),
                            'is_data_cell' => $isPossiblyADataCell,
                        ]));
                    }

                    // If a row has more than one cell, it gets a row total cell
                    if ($this->divisionConfiguration->shouldHaveTotal()) {
                        $cells[] = new Cell([
                            'disabled' => true,
                            'key' => "expense__{$divSlug}__{$rowSlug}__total",
                            'text' => "{$title} {$totalTitle}",
                        ], array_merge($attributes, [
                            'sub_division' => 'total',
                            'is_row_total' => true,
                        ]));
                    }

                    if (!empty($cells)) {
                        $tableRows[] = new Row($cells);
                        $cells = [];
                    }
                }
            } else if ($group instanceof TotalConfiguration) {
                $rowSlug = $group->getSlug();
                $title = $this->translator->trans($group->getTitle());

                $cells = [
                    new Header(['text' => $title, 'colspan' => 2]),
                ];

                foreach($this->divisionConfiguration->getSubDivisionConfigurations() as $subDiv) {
                    $colSlug = $subDiv->getSlug();

                    $cells[] = new Cell([
                        'disabled' => true,
                        'key' => "expense__{$rowSlug}__{$colSlug}",
                        'text' => "{$title} {$subDiv->getTitle()}",
                    ], [
                        'sub_division' => $colSlug,
                        'is_forecast' => false,
                        'is_data_cell' => false,
                        'total_rows_to_sum' => $group->getSlugsOfRowsToSum(),
                        'row_slug' => $rowSlug,
                    ]);
                }

                // If a row has more than one cell, it gets a row total cell
                if ($this->divisionConfiguration->shouldHaveTotal()) {
                    $cells[] = new Cell([
                        'disabled' => true,
                        'key' => "expense__{$rowSlug}__total",
                        'text' => "{$title} {$totalTitle}",
                    ], [
                        'sub_division' => 'total',
                        'is_row_total' => true,
                        'row_slug' => $rowSlug,
                    ]);
                }

                $tableRows[] = new Row($cells);
            }
        }

        $this->cache[$cacheKey] = $tableRows;
        return $tableRows;
    }

    /**
     * @return array<int, RowGroupInterface>
     */
    public function getRowGroupConfigurations(): array
    {
        return $this->rowGroupConfigurations;
    }

    public function getDivisionConfiguration(): DivisionConfiguration
    {
        return $this->divisionConfiguration;
    }

    public function getFund(): Fund
    {
        return $this->fund;
    }

    /**
     * @return array<int, ExpenseType>
     */
    public function getExpenseTypes(): array
    {
        return array_merge(...array_map(
            fn(CategoryConfiguration $category) => $category->getExpenseTypes(),
            array_filter($this->rowGroupConfigurations, fn(RowGroupInterface $r) => $r instanceof CategoryConfiguration)
        ));
    }

    /**
     * @return array<int, Cell>
     */
    public function getAllCells(): array
    {
        return array_merge(...array_map(
            fn(Row $row) => array_filter($row->getCells(), fn($cell) => $cell instanceof Cell),
            $this->getTableRows()
        ));
    }
}
