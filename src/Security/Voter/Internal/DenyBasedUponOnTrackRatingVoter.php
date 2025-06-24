<?php

namespace App\Security\Voter\Internal;

use App\Entity\Enum\InternalRole;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Repository\SchemeReturn\SchemeReturnRepository;
use App\Security\SubjectResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DenyBasedUponOnTrackRatingVoter extends Voter
{
    public function __construct(
        protected LoggerInterface               $logger,
        protected SubjectResolver               $subjectResolver,
        protected SchemeReturnRepository        $schemeReturnRepository,
    ) {}

    protected function supports(string $attribute, mixed $subject): bool
    {
        return
            $subject instanceof CrstsSchemeReturn &&
            in_array($attribute, [
                InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION,
                InternalRole::HAS_VALID_EDIT_PERMISSION,
            ]) &&
            $this->subjectResolver->isValidSubjectForInternalRole($subject, $attribute);
    }

    /**
     * @param CrstsSchemeReturn $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        // If null, the scheme never had a non-editable (split/merged/complete) onTrackRating, OR only the current return does
        return $this->schemeReturnRepository->cachedFindPointWhereReturnBecameNonEditable($subject) === null;
    }
}
