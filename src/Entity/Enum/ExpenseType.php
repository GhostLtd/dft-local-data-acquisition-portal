<?php

namespace App\Entity\Enum;

enum ExpenseType: string
{
    case CAPITAL_EXPENDITURE = "capital_expenditure";
    case CAPITAL_EXPENDITURE_BASELINE = "capital_expenditure_baseline";
    case CAPITAL_EXPENDITURE_WITH_OVER_PROGRAMMING = "capital_expenditure_inc_over_programming";
    case CAPITAL_LOCAL_CONTRIBUTION = "capital_local_contribution";
    case CAPITAL_THIRD_PARTY_CONTRIBUTION = "capital_third_party_contribution";
    case CAPITAL_OTHER = "capital_other";
    case CAPITAL_TOTAL = "total_capital_expenditure";
    case RESOURCE_TOTAL = "total_resource_expenditure";
    case TOTAL = "total_expenditure";
}
