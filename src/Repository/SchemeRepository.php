<?php

namespace App\Repository;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<Scheme>
 */
class SchemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Scheme::class);
    }

    public function getSchemesForAuthority(Authority $authority): array
    {
        return $this->getQueryBuilderForSchemesForAuthority($authority)
            ->getQuery()
            ->getResult();
    }

    public function getQueryBuilderForSchemesForAuthority(Authority $authority): QueryBuilder
    {
        return $this->createQueryBuilder('scheme')
            ->join('scheme.authority', 'authority')
            ->where('authority.id = :authority_id')
            ->orderBy('scheme.name', 'ASC')
            ->setParameter('authority_id', $authority->getId(), UlidType::NAME);
    }

    public function getQueryBuilderForSchemesForFundReturn(FundReturn $fundReturn): QueryBuilder
    {
        // We get the schemes from this direction, so that we can list all of them and explicitly any that
        // do not requiring a return, if that is the case (e.g. CRSTS - if not retained and not quarter 1)

        // (Fetching via fundReturn->getSchemeReturns() direction would only fetch those schemes that
        //  do have returns, resulting in an incomplete list)

        $fund = $fundReturn->getFund();

        return $this->createQueryBuilder('scheme')
            ->select('scheme, authority, fundAward, schemeReturn, fundReturn')
            ->join('scheme.authority', 'authority')
            ->leftJoin('authority.fundAwards', 'fundAward')
            ->leftJoin('fundAward.returns', 'fundReturn')
            ->leftJoin('fundReturn.schemeReturns', 'schemeReturn', Join::WITH, 'schemeReturn.scheme = scheme.id')
            ->where('fundReturn.id = :fund_return_id')
            ->orderBy('scheme.name', 'ASC')
            ->setParameter('fund_return_id', $fundReturn->getId(), UlidType::NAME);
    }

    public function findForDashboard(string $id): ?Scheme
    {
        return $this->createQueryBuilder('scheme')
            ->where('scheme.id = :id')
            ->getQuery()
            ->setParameter('id', new Ulid($id), UlidType::NAME)
            ->getOneOrNullResult();
    }
}
