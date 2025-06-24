<?php

namespace App\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Utility\SignoffHelper\CrstsSignoffHelper;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CrstsReturnSignoffEligibilityVoter extends Voter
{
    public function __construct(
        protected CrstsSignoffHelper  $crstsSignoffHelper,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return
            $attribute === Role::CAN_RETURN_BE_SIGNED_OFF &&
            $subject instanceof CrstsFundReturn;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof CrstsFundReturn) {
            throw new \RuntimeException('Subject must be an instance of CrstsFundReturn');
        }

        return !$this->crstsSignoffHelper->hasSignoffEligibilityProblems($subject);
    }
}
