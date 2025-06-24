<?php

namespace App\Security\Voter\External;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\SchemeReturn\SchemeReturn;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EditVoter extends Voter
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_EDIT;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$this->authorizationChecker->isGranted(InternalRole::HAS_VALID_EDIT_PERMISSION, $subject)) {
            return false;
        }

        if ($subject instanceof SchemeReturn) {
            if ($subject->getReadyForSignoff()) {
                // No editing if the scheme's been marked as ready for sign-off
                return false;
            }
        }

        return true;
    }
}
