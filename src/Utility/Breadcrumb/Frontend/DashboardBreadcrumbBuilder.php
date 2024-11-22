<?php

namespace App\Utility\Breadcrumb\Frontend;

use App\Entity\Enum\FundLevelSection;
use App\Entity\Enum\ProjectLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\ProjectFund\ProjectFund;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Utility\Breadcrumb\AbstractBreadcrumbBuilder;

class DashboardBreadcrumbBuilder extends AbstractBreadcrumbBuilder
{
    protected function addInitialItems(): void
    {
        $this->addItem('dashboard', 'frontend.pages.dashboard.breadcrumb', 'app_dashboard');
    }

    public function setAtFundReturn(FundReturn $fundReturn): void
    {
        $this->addItem(
            'fund_return',
            'frontend.pages.fund_return.title',
            'app_fund_return',
            routeParameters: ['fundReturnId' => $fundReturn->getId()],
            translationParameters: [
                'recipientName' => $fundReturn->getFundAward()->getRecipient()->getName(),
                'quarter' => $fundReturn->getQuarter(),
                'type' => $fundReturn->getFundAward()->getType()->name,
                'year' => $fundReturn->getYear(),
            ]
        );
    }

    public function setAtFundReturnEdit(FundReturn $fundReturn, FundLevelSection $section): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addItem(
            'fund_return_edit',
            "sections.fund.{$section->value}",
            'app_fund_return_edit',
            routeParameters: ['fundReturnId' => $fundReturn->getId(), 'section' => $section->value],
        );
    }

    public function setAtProjectFund(FundReturn $fundReturn, ProjectFund $projectFund): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addItem(
            'project_return',
            'frontend.pages.project_return.title',
            'app_project_return',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'projectFundId' => $projectFund->getId()
            ],
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
            "sections.project.{$section->value}",
            'app_project_return_edit',
            routeParameters: [
                'fundReturnId' => $fundReturn->getId(),
                'projectFundId' => $projectFund->getId(),
                'section' => $section->value,
            ],
        );
    }
}
