<?php

namespace App\Utility\Breadcrumb\Frontend;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Authority;
use App\Entity\Enum\FundLevelSection;
use App\Entity\Enum\SchemeLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use Symfony\Component\Translation\TranslatableMessage;

class DashboardLinksBuilder extends AbstractFrontendLinksBuilder
{
    public function setAtAuthority(Authority $authority): void
    {
        $this->addBreadcrumb(
            'dashboard',
            'app_dashboard_authority',
            routeParameters: ['authorityId' => $authority->getId()],
            translationKey: 'frontend.pages.dashboard.breadcrumb',
            translationParameters: ['authorityName' => $authority->getName()],
        );

        $this->setNavLinks($authority);
    }

    public function setAtFundReturn(FundReturn $fundReturn): void
    {
        $this->setAtAuthority($fundReturn->getFundAward()->getAuthority());

        $this->addBreadcrumb(
            'fund_return',
            'app_fund_return',
            routeParameters: ['fundReturnId' => $fundReturn->getId()],
            translationKey: 'frontend.pages.fund_return.title',
            translationParameters: $this->getFundReturnTranslationKeys($fundReturn),
        );
    }

    public function setAtSpreadsheetExport(FundReturn $fundReturn): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addBreadcrumb(
            'spreadsheet_export',
            'app_fund_return_export_spreadsheet',
            routeParameters: ['fundReturnId' => $fundReturn->getId()],
            translationKey: 'frontend.pages.export_spreadsheet.title',
        );
    }

    public function setAtFundReturnSignoff(FundReturn $fundReturn): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addBreadcrumb(
            'fund_return_signoff',
            'app_fund_return_signoff',
            routeParameters: ['fundReturnId' => $fundReturn->getId()],
            translationKey: 'frontend.pages.fund_return_signoff.title',
        );
    }

    public function setAtViewIssuesPreventingSignoff(FundReturn $fundReturn): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addBreadcrumb(
            'fund_return_signoff_issues',
            'app_fund_return_signoff_issues',
            routeParameters: ['fundReturnId' => $fundReturn->getId()],
            translationKey: 'frontend.pages.signoff_issues.breadcrumb',
        );
    }

    public function setAtFundReturnReOpen(FundReturn $fundReturn): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addBreadcrumb(
            'fund_return_reopen',
            'app_fund_return_reopen',
            routeParameters: ['fundReturnId' => $fundReturn->getId()],
            translationKey: 'frontend.pages.fund_return_reopen.title',
            translationParameters: $this->getFundReturnTranslationKeys($fundReturn),
        );
    }

    protected function getFundReturnTranslationKeys(FundReturn $fundReturn): array
    {
        $typeKey = "enum.fund.".$fundReturn->getFundAward()->getType()->value;

        return [
            'authorityName' => $fundReturn->getFundAward()->getAuthority()->getName(),
            'quarter' => $fundReturn->getQuarter(),
            'type' => new TranslatableMessage($typeKey),
            'year' => $fundReturn->getYear(),
            'nextYear' => $fundReturn->getNextYearAsTwoDigits(),
        ];
    }

    public function setAtScheme(FundReturn $fundReturn, Scheme $scheme): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addBreadcrumb(
            'scheme_return',
            'app_scheme_return',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'schemeId' => $scheme->getId()
            ],
            translationKey: 'frontend.pages.scheme_return.title',
            translationParameters: [
                'schemeName' => $scheme->getName(),
            ]
        );
    }

    public function setAtSchemeEdit(FundReturn $fundReturn, Scheme $scheme, SchemeLevelSection $section): void
    {
        $this->setAtScheme($fundReturn, $scheme);
        $this->addBreadcrumb(
            'scheme_return_edit',
            'app_scheme_return_edit',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'schemeId' => $scheme->getId(),
                'section' => $section->value,
            ],
            translationKey: "sections.scheme.{$section->value}",
        );
    }

    public function setAtSchemeReadyForSignoff(FundReturn $fundReturn, Scheme $scheme): void
    {
        $this->setAtScheme($fundReturn, $scheme);
        $this->addBreadcrumb(
            'scheme_return_signoff',
            'app_scheme_return_mark_as_ready_for_signoff',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'schemeId' => $scheme->getId(),
            ],
            translationKey: 'frontend.pages.scheme_mark_as_ready_for_signoff.breadcrumb',
        );
    }

    public function setAtSchemeNotReadyForSignoff(FundReturn $fundReturn, Scheme $scheme): void
    {
        $this->setAtScheme($fundReturn, $scheme);
        $this->addBreadcrumb(
            'scheme_return_signoff',
            'app_scheme_return_mark_as_not_ready_for_signoff',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'schemeId' => $scheme->getId(),
            ],
            translationKey: 'frontend.pages.scheme_mark_as_not_ready_for_signoff.breadcrumb',
        );
    }

    public function setAtSchemeExpenseEdit(FundReturn $fundReturn, Scheme $scheme, DivisionConfiguration $division): void
    {
        $this->setAtScheme($fundReturn, $scheme);
        $this->addBreadcrumb(
            'scheme_return_expense_edit',
            'app_scheme_return_expense_edit',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'schemeId' => $scheme->getId(),
                'divisionKey' => $division->getKey(),
            ],
            text: $division->getLabel(),
        );
    }

    public function setAtFundReturnSectionEdit(FundReturn $fundReturn, FundLevelSection $section): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addBreadcrumb(
            'fund_return_edit',
            'app_fund_return_edit',
            routeParameters: ['fundReturnId' => $fundReturn->getId(), 'section' => $section->value],
            translationKey: "sections.fund.{$section->value}",
        );
    }

    public function setAtFundReturnExpenseEdit(FundReturn $fundReturn, DivisionConfiguration $division): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addBreadcrumb(
            'fund_return_expense_edit',
            'app_fund_return_expense_edit',
            routeParameters: ['fundReturnId' => $fundReturn->getId(), 'divisionKey' => $division->getKey()],
            text: $division->getLabel(),
        );
    }
}
