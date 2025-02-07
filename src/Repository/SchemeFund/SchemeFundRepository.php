<?php

namespace App\Repository\SchemeFund;

use App\Entity\Enum\Fund;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeFund\BsipSchemeFund;
use App\Entity\SchemeFund\CrstsSchemeFund;
use App\Entity\SchemeFund\SchemeFund;
use App\Entity\Authority;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<SchemeFund>
 */
class SchemeFundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchemeFund::class);
    }

    /**
     * @return class-string<SchemeFund>
     */
    public function getSchemeFundClassForFund(?Fund $fund=null): string
    {
        return match($fund) {
            null => SchemeFund::class,
            Fund::BSIP => BsipSchemeFund::class,
            Fund::CRSTS1 => CrstsSchemeFund::class,
            Fund::CRSTS2 => throw new \RuntimeException('Not yet supported'),
        };
    }

    public function getSchemeFundsForAuthority(Authority $authority, Fund $fund=null): array
    {
        return $this->getQueryBuilderForSchemeFundsForAuthority($authority, $fund)
            ->getQuery()
            ->getResult();
    }

    public function getQueryBuilderForSchemeFundsForAuthority(Authority $authority, Fund $fund=null): QueryBuilder
    {
        $schemeFundClass = $this->getSchemeFundClassForFund($fund);

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('schemeFund, scheme')
            ->from($schemeFundClass, 'schemeFund')
            ->join('schemeFund.scheme', 'scheme')
            ->join('scheme.authority', 'authority')
            ->where('authority.id = :authority_id')
            ->orderBy('scheme.name', 'ASC')
            ->setParameter('authority_id', $authority->getId(), UlidType::NAME);
    }

    public function getQueryBuilderForSchemeReturnsForFundReturn(FundReturn $fundReturn): QueryBuilder
    {
        // We get the schemeFunds from this direction, so that we can list all of them and explicitly any that
        // do not requiring a return, if that is the case (e.g. CRSTS - if not retained and not quarter 1)

        // (Fetching via fundReturn->getSchemeReturns() direction would only fetch those schemes that
        //  do have returns, resulting in an incomplete list)

        $fund = $fundReturn->getFund();
        $schemeFundClass = $this->getSchemeFundClassForFund($fund);

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('schemeFund, scheme, authority, fundAward, schemeReturn, fundReturn')
            ->from($schemeFundClass, 'schemeFund')
            ->join('schemeFund.scheme', 'scheme')
            ->join('scheme.authority', 'authority')
            ->leftJoin('authority.fundAwards', 'fundAward')
            ->leftJoin('fundAward.returns', 'fundReturn')
            ->leftJoin('fundReturn.schemeReturns', 'schemeReturn', Join::WITH, 'schemeReturn.schemeFund = schemeFund')
            ->where('fundReturn.id = :fund_return_id')
            ->orderBy('scheme.name', 'ASC')
            ->setParameter('fund_return_id', $fundReturn->getId(), UlidType::NAME)
        ;
    }

    public function findForDashboard(string $id): ?SchemeFund
    {
        return $this->createQueryBuilder('schemeFund')
            ->select('schemeFund, scheme')
            ->join('schemeFund.scheme', 'scheme')
            ->where('schemeFund.id = :id')
            ->getQuery()
            ->setParameter('id', new Ulid($id), UlidType::NAME)
            ->getOneOrNullResult();
    }
}
