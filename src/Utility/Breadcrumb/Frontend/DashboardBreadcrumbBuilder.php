<?php

namespace App\Utility\Breadcrumb\Frontend;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\FundLevelSection;
use App\Entity\Enum\SchemeLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeFund\SchemeFund;
use App\Utility\Breadcrumb\AbstractBreadcrumbBuilder;
use Symfony\Component\Translation\TranslatableMessage;

class DashboardBreadcrumbBuilder extends AbstractBreadcrumbBuilder
{
    protected function addInitialItems(): void
    {
        $this->addItem(
            'dashboard',
            'app_dashboard',
            translationKey: 'frontend.pages.dashboard.breadcrumb',
        );
    }

    public function setAtFundReturn(FundReturn $fundReturn): void
    {
        $typeKey = "enum.fund.".$fundReturn->getFundAward()->getType()->value;

        $this->addItem(
            'fund_return',
            'app_fund_return',
            routeParameters: ['fundReturnId' => $fundReturn->getId()],
            translationKey: 'frontend.pages.fund_return.title',
            translationParameters: [
                'authorityName' => $fundReturn->getFundAward()->getAuthority()->getName(),
                'quarter' => $fundReturn->getQuarter(),
                'type' => new TranslatableMessage($typeKey),
                'year' => $fundReturn->getYear(),
                'nextYear' => $fundReturn->getNextYearAsTwoDigits(),
            ]
        );
    }

    public function setAtSchemeFund(FundReturn $fundReturn, SchemeFund $schemeFund): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addItem(
            'scheme_return',
            'app_scheme_return',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'schemeFundId' => $schemeFund->getId()
            ],
            translationKey: 'frontend.pages.scheme_return.title',
            translationParameters: [
                'schemeName' => $schemeFund->getScheme()->getName(),
            ]
        );
    }

    public function setAtSchemeFundEdit(FundReturn $fundReturn, SchemeFund $schemeFund, SchemeLevelSection $section): void
    {
        $this->setAtSchemeFund($fundReturn, $schemeFund);
        $this->addItem(
            'scheme_return_edit',
            'app_scheme_return_edit',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'schemeFundId' => $schemeFund->getId(),
                'section' => $section->value,
            ],
            translationKey: "sections.scheme.{$section->value}",
        );
    }

    public function setAtSchemeExpenseEdit(FundReturn $fundReturn, SchemeFund $scheme, DivisionConfiguration $division): void
    {
        $this->setAtSchemeFund($fundReturn, $scheme);
        $this->addItem(
            'scheme_return_expense_edit',
            'app_scheme_return_expense_edit',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'schemeFundId' => $scheme->getId(),
                'divisionKey' => $division->getKey(),
            ],
            text: $division->getLabel(),
        );
    }

    public function setAtFundReturnSectionEdit(FundReturn $fundReturn, FundLevelSection $section): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addItem(
            'fund_return_edit',
            'app_fund_return_edit',
            routeParameters: ['fundReturnId' => $fundReturn->getId(), 'section' => $section->value],
            translationKey: "sections.fund.{$section->value}",
        );
    }

    public function setAtFundReturnExpenseEdit(FundReturn $fundReturn, DivisionConfiguration $division): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addItem(
            'fund_return_expense_edit',
            'app_fund_return_expense_edit',
            routeParameters: ['fundReturnId' => $fundReturn->getId(), 'divisionKey' => $division->getKey()],
            text: $division->getLabel(),
        );
    }
}
