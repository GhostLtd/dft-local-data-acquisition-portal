<?php

namespace App\Repository\SchemeReturn;

use App\Entity\Enum\OnTrackRating;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<SchemeReturn>
 */
class SchemeReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchemeReturn::class);
    }

    public function findForDashboard(string $id): ?FundReturn
    {
        return $this
            ->createQueryBuilder('schemeReturn')
            ->select('schemeReturn')
            ->where('schemeReturn.id = :id')
            ->setParameter('id', new Ulid($id), UlidType::NAME)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findForSpreadsheetExport(CrstsFundReturn $fundReturn): array
    {
        return $this->getEntityManager()
            ->createQueryBuilder()
            ->from(CrstsSchemeReturn::class, 'schemeReturn')
            ->select('schemeReturn, scheme, milestones, expenses')
            ->join('schemeReturn.scheme', 'scheme')
            ->leftJoin('schemeReturn.milestones', 'milestones')
            ->leftJoin('schemeReturn.expenses', 'expenses')
            ->where('IDENTITY(schemeReturn.fundReturn) = :fundReturnId')
            ->setParameter('fundReturnId', $fundReturn->getId(), UlidType::NAME)
            ->getQuery()
            ->getResult();
    }
}
