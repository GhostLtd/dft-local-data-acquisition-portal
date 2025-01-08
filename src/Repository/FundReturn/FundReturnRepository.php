<?php

namespace App\Repository\FundReturn;

use App\Entity\FundReturn\FundReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<FundReturn>
 */
class FundReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FundReturn::class);
    }

    public function findForDashboard(string $id): ?FundReturn
    {
        return $this
            ->createQueryBuilder('fundReturn')
            ->select('fundReturn, sectionStatus, fundAward, recipient')
            ->leftJoin('fundReturn.sectionStatuses', 'sectionStatus')
            ->join('fundReturn.fundAward', 'fundAward')
            ->join('fundAward.recipient', 'recipient')
            ->where('fundReturn.id = :id')
            ->setParameter('id', new Ulid($id), UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
