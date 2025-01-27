<?php

namespace App\Repository;

use App\Entity\Authority;
use App\Utility\DoctrineUlidHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<Authority>
 */
class AuthorityRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry              $registry,
        protected DoctrineUlidHelper $doctrineUlidHelper,
    ) {
        parent::__construct($registry, Authority::class);
    }

    /**
     * @param array<int, Ulid> $authorityIds
     * @return array<int, Authority>
     */
    public function getAuthoritiesFundAwardsAndReturns(array $authorityIds): array
    {
        if (empty($authorityIds)) {
            return [];
        }

        $qb = $this->createQueryBuilder('authority');
        $whereInSQL = $this->doctrineUlidHelper->getSqlForWhereInAndInjectParams($qb, 'authority', $authorityIds);

        return $qb
            ->select('authority, admin, fundAward, return')
            ->join('authority.admin', 'admin')
            ->join('authority.fundAwards', 'fundAward')
            ->join('fundAward.returns', 'return')
            ->where("authority.id IN ({$whereInSQL})")
            ->orderBy('authority.name', 'ASC')
            ->addOrderBy('fundAward.type', 'ASC')
            ->addOrderBy('return.year', 'DESC')
            ->addOrderBy('return.quarter', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
