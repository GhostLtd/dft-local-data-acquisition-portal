<?php

namespace App\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\FundReturn;
use App\Entity\PermissionsView;
use App\Entity\Project;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Entity\Recipient;
use App\Entity\User;
use App\Security\SubjectResolver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ViewPermissionVoter extends Voter
{
    protected array $cache;

    public function __construct(
        protected EntityManagerInterface  $entityManager,
        protected SubjectResolver         $subjectResolver,
    ) {
        $this->cache = [];
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute !== Role::CAN_VIEW) {
            return false;
        }

        return $this->subjectResolver->isValidSubjectForRole($subject, Role::CAN_VIEW);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $resolvedSubject = $this->subjectResolver->resolveSubjectForRole($subject, Role::CAN_VIEW);

        if ($user === $resolvedSubject->getOwner()) {
            // dump("{$baseEntityClass} {$subject->getId()} - Found a match - is owner");
            return true;
        }

        $permissionViews = $this->getPermissionsViewsForUser($user);

        $idMap = $resolvedSubject->getIdMap();
        $subjectBaseClass = $resolvedSubject->getBaseClass();
        $subjectSection = $resolvedSubject->getSection();

        $permissionClassChain = match ($subjectBaseClass) {
            Recipient::class => [Recipient::class],
            FundReturn::class => [FundReturn::class, Recipient::class],
            ProjectReturn::class => [ProjectReturn::class, Project::class, FundReturn::class, Recipient::class],
            Project::class => [Project::class, Recipient::class],
        };

        foreach($permissionViews as $permissionView) {
            $match = true;

            $permissionSectionTypes = $permissionView->getSectionTypes();
            $permissionEntityClass = $permissionView->getEntityClass();

            if (!empty($permissionSectionTypes)) {
                if (
                    $permissionEntityClass === $subjectBaseClass &&
                    $subjectSection !== null &&
                    !in_array($subjectSection, $permissionSectionTypes)
                ) {
                    // Section type doesn't match. Skip
                    continue;
                }

                if (
                    $subjectSection !== null &&
                    $permissionEntityClass === FundReturn::class &&
                    $subjectBaseClass === ProjectReturn::class
                ) {
                    continue;
                }
            }

            foreach($permissionClassChain as $class) {
                $permissionEntityId = match ($class) {
                    Recipient::class => $permissionView->getRecipientId(),
                    FundReturn::class => $permissionView->getFundReturnId(),
                    ProjectReturn::class => $permissionView->getProjectReturnId(),
                    Project::class => $permissionView->getProjectId(),
                };

                $keyId = $idMap[$class] ?? null;

                if ($permissionEntityId && $keyId && !$keyId->equals($permissionEntityId)) {
                    $match = false;
                    break;
                }

                if ($resolvedSubject->getSection() !== null) {
                    // A Project/ProjectReturn permission does only gives access to Project/ProjectReturn sections
                    if (in_array($permissionView->getEntityClass(), [Project::class, ProjectReturn::class])) {
                        if (!in_array($subjectBaseClass, [Project::class, ProjectReturn::class])) {
                            $match = false;
                            break;
                        }
                    }
                }
            }

            if ($match) {
//                dump("{$subjectBaseClass} {$resolvedSubject->getEntity()->getId()} - Found a match for {$permissionView->getEntityClass()}");
                return true;
            }
        }

//         dump("{$subjectBaseClass} {$subject->getId()} - No match");
        return false;
    }


    /**
     * @return array<int, PermissionsView>
     */
    protected function getPermissionsViewsForUser(User $user): array
    {
        $key = strval($user->getId());

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->entityManager
                ->getRepository(PermissionsView::class)
                ->findBy(['userId' => $user->getId()]);
        }

        return $this->cache[$key];
    }
}
