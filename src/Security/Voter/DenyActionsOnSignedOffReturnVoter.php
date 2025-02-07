<?php

namespace App\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Security\SubjectResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DenyActionsOnSignedOffReturnVoter extends Voter
{
    public function __construct(
        protected AuthorizationCheckerInterface $authorizationChecker,
        protected LoggerInterface               $logger,
        protected SubjectResolver               $subjectResolver,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
                InternalRole::HAS_VALID_SIGN_OFF_PERMISSION,
                InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION,
                InternalRole::HAS_VALID_EDIT_PERMISSION,
            ]) &&
            $this->subjectResolver->isValidSubjectForInternalRole($subject, $attribute) &&
            ($subject instanceof FundReturn || $subject instanceof SchemeReturn);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $resolvedSubject = $this->subjectResolver->resolveSubjectForRole($subject, $attribute);

        $fundReturn = match($resolvedSubject->getBaseClass()) {
            FundReturn::class => $subject,
            SchemeReturn::class => $subject->getFundReturn(),
        };

        if (!$fundReturn || $fundReturn->getSignoffUser() !== null) {
            // Cannot <sign_off/mark_as_ready_edit> if the return has already been signed off
            return false;
        }

        return true;
    }
}
