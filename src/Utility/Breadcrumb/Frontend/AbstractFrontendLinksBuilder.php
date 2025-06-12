<?php

namespace App\Utility\Breadcrumb\Frontend;

use App\Entity\Authority;
use App\Entity\Enum\Role;
use App\Entity\User;
use App\Utility\Breadcrumb\AbstractBreadcrumbAndNavBuilder;
use App\Utility\UserReachableEntityResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractFrontendLinksBuilder extends AbstractBreadcrumbAndNavBuilder
{
    public function __construct(
        RouterInterface                       $router,
        TranslatorInterface                   $translator,
        protected Security                    $security,
        protected UserReachableEntityResolver $userReachableEntityResolver,
    )
    {
        parent::__construct($router, $translator);
    }

    protected function setNavLinks(?Authority $authority): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User || $authority === null) {
            return;
        }

        $this->addNavLink(
            'dashboard',
            'app_dashboard_authority',
            routeParameters: ['authorityId' => $authority->getId()],
            translationKey: 'frontend.pages.dashboard.home',
        );

        if ($this->security->isGranted(Role::CAN_MANAGE_USERS, $authority)) {
            $this->addNavLink(
                'users',
                'app_user_list',
                routeParameters: ['authorityId' => $authority->getId()],
                translationKey: 'frontend.pages.dashboard.users',
            );
        }

        if ($this->security->isGranted(Role::CAN_MANAGE_SCHEMES, $authority)) {
            $this->addNavLink(
                'schemes',
                'app_schemes_authority',
                routeParameters: ['authorityId' => $authority->getId()],
                translationKey: 'frontend.pages.dashboard.schemes',
            );
        }


        if ($this->security->isGranted(Role::CAN_CHANGE_AUTHORITY)) {
            $this->addRightNavLink(
                'change_authority',
                'app_dashboard',
                translationKey: 'auth.change_authority',
            );
        }

        $this->addRightNavLink(
            'sign_out',
            'app_logout',
            translationKey: 'auth.sign_out',
        );
    }
}
