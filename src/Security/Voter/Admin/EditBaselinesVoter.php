<?php

namespace App\Security\Voter\Admin;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\UserTypeRoles;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EditBaselinesVoter extends Voter
{
    public function __construct(
        protected AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_EDIT_BASELINES &&
            $subject instanceof FundReturn;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        return
            $this->accessDecisionManager->decide($token, [UserTypeRoles::ROLE_IAP_ADMIN]) &&
            $subject instanceof FundReturn &&
            $subject->getState() === FundReturn::STATE_INITIAL;
    }
}
