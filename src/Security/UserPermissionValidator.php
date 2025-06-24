<?php

namespace App\Security;

use App\Entity\Enum\Permission;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\Authority;
use App\Entity\UserPermission;

class UserPermissionValidator
{
    public function isUserPermissionValid(UserPermission $userPermission): bool
    {
        $permission = $userPermission->getPermission();
        $entityClass = $userPermission->getEntityClass();
        $fundTypes = $userPermission->getFundTypes();

        if ($permission === Permission::SIGN_OFF) {
            if (!in_array($entityClass, [Authority::class, FundReturn::class])) {
                return false;
            }

            if ($fundTypes !== null && $entityClass !== Authority::class) {
                return false;
            }
        } else if (in_array($permission, [Permission::MARK_AS_READY, Permission::EDITOR, Permission::VIEWER])) {
            if (!in_array($entityClass, [Authority::class, FundReturn::class, Scheme::class, SchemeReturn::class])) {
                return false;
            }

            if ($fundTypes !== null && !in_array($entityClass, [Authority::class, Scheme::class])) {
                return false;
            }
        } else if ($permission === Permission::SCHEME_MANAGER) {
            if ($fundTypes !== null || $entityClass !== Authority::class) {
                return false;
            }
        } else {
            // Unsupported permission
            return false;
        }

        return true;
    }
}
