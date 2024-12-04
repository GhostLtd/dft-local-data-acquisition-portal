<?php

namespace App\Utility\Breadcrumb\Frontend;

use App\Entity\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\FundLevelSection;
use App\Entity\Enum\ProjectLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\ProjectFund\ProjectFund;
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
                'recipientName' => $fundReturn->getFundAward()->getRecipient()->getName(),
                'quarter' => $fundReturn->getQuarter(),
                'type' => new TranslatableMessage($typeKey),
                'year' => $fundReturn->getYear(),
            ]
        );
    }

    public function setAtProjectFund(FundReturn $fundReturn, ProjectFund $projectFund): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addItem(
            'project_return',
            'app_project_return',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'projectFundId' => $projectFund->getId()
            ],
            translationKey: 'frontend.pages.project_return.title',
            translationParameters: [
                'projectName' => $projectFund->getProject()->getName(),
            ]
        );
    }

    public function setAtProjectFundEdit(FundReturn $fundReturn, ProjectFund $projectFund, ProjectLevelSection $section): void
    {
        $this->setAtProjectFund($fundReturn, $projectFund);
        $this->addItem(
            'project_return_edit',
            'app_project_return_edit',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'projectFundId' => $projectFund->getId(),
                'section' => $section->value,
            ],
            translationKey: "sections.project.{$section->value}",
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
            routeParameters: ['fundReturnId' => $fundReturn->getId(), 'divisionSlug' => $division->getSlug()],
            text: $division->getTitle(),
        );
    }
}
