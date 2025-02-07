<?php

namespace App\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\SchemeReturn\SchemeReturn;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SchemeSignoffVoter extends Voter
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected LoggerInterface               $logger
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [Role::CAN_SIGN_OFF_SCHEME, Role::CAN_REOPEN_SCHEME]) &&
            $subject instanceof SchemeReturn;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$this->authorizationChecker->isGranted(Role::CAN_COMPLETE, $subject)) {
            return false;
        }
        /** @var SchemeReturn $subject */
        if ($attribute === Role::CAN_SIGN_OFF_SCHEME && !$subject->getReadyForSignoff()) {
            return true;
        }

        if ($attribute === Role::CAN_REOPEN_SCHEME && $subject->getReadyForSignoff()) {
            return true;
        }

        return false;
    }
}
