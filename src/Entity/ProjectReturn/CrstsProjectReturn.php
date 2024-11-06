<?php

namespace App\Entity\ProjectReturn;

use App\Entity\Contact;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\OnTrackRating;
use App\Entity\Expense\ExpenseSeries;
use App\Entity\Milestone;
use App\Entity\ProjectFund\CrstsProjectFund;
use App\Entity\Traits\IdTrait;
use App\Repository\Return\CrstsProjectReturnRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CrstsProjectReturnRepository::class)]
class CrstsProjectReturn
{
    use IdTrait;

    #[ORM\ManyToOne(inversedBy: 'returns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CrstsProjectFund $projectFund = null;

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
     * @var Collection<int, ExpenseSeries>
     */
    #[ORM\ManyToMany(targetEntity: ExpenseSeries::class)]
    private Collection $expenses;

    /**
     * @var Collection<int, Milestone>
     */
    #[ORM\ManyToMany(targetEntity: Milestone::class)]
    private Collection $milestones;


    public function __construct()
    {
        $this->expenses = new ArrayCollection();
        $this->milestones = new ArrayCollection();
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
        }

        return $this;
    }

    public function removeMilestone(Milestone $milestone): static
    {
        $this->milestones->removeElement($milestone);
        return $this;
    }
}
