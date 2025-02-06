<?php

namespace App\Entity\FundReturn;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\Fund;
use App\Entity\Enum\Rating;
use App\Entity\ExpenseEntry;
use App\Entity\ExpensesContainerInterface;
use App\Entity\ReturnExpenseDivisionCommentsTrait;
use App\Utility\CrstsHelper;
use App\Repository\FundReturn\CrstsFundReturnRepository;
use App\Validator\ExpensesValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

#[ORM\Entity(repositoryClass: CrstsFundReturnRepository::class)]
#[Callback([ExpensesValidator::class, 'validate'], groups: ['expenses'])]
class CrstsFundReturn extends FundReturn implements ExpensesContainerInterface
{
    use ReturnExpenseDivisionCommentsTrait;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[NotBlank(message: 'crsts_fund_return.progress_summary.not_blank', groups: ["overall_progress"])]
    private ?string $progressSummary = null; // 1top_info: Programme level progress summary

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[NotBlank(message: 'crsts_fund_return.delivery_confidence.not_blank', groups: ["overall_progress"])]
    private ?string $deliveryConfidence = null; // 1top_info: Programme delivery confidence comment assessment

    #[ORM\Column(nullable: true, enumType: Rating::class)]
    #[NotNull(message: 'crsts_fund_return.overall_confidence.not_null', groups: ["overall_progress"])]
    private ?Rating $overallConfidence = null; // 1top_info: Overall confidence

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[NotNull(message: 'crsts_fund_return.rag_progress_summary.not_null', groups: ["quarterly_progress"])]
    private ?string $ragProgressSummary = null; // 1top_info: RAG progress this quarter - commentary

    #[ORM\Column(nullable: true, enumType: Rating::class)]
    #[NotNull(message: 'crsts_fund_return.rag_progress_rating.not_null', groups: ["quarterly_progress"])]
    private ?Rating $ragProgressRating = null; // 1top_info: RAG progress this quarter

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[NotBlank(message: 'crsts_fund_return.local_contribution.not_blank', groups: ["local_and_rdel"])]
    private ?string $localContribution = null; // 2top_exp: Local contribution.  Please provide a current breakdown of local contribution achieved, by source.

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[NotBlank(message: 'crsts_fund_return.resource_funding.not_blank', groups: ["local_and_rdel"])]
    private ?string $resourceFunding = null; // 2top_exp: Resource (RDEL) funding.  Please see Appendix A.

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comments = null; // 2top_exp: Comment box.  Please provide some commentary on the programme expenditure table above.  Any expenditure post 26/27 MUST be explained.

    /**
     * @var Collection<int, ExpenseEntry>
     */
    #[ORM\ManyToMany(targetEntity: ExpenseEntry::class)] // N.B. Validator is at class level
    private Collection $expenses;

    public function __construct()
    {
        parent::__construct();
        $this->expenses = new ArrayCollection();
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
     * @return Collection<int, ExpenseEntry>
     */
    public function getExpenses(): Collection
    {
        return $this->expenses;
    }

    public function addExpense(ExpenseEntry $expense): static
    {
        if (!$this->expenses->contains($expense)) {
            $this->expenses->add($expense);
        }

        return $this;
    }

    public function removeExpense(ExpenseEntry $expense): static
    {
        $this->expenses->removeElement($expense);
        return $this;
    }

    public function getFund(): Fund
    {
        return Fund::CRSTS1;
    }

    /**
     * @return array<int, DivisionConfiguration>
     */
    public function getDivisionConfigurations(): array
    {
        return CrstsHelper::getExpenseDivisionConfigurations($this->getYear(), $this->getQuarter());
    }
}
