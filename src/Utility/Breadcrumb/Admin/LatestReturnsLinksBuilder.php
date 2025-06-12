<?php

namespace App\Utility\Breadcrumb\Admin;

use App\Entity\Enum\Fund;

class LatestReturnsLinksBuilder extends AbstractAdminLinksBuilder
{
    public function setAtDashboard(): void
    {
        $this->addBreadcrumb(
            'dashboard',
            'admin_dashboard',
            translationKey: 'pages.dashboard.breadcrumb',
            translationDomain: 'admin',
        );

        $this->setNavLinks(null);
    }

    public function setAtReleaseSurveys(Fund $fund, int $quarter, int $year): void
    {
        $this->setAtDashboard();

        $this->addBreadcrumb(
            'release_returns',
            'admin_fund_release_returns',
            routeParameters: ['fund' => $fund->value],
            translationKey: 'pages.release_returns.breadcrumb',
            translationParameters: [
                'fundName' => $fund->name,
                'quarter' => $quarter,
                'year' => $year,
                'nextYear' => substr($year + 1, -2),
            ],
            translationDomain: 'admin',
        );
    }
}
