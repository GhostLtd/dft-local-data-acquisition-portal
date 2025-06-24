<?php

namespace App\Security\Voter\External;

use App\Entity\Authority;
use App\Entity\Enum\Role;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserManagementVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_MANAGE_USERS && $subject instanceof Authority;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        foreach($user->getAuthoritiesAdminOf() as $authority) {
            if ($authority === $subject) {
                return true;
            }
        }

        return false;
    }
}
