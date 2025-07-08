<?php

namespace App\Utility;

use App\Config\ExpenseDivision\ColumnConfiguration;
use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Config\ExpenseDivision\TableConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\ExpenseRow\TotalConfiguration;
use App\Config\ExpenseRow\UngroupedConfiguration;
use App\Config\Table\Cell;
use App\Config\Table\Header;
use App\Config\Table\Row;
use App\Config\Table\Table;
use App\Config\Table\TableBody;
use App\Config\Table\TableHead;
use App\Entity\Enum\ExpenseType;
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
    protected array $cache = [];
    protected TableConfiguration $configuration;
    protected string $divisionKey;
    protected bool $editableBaselines = false;

    public function setConfiguration(TableConfiguration $configuration): static
    {
        $this->configuration = $configuration;
        return $this;
    }

    public function setDivisionKey(string $divisionKey): static
    {
        $this->divisionKey = $divisionKey;
        return $this;
    }

    public function getDivisionKey(): string
    {
        return $this->divisionKey;
    }

    public function setEditableBaselines(bool $editableBaselines): static
    {
        $this->editableBaselines = $editableBaselines;
        return $this;
    }

    public function getTable(): ?Table
    {
        $cacheKey = spl_object_id($this->configuration) . '-' . $this->divisionKey.($this->editableBaselines ? '-editable_baselines':'');

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $divisionConfiguration = $this->getDivisionConfigurationByKey($this->divisionKey);
        if (!$divisionConfiguration) {
            return null;
        }

        $tableHeadAndBodies = [];

        $totalTitle = new TranslatableMessage('forms.crsts.expenses.total');
        $extraParameters = $this->configuration->getExtraTranslationParameters();

        $hasCategories = false;
        foreach($this->configuration->getRowGroupConfigurations() as $rowGroupConfiguration) {
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

        foreach($divisionConfiguration->getColumnConfigurations() as $subDiv) {
            $cells[] = new Header([
                'text' => $subDiv->getLabel($extraParameters),
                'classes' => 'number_column',
            ]);
        }

        if ($divisionConfiguration->shouldHaveTotal()) {
            $cells[] = new Header([
                'text' => $totalTitle,
                'classes' => 'number_column'
            ]);
        }

        $tableHeadAndBodies[] = new TableHead([new Row($cells)]);

        // Table data rows...
        // --------------------------------------------------------------------------------
        foreach($this->configuration->getRowGroupConfigurations() as $group) {
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
                        $disabled = !$this->editableBaselines && $row->isBaseline();
                        $attributes = [
                            'division' => $this->divisionKey,
                            'expense_type' => $row,
                            'row_key' => $row->value,
                        ];
                        $isPossiblyADataCell = true;
                    } else {
                        // Not actually possible, but catch to keep PHPStorm happy
                        throw new \RuntimeException('Unexpected row configuration');
                    }

                    $columnConfigurations = $divisionConfiguration->getColumnConfigurations();

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
                            'key' => "expense__{$this->divisionKey}__{$rowKey}__{$colKey}",
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
                    if ($divisionConfiguration->shouldHaveTotal()) {
                        $cells[] = new Cell([
                            'disabled' => true,
                            'key' => "expense__{$this->divisionKey}__{$rowKey}__total",
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
                foreach($divisionConfiguration->getColumnConfigurations() as $subDiv) {
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
                if ($divisionConfiguration->shouldHaveTotal()) {
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
        return $this->configuration->getRowGroupConfigurations();
    }

    public function getRowCount(): int
    {
        $count = 0;
        foreach($this->getRowGroupConfigurations() as $groupConfiguration) {
            $count += $groupConfiguration instanceof CategoryConfiguration ? $groupConfiguration->rowCount() : 1;
        }
        return $count;
    }

    /**
     * @return \Generator<TotalConfiguration|ExpenseType>
     */
    public function walkRowConfigurations(): \Generator
    {
        foreach($this->getRowGroupConfigurations() as $rowGroupConfiguration) {
            if ($rowGroupConfiguration instanceof CategoryConfiguration) {
                foreach($rowGroupConfiguration->getRowConfigurations() as $rowConfiguration) {
                    yield $rowConfiguration;
                }
            } else if ($rowGroupConfiguration instanceof TotalConfiguration) {
                yield $rowGroupConfiguration;
            }
        }
    }

    /**
     * @template T
     * @param class-string<T> $type
     * @return \Generator<T>
     */
    public function getRowConfigurationsByType(string $type): \Generator
    {
        foreach($this->walkRowConfigurations() as $rowConfiguration) {
            if ($rowConfiguration::class === $type) {
                yield $rowConfiguration;
            }
        }
    }

    /**
     * @return array<string, int>
     */
    public function getRowIndexes(): array
    {
        static $rowIndexes = null;

        if ($rowIndexes === null) {
            $row = 0;
            $rowIndexes = [];
            foreach($this->walkRowConfigurations() as $rowConfiguration) {
                if ($rowConfiguration instanceof ExpenseType) {
                    $key = $rowConfiguration->value;
                } else if ($rowConfiguration instanceof TotalConfiguration) {
                    $key = $rowConfiguration->getKey();
                }

                $rowIndexes[$key] = $row++;
            }
        }

        return $rowIndexes;
    }

    public function getAbsoluteRowIndexFor(ExpenseType|TotalConfiguration $type): ?int
    {
        $key = $type instanceof ExpenseType ? $type->value : $type->getKey();
        return $this->getAbsoluteRowIndexForKey($key);
    }

    public function getAbsoluteRowIndexForKey(string $key): ?int
    {
        return $this->getRowIndexes()[$key] ?? null;
    }

    /**
     * @param array<int, DivisionConfiguration> $divisionConfigurations
     */
    public function getAbsoluteColumnIndexFor(array $divisionConfigurations, string $divisionKey, string $columnKey, bool $accountForTotalColumns=false): ?int
    {
        $columnIdx = 0;
        foreach($divisionConfigurations as $divisionConfiguration) {
            foreach($divisionConfiguration->getColumnConfigurations() as $columnConfiguration) {
                if (
                    $divisionConfiguration->getKey() === $divisionKey &&
                    $columnConfiguration->getKey() === $columnKey
                ) {
                    return $columnIdx;
                }

                $columnIdx++;
            }

            if ($accountForTotalColumns) {
                $columnIdx++;
            }
        }

        return null;
    }

    /**
     * @return array<int, ExpenseType>
     */
    public function getExpenseTypes(): array
    {
        return array_merge(...array_map(
            fn(CategoryConfiguration $category) => $category->getExpenseTypes(),
            array_filter($this->getRowGroupConfigurations(), fn(RowGroupInterface $r) => $r instanceof CategoryConfiguration)
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

    /**
     * @return array<int, DivisionConfiguration>
     */
    public function getDivisionConfigurations(): array
    {
        return $this->configuration->getDivisionConfigurations();
    }

    public function getDivisionConfiguration(): ?DivisionConfiguration
    {
        return $this->getDivisionConfigurationByKey($this->divisionKey);
    }

    public function getDivisionConfigurationByKey(string $divisionKey): ?DivisionConfiguration
    {
        foreach($this->getDivisionConfigurations() as $divisionConfiguration) {
            if ($divisionConfiguration->getKey() === $divisionKey) {
                return $divisionConfiguration;
            }
        }

        return null;
    }
}
