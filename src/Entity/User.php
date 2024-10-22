<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use IdTrait;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
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

    public function getRoles(): array
    {
        return [Roles::ROLE_USER];
    }

    public function eraseCredentials()
    {
    }
}
