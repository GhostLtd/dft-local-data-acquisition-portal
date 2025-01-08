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
        protected ?string $section,
        protected ?array  $idMap,
        protected ?User   $owner,
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

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function getIdMap(): ?array
    {
        return $this->idMap;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function getFund(): ?Fund
    {
        return $this->fund;
    }
}
