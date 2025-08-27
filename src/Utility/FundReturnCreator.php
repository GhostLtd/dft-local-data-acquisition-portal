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

    public function getLatestFinancialQuarterToCreate(): FinancialQuarter
    {
        // This is the amount of time in advance we create new surveys
        $cutoffDate = (new \DateTime('midnight today'))->modify('+3 weeks');

        return FinancialQuarter::createFromDate($cutoffDate)
            ->getPreviousQuarter();
    }

    public function createRequiredFundReturns(): void
    {
        $nextQuarter = $this->getLatestFinancialQuarterToCreate();
        $this->createFundReturnsForFinancialQuarter($nextQuarter);
    }

    public function createFundReturnsForFinancialQuarter(FinancialQuarter $financialQuarter): void
    {
        $awards = $this->getFundAwardsWithNoFundReturnForFinancialQuarter($financialQuarter);

        if (empty($awards)) {
            $nextYear = substr($financialQuarter->initialYear + 1, 2);
            $this->logger->info("No new returns required for {$financialQuarter->initialYear}/{$nextYear} Q{$financialQuarter->quarter}");
            return;
        }

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

            $returnName = "{$authority->getName()} {$financialQuarter->initialYear}Q{$financialQuarter->quarter}";
            if ($previousReturn && $previousReturn->getState() !== FundReturn::STATE_SUBMITTED) {
                $this->logger->error("Unable to create return for {$returnName}: Previous return is not in the submitted state");
                continue;
            }

            if (!$previousReturn) {
                $returnsForAward = $fundReturnRepository
                    ->createQueryBuilder('r')
                    ->where('IDENTITY(r.fundAward) = :awardId')
                    ->setParameter('awardId', $award->getId(), UlidType::NAME)
                    ->getQuery()
                    ->execute();

                if (count($returnsForAward) > 0) {
                    $this->logger->error("Unable to create return for {$returnName}: Previous return not found, despite other returns existing (Searched for {$previousQuarter->initialYear}Q{$previousQuarter->quarter})");
                    continue;
                }

                $fundReturnClass = $award->getType()->getFundReturnClass();

                $message = "Created initial return for {$returnName}";
                $newReturn = $fundReturnClass::createInitialFundReturnStartingAt($financialQuarter, $award);
            } else {
                $message = "Created return for {$returnName}";
                $newReturn = $previousReturn->createFundReturnForNextQuarter();
            }

            $this->entityManager->persist($newReturn);
            $this->entityManager->flush();
            $this->logger->info($message);
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
