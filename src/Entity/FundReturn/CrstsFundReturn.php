<?php

namespace App\Entity\FundReturn;

use App\Entity\Enum\Rating;
use App\Repository\FundReturn\CrstsFundReturnRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CrstsFundReturnRepository::class)]
class CrstsFundReturn extends FundReturn
{

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $quarter = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $progressSummary = null; // 1top_info: Programme level progress summary

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $deliveryConfidence = null; // 1top_info: Programme delivery confidence comment assessment

    #[ORM\Column(nullable: true, enumType: Rating::class)]
    private ?Rating $overallConfidence = null; // 1top_info: Overall confidence

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $ragProgressSummary = null; // 1top_info: RAG progress this quarter - commentary

    #[ORM\Column(nullable: true, enumType: Rating::class)]
    private ?Rating $ragProgressRating = null; // 1top_info: RAG progress this quarter

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $localContribution = null; // 2top_exp: Local contribution.  Please provide a current breakdown of local contribution achieved, by source.

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resourceFunding = null; // 2top_exp: Resource (RDEL) funding.  Please see Appendix A.

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null; // 2top_exp: Comment box.  Please provide some commentary on the programme expenditure table above.  Any expenditure post 26/27 MUST be explained.


    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getQuarter(): ?int
    {
        return $this->quarter;
    }

    public function setQuarter(?int $quarter): static
    {
        $this->quarter = $quarter;
        return $this;
    }

    public function getProgressSummary(): ?string
    {
        return $this->progressSummary;
    }

    public function setProgressSummary(?string $progressSummary): static
    {
        $this->progressSummary = $progressSummary;
        return $this;
    }

    public function getDeliveryConfidence(): ?string
    {
        return $this->deliveryConfidence;
    }

    public function setDeliveryConfidence(?string $deliveryConfidence): static
    {
        $this->deliveryConfidence = $deliveryConfidence;
        return $this;
    }

    public function getOverallConfidence(): ?Rating
    {
        return $this->overallConfidence;
    }

    public function setOverallConfidence(?Rating $overallConfidence): static
    {
        $this->overallConfidence = $overallConfidence;
        return $this;
    }

    public function getRagProgressSummary(): ?string
    {
        return $this->ragProgressSummary;
    }

    public function setRagProgressSummary(?string $ragProgressSummary): static
    {
        $this->ragProgressSummary = $ragProgressSummary;
        return $this;
    }

    public function getRagProgressRating(): ?Rating
    {
        return $this->ragProgressRating;
    }

    public function setRagProgressRating(?Rating $ragProgressRating): static
    {
        $this->ragProgressRating = $ragProgressRating;
        return $this;
    }

    public function getLocalContribution(): ?string
    {
        return $this->localContribution;
    }

    public function setLocalContribution(?string $localContribution): static
    {
        $this->localContribution = $localContribution;
        return $this;
    }

    public function getResourceFunding(): ?string
    {
        return $this->resourceFunding;
    }

    public function setResourceFunding(?string $resourceFunding): static
    {
        $this->resourceFunding = $resourceFunding;
        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): static
    {
        $this->comments = $comments;
        return $this;
    }
}
