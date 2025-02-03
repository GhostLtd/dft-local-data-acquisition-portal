<?php

namespace App\Repository;

use App\Entity\Authority;
use App\Entity\PermissionsView;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getAllForAuthorityQueryBuilder(Authority $authority): QueryBuilder
    {
        $em = $this->getEntityManager();

        $userIdsQuery = $em->createQueryBuilder()
            ->from(PermissionsView::class, 'pv')
            ->select('pv.userId')
            ->where('pv.authorityId = :authorityId')
            ->getDQL();

        return $this
            ->createQueryBuilder('user', 'user.id')
            ->where(new Expr\Orx([
                $em->getExpressionBuilder()->in('user.id', $userIdsQuery),
                $em->getExpressionBuilder()->eq('user.id', ':userId')
            ]))
            ->setParameter('userId', $authority->getAdmin()->getId()->toRfc4122())
            ->setParameter('authorityId', $authority->getId()->toRfc4122())
        ;
    }

    public function findAllForAuthority(Authority $authority): array
    {
        return $this->getAllForAuthorityQueryBuilder($authority)->getQuery()->getResult();
    }
}
