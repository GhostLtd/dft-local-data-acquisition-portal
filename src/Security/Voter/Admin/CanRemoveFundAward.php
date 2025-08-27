<?php

namespace App\Security\Voter\Admin;

use App\Entity\Enum\Role;
use App\Entity\FundAward;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CanRemoveFundAward extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_REMOVE_FUND_AWARD && $subject instanceof FundAward;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return $subject instanceof FundAward
            && $subject->getReturns()->isEmpty();
    }
}