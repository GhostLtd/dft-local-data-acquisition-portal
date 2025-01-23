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
        $sectionTypes = $userPermission->getSectionTypes();
        $fundTypes = $userPermission->getFundTypes();

        if ($permission === Permission::SUBMITTER) {
            if (!in_array($entityClass, [Authority::class, FundReturn::class])) {
                return false;
            }

            if ($sectionTypes !== null) {
                return false;
            }

            if ($fundTypes !== null && $entityClass !== Authority::class) {
                return false;
            }
        } else if (in_array($permission, [Permission::CHECKER, Permission::EDITOR, Permission::VIEWER])) {
            if (!in_array($entityClass, [Authority::class, FundReturn::class, Scheme::class, SchemeReturn::class])) {
                return false;
            }

            if ($sectionTypes !== null) {
                if ($entityClass === Authority::class) {
                    return false;
                }

                if ($entityClass === Scheme::class && $fundTypes === null) {
                    return false;
                }
            }
        } else {
            // Unsupported permission
            return false;
        }

        return true;
    }
}
