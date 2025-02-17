<?php

namespace App\Utility;

use App\Entity\Authority;
use App\Entity\Enum\Permission;
use App\Entity\PermissionsView;
use App\Entity\User;
use App\Repository\PermissionsViewRepository;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * Essentially the system provides a comprehensive and granular permissions system, but we need to make to
 * act like a much simpler one in order to demo the app. This class provides routines to help to that end.
 */
class SimplifiedPermissionsHelper
{
    public function __construct(
        protected PermissionsViewRepository $permissionsViewRepository
    ) {}

    public function getSimplifiedPermissionAsString(User $user, Authority $authority): ?string
    {
        if ($authority->getAdmin() === $user) {
            return 'admin';
        }

        $bestPermissionsView = $this->getBestPermissionView($user, $authority);

        if ($bestPermissionsView) {
            return strtolower($bestPermissionsView->getPermission()->value);
        }

        return null;
    }

    /**
     * Look at the userPermissions that the user has which are applied to a matching
     * Authority, and return the one that has the most powerful permission, if any.
     */
    public function getBestPermissionView(User $user, Authority $authority): ?PermissionsView
    {
        /** @var array<PermissionsView> $permissions */
        $permissions = $this->permissionsViewRepository->createQueryBuilder('pv')
            ->where('pv.userId = :user_id')
            ->andWhere('pv.authorityId = :authority_id')
            ->andWhere('pv.entityClass = :entity_class')
            ->setParameter('user_id', $user->getId(), UlidType::NAME)
            ->setParameter('authority_id', $authority->getId(), UlidType::NAME)
            ->setParameter('entity_class', Authority::class)
            ->getQuery()
            ->getResult();

        $permissionOrdering = [
            Permission::VIEWER->value => 1,
            Permission::EDITOR->value => 2,
            Permission::MARK_AS_READY->value => 3,
            Permission::SIGN_OFF->value => 4,
        ];

        $bestPermissionView = null;
        $bestPermissionScore = 0;

        foreach($permissions as $permissionView) {
            $permission = $permissionView->getPermission();
            $score = $permissionOrdering[$permission->value] ?? 0;

            if ($score > $bestPermissionScore) {
                $bestPermissionView = $permissionView;
                $bestPermissionScore = $score;
            }
        }

        return $bestPermissionView;
    }
}
