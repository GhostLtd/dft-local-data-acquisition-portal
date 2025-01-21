<?php

namespace App\Entity\ProjectFund;

use App\Entity\Enum\Fund;
use App\Entity\Enum\FundedMostlyAs;
use App\Repository\ProjectFund\CrstsProjectFundRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Valid;

#[ORM\Entity(repositoryClass: CrstsProjectFundRepository::class)]
class CrstsProjectFund extends ProjectFund
{
    #[ORM\Column]
    private ?bool $retained = null; // 1proj_info: Is this a retained scheme / project?

    #[ORM\Column(nullable: true, enumType: FundedMostlyAs::class)]
    private ?FundedMostlyAs $fundedMostlyAs = null; // 1proj_info: CDEL or RDEL

    #[ORM\Column(nullable: true)]
    private ?bool $previouslyTcf = null;

    #[ORM\Embedded(class: BenefitCostRatio::class)]
    #[Valid(groups: ['project_details'])]
    private ?BenefitCostRatio $benefitCostRatio = null;

    public function isRetained(): ?bool
    {
        return $this->retained;
    }

    public function setRetained(bool $retained): static
    {
        $this->retained = $retained;
        return $this;
    }

    public function getFund(): Fund
    {
        return Fund::CRSTS1;
    }

    public function isReturnRequiredFor(int $quarter): bool
    {
        // If project is retained, we require a return every quarter, otherwise only once a year
        return $this->isRetained() || $quarter === 1;
    }

    public function getFundedMostlyAs(): ?FundedMostlyAs
    {
        return $this->fundedMostlyAs;
    }

    public function setFundedMostlyAs(?FundedMostlyAs $fundedMostlyAs): static
    {
        $this->fundedMostlyAs = $fundedMostlyAs;
        return $this;
    }

    public function isPreviouslyTcf(): ?bool
    {
        return $this->previouslyTcf;
    }

    public function setPreviouslyTcf(?bool $previouslyTcf): static
    {
        $this->previouslyTcf = $previouslyTcf;
        return $this;
    }

    public function getBenefitCostRatio(): ?BenefitCostRatio
    {
        return $this->benefitCostRatio;
    }

    public function setBenefitCostRatio(?BenefitCostRatio $benefitCostRatio): static
    {
        $this->benefitCostRatio = $benefitCostRatio;
        return $this;
    }
}
