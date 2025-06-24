<?php

namespace App\Security\Voter\Internal;

use App\Entity\Enum\Fund;
use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Permission;
use App\Entity\User;
use App\Security\SubjectResolver;
use App\Security\UserPermissionValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PermissionVoter extends Voter
{
    public function __construct(
        protected EntityManagerInterface  $entityManager,
        protected UserPermissionValidator $userPermissionValidator,
        protected SubjectResolver         $subjectResolver,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [
            InternalRole::HAS_VALID_SIGN_OFF_PERMISSION,
            InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION,
            InternalRole::HAS_VALID_EDIT_PERMISSION,
        ])) {
            return false;
        }

        return $this->subjectResolver->isValidSubjectForInternalRole($subject, $attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $resolvedSubject = $this->subjectResolver->resolveSubjectForRole($subject, $attribute);

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($resolvedSubject->getAdmin() === $user) {
            return true;
        }

        // If you can sign_off, then you can also mark_as_ready. If you can mark_as_ready then you can also edit.
        $permissionsWhichConferTheDesiredAttribute = match ($attribute) {
            InternalRole::HAS_VALID_SIGN_OFF_PERMISSION => [Permission::SIGN_OFF],
            InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION => [Permission::SIGN_OFF, Permission::MARK_AS_READY],
            InternalRole::HAS_VALID_EDIT_PERMISSION => [Permission::SIGN_OFF, Permission::MARK_AS_READY, Permission::EDITOR],
            default => [],
        };

        $idMap = $resolvedSubject->getIdMap();

        foreach($user->getPermissions() as $userPermission) {
            if (!$this->userPermissionValidator->isUserPermissionValid($userPermission)) {
                // Skip. Permission is invalid
                continue;
            }

            if (!in_array($userPermission->getPermission(), $permissionsWhichConferTheDesiredAttribute)) {
                // Skip. This permission does not grant the requested role
                continue;
            }

            $entityClass = $userPermission->getEntityClass();
            $entityId = $userPermission->getEntityId();
            $fundTypes = $userPermission->getFundTypes();

            $match = true;
            if (!$entityId->equals($idMap[$entityClass] ?? null)) {
                $match = false;
            }

            if ($match && !empty($fundTypes)) {
                $match = in_array($idMap[Fund::class] ?? null, $fundTypes);
            }

            if ($match) {
                return true;
            }
        }

        return false;
    }
}
