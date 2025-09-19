<?php

namespace App\Tests\DataFixtures\Functional\Frontend;

use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class LoginFixtures extends AbstractFixture
{
    public function load(ObjectManager $manager): void
    {
        $user = (new User())
            ->setName('Mr Test')
            ->setEmail('test@example.com');

        $manager->persist($user);
        $manager->flush();
    }
}