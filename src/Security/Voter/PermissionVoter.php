<?php

namespace App\Security\Voter;

use App\Entity\Enum\Fund;
use App\Entity\Enum\Permission;
use App\Entity\Enum\Role;
use App\Entity\SchemeReturn\SchemeReturn;
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
        if (!in_array($attribute, [Role::CAN_SUBMIT, Role::CAN_COMPLETE, Role::CAN_EDIT])) {
            return false;
        }

        return $this->subjectResolver->isValidSubjectForRole($subject, $attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $resolvedSubject = $this->subjectResolver->resolveSubjectForRole($subject, $attribute);

        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if (
            $attribute === Role::CAN_EDIT &&
            $resolvedSubject->getBaseClass() === SchemeReturn::class
        ) {
            /** @var $entity SchemeReturn */
            $entity = $resolvedSubject->getEntity();
            if ($entity->getReadyForSignoff() === true) {
                // No editing if the scheme's been marked as ready for sign-off
                return false;
            }
        }

        if ($resolvedSubject->getAdmin() === $user) {
            return true;
        }

        $permissionsWhichAreValidForGivenAttribute = match ($attribute) {
            Role::CAN_SUBMIT => [Permission::SUBMITTER],
            Role::CAN_COMPLETE => [Permission::SUBMITTER, Permission::CHECKER],
            Role::CAN_EDIT => [Permission::SUBMITTER, Permission::CHECKER, Permission::EDITOR],
            default => [],
        };

        $subjectSection = $resolvedSubject->getSection();
        $idMap = $resolvedSubject->getIdMap();

        foreach($user->getPermissions() as $userPermission) {
            if (!$this->userPermissionValidator->isUserPermissionValid($userPermission)) {
                // Skip. Permission is invalid
                continue;
            }

            if (!in_array($userPermission->getPermission(), $permissionsWhichAreValidForGivenAttribute)) {
                // Skip. This permission does not grant the requested role
                continue;
            }

            $entityClass = $userPermission->getEntityClass();
            $entityId = $userPermission->getEntityId();
            $fundTypes = $userPermission->getFundTypes();
            $sectionTypes = $userPermission->getSectionTypes();

            $match = true;
            if (!$entityId->equals($idMap[$entityClass] ?? null)) {
                $match = false;
            }

            if ($match && !empty($fundTypes)) {
                $match = in_array($idMap[Fund::class] ?? null, $fundTypes);
            }

            if ($match && !empty($sectionTypes)) {
                if ($entityClass !== $resolvedSubject->getBaseClass()) {
                    $match = false;
                } else {
                    $match = in_array($subjectSection, $sectionTypes);
                }
            }

            if ($match) {
                return true;
            }
        }

        return false;
    }
}
