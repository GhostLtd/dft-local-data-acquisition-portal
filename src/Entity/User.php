<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

// These fields are currently used to represent a Recipient's owner as well as the
// "Lead contact" fields from 1top_info, but later can be additionally used for
// storing other contacts

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use IdTrait;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null; #1top_info

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $position = null; #1top_info

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone = null; #1top_info

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null; #1top_info

    /**
     * @var Collection<int, UserPermission>
     */
    #[ORM\OneToMany(targetEntity: UserPermission::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $permissions;

    /**
     * @var Collection<int, Recipient>
     */
    #[ORM\OneToMany(targetEntity: Recipient::class, mappedBy: 'admin')]
    private Collection $recipientsAdminOf;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->recipientsAdminOf = new ArrayCollection();
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): static
    {
        $this->lastLogin = $lastLogin;
        return $this;
    }

    public function getPassword(): ?string
    {
        return null;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): static
    {
        $this->position = $position;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return Collection<int, UserPermission>
     */
    public function getPermissions(): Collection
    {
        return $this->permissions;
    }

    public function addPermission(UserPermission $permission): static
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
            $permission->setUser($this);
        }

        return $this;
    }

    public function removePermission(UserPermission $permission): static
    {
        if ($this->permissions->removeElement($permission)) {
            // set the owning side to null (unless already changed)
            if ($permission->getUser() === $this) {
                $permission->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Recipient>
     */
    public function getRecipientsAdminOf(): Collection
    {
        return $this->recipientsAdminOf;
    }

    public function addRecipientAdminOf(Recipient $recipient): static
    {
        if (!$this->recipientsAdminOf->contains($recipient)) {
            $this->recipientsAdminOf->add($recipient);
            $recipient->setAdmin($this);
        }

        return $this;
    }

    public function removeRecipientAdminOf(Recipient $recipient): static
    {
        if ($this->recipientsAdminOf->removeElement($recipient)) {
            // set the owning side to null (unless already changed)
            if ($recipient->getAdmin() === $this) {
                $recipient->setAdmin(null);
            }
        }

        return $this;
    }
}
