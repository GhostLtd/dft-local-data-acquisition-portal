<?php

namespace App\Repository\SchemeReturn;

use App\Entity\Enum\OnTrackRating;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
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


    public function cachedFindPointWhereReturnBecameNonEditable(CrstsSchemeReturn $currentSchemeReturn): ?CrstsSchemeReturn
    {
        static $cache = [];
        $key = strval($currentSchemeReturn->getId());
        return $cache[$key] ?? ($cache[$key] = $this->findPointWhereReturnBecameNonEditable($currentSchemeReturn));
    }

    public function findPointWhereReturnBecameNonEditable(CrstsSchemeReturn $currentSchemeReturn): ?CrstsSchemeReturn
    {
        if (
            $currentSchemeReturn->getOnTrackRating() === null ||
            $currentSchemeReturn->getOnTrackRating()->shouldSchemeBeEditableInTheFuture()
        ) {
            // Well, it's currently editable (i.e. not split/merged/completed), so it never became non-editable
            return null;
        }

        return $this->getEntityManager()
                ->createQueryBuilder()
                ->select('schemeReturn, fundReturn')
                ->from(CrstsSchemeReturn::class, 'schemeReturn')
                ->join('schemeReturn.fundReturn', 'fundReturn')
                ->where('IDENTITY(schemeReturn.scheme) = :schemeId')
                ->andWhere('schemeReturn.onTrackRating IN (:ratings)')
                ->andWhere('schemeReturn.id != :currentSchemeReturnId')
                ->setParameter('schemeId', $currentSchemeReturn->getScheme()->getId(), UlidType::NAME)
                ->setParameter('ratings', OnTrackRating::getFutureNonEditableStates())
                ->setParameter('currentSchemeReturnId', $currentSchemeReturn->getId(), UlidType::NAME)
                ->orderBy('fundReturn.year')
                ->addOrderBy('fundReturn.quarter')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
    }
}
