<?php

namespace App\Repository\FundReturn;

use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
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
            ->select('fundReturn, fundAward, authority')
            ->join('fundReturn.fundAward', 'fundAward')
            ->join('fundAward.authority', 'authority')
            ->where('fundReturn.id = :id')
            ->setParameter('id', new Ulid($id), UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Scheme $scheme
     * @return array<int, FundReturn>
     */
    public function findFundReturnsContainingScheme(Scheme $scheme): array
    {
        return $this->createQueryBuilder('fundReturn')
            ->select('fundReturn, schemeReturn, scheme')
            ->join('fundReturn.schemeReturns', 'schemeReturn')
            ->join('schemeReturn.scheme', 'scheme')
            ->where('scheme.id = :scheme_id')
            ->setParameter('scheme_id', new Ulid($scheme->getId()), UlidType::NAME)
            ->getQuery()
            ->execute();
    }
}
