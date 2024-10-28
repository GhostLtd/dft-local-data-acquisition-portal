<?php

namespace App\Repository\Utility;

use App\Entity\Utility\MaintenanceLock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class MaintenanceLockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaintenanceLock::class);
    }

    /** @return array{0: bool, 1: array<string>} */
    public function getIsActiveAndWhitelistedIps(): array
    {
        try {
            $result =  $this->createQueryBuilder('m')
                ->select('m.whitelistedIps')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleScalarResult();

            $whitelistedIps = json_decode($result, true);
            return [true, $whitelistedIps];
        } catch (NoResultException) {
            return [false, []];
        }
    }
}
