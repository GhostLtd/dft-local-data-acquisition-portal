<?php

namespace App\Security\Voter\External;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ViewVoter extends Voter
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_VIEW;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($this->authorizationChecker->isGranted(InternalRole::HAS_VALID_VIEW_PERMISSION, $subject)) {
            return true;
        }

        return false;
    }
}
