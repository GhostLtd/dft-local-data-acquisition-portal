<?php

namespace App\Utility\Breadcrumb\Admin;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Authority;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\SchemeReturn;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

class DashboardLinksBuilder extends AbstractAdminLinksBuilder
{
    protected function addLink(
        string                            $set,
        string                            $key,
        string                            $routeName,
        array                             $routeParameters = [],
        ?string                           $translationKey = null,
        array                             $translationParameters = [],
        ?string                           $translationDomain = null,
        null|string|TranslatableInterface $text = null,
        ?string                           $hash = null,
    ): void
    {
        $routeName = preg_replace('/^app_/', 'admin_', $routeName);
        parent::addLink($set, $key, $routeName, $routeParameters, $translationKey, $translationParameters, $translationDomain, $text, $hash);
    }

    public function setAtAuthority(Authority $authority): void
    {
        $this->addBreadcrumb(
            'dashboard',
            'admin_authority_view',
            routeParameters: ['id' => $authority->getId()],
            text: new TranslatableMessage('pages.authority_view.title', ['name' => $authority->getName()], 'admin'),
            hash: 'mca-returns'
        );

        $this->setNavLinks(null);
    }

    public function setAtAuthorityAdd(): void
    {
        $this->setNavLinks(null);
    }

    public function setAtAuthorityEdit(Authority $authority): void
    {
        $this->setAtAuthority($authority);

        $this->addBreadcrumb(
            'authority_edit',
            'admin_authority_edit',
            routeParameters: ['id' => $authority->getId()],
            text: new TranslatableMessage('pages.authority_edit.title', ['name' => $authority->getName()], 'admin'),
        );
    }

    public function setAtAuthorityEditAdmin(Authority $authority): void
    {
        $this->setAtAuthority($authority);

        $this->addBreadcrumb(
            'edit_admin_user',
            'admin_authority_edit_admin_user',
            routeParameters: ['id' => $authority->getId()],
            text: new TranslatableMessage('pages.authority_admin_edit.breadcrumb', ['name' => $authority->getName()], 'admin'),
        );
    }

    public function setAtFundReturnBaselinesEdit(FundReturn $fundReturn, DivisionConfiguration $division): void
    {
        $this->setAtFundReturn($fundReturn);
        $this->addBreadcrumb(
            'fund_return_baselines_edit',
            'admin_fund_return_baselines_edit',
            routeParameters: ['fundReturnId' => $fundReturn->getId(), 'divisionKey' => $division->getKey()],
            text: new TranslatableMessage('pages.baselines_edit.breadcrumb', ['divisionLabel' => $division->getLabel()], 'admin'),
        );
    }

    public function setAtSchemeMilestonesBaselinesEdit(FundReturn $fundReturn, Scheme $scheme): void
    {
        $this->setAtFundReturn($fundReturn);

        $this->addBreadcrumb(
            'scheme_return_milestones_baselines_edit',
            'admin_scheme_return_milestone_baselines_edit',
            routeParameters: ['fundReturnId' => $fundReturn->getId(), 'schemeId' => $scheme->getId()],
            text: new TranslatableMessage('pages.scheme_milestone_baselines_edit.breadcrumb', ['schemeName' => $scheme->getName()], 'admin'),
        );
    }
}
