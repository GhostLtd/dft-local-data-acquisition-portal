<?php

namespace App\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Security\SubjectResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class SchemeReturnExpensesVoter extends Voter
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected LoggerInterface               $logger,
        protected SubjectResolver               $subjectResolver,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                Role::CAN_EDIT_SCHEME_RETURN_EXPENSES,
                Role::CAN_VIEW_SCHEME_RETURN_EXPENSES,
            ]) &&
            $subject instanceof CrstsSchemeReturn;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $internalRole = match($attribute) {
            Role::CAN_EDIT_SCHEME_RETURN_EXPENSES => InternalRole::HAS_VALID_EDIT_PERMISSION,
            Role::CAN_VIEW_SCHEME_RETURN_EXPENSES => InternalRole::HAS_VALID_VIEW_PERMISSION,
        };

        if (!$this->authorizationChecker->isGranted($internalRole, $subject)) {
            return false;
        }

        if (!$subject instanceof CrstsSchemeReturn) {
            throw new \RuntimeException('$subject must be instance of CrstsSchemeReturn');
        }

        $crstsData = $subject->getScheme()->getCrstsData();
        $quarter = $subject->getFundReturn()->getQuarter();

        // Non-retained scheme expense data can only be edited in quarter 4...
        if ($attribute === Role::CAN_EDIT_SCHEME_RETURN_EXPENSES) {
            return $crstsData->isExpenseDataRequiredFor($quarter);
        }

        // ...but scheme expense data is visible (retained or not), as long as permission checks pass
        return true;
    }
}
