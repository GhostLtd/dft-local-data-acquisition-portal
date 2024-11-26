<?php

namespace App\Entity\FundReturn;

use App\Entity\Enum\Fund;
use App\Entity\Enum\Rating;
use App\Entity\Expense\ExpenseSeries;
use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Repository\FundReturn\CrstsFundReturnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CrstsFundReturnRepository::class)]
class CrstsFundReturn extends FundReturn
{
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

    /**
     * @var Collection<int, ExpenseSeries>
     */
    #[ORM\ManyToMany(targetEntity: ExpenseSeries::class)]
    private Collection $expenses;

    /**
     * @var Collection<int, CrstsProjectReturn>
     */
    #[ORM\OneToMany(targetEntity: CrstsProjectReturn::class, mappedBy: 'fundReturn', orphanRemoval: true)]
    private Collection $projectReturns;

    public function __construct()
    {
        parent::__construct();
        $this->expenses = new ArrayCollection();
        $this->projectReturns = new ArrayCollection();
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

    /**
     * @return Collection<int, ExpenseSeries>
     */
    public function getExpenses(): Collection
    {
        return $this->expenses;
    }

    public function addExpense(ExpenseSeries $expense): static
    {
        if (!$this->expenses->contains($expense)) {
            $this->expenses->add($expense);
        }

        return $this;
    }

    public function removeExpense(ExpenseSeries $expense): static
    {
        $this->expenses->removeElement($expense);
        return $this;
    }

    /**
     * @return Collection<int, CrstsProjectReturn>
     */
    public function getProjectReturns(): Collection
    {
        return $this->projectReturns;
    }

    public function addProjectReturn(CrstsProjectReturn $projectReturn): static
    {
        if (!$this->projectReturns->contains($projectReturn)) {
            $this->projectReturns->add($projectReturn);
            $projectReturn->setFundReturn($this);
        }

        return $this;
    }

    public function removeProjectReturn(CrstsProjectReturn $projectReturn): static
    {
        if ($this->projectReturns->removeElement($projectReturn)) {
            // set the owning side to null (unless already changed)
            if ($projectReturn->getFundReturn() === $this) {
                $projectReturn->setFundReturn(null);
            }
        }

        return $this;
    }

    public function getFund(): Fund
    {
        return Fund::CRSTS;
    }


}
