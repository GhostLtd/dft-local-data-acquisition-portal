<?php

namespace App\Utility\Breadcrumb\Frontend;

use App\Entity\Authority;
use App\Entity\User;

class UsersLinksBuilder extends AbstractFrontendLinksBuilder
{
    public function setAtUsers(Authority $authority): void
    {
        $this->addBreadcrumb(
            'users',
            'app_users',
            routeParameters: ['authorityId' => $authority->getId()],
            translationKey: 'frontend.pages.users.breadcrumb',
            translationParameters: ['authorityName' => $authority->getName()],
        );

        $this->setNavLinks($authority);
    }

    public function setAtUser(Authority $authority, User $user): void
    {
        $this->setAtUsers($authority);

        $this->addBreadcrumb(
            'user',
            'app_user',
            routeParameters: ['authorityId' => $authority->getId(), 'userId' => $user->getId()],
            translationKey: 'frontend.pages.user.breadcrumb',
            translationParameters: ['userName' => $user->getName()],
        );
    }

    public function setAtUserEdit(Authority $authority, User $user): void
    {
        $this->setAtUser($authority, $user);

        $this->addBreadcrumb(
            'user_edit',
            'app_user_edit',
            routeParameters: ['authorityId' => $authority->getId(), 'userId' => $user->getId()],
            translationKey: 'frontend.pages.user_edit.breadcrumb',
            translationParameters: ['userName' => $user->getName()],
        );
    }

    public function setAtUserAdd(Authority $authority): void
    {
        $this->setAtUsers($authority);

        $this->addBreadcrumb(
            'user_add',
            'app_user_add',
            routeParameters: ['authorityId' => $authority->getId()],
            translationKey: 'frontend.pages.user_add.breadcrumb',
        );
    }
}
