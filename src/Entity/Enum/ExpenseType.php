<?php

namespace App\Entity\Enum;

enum ExpenseType: string
{
    case FUND_CAPITAL_EXPENDITURE = "fund_capital_expenditure";
    case FUND_CAPITAL_EXPENDITURE_BASELINE = "fund_capital_expenditure_baseline";
    case FUND_CAPITAL_EXPENDITURE_WITH_OVER_PROGRAMMING = "fund_capital_expenditure_inc_over_programming";
    case FUND_CAPITAL_LOCAL_CONTRIBUTION = "fund_capital_local_contribution";
    case FUND_CAPITAL_THIRD_PARTY_CONTRIBUTION = "fund_capital_third_party_contribution";
    case FUND_CAPITAL_OTHER = "fund_capital_other";
    case FUND_CAPITAL_TOTAL = "fund_total_capital_expenditure";
    case FUND_RESOURCE_TOTAL = "fund_total_resource_expenditure";
    case FUND_TOTAL = "fund_total_expenditure";

    case PROJECT_CAPITAL_SPEND_FUND = "project_capital_spend_fund";
    case PROJECT_CAPITAL_SPEND_ALL_SOURCES = "project_capital_spend_all_sources";

    /**
     * @return array<ExpenseType>
     */
    public static function filterForFund(): array
    {
        return self::filterByPrefix('fund_');
    }

    /**
     * @return array<ExpenseType>
     */
    public static function filterForProject(): array
    {
        return self::filterByPrefix('project_');
    }

    /**
     * @return array<ExpenseType>
     */
    protected static function filterByPrefix(string $prefix): array
    {
        return array_values(
            array_filter(self::cases(), fn(\UnitEnum $e) => str_starts_with($e->value, $prefix))
        );
    }
}
