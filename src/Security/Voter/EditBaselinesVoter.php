<?php

namespace App\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Roles;
use App\Entity\SchemeReturn\SchemeReturn;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EditBaselinesVoter extends Voter
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_EDIT_BASELINES &&
            $subject instanceof FundReturn;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return
            $this->authorizationChecker->isGranted(Roles::ROLE_ADMIN) &&
            $subject instanceof FundReturn &&
            $subject->getState() === FundReturn::STATE_INITIAL;
    }
}
