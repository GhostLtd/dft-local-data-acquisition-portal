<?php

namespace App\Tests\DataFixtures\Form\Type;

use App\Entity\Authority;
use App\Entity\Scheme;
use Doctrine\Persistence\ObjectManager;

class SchemeTypeEditModeFixtures extends SchemeTypeAddModeFixtures
{
    public const string EXISTING_SCHEME = 'existing_scheme';

    public function load(ObjectManager $manager): void
    {
        // Load the base fixtures (User and Authority)
        parent::load($manager);

        /** @var Authority $authority */
        $authority = $this->getReference(self::AUTHORITY, Authority::class);

        // Create and persist a scheme so it has an ID for MODE_EDIT testing
        $existingScheme = (new Scheme())
            ->setName('Test Existing Scheme')
            ->setAuthority($authority);

        $manager->persist($existingScheme);
        $this->addReference(self::EXISTING_SCHEME, $existingScheme);

        $manager->flush();
    }
}