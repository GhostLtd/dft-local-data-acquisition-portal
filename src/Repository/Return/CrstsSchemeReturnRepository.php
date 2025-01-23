<?php

namespace App\Repository\Return;

use App\Entity\SchemeReturn\CrstsSchemeReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CrstsSchemeReturn>
 */
class CrstsSchemeReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrstsSchemeReturn::class);
    }
}
