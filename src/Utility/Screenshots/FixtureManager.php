<?php

namespace App\Utility\Screenshots;

use Doctrine\ORM\EntityManagerInterface;

class FixtureManager
{
    public const string USERNAME = 'screenshots@example.com';
    public const string PASSWORD = 'password';

    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }
}
