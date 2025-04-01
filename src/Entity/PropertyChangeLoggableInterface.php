<?php

namespace App\Entity;

use Symfony\Component\Uid\Ulid;

interface PropertyChangeLoggableInterface
{
    public function getId(): ?Ulid;
}