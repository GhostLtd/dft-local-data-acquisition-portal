<?php

namespace App\Tests\DataFixtures\Form\Type\Admin;

use App\Entity\Authority;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class AuthorityTypeFixtures extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        // Create some users for the existing_admin dropdown
        $user1 = (new User())
            ->setName('John Doe')
            ->setEmail('john.doe@example.com')
            ->setPosition('Manager')
            ->setPhone('01234567890');

        $user2 = (new User())
            ->setName('Jane Smith')
            ->setEmail('jane.smith@example.com')
            ->setPosition('Director')
            ->setPhone('09876543210');

        $manager->persist($user1);
        $manager->persist($user2);

        // Create an authority with an admin
        $authority = (new Authority())
            ->setName('Test Authority')
            ->setAdmin($user1);

        $manager->persist($authority);

        // Add references so tests can access these entities
        $this->addReference('user-1', $user1);
        $this->addReference('user-2', $user2);
        $this->addReference('authority', $authority);

        $manager->flush();
    }
}
