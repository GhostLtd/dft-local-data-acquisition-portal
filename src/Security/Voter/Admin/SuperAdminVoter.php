<?php

namespace App\Security\Voter\Admin;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
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

        if (preg_match('/@ghostlimited.com$/', $token->getUser()->getUserIdentifier())) {
            return true;
        }

        return false;
    }
}
