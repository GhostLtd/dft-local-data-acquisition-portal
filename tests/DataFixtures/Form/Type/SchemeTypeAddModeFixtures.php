<?php

namespace App\Tests\DataFixtures\Form\Type;

use App\Entity\Authority;
use App\Entity\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class SchemeTypeAddModeFixtures extends AbstractFixture
{
    public const string AUTHORITY = 'authority';

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setName('Mr Test')
            ->setEmail('test@example.com');

        $manager->persist($admin);

        $authority = (new Authority())
            ->setName('Test Authority')
            ->setAdmin($admin);

        $manager->persist($authority);

        $this->addReference(self::AUTHORITY, $authority);

        $manager->flush();
    }
}