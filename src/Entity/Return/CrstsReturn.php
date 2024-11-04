<?php

namespace App\Entity\Return;

use App\Entity\Contact;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\OnTrackRating;
use App\Entity\Enum\Rating;
use App\Entity\Expense\ExpenseSeries;
use App\Entity\Milestone;
use App\Entity\ProjectFund\CrstsProjectFund;
use App\Entity\Traits\IdTrait;
use App\Repository\Return\CrstsReturnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CrstsReturnRepository::class)]
class CrstsReturn
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'returns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CrstsProjectFund $projectFund = null;

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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $totalCost = null; // 2proj_exp: Total cost of project (<this fund> plus other expenditure)

    #[ORM\Column(length: 255)]
    private ?string $agreedFunding = null; // 2proj_exp: Agreed funding, <this fund>

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $spendToDate = null; // 2proj_exp: Spend to date, <this fund>

    #[ORM\Column(nullable: true, enumType: OnTrackRating::class)]
    private ?OnTrackRating $onTrackRating = null; // 4proj_exp: On-track rating (delivery confidence assessment)

    #[ORM\Column(nullable: true, enumType: BusinessCase::class)]
    private ?BusinessCase $businessCase = null; // 4proj_exp: Current business case

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expectedBusinessCaseApproval = null; // 4proj_exp: Expected date of approval for current business case

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $progressUpdate = null; // 4proj_exp: Progress update (comment)

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signoffBy = null; // top_signoff

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Contact $signoffContact = null; // top_signoff

    /**
     * @var Collection<int, Milestone>
     */
    #[ORM\OneToMany(targetEntity: Milestone::class, mappedBy: 'return', orphanRemoval: true)]
    private Collection $milestones;

    /**
     * @var Collection<int, ExpenseSeries>
     */
    #[ORM\ManyToMany(targetEntity: ExpenseSeries::class)]
    private Collection $expenses;


    public function __construct()
    {
        $this->milestones = new ArrayCollection();
        $this->expenses = new ArrayCollection();
    }

    public function getProjectFund(): ?CrstsProjectFund
    {
        return $this->projectFund;
    }

    public function setProjectFund(?CrstsProjectFund $projectFund): static
    {
        $this->projectFund = $projectFund;
        return $this;
    }

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

    public function getTotalCost(): ?string
    {
        return $this->totalCost;
    }

    public function setTotalCost(?string $totalCost): static
    {
        $this->totalCost = $totalCost;
        return $this;
    }

    public function getAgreedFunding(): ?string
    {
        return $this->agreedFunding;
    }

    public function setAgreedFunding(string $agreedFunding): static
    {
        $this->agreedFunding = $agreedFunding;
        return $this;
    }

    public function getSpendToDate(): ?string
    {
        return $this->spendToDate;
    }

    public function setSpendToDate(?string $spendToDate): static
    {
        $this->spendToDate = $spendToDate;
        return $this;
    }

    public function getOnTrackRating(): ?OnTrackRating
    {
        return $this->onTrackRating;
    }

    public function setOnTrackRating(?OnTrackRating $onTrackRating): static
    {
        $this->onTrackRating = $onTrackRating;
        return $this;
    }

    public function getBusinessCase(): ?BusinessCase
    {
        return $this->businessCase;
    }

    public function setBusinessCase(?BusinessCase $businessCase): static
    {
        $this->businessCase = $businessCase;
        return $this;
    }

    public function getExpectedBusinessCaseApproval(): ?\DateTimeInterface
    {
        return $this->expectedBusinessCaseApproval;
    }

    public function setExpectedBusinessCaseApproval(?\DateTimeInterface $expectedBusinessCaseApproval): static
    {
        $this->expectedBusinessCaseApproval = $expectedBusinessCaseApproval;
        return $this;
    }

    public function getProgressUpdate(): ?string
    {
        return $this->progressUpdate;
    }

    public function setProgressUpdate(?string $progressUpdate): static
    {
        $this->progressUpdate = $progressUpdate;
        return $this;
    }

    public function getSignoffBy(): ?string
    {
        return $this->signoffBy;
    }

    public function setSignoffBy(?string $signoffBy): static
    {
        $this->signoffBy = $signoffBy;
        return $this;
    }

    /**
     * @return Collection<int, Milestone>
     */
    public function getMilestones(): Collection
    {
        return $this->milestones;
    }

    public function addMilestone(Milestone $milestone): static
    {
        if (!$this->milestones->contains($milestone)) {
            $this->milestones->add($milestone);
            $milestone->setReturn($this);
        }

        return $this;
    }

    public function removeMilestone(Milestone $milestone): static
    {
        if ($this->milestones->removeElement($milestone)) {
            // set the owning side to null (unless already changed)
            if ($milestone->getReturn() === $this) {
                $milestone->setReturn(null);
            }
        }

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

    public function getSignoffContact(): ?Contact
    {
        return $this->signoffContact;
    }

    public function setSignoffContact(?Contact $signoffContact): static
    {
        $this->signoffContact = $signoffContact;

        return $this;
    }
}
