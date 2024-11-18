<?php

namespace App\Utility\Breadcrumb\Frontend;

use App\Entity\Enum\FundLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Utility\Breadcrumb\AbstractBreadcrumbBuilder;

class DashboardBreadcrumbBuilder extends AbstractBreadcrumbBuilder
{
    protected function addInitialItems(): void
    {
        $this->addItem('dashboard', 'frontend.pages.dashboard.title', 'app_dashboard');
    }

    public function setAtFundReturn(FundReturn $fundReturn): void
    {
        $this->addItem(
            'fund_return',
            'frontend.pages.fund_return.title',
            'app_fund_return',
            routeParameters: ['id' => $fundReturn->getId()],
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
            routeParameters: ['id' => $fundReturn->getId(), 'section' => $section->value],
        );
    }
}
