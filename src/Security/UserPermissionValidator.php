<?php

namespace App\Security;

use App\Entity\Enum\Permission;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Project;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Entity\Recipient;
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
            if (!in_array($entityClass, [Recipient::class, FundReturn::class])) {
                return false;
            }

            if ($sectionTypes !== null) {
                return false;
            }

            if ($fundTypes !== null && $entityClass !== Recipient::class) {
                return false;
            }
        } else if (in_array($permission, [Permission::CHECKER, Permission::EDITOR, Permission::VIEWER])) {
            if (!in_array($entityClass, [Recipient::class, FundReturn::class, Project::class, ProjectReturn::class])) {
                return false;
            }

            if ($sectionTypes !== null) {
                if ($entityClass === Recipient::class) {
                    return false;
                }

                if ($entityClass === Project::class && $fundTypes === null) {
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
