<?php

namespace App\Utility\Breadcrumb;

use Symfony\Contracts\Translation\TranslatableInterface;

abstract class AbstractBreadcrumbAndNavBuilder extends AbstractLinksBuilder
{
    protected function addBreadcrumb(
        string                            $key,
        string                            $routeName,
        array                             $routeParameters = [],
        ?string                           $translationKey = null,
        array                             $translationParameters = [],
        null|string|TranslatableInterface $text = null,
        ?string                           $hash = null,
    ): void {
        $this->addLink('breadcrumbs', ...func_get_args());
    }

    public function getBreadcrumbs(): array {
        return $this->getLinks('breadcrumbs');
    }

    public function getBreadcrumbTitleFor(string $key): string {
        return $this->getTitleFor('breadcrumbs', $key);
    }

    protected function addNavLink(
        string                            $key,
        string                            $routeName,
        array                             $routeParameters = [],
        ?string                           $translationKey = null,
        array                             $translationParameters = [],
        null|string|TranslatableInterface $text = null,
        ?string                           $hash = null,
    ): void {
        $this->addLink('nav', ...func_get_args());
    }

    public function getNavLinks(?string $currentMenu = null): array {
        $links = $this->getLinksWithOriginalKeys('nav');

        if ($currentMenu) {
            foreach($links as $key => $link) {
                $links[$key]['active'] = ($currentMenu === $key);
            }
        }

        return $links;
    }

    public function getNavLinkTitleFor(string $key): string {
        return $this->getTitleFor('nav', $key);
    }

    protected function addRightNavLink(
        string                            $key,
        string                            $routeName,
        array                             $routeParameters = [],
        ?string                           $translationKey = null,
        array                             $translationParameters = [],
        null|string|TranslatableInterface $text = null,
        ?string                           $hash = null,
    ): void {
        $this->addLink('right_nav', ...func_get_args());
    }

    public function getRightNavLinks(): array {
        return $this->getLinks('right_nav');
    }

    public function getRightNavLinkTitleFor(string $key): string {
        return $this->getTitleFor('right_nav', $key);
    }
}
