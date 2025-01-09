<?php

namespace App\Entity\FundReturn;

use App\Entity\SectionStatusInterface;
use App\Entity\SectionStatusTrait;
use App\Entity\Traits\IdTrait;
use App\Repository\FundReturn\FundReturnSectionStatusRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FundReturnSectionStatusRepository::class)]
#[ORM\UniqueConstraint(columns: ['name', 'status'])]
class FundReturnSectionStatus implements SectionStatusInterface
{
    use IdTrait, SectionStatusTrait;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'sectionStatuses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FundReturn $fundReturn = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getFundReturn(): ?FundReturn
    {
        return $this->fundReturn;
    }

    public function setFundReturn(?FundReturn $fundReturn): static
    {
        $this->fundReturn = $fundReturn;
        return $this;
    }
}
