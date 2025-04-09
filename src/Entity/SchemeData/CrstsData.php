<?php

namespace App\Entity\SchemeData;

use App\Entity\Enum\FundedMostlyAs;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\NotNull;

#[ORM\Embeddable]
class CrstsData
{
    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    #[NotNull(message: 'crsts_scheme_fund.is_retained.not_null', groups: ["scheme.crsts1.add", "scheme.crsts1.edit"])]
    private ?bool $retained = null; // 1proj_info: Is this a retained scheme?

    #[ORM\Column(nullable: true, enumType: FundedMostlyAs::class)]
    private ?FundedMostlyAs $fundedMostlyAs = FundedMostlyAs::CDEL; // 1proj_info: CDEL or RDEL

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    #[NotNull(message: 'crsts_scheme_fund.previously_tcf.not_null', groups: ["scheme.crsts1.add", "scheme.crsts1.edit"])]
    private ?bool $previouslyTcf = null; // 1proj_info: Was this previously a scheme in the Transporting Cities Fund (TCF)?

    public function isRetained(): ?bool
    {
        return $this->retained;
    }

    public function setRetained(?bool $retained): static
    {
        $this->retained = $retained;
        return $this;
    }

    public function isExpenseDataRequiredFor(int $quarter): bool
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
}
