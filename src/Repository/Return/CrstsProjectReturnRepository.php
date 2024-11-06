<?php

namespace App\Repository\Return;

use App\Entity\ProjectReturn\CrstsProjectReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CrstsProjectReturn>
 */
class CrstsProjectReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrstsProjectReturn::class);
    }
}
