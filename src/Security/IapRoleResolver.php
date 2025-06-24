<?php

namespace App\Security;

use App\Entity\UserTypeRoles;
use Ghost\GovUkCoreBundle\Security\GoogleIap\RoleResolverInterface;

class IapRoleResolver implements RoleResolverInterface
{
    public function getRolesForEmailAddress(string $emailAddress): array
    {
        return [UserTypeRoles::ROLE_IAP_ADMIN];
    }
}
