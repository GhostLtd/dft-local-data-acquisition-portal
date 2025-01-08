<?php

namespace App\Repository\ProjectReturn;

use App\Entity\FundReturn\FundReturn;
use App\Entity\ProjectReturn\ProjectReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<ProjectReturn>
 */
class ProjectReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectReturn::class);
    }

    public function findForDashboard(string $id): ?FundReturn
    {
        return $this
            ->createQueryBuilder('projectReturn')
            ->select('projectReturn')
            ->where('projectReturn.id = :id')
            ->setParameter('id', new Ulid($id), UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
