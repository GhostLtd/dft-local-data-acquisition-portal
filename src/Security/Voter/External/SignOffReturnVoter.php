<?php

namespace App\Security\Voter\External;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SignOffReturnVoter extends Voter
{
    public function __construct(
        protected AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_SIGN_OFF_RETURN &&
            $subject instanceof FundReturn;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$this->accessDecisionManager->decide($token, [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], $subject)) {
            return false;
        }

        // N.B. Already signed-off case dealt with by DenyActionsOnSignedOffReturnVoter

        return true;
    }
}
