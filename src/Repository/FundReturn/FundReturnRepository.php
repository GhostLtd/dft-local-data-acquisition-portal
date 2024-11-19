<?php

namespace App\Repository\FundReturn;

use App\Entity\FundReturn\FundReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
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
        // If this query turns out to be too costly, split it into two so that
        // projects/projectFunds are fetched separately.
        return $this
            ->createQueryBuilder('fundReturn')
            ->select('fundReturn, sectionStatus, fundAward, recipient, projects, projectFunds')
            ->leftJoin('fundReturn.sectionStatuses', 'sectionStatus')
            ->join('fundReturn.fundAward', 'fundAward')
            ->join('fundAward.recipient', 'recipient')
            ->leftJoin('recipient.projects', 'projects')
            ->leftJoin('projects.projectFunds', 'projectFunds')
            ->where('fundReturn.id = :id')
            ->setParameter('id', (new Ulid($id))->toRfc4122())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
