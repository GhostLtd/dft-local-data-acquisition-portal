<?php

namespace App\Entity;

use App\Entity\Enum\Permission;
use App\Entity\Traits\IdTrait;
use App\Repository\UserPermissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: UserPermissionRepository::class)]
class UserPermission
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'permissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(enumType: Permission::class)]
    private ?Permission $permission = null;

    #[ORM\Column(length: 255)]
    private ?string $entityClass = null;

    #[ORM\Column(type: UlidType::NAME, unique: true)]
    private ?Ulid $entityId = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    private ?array $fundTypes = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getPermission(): ?Permission
    {
        return $this->permission;
    }

    public function setPermission(?Permission $permission): static
    {
        $this->permission = $permission;
        return $this;
    }

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): static
    {
        $this->entityClass = $entityClass;
        return $this;
    }

    public function getEntityId(): ?Ulid
    {
        return $this->entityId;
    }

    public function setEntityId(?Ulid $entityId): static
    {
        $this->entityId = $entityId;
        return $this;
    }

    /**
     * @return array<int, string>|null
     */
    public function getFundTypes(): ?array
    {
        return $this->fundTypes;
    }

    /**
     * @param array<int, string>|null $fundTypes
     */
    public function setFundTypes(?array $fundTypes): static
    {
        $this->fundTypes = $fundTypes;
        return $this;
    }
}
