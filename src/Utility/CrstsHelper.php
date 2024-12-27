<?php

namespace App\Utility;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Config\ExpenseDivision\ColumnConfiguration;
use App\Config\ExpenseRow\CategoryConfiguration;
use App\Config\ExpenseRow\RowGroupInterface;
use App\Config\ExpenseRow\TotalConfiguration;
use App\Config\ExpenseRow\UngroupedConfiguration;
use App\Entity\Enum\ExpenseCategory;
use App\Entity\Enum\ExpenseType;
use Symfony\Component\Translation\TranslatableMessage;

class CrstsHelper
{
    /**
     * @return array<int, RowGroupInterface>
     */
    public static function getProjectExpenseRowsConfiguration(): array
    {
        return [
            new UngroupedConfiguration([
                ExpenseType::PROJECT_CAPITAL_SPEND_FUND,
                ExpenseType::PROJECT_CAPITAL_SPEND_ALL_SOURCES,
            ]),
        ];
    }

    /**
     * @return array<int, RowGroupInterface>
     */
    public static function getFundExpenseRowsConfiguration(): array
    {
        return [
            new CategoryConfiguration(
                ExpenseCategory::FUND_CAPITAL,
                [
                    ExpenseType::FUND_CAPITAL_EXPENDITURE,
                    ExpenseType::FUND_CAPITAL_EXPENDITURE_BASELINE,
                    ExpenseType::FUND_CAPITAL_EXPENDITURE_WITH_OVER_PROGRAMMING,
                    ExpenseType::FUND_CAPITAL_EXPENDITURE_WITH_OVER_PROGRAMMING_BASELINE,
                ]
            ),
            new CategoryConfiguration(
                ExpenseCategory::LOCAL_CAPITAL_CONTRIBUTIONS,
                [
                    ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION,
                    ExpenseType::FUND_CAPITAL_THIRD_PARTY_CONTRIBUTION,
                    new TotalConfiguration('SubTotal', [
                            ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION,
                            ExpenseType::FUND_CAPITAL_THIRD_PARTY_CONTRIBUTION,
                        ],
                        new TranslatableMessage('forms.crsts.expenses.sub_total')
                    ),
                    ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION_BASELINE,
                ]
            ),
            new CategoryConfiguration(
                ExpenseCategory::OTHER_CAPTIAL_CONTRIBUTIONS,
                [
                    ExpenseType::FUND_CAPITAL_OTHER,
                ]
            ),
            new TotalConfiguration('Total', [
                ExpenseType::FUND_CAPITAL_EXPENDITURE,
                ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION,
                ExpenseType::FUND_CAPITAL_THIRD_PARTY_CONTRIBUTION,
                ExpenseType::FUND_CAPITAL_OTHER,
            ], new TranslatableMessage('forms.crsts.expenses.total')),
            new CategoryConfiguration(
                ExpenseCategory::FUND_RESOURCE,
                [
                    ExpenseType::FUND_RESOURCE_EXPENDITURE,
                ]
            ),
        ];
    }

    /**
     * @return array<int, DivisionConfiguration>
     */
    public static function getExpenseDivisionConfigurations(int $returnYear, int $returnQuarter): array
    {
        $naturalEndYear = 2026;

        $divisionConfiguration = [];

        // If we're still filling out a return later than 2026 then
        // we'll need "actual" expenses for up until the current year
        $endYear = max($naturalEndYear, 2026);

        foreach(range(2022, $endYear) as $year) {
            $nextYear = substr(strval($year + 1), 2);

            $columnConfigurations = [];

            foreach([1, 2, 3, 4] as $quarter) {
                $isFuture = ($year > $returnYear) || ($year === $returnYear && $quarter > $returnQuarter);

                if (!$isFuture) {
                    $columnConfigurations[] =
                        new ColumnConfiguration(
                            "Q{$quarter}",
                            isForecast: false,
                            label: new TranslatableMessage("forms.crsts.expenses.Q{$quarter}", [
                                'actual_or_forecast' => new TranslatableMessage('forms.crsts.expenses.actual_or_forecast', ['is_forecast' => 'false']),
                            ])
                        );
                }
            }

            if ($returnQuarter === 4 && empty($columnConfigurations)) {
                // The yearly forecast is only shown if:
                //   a) The return is for Q4
                //   b) The year has no active quarters in it

                $columnConfigurations[] =
                    new ColumnConfiguration('yearly', isForecast: true, label: new TranslatableMessage('forms.crsts.expenses.yearly_forecast'));
            }


            if (count($columnConfigurations) > 0) {
                $divisionConfiguration[] = new DivisionConfiguration(
                    "{$year}-{$nextYear}",
                    $columnConfigurations,
                    label: new TranslatableMessage('forms.crsts.expenses.division_year_title', ['startYear' => $year, 'endYear' => $nextYear]),
                );
            }
        }

        if ($returnQuarter === 4) {
            // This is a forecast and only added in Q4
            $nextYear = substr(strval($endYear + 1), 2);
            $divisionConfiguration[] = new DivisionConfiguration(
                "post-{$endYear}-{$nextYear}",
                [
                    new ColumnConfiguration("forecast", isForecast: true, label: new TranslatableMessage('forms.crsts.expenses.forecast'))
                ],
                label: new TranslatableMessage('forms.crsts.expenses.division_post_title', ['startYear' => $endYear, 'endYear' => $nextYear])
            );
        }

        return $divisionConfiguration;
    }

}