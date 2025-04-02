<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

// These fields are currently used to represent an Authority's admin, but
// later can be additionally used for storing other contacts as well.

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: 'email', message: 'user.email.unique', groups: ['authority.new_admin', 'user.edit'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use IdTrait;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'user.name.not_null', groups: ['authority.new_admin', 'user.edit'])]
    #[Assert\Length(max: 255, groups: ['authority.new_admin', 'user.edit'])]
    private ?string $name = null; #1top_info

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotNull(message: 'user.position.not_null', groups: ['authority.new_admin', 'user.edit'])]
    #[Assert\Length(max: 255, groups: ['authority.new_admin', 'user.edit'])]
    private ?string $position = null; #1top_info

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotNull(message: 'user.phone.not_null', groups: ['authority.new_admin', 'user.edit'])]
    #[Assert\Length(max: 255, groups: ['authority.new_admin', 'user.edit'])]
    private ?string $phone = null; #1top_info

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotNull(message: 'user.email.not_null', groups: ['authority.new_admin', 'user.edit'])]
    #[Assert\Length(max: 255, groups: ['authority.new_admin', 'user.edit'])]
    #[Assert\Email(message: 'auth.login.invalid_email', groups: ['authority.new_admin', 'user.edit'])]
    private ?string $email = null; #1top_info

    /**
     * @var Collection<int, UserPermission>
     */
    #[ORM\OneToMany(targetEntity: UserPermission::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $permissions;

    /**
     * @var Collection<int, Authority>
     */
    #[ORM\OneToMany(targetEntity: Authority::class, mappedBy: 'admin')]
    private Collection $authoritiesAdminOf;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->authoritiesAdminOf = new ArrayCollection();
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
     * @return Collection<int, Authority>
     */
    public function getAuthoritiesAdminOf(): Collection
    {
        return $this->authoritiesAdminOf;
    }

    public function addAuthorityAdminOf(Authority $authority): static
    {
        if (!$this->authoritiesAdminOf->contains($authority)) {
            $this->authoritiesAdminOf->add($authority);
            $authority->setAdmin($this);
        }

        return $this;
    }

    public function removeAuthorityAdminOf(Authority $authority): static
    {
        if ($this->authoritiesAdminOf->removeElement($authority)) {
            // set the owning side to null (unless already changed)
            if ($authority->getAdmin() === $this) {
                $authority->setAdmin(null);
            }
        }

        return $this;
    }
}
