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
        $entityManager = $this->getEntityManager();
        $conn = $entityManager->getConnection();

        $sql = <<<SQL
WITH
current_return AS (
    SELECT
        sr.scheme_id,
        fr.year,
        fr.quarter
    FROM scheme_return sr
    JOIN fund_return fr ON sr.fund_return_id = fr.id
    WHERE sr.id = :current_scheme_return_id
),
base_data AS (
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
    JOIN current_return cr ON cr.scheme_id = sr.scheme_id
    JOIN fund_return fr ON sr.fund_return_id = fr.id
),
previous_returns AS (
    SELECT
        bd.scheme_return_id,
        bd.year,
        bd.quarter,
        bd.is_closed
    FROM base_data bd
    JOIN current_return cr ON bd.scheme_id = cr.scheme_id
    WHERE (bd.year < cr.year) OR (bd.year = cr.year AND bd.quarter < cr.quarter)
    ORDER BY bd.year ASC, bd.quarter ASC
),
most_recent_open AS (
    SELECT p.scheme_return_id, p.year, p.quarter
    FROM previous_returns p
    WHERE p.is_closed = 0
    ORDER BY p.year DESC, p.quarter DESC
    LIMIT 1
),
relevant_returns AS (
    SELECT
        pr.scheme_return_id,
        pr.year,
        pr.quarter,
        pr.is_closed
    FROM previous_returns pr
    LEFT JOIN most_recent_open mo ON 1=1
    WHERE (pr.year > mo.year) OR (pr.year = mo.year AND pr.quarter > mo.quarter)
    ORDER BY pr.year ASC, pr.quarter ASC
)
SELECT scheme_return_id
FROM relevant_returns
ORDER BY year, quarter
LIMIT 1
SQL;

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('current_scheme_return_id', $currentSchemeReturn->getId(), UlidType::NAME);
        $result = $stmt->executeQuery();
        $row = $result->fetchAssociative();
        return $row
            ? $entityManager->find(CrstsSchemeReturn::class, $row['scheme_return_id'])
            : null;
    }
}
