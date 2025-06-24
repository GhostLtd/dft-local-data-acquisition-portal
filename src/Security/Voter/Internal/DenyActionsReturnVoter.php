<?php

namespace App\Security\Voter\Internal;

use App\Entity\Enum\InternalRole;
use App\Entity\FundReturn\FundReturn;
use App\Entity\UserTypeRoles;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Security\SubjectResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DenyActionsReturnVoter extends Voter
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
                InternalRole::HAS_VALID_VIEW_PERMISSION,
            ]) &&
            $this->subjectResolver->isValidSubjectForInternalRole($subject, $attribute) &&
            ($subject instanceof FundReturn || $subject instanceof SchemeReturn);
    }

    /**
     * @param FundReturn|SchemeReturn $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $resolvedSubject = $this->subjectResolver->resolveSubjectForRole($subject, $attribute);

        $fundReturn = match($resolvedSubject->getBaseClass()) {
            FundReturn::class => $subject,
            SchemeReturn::class => $subject->getFundReturn(),
        };

        if (
            $fundReturn &&
            $attribute === InternalRole::HAS_VALID_VIEW_PERMISSION
        ) {
            if ($fundReturn->getState() === FundReturn::STATE_SUBMITTED) {
                // This voter does not affect the viewing of "submitted" returns, but
                // it does later deny the viewing of "initial" returns...
                return true;
            }

            if ($this->authorizationChecker->isGranted(UserTypeRoles::ROLE_IAP_ADMIN)) {
                // ... except for admins
                return true;
            }
        }

        if (!$fundReturn || $fundReturn->getState() !== FundReturn::STATE_OPEN) {
            // Cannot <sign_off/mark_as_ready_edit> if the return isn't open, which means either:
            // a) Return is already submitted
            // b) Return is in the "initial" state
            return false;
        }

        return true;
    }
}
