<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function loadUserByIdentifier(string $identifier): ?UserInterface
    {
        // N.B. This purposely uses a join rather than a leftJoin, so that users with no
        //      assigned recipientRoles will not be eligible to log in.
        return $this->createQueryBuilder('user')
            ->select('user, recipientRoles')
            ->join('user.recipientRoles', 'recipientRoles')
            ->where('user.email = :email')
            ->setParameter('email', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
