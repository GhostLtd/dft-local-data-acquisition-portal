<?php

namespace App\Security\Voter\Admin;

use App\Entity\UserTypeRoles;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SuperAdminVoter extends Voter
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'DFT_SUPER_ADMIN';
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$this->authorizationChecker->isGranted(UserTypeRoles::ROLE_IAP_ADMIN)) {
            return false;
        }

        // There was previously logic that checked the domain of $token->getUser()->getUserIdentifier()
        // to grant Ghost this ability. Ultimately this just allowed the admin of an MCA to be changed.
        //
        // This is now just allowed to all admins, as it will sometimes be useful (e.g. if MCA admin is
        // away)

        return true;
    }
}
