<?php

namespace App\Repository\ProjectFund;

use App\Entity\ProjectFund\CrstsProjectFund;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CrstsProjectFund>
 */
class CrstsProjectFundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrstsProjectFund::class);
    }
}
