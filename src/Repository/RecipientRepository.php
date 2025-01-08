<?php

namespace App\Repository;

use App\Entity\Recipient;
use App\Utility\DoctrineUlidHelper;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<Recipient>
 */
class RecipientRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry              $registry,
        protected DoctrineUlidHelper $doctrineUlidHelper,
    ) {
        parent::__construct($registry, Recipient::class);
    }

    /**
     * @param array<int, Ulid> $recipientIds
     * @return array<int, Recipient>
     */
    public function getRecipientsFundAwardsAndReturns(array $recipientIds): array
    {
        $qb = $this->createQueryBuilder('recipient');
        $whereInSQL = $this->doctrineUlidHelper->getSqlForWhereInAndInjectParams($qb, 'recipient', $recipientIds);

        return $qb
            ->select('recipient, fundAward, return')
            ->join('recipient.owner', 'owner')
            ->join('recipient.fundAwards', 'fundAward')
            ->join('fundAward.returns', 'return')
            ->where("recipient.id IN ({$whereInSQL})")
            ->orderBy('recipient.name', 'ASC')
            ->addOrderBy('fundAward.type', 'ASC')
            ->addOrderBy('return.year', 'DESC')
            ->addOrderBy('return.quarter', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
