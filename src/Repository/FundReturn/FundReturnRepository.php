<?php

namespace App\Repository\FundReturn;

use App\Entity\Enum\Fund;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Utility\FinancialQuarter;
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

    public function findForSpreadsheetExport(string $id): ?FundReturn
    {
        return $this
            ->getEntityManager()
            ->createQueryBuilder()
            ->from(CrstsFundReturn::class, 'fundReturn')
            ->select('fundReturn, fundAward, authority, expenses')
            ->join('fundReturn.fundAward', 'fundAward')
            ->join('fundAward.authority', 'authority')
            ->leftJoin('fundReturn.expenses', 'expenses')
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

    /**
     * @param FinancialQuarter $financialQuarter
     * @return array<string, array{fund: Fund, returns: array<int, FundReturn>}>
     */
    public function findFundReturnsForQuarterGroupedByFund(FinancialQuarter $financialQuarter): array
    {
        $returnsByFund = [];

        foreach($this->findFundReturnsForQuarter($financialQuarter) as $fundReturn) {
            $fund = $fundReturn->getFundAward()->getType();
            $returnsByFund[$fund->value] ??= [
                'fund' => $fund,
                'returns' => [],
            ];
            $returnsByFund[$fund->value]['returns'][] = $fundReturn;
        }

        ksort($returnsByFund);

        return $returnsByFund;
    }

    /**
     * @param FinancialQuarter $financialQuarter
     * @return array<int, FundReturn>
     */
    public function findFundReturnsForQuarter(FinancialQuarter $financialQuarter): array
    {
        return $this->createQueryBuilder('fundReturn')
            ->select('fundReturn, fundAward, authority')
            ->join('fundReturn.fundAward', 'fundAward')
            ->join('fundAward.authority', 'authority')
            ->where('fundReturn.quarter = :quarter')
            ->andWhere('fundReturn.year = :year')
            ->orderBy('fundAward.type', 'ASC')
            ->addOrderBy('authority.name', 'ASC')
            ->setParameter('quarter', $financialQuarter->quarter)
            ->setParameter('year', $financialQuarter->initialYear)
            ->getQuery()
            ->execute();
    }

    public function isMostRecentReturnForAward(FundReturn $fundReturn): bool
    {
        $award = $fundReturn->getFundAward();

        $latestReturn = $this->createQueryBuilder('fundReturn')
            ->join('fundReturn.fundAward', 'fundAward')
            ->where('fundAward.id = :award_id')
            ->orderBy('fundReturn.year', 'DESC')
            ->addOrderBy('fundReturn.quarter', 'DESC')
            ->setParameter('award_id', $award->getId(), UlidType::NAME)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $latestReturn === $fundReturn;
    }
}
