<?php

namespace App\Utility\Breadcrumb\Admin;

use App\Entity\Authority;
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
}
