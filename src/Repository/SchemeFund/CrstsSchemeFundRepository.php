<?php

namespace App\Repository\SchemeFund;

use App\Entity\SchemeFund\CrstsSchemeFund;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CrstsSchemeFund>
 */
class CrstsSchemeFundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrstsSchemeFund::class);
    }
}
