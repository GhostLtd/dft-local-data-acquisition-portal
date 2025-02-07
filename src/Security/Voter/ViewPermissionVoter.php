<?php

namespace App\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\FundReturn\FundReturn;
use App\Entity\PermissionsView;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\Authority;
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
        if ($attribute !== InternalRole::HAS_VALID_VIEW_PERMISSION) {
            return false;
        }

        return $this->subjectResolver->isValidSubjectForInternalRole($subject, InternalRole::HAS_VALID_VIEW_PERMISSION);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        $resolvedSubject = $this->subjectResolver->resolveSubjectForRole($subject, InternalRole::HAS_VALID_VIEW_PERMISSION);

        if ($user === $resolvedSubject->getAdmin()) {
            // dump("{$baseEntityClass} {$subject->getId()} - Found a match - is admin");
            return true;
        }

        $permissionViews = $this->getPermissionsViewsForUser($user);

        $idMap = $resolvedSubject->getIdMap();
        $subjectBaseClass = $resolvedSubject->getBaseClass();

        $permissionClassChain = match ($subjectBaseClass) {
            Authority::class => [Authority::class],
            FundReturn::class => [FundReturn::class, Authority::class],
            SchemeReturn::class => [SchemeReturn::class, Scheme::class, FundReturn::class, Authority::class],
            Scheme::class => [Scheme::class, Authority::class],
        };

        foreach($permissionViews as $permissionView) {
            $match = true;

            foreach($permissionClassChain as $class) {
                $permissionEntityId = match ($class) {
                    Authority::class => $permissionView->getAuthorityId(),
                    FundReturn::class => $permissionView->getFundReturnId(),
                    SchemeReturn::class => $permissionView->getSchemeReturnId(),
                    Scheme::class => $permissionView->getSchemeId(),
                };

                $keyId = $idMap[$class] ?? null;

                if ($permissionEntityId && $keyId && !$keyId->equals($permissionEntityId)) {
                    $match = false;
                    break;
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
