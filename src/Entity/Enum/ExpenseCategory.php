<?php

namespace App\Entity\Enum;

enum ExpenseCategory: string
{
    case FUND_CAPITAL = 'fund_capital';
    case LOCAL_CAPITAL_CONTRIBUTIONS = 'local_capital_contributions';
    case OTHER_CAPTIAL_CONTRIBUTIONS = 'other_capital_contributions';
    case FUND_RESOURCE = 'fund_resource';
}
