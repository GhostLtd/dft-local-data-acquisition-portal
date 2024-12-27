<?php

namespace App\Entity\ProjectReturn;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\Fund;
use App\Entity\Enum\OnTrackRating;
use App\Entity\ExpenseEntry;
use App\Entity\ExpensesContainerInterface;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Milestone;
use App\Entity\ProjectFund\CrstsProjectFund;
use App\Repository\Return\CrstsProjectReturnRepository;
use App\Utility\CrstsHelper;
use App\Validator\ExpensesValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ghost\GovUkCoreBundle\Validator\Constraint\Decimal;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Valid;

#[ORM\Entity(repositoryClass: CrstsProjectReturnRepository::class)]
#[Callback([ExpensesValidator::class, 'validate'], groups: ['expenses'])]
class CrstsProjectReturn extends ProjectReturn implements ExpensesContainerInterface
{
    #[ORM\ManyToOne(inversedBy: 'returns')]
    #[ORM\JoinColumn(nullable: false)]
    #[Valid(groups: ['project_details'])]
    private ?CrstsProjectFund $projectFund = null;

    #[ORM\ManyToOne(inversedBy: 'projectReturns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CrstsFundReturn $fundReturn = null;

    #[ORM\Column(type: Types::DECIMAL, length: 255, precision: 10, scale: 2, nullable: true)]
    #[Decimal(precision: 10, scale: 2, groups: ['overall_funding'])]
    private ?string $totalCost = null; // 2proj_exp: Total cost of project (<this fund> plus other expenditure)

    #[ORM\Column(type: Types::DECIMAL, length: 255, precision: 10, scale: 2, nullable: true)]
    #[Decimal(precision: 10, scale: 2, groups: ['overall_funding'])]
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

    /**
     * @var Collection<int, ExpenseEntry>
     */
    #[ORM\ManyToMany(targetEntity: ExpenseEntry::class)]
    private Collection $expenses;

    /**
     * @var Collection<int, Milestone>
     */
    #[ORM\ManyToMany(targetEntity: Milestone::class)]
    private Collection $milestones;

    public function __construct()
    {
        parent::__construct();
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


    public function getFundReturn(): ?CrstsFundReturn
    {
        return $this->fundReturn;
    }

    public function setFundReturn(?CrstsFundReturn $fundReturn): static
    {
        $this->fundReturn = $fundReturn;
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

    public function setAgreedFunding(?string $agreedFunding): static
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

    public function getFund(): Fund
    {
        return Fund::CRSTS1;
    }

    /**
     * @return array<int, DivisionConfiguration>
     */
    public function getDivisionConfigurations(): array
    {
        $fundReturn = $this->getFundReturn();
        return CrstsHelper::getExpenseDivisionConfigurations($fundReturn->getYear(), $fundReturn->getQuarter());
    }
}
