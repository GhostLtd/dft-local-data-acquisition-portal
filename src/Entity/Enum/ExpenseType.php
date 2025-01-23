<?php

namespace App\Entity\Enum;

use App\Config\LabelProviderInterface;
use Symfony\Component\Translation\TranslatableMessage;

enum ExpenseType: string implements LabelProviderInterface
{
    case FUND_CAPITAL_EXPENDITURE = "fex";
    case FUND_CAPITAL_EXPENDITURE_BASELINE = "feb";
    case FUND_CAPITAL_EXPENDITURE_WITH_OVER_PROGRAMMING = "fop";
    case FUND_CAPITAL_EXPENDITURE_WITH_OVER_PROGRAMMING_BASELINE = "fob";
    case FUND_CAPITAL_LOCAL_CONTRIBUTION = "flc";
    case FUND_CAPITAL_THIRD_PARTY_CONTRIBUTION = "ftp";
    case FUND_CAPITAL_LOCAL_CONTRIBUTION_BASELINE = "flb";
    case FUND_CAPITAL_OTHER = "fot";
    case FUND_RESOURCE_EXPENDITURE = "fre";

    case SCHEME_CAPITAL_SPEND_FUND = "ssp";
    case SCHEME_CAPITAL_SPEND_ALL_SOURCES = "ssa";

    public function isBaseline(): bool
    {
        return in_array($this, [
            self::FUND_CAPITAL_EXPENDITURE_BASELINE,
            self::FUND_CAPITAL_EXPENDITURE_WITH_OVER_PROGRAMMING_BASELINE,
            self::FUND_CAPITAL_LOCAL_CONTRIBUTION_BASELINE,
        ]);
    }

    public function getLabel(array $extraParameters=[]): TranslatableMessage
    {
        return new TranslatableMessage("enum.expense_type.{$this->value}", $extraParameters);
    }

    /**
     * @return array<ExpenseType>
     */
    public static function filterForFund(Fund $fund): array
    {
        // $fund not currently used to decided fields, as we only really have one fund
        return match($fund) {
            Fund::CRSTS1 => self::filterByPrefix('FUND_'),
            Fund::CRSTS2, Fund::BSIP => throw new \RuntimeException('Not yet supported'),
        };
    }

    /**
     * @return array<ExpenseType>
     */
    public static function filterForScheme(Fund $fund): array
    {
        return match($fund) {
            Fund::CRSTS1 => self::filterByPrefix('SCHEME_'),
            Fund::CRSTS2, Fund::BSIP => throw new \RuntimeException('Not yet supported'),
        };
    }

    /**
     * @return array<ExpenseType>
     */
    protected static function filterByPrefix(string $prefix): array
    {
        return array_values(
            array_filter(self::cases(), fn(\UnitEnum $e) => str_starts_with($e->name, $prefix))
        );
    }
}
