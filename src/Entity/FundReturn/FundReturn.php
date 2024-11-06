<?php

namespace App\Entity\FundReturn;

use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\Traits\IdTrait;
use App\Entity\User;
use App\Repository\FundReturn\FundReturnRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FundReturnRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::CRSTS->value => CrstsFundReturn::class,
])]
class FundReturn
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'returns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FundAward $fundAward = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signoffEmail = null; // top_signoff

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $signoffUser = null; // top_signoff

    public function getFundAward(): ?FundAward
    {
        return $this->fundAward;
    }

    public function setFundAward(?FundAward $fundAward): static
    {
        $this->fundAward = $fundAward;
        return $this;
    }

    public function getSignoffEmail(): ?string
    {
        return $this->signoffEmail;
    }

    public function setSignoffEmail(?string $signoffEmail): static
    {
        $this->signoffEmail = $signoffEmail;
        return $this;
    }

    public function getSignoffUser(): ?User
    {
        return $this->signoffUser;
    }

    public function setSignoffUser(?User $signoffUser): static
    {
        $this->signoffUser = $signoffUser;
        return $this;
    }
}
