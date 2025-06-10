<?php

namespace App\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\CrstsFundReturn;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SpreadsheetExportVoter extends Voter
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_EXPORT_SPREADSHEET &&
            $subject instanceof CrstsFundReturn;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof CrstsFundReturn) {
            return false;
        }

        return $subject->isSignedOff();
    }
}
