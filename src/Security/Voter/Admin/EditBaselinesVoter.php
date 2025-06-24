<?php

namespace App\Security\Voter\Admin;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\UserTypeRoles;
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
            $this->authorizationChecker->isGranted(UserTypeRoles::ROLE_IAP_ADMIN) &&
            $subject instanceof FundReturn &&
            $subject->getState() === FundReturn::STATE_INITIAL;
    }
}
