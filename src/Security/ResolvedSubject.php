<?php

namespace App\Security;

use App\Entity\Enum\Fund;
use App\Entity\User;

/**
 * @template T
 */
class ResolvedSubject
{
    /**
     * @param class-string<T> $baseClass
     * @param T $entity
     */
    public function __construct(
        protected string  $baseClass,
        protected mixed   $entity,
        protected ?array  $idMap,
        protected ?User   $admin,
        protected ?Fund   $fund,
    ) {}

    /**
     * @return class-string<T>
     */
    public function getBaseClass(): string
    {
        return $this->baseClass;
    }

    /**
     * @return T
     */
    public function getEntity(): mixed
    {
        return $this->entity;
    }

    public function getIdMap(): ?array
    {
        return $this->idMap;
    }

    public function getAdmin(): ?User
    {
        return $this->admin;
    }

    public function getFund(): ?Fund
    {
        return $this->fund;
    }
}
