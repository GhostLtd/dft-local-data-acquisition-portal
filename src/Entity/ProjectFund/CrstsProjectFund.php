<?php

namespace App\Entity\ProjectFund;

use App\Entity\Enum\CrstsPhase;
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

    #[ORM\Column(enumType: CrstsPhase::class)]
    private ?CrstsPhase $phase = null; // 1proj_info: Is this project funded by CRSTS1 or CRSTS2?

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

    public function getPhase(): ?CrstsPhase
    {
        return $this->phase;
    }

    public function setPhase(?CrstsPhase $phase): static
    {
        $this->phase = $phase;
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
}
