<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Repository\FundAwardRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FundAwardRepository::class)]
class FundAward
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'fundAwards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipient $recipient = null;

    public function getRecipient(): ?Recipient
    {
        return $this->recipient;
    }

    public function setRecipient(?Recipient $recipient): static
    {
        $this->recipient = $recipient;
        return $this;
    }
}
