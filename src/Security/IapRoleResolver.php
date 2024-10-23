<?php

namespace App\Security;

use App\Entity\Roles;
use Ghost\GovUkCoreBundle\Security\GoogleIap\RoleResolverInterface;

class IapRoleResolver implements RoleResolverInterface
{
    public function getRolesForEmailAddress(string $emailAddress): array
    {
        return [Roles::ROLE_ADMIN];
    }
}
