<?php

namespace App\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EditUserVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_EDIT_USER && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($token->getUser()->getUserIdentifier() === $subject->getUserIdentifier()) {
            return false;
        }

        return true;
    }
}