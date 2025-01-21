<?php

namespace App\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\FundReturn;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SetLeadContactVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_SET_LEAD_CONTACT &&
            ($subject instanceof FundAward || $subject instanceof FundReturn);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if ($user instanceof User) {
            if ($subject instanceof FundReturn) {
                $subject = $subject->getFundAward();
            }

            if ($subject instanceof FundAward) {
                return $subject->getRecipient()->getAdmin() === $user;
            }
        }

        return false;
    }
}
