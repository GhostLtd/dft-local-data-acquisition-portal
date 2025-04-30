<?php

namespace App\Entity;

use App\Entity\Enum\Fund;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Traits\IdTrait;
use App\Repository\FundAwardRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FundAwardRepository::class)]
class FundAward implements PropertyChangeLoggableInterface
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'fundAwards')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Authority $authority = null;

    #[ORM\Column(enumType: Fund::class)]
    private ?Fund $type = null;

    /**
     * @var Collection<int, FundReturn>
     */
    #[ORM\OneToMany(targetEntity: FundReturn::class, mappedBy: 'fundAward', orphanRemoval: true)]
    #[ORM\OrderBy(['year' => 'DESC', 'quarter' => 'DESC'])]
    private Collection $returns;

    public function __construct()
    {
        $this->returns = new ArrayCollection();
    }

    public function getAuthority(): ?Authority
    {
        return $this->authority;
    }

    public function setAuthority(?Authority $authority): static
    {
        $this->authority = $authority;
        return $this;
    }

    public function getType(): ?Fund
    {
        return $this->type;
    }

    public function setType(Fund $type): static
    {
        $this->type = $type;
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

    // ----------------------------------------------------------------------

    public function getReturnByYearAndQuarter(int $initialYear, int $quarter): ?FundReturn
    {
        foreach($this->getReturns() as $return) {
            if ($return->getYear() === $initialYear && $return->getQuarter() === $quarter) {
                return $return;
            }
        }

        return null;
    }
}
