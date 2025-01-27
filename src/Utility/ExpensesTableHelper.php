<?php

namespace App\Utility;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\ExpenseRow\RowGroupInterface;
use App\Config\ExpenseRow\TotalConfiguration;
use App\Config\ExpenseRow\UngroupedConfiguration;
use App\Config\Table\Cell;
use App\Config\Table\Header;
use App\Config\Table\Row;
use App\Config\Table\Table;
use App\Config\Table\TableBody;
use App\Config\Table\TableHead;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\Fund;
use Symfony\Component\Translation\TranslatableMessage;

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

    public function getTable(): Table
    {
        $cacheKey = $this->fund->value . '-' . $this->divisionConfiguration->getKey();
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $tableHeadAndBodies = [];

        $divKey = $this->divisionConfiguration->getKey();

        $totalTitle = new TranslatableMessage('forms.crsts.expenses.total');
        $extraParameters = ['fund' => new TranslatableMessage("enum.fund.{$this->fund->value}")];

        $hasCategories = false;
        foreach($this->rowGroupConfigurations as $rowGroupConfiguration) {
            if ($rowGroupConfiguration instanceof CategoryConfiguration) {
                $hasCategories = true;
                break;
            }
        }

        // Table header row...
        // --------------------------------------------------------------------------------

        $cells = [
            new Header($hasCategories ? ['colspan' => 2] : [])
        ];

        foreach($this->divisionConfiguration->getColumnConfigurations() as $subDiv) {
            $cells[] = new Header([
                'text' => $subDiv->getLabel($extraParameters),
                'classes' => 'number_column',
            ]);
        }

        if ($this->divisionConfiguration->shouldHaveTotal()) {
            $cells[] = new Header([
                'text' => $totalTitle,
                'classes' => 'number_column'
            ]);
        }

        $tableHeadAndBodies[] = new TableHead([new Row($cells)]);

        // Table data rows...
        // --------------------------------------------------------------------------------
        foreach($this->rowGroupConfigurations as $group) {
            $cells = [];
            $rowHasGroupHeader = false;

            $tableRows = [];

            // groups comprise either categories (e.g. "CRSTS Capital") or totals
            if ($group instanceof CategoryConfiguration || $group instanceof UngroupedConfiguration) {
                $isSingleRowGroup = count($group->getRowConfigurations()) === 1;

                foreach($group->getRowConfigurations() as $idx => $row) {
                    if ($group instanceof CategoryConfiguration) {
                        $groupLabel = $group->getLabel($extraParameters);

                        if ($idx === 0) {
                            if ($isSingleRowGroup) {
                                $cells[] = new Header([
                                    'text' => $groupLabel,
                                    'colspan' => 2,
                                ]);
                            } else {
                                $tableRows[] = new Row([
                                    new Header([
                                        'text' => $groupLabel,
                                        'colspan' => 7,
                                        'classes' => 'group-header',
                                    ])
                                ]);
                                $rowHasGroupHeader = true;
                            }
                        }

                        if ($rowHasGroupHeader) {
                            $cells[] = new Header([
                                'text' => '',
                                'classes' => 'spacer',
                            ]);
                        }
                    } else {
                        $groupLabel = '';
                    }

                    // categories comprise rows of either expenses (e.g. "Q1 Actual") or totals
                    if ($row instanceof TotalConfiguration) {
                        $rowKey = $row->getKey();
                        $disabled = true;
                        $attributes = [
                            'total_rows_to_sum' => $row->getKeysOfRowsToSum(),
                            'row_key' => $rowKey,
                        ];
                        $isPossiblyADataCell = false;
                    } else if ($row instanceof ExpenseType) {
                        $rowKey = $row->value;
                        $disabled = $row->isBaseline();
                        $attributes = [
                            'division' => $divKey,
                            'expense_type' => $row,
                            'row_key' => $row->value,
                        ];
                        $isPossiblyADataCell = true;
                    } else {
                        // Not actually possible, but catch to keep PHPStorm happy
                        throw new \RuntimeException('Unexpected row configuration');
                    }

                    $columnConfigurations = $this->divisionConfiguration->getColumnConfigurations();

                    $rowTitle = $row->getLabel($extraParameters);

                    if (!$isSingleRowGroup) {
                        $cells[] = new Header([
                            'text' => $rowTitle,
                        ]);
                    }

                    $isOdd = true;
                    foreach($columnConfigurations as $subDiv) {
                        $colKey = $subDiv->getKey();
                        $cells[] = new Cell([
                            'disabled' => $disabled,
                            'key' => "expense__{$divKey}__{$rowKey}__{$colKey}",
                            'text' => $this->cellTitle($rowTitle, $subDiv->getLabel($extraParameters), $groupLabel),
                            'classes' => $isOdd ? 'odd' : 'even',
                        ], array_merge($attributes, [
                            'col_key' => $colKey,
                            'is_forecast' => $subDiv->isForecast(),
                            'is_data_cell' => $isPossiblyADataCell,
                        ]));

                        $isOdd = !$isOdd;
                    }

                    // If a row has more than one cell, it gets a row total cell
                    if ($this->divisionConfiguration->shouldHaveTotal()) {
                        $cells[] = new Cell([
                            'disabled' => true,
                            'key' => "expense__{$divKey}__{$rowKey}__total",
                            'text' => $this->cellTitle($rowTitle, $totalTitle, $groupLabel),
                            'classes' => $isOdd ? 'odd' : 'even',
                        ], array_merge($attributes, [
                            'col_key' => 'total',
                            'is_row_total' => true,
                        ]));
                    }

                    if (!empty($cells)) {
                        $options = $group instanceof UngroupedConfiguration ? ['classes' => 'ungrouped'] : [];
                        $tableRows[] = new Row($cells, $options);
                        $cells = [];
                    }
                }
            } else if ($group instanceof TotalConfiguration) {
                $rowKey = $group->getKey();
                $rowTitle = $group->getLabel($extraParameters);

                $cells = [
                    new Header(['text' => $rowTitle, 'colspan' => 2]),
                ];

                $isOdd = true;
                foreach($this->divisionConfiguration->getColumnConfigurations() as $subDiv) {
                    $colKey = $subDiv->getKey();

                    $cells[] = new Cell([
                        'disabled' => true,
                        'key' => "expense__{$rowKey}__{$colKey}",
                        'text' => $this->cellTitle($rowTitle, $subDiv->getLabel($extraParameters)),
                        'classes' => $isOdd ? 'odd' : 'even',
                    ], [
                        'col_key' => $colKey,
                        'row_key' => $rowKey,
                        'is_forecast' => false,
                        'is_data_cell' => false,
                        'total_rows_to_sum' => $group->getKeysOfRowsToSum(),
                    ]);

                    $isOdd = !$isOdd;
                }

                // If a row has more than one cell, it gets a row total cell
                if ($this->divisionConfiguration->shouldHaveTotal()) {
                    $cells[] = new Cell([
                        'disabled' => true,
                        'key' => "expense__{$rowKey}__total",
                        'text' => $this->cellTitle($rowTitle, $totalTitle),
                        'classes' => $isOdd ? 'odd' : 'even',
                    ], [
                        'col_key' => 'total',
                        'is_row_total' => true,
                        'row_key' => $rowKey,
                    ]);
                }

                $tableRows[] = new Row($cells);
            }

            $tableHeadAndBodies[] = new TableBody($tableRows);
        }

        $this->cache[$cacheKey] = new Table($tableHeadAndBodies, ['classes' => 'expenses']);
        return $this->cache[$cacheKey];
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
            $this->getTable()->getRows()
        ));
    }

    protected function cellTitle(
        TranslatableMessage|string      $rowLabel,
        TranslatableMessage|string      $columnLabel,
        TranslatableMessage|string|null $groupLabel = null
    ): TranslatableMessage
    {
        return new TranslatableMessage('forms.crsts.expenses.cell_title', [
            'group' => $groupLabel ?? '',
            'row' => $rowLabel,
            'column' => $columnLabel,
        ]);
    }
}
