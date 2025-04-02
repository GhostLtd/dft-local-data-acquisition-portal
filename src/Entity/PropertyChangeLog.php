<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Repository\PropertyChangeLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: PropertyChangeLogRepository::class)]
#[ORM\Table]
#[ORM\Index(name: 'property_change_idx', fields: ['entityId', 'entityClass'])]
class PropertyChangeLog
{
    use IdTrait;

    #[ORM\Column(type: UlidType::NAME)]
    private ?Ulid $entityId = null;

    #[ORM\Column(type: 'string', length: 6)]
    private string $action;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $entityClass = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $propertyName = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private mixed $propertyValue;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $userEmail = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $firewallName = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $timestamp = null;

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
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

    public function getEntityClass(): ?string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): static
    {
        $this->entityClass = $entityClass;
        return $this;
    }

    public function getPropertyName(): ?string
    {
        return $this->propertyName;
    }

    public function setPropertyName(?string $propertyName): static
    {
        $this->propertyName = $propertyName;
        return $this;
    }

    public function getPropertyValue(): mixed
    {
        return $this->propertyValue;
    }

    public function setPropertyValue($propertyValue): static
    {
        $this->propertyValue = $propertyValue;
        return $this;
    }

    public function validatePropertyValue(): void
    {
        if (is_array($this->propertyValue) || (is_object($this->propertyValue) && !$this->propertyValue instanceof \JsonSerializable)) {
            $propertyName = $this->propertyName ?? '<null>';
            $entityId = $this->entityId ?? '<null>';
            $entityClass = $this->entityClass ?? '<null>';

            throw new \RuntimeException("Non-primitive / non-json-serializable value emitted from ChangeSetNormalizer (class: {$entityClass}, id: {$entityId}, property: {$propertyName}");
        }
    }

    public function getUserEmail(): ?string
    {
        return $this->userEmail;
    }

    public function setUserEmail(?string $userEmail): static
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    public function getFirewallName(): ?string
    {
        return $this->firewallName;
    }

    public function setFirewallName(?string $firewallName): static
    {
        $this->firewallName = $firewallName;
        return $this;
    }

    public function getTimestamp(): ?\DateTime
    {
        return $this->timestamp;
    }

    public function setTimestamp(?\DateTime $timestamp): static
    {
        $this->timestamp = $timestamp;
        return $this;
    }
}
