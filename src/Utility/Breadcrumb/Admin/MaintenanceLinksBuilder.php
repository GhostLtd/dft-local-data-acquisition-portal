<?php

namespace App\Utility\Breadcrumb\Admin;

use App\Entity\MaintenanceWarning;

class MaintenanceLinksBuilder extends AbstractAdminLinksBuilder
{
    public function setAtDashboard(): void
    {
        $this->addBreadcrumb(
            'maintenance',
            'admin_maintenance',
            translationKey: 'pages.maintenance.title',
            translationDomain: 'admin',
        );

        $this->setNavLinks(null);
    }

    public function setAtEdit(MaintenanceWarning $warning): void
    {
        $this->setAtDashboard();

        $this->addBreadcrumb(
            'maintenance_edit',
            'admin_maintenance_edit',
            ['id' => $warning->getId()],
            translationKey: 'pages.maintenance_edit.title',
            translationDomain: 'admin',
        );
    }

    public function setAtAdd(): void
    {
        $this->setAtDashboard();

        $this->addBreadcrumb(
            'maintenance_add',
            'admin_maintenance_add',
            translationKey: 'pages.maintenance_add.title',
            translationDomain: 'admin',
        );
    }

    public function setAtDelete(MaintenanceWarning $warning): void
    {
        $this->setAtDashboard();

        $this->addBreadcrumb(
            'maintenance_delete',
            'admin_maintenance_delete',
            ['id' => $warning->getId()],
            translationKey: 'pages.maintenance_delete.title',
            translationDomain: 'admin',
        );
    }
}
