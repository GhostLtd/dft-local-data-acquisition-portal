<?php

namespace App\Repository;

use App\Entity\Recipient;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Recipient>
 */
class RecipientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Recipient::class);
    }

    public function getRecipientsFundAwardsAndReturnsForUser(User $user): array
    {
        return $this->createQueryBuilder('recipient')
            ->select('recipient, fundAward, return')
            ->join('recipient.userRoles', 'recipientRole')
            ->join('recipientRole.user', 'user')
            ->join('recipient.fundAwards', 'fundAward')
            ->join('fundAward.returns', 'return')
            ->where('user.id = :userId')
            ->orderBy('recipient.name', 'ASC')
            ->addOrderBy('fundAward.type', 'ASC')
            ->addOrderBy('return.year', 'DESC')
            ->addOrderBy('return.quarter', 'DESC')
            ->setParameter('userId', $user->getId()->toRfc4122())
            ->getQuery()
            ->getResult();
    }
}
