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

    public function getSimplifiedPermissionStrings(User $user, Authority $authority): ?array
    {
        if ($authority->getAdmin() === $user) {
            return ['admin'];
        }

        $permissionsViews = $this->getPermissionViews($user, $authority);

        return array_map(fn(PermissionsView $p) => strtolower($p->getPermission()->value), $permissionsViews);
    }

    /**
     * @return array<PermissionsView>
     */
    public function getPermissionViews(User $user, Authority $authority): array
    {
        return $this->permissionsViewRepository->createQueryBuilder('pv')
            ->where('pv.userId = :user_id')
            ->andWhere('pv.authorityId = :authority_id')
            ->andWhere('pv.entityClass = :entity_class')
            ->setParameter('user_id', $user->getId(), UlidType::NAME)
            ->setParameter('authority_id', $authority->getId(), UlidType::NAME)
            ->setParameter('entity_class', Authority::class)
            ->getQuery()
            ->getResult();
    }
}
