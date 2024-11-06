<?php

namespace App\Entity\FundReturn;

use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\Traits\IdTrait;
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

    public function getFundAward(): ?FundAward
    {
        return $this->fundAward;
    }

    public function setFundAward(?FundAward $fundAward): static
    {
        $this->fundAward = $fundAward;
        return $this;
    }
}
