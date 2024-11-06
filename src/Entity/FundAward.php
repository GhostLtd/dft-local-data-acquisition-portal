<?php

namespace App\Entity;

use App\Entity\FundReturn\FundReturn;
use App\Entity\Traits\IdTrait;
use App\Repository\FundAwardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FundAwardRepository::class)]
class FundAward
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'fundAwards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipient $recipient = null;

    /**
     * @var Collection<int, FundReturn>
     */
    #[ORM\OneToMany(targetEntity: FundReturn::class, mappedBy: 'fundAward', orphanRemoval: true)]
    private Collection $returns;

    public function __construct()
    {
        $this->returns = new ArrayCollection();
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

    /**
     * @return Collection<int, FundReturn>
     */
    public function getReturns(): Collection
    {
        return $this->returns;
    }

    public function addReturn(FundReturn $return): static
    {
        if (!$this->returns->contains($return)) {
            $this->returns->add($return);
            $return->setFundAward($this);
        }

        return $this;
    }

    public function removeReturn(FundReturn $return): static
    {
        if ($this->returns->removeElement($return)) {
            // set the owning side to null (unless already changed)
            if ($return->getFundAward() === $this) {
                $return->setFundAward(null);
            }
        }

        return $this;
    }
}
