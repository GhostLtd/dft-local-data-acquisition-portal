<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Repository\UserRecipientRoleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRecipientRoleRepository::class)]
class UserRecipientRole
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'recipientRoles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'usersRoles')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipient $recipient = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRecipient(): ?Recipient
    {
        return $this->recipient;
    }

    public function setRecipient(?Recipient $recipient): static
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }
}
