<?php

namespace App\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Roles;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ReOpenReturnVoter extends Voter
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
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
            $this->authorizationChecker->isGranted(Roles::ROLE_ADMIN, $user);
    }
}
