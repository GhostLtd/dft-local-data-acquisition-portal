<?php

namespace App\Utility\Breadcrumb\Admin;

use App\Entity\Authority;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder as FrontendDashboardLinksBuilder;

abstract class AbstractAdminLinksBuilder extends FrontendDashboardLinksBuilder
{
    public function setNavLinks(?Authority $authority): void
    {
        $this->addNavLink(
            'dashboard',
            'admin_dashboard',
            translationKey: 'pages.dashboard.title',
            translationDomain: 'admin',
        );

        $this->addNavLink(
            'authority',
            'admin_authority',
            translationKey: 'pages.authority.title',
            translationDomain: 'admin',
        );

        $this->addNavLink(
            'data_export',
            'admin_data_export',
            translationKey: 'pages.data_export.title',
            translationDomain: 'admin',
        );

        $this->addNavLink(
            'maintenance',
            'admin_maintenance',
            translationKey: 'pages.maintenance.title',
            translationDomain: 'admin',
        );
    }
}
