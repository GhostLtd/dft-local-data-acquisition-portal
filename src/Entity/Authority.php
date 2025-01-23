<?php

namespace App\Entity;

use App\Entity\Enum\Fund;
use App\Entity\SchemeFund\SchemeFund;
use App\Entity\Traits\IdTrait;
use App\Repository\AuthorityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthorityRepository::class)]
class Authority
{
    use IdTrait;

    #[ORM\Column(length: 255)]
    private ?string $name = null; // 1top_info: Local Authority name

    #[ORM\ManyToOne(inversedBy: 'authoritiesAdminOf')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $admin = null;

    /**
     * @var Collection<int, Scheme>
     */
    #[ORM\OneToMany(targetEntity: Scheme::class, mappedBy: 'authority')]
    private Collection $schemes;

    /**
     * @var Collection<int, FundAward>
     */
    #[ORM\OneToMany(targetEntity: FundAward::class, mappedBy: 'authority', orphanRemoval: true)]
    private Collection $fundAwards;

    public function __construct()
    {
        $this->schemes = new ArrayCollection();
        $this->fundAwards = new ArrayCollection();
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

    public function getAdmin(): ?User
    {
        return $this->admin;
    }

    public function setAdmin(?User $admin): static
    {
        $this->admin = $admin;
        return $this;
    }

    /**
     * @return Collection<int, Scheme>
     */
    public function getSchemes(): Collection
    {
        return $this->schemes;
    }

    public function addScheme(Scheme $scheme): static
    {
        if (!$this->schemes->contains($scheme)) {
            $this->schemes->add($scheme);
            $scheme->setAuthority($this);
        }

        return $this;
    }

    public function removeScheme(Scheme $scheme): static
    {
        if ($this->schemes->removeElement($scheme)) {
            // set the owning side to null (unless already changed)
            if ($scheme->getAuthority() === $this) {
                $scheme->setAuthority(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FundAward>
     */
    public function getFundAwards(): Collection
    {
        return $this->fundAwards;
    }

    public function addFundAward(FundAward $fundAward): static
    {
        if (!$this->fundAwards->contains($fundAward)) {
            $this->fundAwards->add($fundAward);
            $fundAward->setAuthority($this);
        }

        return $this;
    }

    public function removeFundAward(FundAward $fundAward): static
    {
        if ($this->fundAwards->removeElement($fundAward)) {
            // set the owning side to null (unless already changed)
            if ($fundAward->getAuthority() === $this) {
                $fundAward->setAuthority(null);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    /**
     * Returns a collection of the schemes that this authority has, that receive funding from the specified $fund
     * @return Collection<Scheme>
     */
    public function getSchemesForFund(Fund $fund): Collection
    {
        return $this->schemes->filter(fn(Scheme $p) => $p->getSchemeFunds()->reduce(
            fn(bool $carry, SchemeFund $schemeFund) => $carry || $schemeFund->getFund() === $fund,
            false,
        ));
    }

    /**
     * Returns a collection of the schemeFunds that this authority has, that receive funding from the specified $fund
     * @return Collection<SchemeFund>
     */
    public function getSchemeFundsForFund(Fund $fund): Collection
    {
        return $this->schemes
            ->map(fn(Scheme $s) => $s->getSchemeFundForFund($fund))
            ->filter(fn(?SchemeFund $sf) => $sf !== null);
    }
}
