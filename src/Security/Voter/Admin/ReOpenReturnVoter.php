<?php

namespace App\Security\Voter\Admin;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\UserTypeRoles;
use App\Repository\FundReturn\FundReturnRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ReOpenReturnVoter extends Voter
{
    public function __construct(
        protected AccessDecisionManagerInterface $accessDecisionManager,
        protected FundReturnRepository           $fundReturnRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_REOPEN_RETURN &&
            $subject instanceof FundReturn;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        return
            $user &&
            $subject instanceof FundReturn &&
            $subject->isSignedOff() &&
            $this->accessDecisionManager->decide($token, [UserTypeRoles::ROLE_IAP_ADMIN]) &&
            $this->fundReturnRepository->isMostRecentReturnForAward($subject);
    }
}
