<?php

namespace App\Security\Voter;

use App\Entity\Authority;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SchemeManagerVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        $supportsCanManageSchemes = $attribute === Role::CAN_MANAGE_SCHEMES && (
                $subject instanceof FundReturn ||
                $subject instanceof Authority
            );

        $supportsSchemeRelatedAttributes = in_array($attribute, [
            Role::CAN_DELETE_SCHEME,
            Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS,
            Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS,
            Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME,
        ]) && $subject instanceof Scheme;

        return $supportsCanManageSchemes || $supportsSchemeRelatedAttributes;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if ($subject instanceof Scheme) {
            $subject = $subject->getAuthority();
        }

        if ($subject instanceof FundReturn) {
            $subject = $subject->getFundAward()->getAuthority();
        }

        if (
            !$subject instanceof Authority ||
            !$user instanceof User
        ) {
            return false;
        }

        if ($user !== $subject->getAdmin()) {
            return false;
        }

        return true;
    }
}
