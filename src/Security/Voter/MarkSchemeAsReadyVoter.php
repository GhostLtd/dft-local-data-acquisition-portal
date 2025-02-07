<?php

namespace App\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\SchemeReturn\SchemeReturn;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MarkSchemeAsReadyVoter extends Voter
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected LoggerInterface               $logger
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [Role::CAN_MARK_AS_READY, Role::CAN_MARK_AS_NOT_READY]) &&
            $subject instanceof SchemeReturn;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$this->authorizationChecker->isGranted(InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, $subject)) {
            return false;
        }

        if ($subject->getFundReturn()?->getSignoffUser() !== null) {
            // Cannot mark_as_ready if the return has been signed off
            return false;
        }

        /** @var SchemeReturn $subject */
        if ($attribute === Role::CAN_MARK_AS_READY && !$subject->getReadyForSignoff()) {
            return true;
        }

        if ($attribute === Role::CAN_MARK_AS_NOT_READY && $subject->getReadyForSignoff()) {
            return true;
        }

        return false;
    }
}
