<?php

namespace App\Utility;

use App\Entity\Enum\Permission;
use App\Entity\FundReturn\FundReturn;
use App\Entity\PermissionsView;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\Authority;
use App\Entity\User;
use App\Entity\UserPermission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Uid\Ulid;

class UserReachableEntityResolver
{
    protected array $cache;

    public function __construct(
        protected DoctrineUlidHelper     $doctrineUlidHelper,
        protected EntityManagerInterface $entityManager
    ) {
        $this->cache = [];
    }

    public function isAuthorityReachableBy(Authority $authority, User $user): bool
    {
        foreach($this->getAuthorityIdsViewableBy($user) as $authorityId) {
            if ($authorityId->equals($authority->getId())) {
                return true;
            }
        }

        return false;
    }

    public function getAuthorityIdsViewableBy(User $user): array
    {
        return $this->getReachableIdsInferredFromUserPermissions($user, Authority::class);
    }

    /**
     * Based on the permissions and admins, and looking *downstream*, what things are reachable?
     *
     * By "downstream" it is meant for example that:
     * - from a permission on a FundReturn, we can infer the ability to view the
     *   Authority to which it belongs, but we are not looking at the SchemeReturns
     *   that are owned by the FundReturn
     *
     * - from a permission on a Scheme, we can infer the ability to view the
     *   Authority to which it belongs, but we are not looking at the SchemeReturns
     *   that are owned by that Scheme
     *
     * @return array<int, Ulid>
     */
    public function getReachableIdsInferredFromUserPermissions(User $user, string $entityType): array
    {
        $key = "{$user->getId()}-{$entityType}";

        if (!isset($this->cache[$key])) {
            $this->cache[$key] = $this->calculateReachableIdsInferredFromUserPermissions($user, $entityType);
        }

        return $this->cache[$key];
    }

    protected function calculateReachableIdsInferredFromUserPermissions(User $user, string $entityType): array
    {
        $reachableIds = [];

        $add = function (?Ulid $ulid) use (&$reachableIds) {
            if ($ulid) {
                $reachableIds[strval($ulid)] = $ulid;
            }
        };

        if ($entityType === Authority::class) {
            foreach($user->getAuthoritiesAdminOf() as $authority) {
                $add($authority->getId());
            }

            foreach($user->getPermissions() as $userPermission) {
                if ($userPermission->getEntityClass() === Authority::class) {
                    $add(Ulid::fromString($userPermission->getEntityId()));
                }
            }
        }

        $permissions = $user->getPermissions()->toArray();
        foreach($this->getPermissionsViewsFor($permissions) as $permissionsView) {
            $ids = match ($entityType) {
                Authority::class => $permissionsView->getAuthorityId(),
                FundReturn::class => $permissionsView->getFundReturnId(),
                Scheme::class => $permissionsView->getSchemeId(),
                SchemeReturn::class => $permissionsView->getSchemeReturnId(),
                default => throw new \RuntimeException('Invalid entity type'),
            };

            $add($ids);
        }

        // Remove keys - they were only there for the purpose of deduplication
        return array_values($reachableIds);
    }

    /**
     * @return array<int, PermissionsView>
     */
    protected function getPermissionsViewsFor(array $permissions): array
    {
        $interestingPermissions = [
            Permission::SIGN_OFF,
            Permission::MARK_AS_READY,
            Permission::EDITOR,
            Permission::VIEWER,
        ];

        $getIds = fn(array $userPermissions) => array_values(array_map(fn(UserPermission $p) => Ulid::fromString($p->getEntityId()), $userPermissions));
        $getFilteredType = fn(string $entityClass) => $getIds(array_filter($permissions,
            fn(UserPermission $p) => $p->getEntityClass() === $entityClass &&
                in_array($p->getPermission(), $interestingPermissions)
        ));

        $idsByColumn = [
            'fundReturnId' => $getFilteredType(FundReturn::class),
            'schemeId' => $getFilteredType(Scheme::class),
            'schemeReturnId' => $getFilteredType(SchemeReturn::class),
        ];

        $qb = $this->entityManager
            ->getRepository(PermissionsView::class)
            ->createQueryBuilder('p');

        $hasIds = false;
        foreach($idsByColumn as $column => $ids) {
            if (!empty($ids)) {
                $hasIds = true;
                $whereInSQL = $this->doctrineUlidHelper->getSqlForWhereInAndInjectParams($qb, $column, $ids);
                $qb->orWhere("p.{$column} IN ({$whereInSQL})");
            }
        }

        return $hasIds ?
            $qb->getQuery()->execute() :
            [];
    }
}
