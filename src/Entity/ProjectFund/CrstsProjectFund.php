<?php

namespace App\Entity\ProjectFund;

use App\Entity\Enum\CrstsPhase;
use App\Entity\Return\CrstsReturn;
use App\Repository\CrstsProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CrstsProjectRepository::class)]
class CrstsProjectFund extends ProjectFund
{
    #[ORM\Column]
    private ?bool $retained = null;

    #[ORM\Column(enumType: CrstsPhase::class)]
    private ?CrstsPhase $phase = null;

    /**
     * @var Collection<int, CrstsReturn>
     */
    #[ORM\OneToMany(targetEntity: CrstsReturn::class, mappedBy: 'projectFund', orphanRemoval: true)]
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
     * @return Collection<int, CrstsReturn>
     */
    public function getReturns(): Collection
    {
        return $this->returns;
    }

    public function addReturn(CrstsReturn $return): static
    {
        if (!$this->returns->contains($return)) {
            $this->returns->add($return);
            $return->setProjectFund($this);
        }

        return $this;
    }

    public function removeReturn(CrstsReturn $return): static
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
