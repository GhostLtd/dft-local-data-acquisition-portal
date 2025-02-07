<?php

namespace App\Entity;

use App\Repository\PermissionsViewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: PermissionsViewRepository::class)]
class PermissionsView
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    protected ?Ulid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $entityClass = null;

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    protected ?Ulid $authorityId = null;

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    protected ?Ulid $schemeId = null;

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    protected ?Ulid $schemeReturnId = null;

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    protected ?Ulid $fundReturnId = null;

    #[ORM\Column(type: UlidType::NAME, nullable: true)]
    protected ?Ulid $userId = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    private ?array $fundTypes = null;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function getAuthorityId(): ?Ulid
    {
        return $this->authorityId;
    }

    public function getSchemeId(): ?Ulid
    {
        return $this->schemeId;
    }

    public function getSchemeReturnId(): ?Ulid
    {
        return $this->schemeReturnId;
    }

    public function getFundReturnId(): ?Ulid
    {
        return $this->fundReturnId;
    }

    public function getUserId(): ?Ulid
    {
        return $this->userId;
    }

    public function getFundTypes(): ?array
    {
        return $this->fundTypes;
    }
}
