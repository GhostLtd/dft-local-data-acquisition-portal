<?php

namespace App\Utility;

use App\Entity\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Config\ExpenseDivision\SubDivisionConfiguration;
use App\Entity\Config\ExpenseRow\CategoryConfiguration;
use App\Entity\Config\ExpenseRow\RowGroupInterface;
use App\Entity\Config\ExpenseRow\TotalConfiguration;
use App\Entity\Enum\ExpenseCategory;
use App\Entity\Enum\ExpenseType;

class CrstsHelper
{
    /**
     * @return array<int, RowGroupInterface>
     */
    public static function getExpenseRowsConfiguration(): array
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
                    new TotalConfiguration('forms.crsts.expenses.sub_total', [
                        ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION,
                        ExpenseType::FUND_CAPITAL_THIRD_PARTY_CONTRIBUTION,
                    ]),
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
            ]),
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

            $subDivisionConfigurations = [];

            foreach([1, 2, 3, 4] as $quarter) {
                $isFuture = ($year > $returnYear) || ($year === $returnYear && $quarter > $returnQuarter);

                if (!$isFuture) {
                    $subDivisionConfigurations[] =
                        new SubDivisionConfiguration("Q{$quarter}", isForecast: false);
                }
            }

            if ($returnQuarter === 4 && empty($subDivisionConfigurations)) {
                // The yearly forecast is only shown if:
                //   a) The return is for Q4
                //   b) The year has no active quarters in it

                $subDivisionConfigurations[] =
                    new SubDivisionConfiguration('Yearly forecast', isForecast: true);
            }


            if (count($subDivisionConfigurations) > 0) {
                $divisionConfiguration[] = new DivisionConfiguration("{$year}/{$nextYear}", $subDivisionConfigurations);
            }
        }

        if ($returnQuarter === 4) {
            // This is a forecast and only added in Q4
            $postTitle = "Post-{$endYear}/" . substr(strval($endYear + 1), 2);
            $divisionConfiguration[] = new DivisionConfiguration($postTitle, [
                new SubDivisionConfiguration("Forecast", isForecast: true),
            ]);
        }

        return $divisionConfiguration;
    }

}