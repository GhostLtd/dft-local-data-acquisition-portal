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

// These fields are currently used to represent the "Lead contact" fields from 1top_info,
// but later can be additionally used for storing other contacts

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
     * @var Collection<int, UserRecipientRole>
     */
    #[ORM\OneToMany(targetEntity: UserRecipientRole::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $recipientRoles;

    public function __construct()
    {
        $this->recipientRoles = new ArrayCollection();
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
        return [Roles::ROLE_USER];
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return Collection<int, UserRecipientRole>
     */
    public function getRecipientRoles(): Collection
    {
        return $this->recipientRoles;
    }

    public function addRecipientRole(UserRecipientRole $recipientRole): static
    {
        if (!$this->recipientRoles->contains($recipientRole)) {
            $this->recipientRoles->add($recipientRole);
            $recipientRole->setUser($this);
        }

        return $this;
    }

    public function removeRecipientRole(UserRecipientRole $recipientRole): static
    {
        if ($this->recipientRoles->removeElement($recipientRole)) {
            // set the owning side to null (unless already changed)
            if ($recipientRole->getUser() === $this) {
                $recipientRole->setUser(null);
            }
        }

        return $this;
    }
}
