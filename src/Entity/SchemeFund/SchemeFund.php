<?php

namespace App\Entity\SchemeFund;

use App\Entity\Enum\Fund;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\Traits\IdTrait;
use App\Repository\SchemeFund\SchemeFundRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SchemeFundRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::BSIP->value => BsipSchemeFund::class,
    Fund::CRSTS1->value => CrstsSchemeFund::class,
])]
abstract class SchemeFund
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'schemeFunds')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Scheme $scheme = null;

    /**
     * @var Collection<int, SchemeReturn>
     */
    #[ORM\OneToMany(targetEntity: SchemeReturn::class, mappedBy: 'schemeFund', orphanRemoval: true)]
    private Collection $returns;

    public function __construct()
    {
        $this->returns = new ArrayCollection();
    }


    public function getScheme(): ?Scheme
    {
        return $this->scheme;
    }

    public function setScheme(?Scheme $scheme): static
    {
        $this->scheme = $scheme;
        return $this;
    }

    /**
     * @return Collection<int, SchemeReturn>
     */
    public function getReturns(): Collection
    {
        return $this->returns;
    }

    public function addReturn(SchemeReturn $return): static
    {
        if (!$this->returns->contains($return)) {
            $this->returns->add($return);
            $return->setSchemeFund($this);
        }

        return $this;
    }

    public function removeReturn(SchemeReturn $return): static
    {
        if ($this->returns->removeElement($return)) {
            // set the owning side to null (unless already changed)
            if ($return->getSchemeFund() === $this) {
                $return->setSchemeFund(null);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    abstract public function getFund(): Fund;
    abstract public function isReturnRequiredFor(int $quarter): bool;
}
