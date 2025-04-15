<?php

namespace App\Entity;

use App\Entity\Traits\IdTrait;
use App\Repository\AuthorityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AuthorityRepository::class)]
class Authority implements PropertyChangeLoggableInterface
{
    use IdTrait;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull(message: 'authority.name.not_null', groups: ['authority'])]
    #[Assert\Length(max: 255, groups: ['authority'])]
    private ?string $name = null; // 1top_info: Local Authority name

    #[ORM\ManyToOne(inversedBy: 'authoritiesAdminOf')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\Valid(groups: ['authority.new_admin'])]
    #[Assert\NotNull(groups: ['authority.existing_admin'])]
    private ?User $admin = null;

    /**
     * @var Collection<int, Scheme>
     */
    #[ORM\OneToMany(targetEntity: Scheme::class, mappedBy: 'authority')]
    private Collection $schemes;

    /**
     * @var Collection<int, FundAward>
     */
    #[ORM\OneToMany(targetEntity: FundAward::class, mappedBy: 'authority', orphanRemoval: true, indexBy: 'type')]
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

    public function setName(?string $name): static
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

    public function addScheme(Scheme $scheme, bool $setSchemeIdentifier = true): static
    {
        if (!$this->schemes->contains($scheme)) {
            $this->schemes->add($scheme);
            $scheme->setAuthority($this);
            if ($setSchemeIdentifier) {
                $scheme->setSchemeIdentifier($this->getNextSchemeIdentifier());
            }
        }

        return $this;
    }

    public function getNextSchemeIdentifier(): ?string
    {
        $maxId = 0;
        $this->schemes->forAll(function(Scheme $s) use($maxId) {
            $x = intval($s->getSchemeIdentifier(true));
            $maxId = ($x > $maxId) ? $x : $maxId;
        });
        return $maxId + 1;
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
     * @return Collection<string, FundAward>
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
}
