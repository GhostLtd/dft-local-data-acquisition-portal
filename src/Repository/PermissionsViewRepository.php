<?php

namespace App\Repository;

use App\Entity\Authority;
use App\Entity\PermissionsView;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<PermissionsView>
 */
class PermissionsViewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PermissionsView::class);
    }

    public function getUsersForAuthority(Authority $authority): array
    {
        $em = $this->getEntityManager();

        $userIdsQuery = $this->createQueryBuilder('pv')
            ->select('pv.userId')
            ->where('pv.authorityId = :authorityId')
            ->getDQL();

        $users = $em
            ->createQueryBuilder()
            ->from(User::class, 'user', 'user.id')
            ->select('user')
            ->where($em->getExpressionBuilder()->in('user.id', $userIdsQuery))
            ->getQuery()
            ->setParameter('authorityId', $authority->getId(), UlidType::NAME)
            ->getResult();

        $admin = $authority->getAdmin();

        if ($admin) {
            $users[$admin->getId()->toRfc4122()] = $admin;
        }

        return $users;
    }
}
