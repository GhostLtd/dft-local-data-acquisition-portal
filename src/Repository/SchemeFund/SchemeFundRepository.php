<?php

namespace App\Repository\SchemeFund;

use App\Entity\Enum\Fund;
use App\Entity\SchemeFund\BsipSchemeFund;
use App\Entity\SchemeFund\CrstsSchemeFund;
use App\Entity\SchemeFund\SchemeFund;
use App\Entity\Authority;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<SchemeFund>
 */
class SchemeFundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SchemeFund::class);
    }

    /**
     * @return class-string<SchemeFund>
     */
    public function getSchemeFundClassForFund(?Fund $fund=null): string
    {
        return match($fund) {
            null => SchemeFund::class,
            Fund::BSIP => BsipSchemeFund::class,
            Fund::CRSTS1 => CrstsSchemeFund::class,
            Fund::CRSTS2 => throw new \RuntimeException('Not yet supported'),
        };
    }

    public function getSchemeFundsForAuthority(Authority $authority, Fund $fund=null): array
    {
        $schemeFundClass = $this->getSchemeFundClassForFund($fund);

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->select('schemeFund, scheme')
            ->from($schemeFundClass, 'schemeFund')
            ->join('schemeFund.scheme', 'scheme')
            ->join('scheme.authority', 'authority')
            ->where('authority.id = :authority_id')
            ->orderBy('scheme.name', 'ASC')
            ->getQuery()
            ->setParameter('authority_id', $authority->getId(), UlidType::NAME)
            ->getResult();
    }

    public function findForDashboard(string $id): ?SchemeFund
    {
        return $this->createQueryBuilder('schemeFund')
            ->select('schemeFund, scheme')
            ->join('schemeFund.scheme', 'scheme')
            ->where('schemeFund.id = :id')
            ->getQuery()
            ->setParameter('id', new Ulid($id), UlidType::NAME)
            ->getOneOrNullResult();
    }
}
