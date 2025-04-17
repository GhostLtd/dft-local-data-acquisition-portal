<?php

namespace App\Utility;

use App\Entity\FundAward;
use App\Entity\FundReturn\FundReturn;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Types\UlidType;

class FundReturnCreator
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface        $logger,
    ) {}

    public function createRequiredFundReturns(): void
    {
        $nextQuarter = FinancialQuarter::createFromDate(new \DateTime())
            ->getPreviousQuarter();

        $this->createFundReturnsForFinancialQuarter($nextQuarter);
    }

    public function createFundReturnsForFinancialQuarter(FinancialQuarter $financialQuarter): void
    {
        $awards = $this->getFundAwardsWithNoFundReturnForFinancialQuarter($financialQuarter);
        $previousQuarter = $financialQuarter->getPreviousQuarter();

        $fundReturnRepository = $this->entityManager
            ->getRepository(FundReturn::class);

        foreach($awards as $award) {
            $authority = $award->getAuthority();

            /** @var FundReturn $previousReturn */
            $previousReturn = $fundReturnRepository
                ->createQueryBuilder('r')
                ->where('IDENTITY(r.fundAward) = :awardId')
                ->andWhere('r.year = :year')
                ->andWhere('r.quarter = :quarter')
                ->setParameter('awardId', $award->getId(), UlidType::NAME)
                ->setParameter('year', $previousQuarter->initialYear)
                ->setParameter('quarter', $previousQuarter->quarter)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$previousReturn) {
                $returnsForAward = $fundReturnRepository
                    ->createQueryBuilder('r')
                    ->where('IDENTITY(r.fundAward) = :awardId')
                    ->setParameter('awardId', $award->getId(), UlidType::NAME)
                    ->getQuery()
                    ->execute();

                if (count($returnsForAward) > 0) {
                    throw new \RuntimeException("Previous return not found for {$authority->getName()}, despite other returns existing (Searched for {$previousQuarter->initialYear}Q{$previousQuarter->quarter})");
                }

                $fundReturnClass = $award->getType()->getFundReturnClass();

                $this->logger->info("Created initial return for {$authority->getName()} {$financialQuarter->initialYear}Q{$financialQuarter->quarter}");
                $newReturn = $fundReturnClass::createInitialFundReturnStartingAt($financialQuarter, $award);
            } else {
                $this->logger->info("Created return for {$authority->getName()} {$financialQuarter->initialYear}Q{$financialQuarter->quarter}");
                $newReturn = $previousReturn->createFundReturnForNextQuarter();
            }

            $this->entityManager->persist($newReturn);
            $this->entityManager->flush();
        }
    }

    /**
     * @return array<int, FundAward>
     */
    public function getFundAwardsWithNoFundReturnForFinancialQuarter(FinancialQuarter $financialQuarter): array
    {
        $subQuery = $this->entityManager
            ->getRepository(FundAward::class)
            ->createQueryBuilder('s')
            ->join('s.returns', 'r')
            ->where('r.year = :year')
            ->andWhere('r.quarter = :quarter')
            ->andWhere('s.id = a.id');

        $queryBuilder = $this->entityManager
            ->getRepository(FundAward::class)
            ->createQueryBuilder('a');

        $queryBuilder
            ->where($queryBuilder->expr()->not(
                $queryBuilder->expr()->exists(
                    $subQuery->getDQL()
                )
            ))
            ->setParameter('year', $financialQuarter->initialYear)
            ->setParameter('quarter', $financialQuarter->quarter);

        return $queryBuilder->getQuery()->execute();
    }
}
