<?php

namespace App\Entity\SchemeFund;

use App\Entity\Enum\Fund;
use App\Entity\Enum\FundedMostlyAs;
use App\Repository\SchemeFund\CrstsSchemeFundRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;

#[ORM\Entity(repositoryClass: CrstsSchemeFundRepository::class)]
class CrstsSchemeFund extends SchemeFund
{
    #[ORM\Column]
    private ?bool $retained = null; // 1proj_info: Is this a retained scheme?

    #[ORM\Column(nullable: true, enumType: FundedMostlyAs::class)]
    #[NotNull(message: 'crsts_scheme_fund.funded_mostly_as.not_null', groups: ["scheme_details"])]
    private ?FundedMostlyAs $fundedMostlyAs = FundedMostlyAs::CDEL; // 1proj_info: CDEL or RDEL

    #[ORM\Column(nullable: true)]
    #[NotNull(message: 'crsts_scheme_fund.previously_tcf.not_null', groups: ["scheme_details"])]
    private ?bool $previouslyTcf = null;

    #[ORM\Embedded(class: BenefitCostRatio::class)]
    #[Valid(groups: ['scheme_details'])]
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
        // If scheme is retained, we require a return every quarter, otherwise only once a year
        return $this->isRetained() || $quarter === 4;
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
