<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

// N.B. Using Symfony's EntityProvider was not viable, as it seems a little confused in its design. It requires
//      UserLoaderInterface to override initial user load behaviour, but UserProviderInterface to override
//      user refresh behaviour, but these two interfaces are incompatible (they have different return signatures for
//      loadUserByIdentifier!)
//
//      As we want to override both of these behaviours, a custom provider was needed.
class UserProvider implements UserProviderInterface
{
    public function __construct(protected UserRepository $userRepository)
    {}

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException('Logic error: refreshUser() was presented with an unexpected User class');
        }

        return $this->loadUserByIdentifier($user->getEmail());
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // N.B. This purposely uses a join rather than a leftJoin, so that users with no
        //      assigned recipientRoles will not be eligible to log in.

        $user = $this->userRepository
            ->createQueryBuilder('user')
            ->select('user, permissions')
            ->leftJoin('user.permissions', 'permissions')
            ->where('user.email = :email')
            ->setParameter('email', $identifier)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            throw new UserNotFoundException();
        }

        return $user;
    }
}
