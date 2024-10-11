<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Uid\Ulid;

trait IdTrait
{
    #[ORM\Id]
    #[ORM\Column(type: 'ulid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    protected ?Ulid $id = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function setId(?Ulid $id): static
    {
        $this->id = $id;
        return $this;
    }
}
