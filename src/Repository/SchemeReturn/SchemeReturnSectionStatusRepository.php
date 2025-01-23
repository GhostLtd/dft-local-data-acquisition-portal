<?php

namespace App\Repository\SchemeReturn;

use App\Entity\SchemeReturn\SchemeReturnSectionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SchemeReturnSectionStatus>
 */
class SchemeReturnSectionStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchemeReturnSectionStatus::class);
    }
}
