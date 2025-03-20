<?php

namespace App\Utility\Breadcrumb\Admin;

use App\Entity\Authority;
use App\Utility\Breadcrumb\Frontend\DashboardLinksBuilder as FrontendDashboardLinksBuilder;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatableInterface;

class DashboardLinksBuilder extends FrontendDashboardLinksBuilder
{
    protected function addLink(string $set, string $key, string $routeName, array $routeParameters = [], ?string $translationKey = null, array $translationParameters = [], TranslatableInterface|string|null $text = null, ?string $hash = null): void
    {
        $routeName = preg_replace('/^app_/', 'admin_', $routeName);
        parent::addLink($set, $key, $routeName, $routeParameters, $translationKey, $translationParameters, $text, $hash);
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

        $this->setNavLinks($authority);
    }

}