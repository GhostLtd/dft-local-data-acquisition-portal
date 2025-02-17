<?php

namespace App\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\User;
use App\Utility\UserReachableEntityResolver;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ChangeAuthorityVoter extends Voter
{
    public function __construct(protected UserReachableEntityResolver $userReachableEntityResolver)
    {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_CHANGE_AUTHORITY;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $authorityIds = $this->userReachableEntityResolver->getAuthorityIdsViewableBy($user);

        return count($authorityIds) > 1;
    }
}
