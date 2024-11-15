<?php

namespace App\Entity\ProjectFund;

use App\Entity\Enum\Fund;
use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Repository\ProjectFund\CrstsProjectFundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CrstsProjectFundRepository::class)]
class CrstsProjectFund extends ProjectFund
{
    #[ORM\Column]
    private ?bool $retained = null; // 1proj_info: Is this a retained scheme / project?

    /**
     * @var Collection<int, CrstsProjectReturn>
     */
    #[ORM\OneToMany(targetEntity: CrstsProjectReturn::class, mappedBy: 'projectFund', orphanRemoval: true)]
    private Collection $returns;

    public function __construct()
    {
        $this->returns = new ArrayCollection();
    }

    public function isRetained(): ?bool
    {
        return $this->retained;
    }

    public function setRetained(bool $retained): static
    {
        $this->retained = $retained;
        return $this;
    }

    /**
     * @return Collection<int, CrstsProjectReturn>
     */
    public function getReturns(): Collection
    {
        return $this->returns;
    }

    public function addReturn(CrstsProjectReturn $return): static
    {
        if (!$this->returns->contains($return)) {
            $this->returns->add($return);
            $return->setProjectFund($this);
        }

        return $this;
    }

    public function removeReturn(CrstsProjectReturn $return): static
    {
        if ($this->returns->removeElement($return)) {
            // set the owning side to null (unless already changed)
            if ($return->getProjectFund() === $this) {
                $return->setProjectFund(null);
            }
        }

        return $this;
    }

    public function getFund(): Fund
    {
        return Fund::CRSTS;
    }

    public function isReturnRequiredFor(int $quarter): bool
    {
        // If project is retained, we require a return every quarter, otherwise only once a year
        return $this->isRetained() || $quarter === 1;
    }
}
