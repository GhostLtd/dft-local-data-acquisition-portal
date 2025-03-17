<?php

namespace App\Utility\Breadcrumb\Frontend;

use App\Entity\Authority;
use App\Entity\Scheme;

class SchemesLinksBuilder extends AbstractFrontendLinksBuilder
{
    public function setAtSchemes(Authority $authority): void
    {
        $this->addBreadcrumb(
            'schemes',
            'app_schemes_authority',
            routeParameters: ['authorityId' => $authority->getId()],
            translationKey: 'frontend.pages.schemes.breadcrumb',
            translationParameters: ['authorityName' => $authority->getName()],
        );

        $this->setNavLinks($authority);
    }

    public function setAtScheme(Authority $authority, Scheme $scheme): void
    {
        $this->setAtSchemes($authority);

        $this->addBreadcrumb(
            'scheme',
            'app_scheme',
            routeParameters: ['authorityId' => $authority->getId(), 'schemeId' => $scheme->getId()],
            translationKey: 'frontend.pages.scheme.breadcrumb',
            translationParameters: ['schemeName' => $scheme->getName()],
        );
    }

    public function setAtSchemeAdd(Authority $authority): void
    {
        $this->setAtSchemes($authority);

        $this->addBreadcrumb(
            'scheme_add',
            'app_scheme_add',
            routeParameters: ['authorityId' => $authority->getId()],
            translationKey: 'frontend.pages.scheme_add.breadcrumb',
        );
    }

    public function setAtSchemeDelete(Authority $authority, Scheme $scheme): void
    {
        $this->setAtScheme($authority, $scheme);

        $this->addBreadcrumb(
            'scheme_delete',
            'app_scheme_delete',
            routeParameters: ['authorityId' => $authority->getId(), 'schemeId' => $scheme->getId()],
            translationKey: 'frontend.pages.scheme_delete.breadcrumb',
            translationParameters: ['schemeName' => $scheme->getName()],
        );
    }

    public function setAtSchemeEdit(Authority $authority, Scheme $scheme): void
    {
        $this->setAtScheme($authority, $scheme);

        $this->addBreadcrumb(
            'scheme_edit',
            'app_scheme_edit',
            routeParameters: ['authorityId' => $authority->getId(), 'schemeId' => $scheme->getId()],
            translationKey: 'frontend.pages.scheme_edit.breadcrumb',
            translationParameters: ['schemeName' => $scheme->getName()],
        );
    }
}
