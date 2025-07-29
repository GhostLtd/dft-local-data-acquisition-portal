<?php

namespace App\Repository\SchemeReturn;

use App\Entity\SchemeReturn\CrstsSchemeReturn;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;

/**
 * @extends ServiceEntityRepository<CrstsSchemeReturn>
 */
class CrstsSchemeReturnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrstsSchemeReturn::class);
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

        $entityManager = $this->getEntityManager();
        $conn = $entityManager->getConnection();

        $sql = <<<SQL
WITH base_data AS (
  SELECT 
    sr.id AS scheme_return_id,
    sr.scheme_id,
    fr.year,
    fr.quarter,
    csr.on_track_rating,
    CASE
      WHEN csr.on_track_rating IN ('scheme_completed', 'scheme_merged', 'scheme_split') THEN true
      ELSE false
    END AS is_closed
  FROM crsts_scheme_return csr
  JOIN scheme_return sr ON csr.id = sr.id
  JOIN fund_return fr ON sr.fund_return_id = fr.id
),

target_return AS (
  SELECT 
    sr.scheme_id AS target_scheme_id,
    fr.year AS target_year,
    fr.quarter AS target_quarter
  FROM scheme_return sr
  JOIN fund_return fr ON sr.fund_return_id = fr.id
  WHERE sr.id = :target_scheme_return_id
),

prior_return AS (
  SELECT bd.*
  FROM base_data bd
  JOIN target_return tr ON bd.scheme_id = tr.target_scheme_id
  WHERE (
    bd.year < tr.target_year OR
    (bd.year = tr.target_year AND bd.quarter < tr.target_quarter)
  )
  ORDER BY bd.year DESC, bd.quarter DESC
  LIMIT 1
)

SELECT *
FROM prior_return
WHERE is_closed = true;
SQL;

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('target_scheme_return_id', $currentSchemeReturn->getId(), UlidType::NAME);
        $result = $stmt->executeQuery();
        $row = $result->fetchAssociative();
        return $row
            ? $entityManager->find(CrstsSchemeReturn::class, $row['scheme_return_id'])
            : null;
    }
}
