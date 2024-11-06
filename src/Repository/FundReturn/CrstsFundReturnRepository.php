<?php

namespace App\Repository\FundReturn;

use App\Entity\FundReturn\CrstsFundReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CrstsFundReturn>
 */
class CrstsFundReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrstsFundReturn::class);
    }
}
