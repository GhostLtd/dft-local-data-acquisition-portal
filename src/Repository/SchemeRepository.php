<?php

namespace App\Repository;

use App\Entity\Authority;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<Scheme>
 */
class SchemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Scheme::class);
    }

    public function getSchemesForAuthority(Authority $authority): array
    {
        return $this->getQueryBuilderForSchemesForAuthority($authority)
            ->getQuery()
            ->getResult();
    }

    public function getQueryBuilderForSchemesForAuthority(Authority $authority, bool $noOrder=false): QueryBuilder
    {
        $qb = $this->createQueryBuilder('scheme')
            ->join('scheme.authority', 'authority')
            ->where('authority.id = :authority_id')
            ->setParameter('authority_id', $authority->getId(), UlidType::NAME);

        if (!$noOrder) {
            $qb->orderBy('scheme.name', 'ASC');
        }

        return $qb;
    }

    public function getQueryBuilderForSchemesForFundReturn(FundReturn $fundReturn): QueryBuilder
    {
        return $this->createQueryBuilder('scheme')
            ->select('scheme, authority, fundAward, schemeReturn, fundReturn')
            ->join('scheme.authority', 'authority')
            ->join('authority.fundAwards', 'fundAward')
            ->join('fundAward.returns', 'fundReturn')
            ->join('fundReturn.schemeReturns', 'schemeReturn', Join::WITH, 'schemeReturn.scheme = scheme.id')
            ->where('fundReturn.id = :fund_return_id')
            ->orderBy('scheme.name', 'ASC')
            ->setParameter('fund_return_id', $fundReturn->getId(), UlidType::NAME);
    }

    /**
     * @return array{previous: ?Ulid, next: ?Ulid}
     */
    public function getPreviousAndNextSchemes(FundReturn $fundReturn, Scheme $currentScheme): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
    SELECT prev_id, next_id
    FROM (
      SELECT
          s.id AS scheme_id,
          LAG(s.id)  OVER (ORDER BY s.name, s.id) AS prev_id,
          LEAD(s.id) OVER (ORDER BY s.name, s.id) AS next_id
      FROM scheme s
      JOIN authority a      ON a.id = s.authority_id
      JOIN fund_award fa    ON fa.authority_id = a.id
      JOIN fund_return fr   ON fr.fund_award_id = fa.id
      JOIN scheme_return sr ON sr.scheme_id = s.id AND sr.fund_return_id = fr.id
      WHERE fr.id = :fund_return_id
    ) AS scoped
    WHERE scheme_id = :current_scheme_id
    LIMIT 1
SQL;

        try {
            $row = $conn->fetchAssociative(
                $sql, [
                'fund_return_id' => $fundReturn->getId(),
                'current_scheme_id' => $currentScheme->getId(),
            ], [
                    'fund_return_id' => UlidType::NAME,
                    'current_scheme_id' => UlidType::NAME,
                ]
            );
        } catch (Exception) {
            $row = null;
        }

        if (!$row) {
            return ['previous' => null, 'next' => null];
        }

        return [
            'previous' => $row['prev_id'] !== null ? Ulid::fromBinary($row['prev_id']) : null,
            'next' => $row['next_id'] !== null ? Ulid::fromBinary($row['next_id']) : null,
        ];
    }

    public function findForDashboard(string $id): ?Scheme
    {
        return $this->createQueryBuilder('scheme')
            ->where('scheme.id = :id')
            ->getQuery()
            ->setParameter('id', new Ulid($id), UlidType::NAME)
            ->getOneOrNullResult();
    }
}
