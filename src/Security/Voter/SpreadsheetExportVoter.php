<?php

namespace App\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\CrstsFundReturn;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SpreadsheetExportVoter extends Voter
{
    public function __construct(
        protected AccessDecisionManagerInterface $accessDecisionManager,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === Role::CAN_EXPORT_SPREADSHEET
            && $subject instanceof CrstsFundReturn;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof CrstsFundReturn) {
            return false;
        }

        return $this->accessDecisionManager->decide($token, [Role::CAN_VIEW], $subject);
    }
}
