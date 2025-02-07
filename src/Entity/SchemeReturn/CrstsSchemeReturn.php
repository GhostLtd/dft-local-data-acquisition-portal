<?php

namespace App\Entity\SchemeReturn;

use App\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\Fund;
use App\Entity\Enum\OnTrackRating;
use App\Entity\ExpenseEntry;
use App\Entity\ExpensesContainerInterface;
use App\Entity\Milestone;
use App\Entity\ReturnExpenseDivisionCommentsTrait;
use App\Entity\SchemeFund\CrstsSchemeFund;
use App\Entity\SchemeFund\SchemeFund;
use App\Repository\SchemeReturn\CrstsSchemeReturnRepository;
use App\Utility\CrstsHelper;
use App\Validator\ExpensesValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ghost\GovUkCoreBundle\Validator\Constraint\Decimal;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CrstsSchemeReturnRepository::class)]
#[Callback([ExpensesValidator::class, 'validate'], groups: ['expenses'])]
class CrstsSchemeReturn extends SchemeReturn implements ExpensesContainerInterface
{
    use ReturnExpenseDivisionCommentsTrait;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 0, nullable: true)]
    #[Decimal(precision: 12, scale: 0, groups: ['overall_funding'])]
    #[NotBlank(message: 'crsts_scheme_return.total_cost.not_blank', groups: ["overall_funding"])]
    private ?string $totalCost = null; // 2proj_exp: Total cost of scheme (<this fund> plus other expenditure)

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 0, nullable: true)]
    #[Decimal(precision: 12, scale: 0, groups: ['overall_funding'])]
    #[NotBlank(message: 'crsts_scheme_return.agreed_funding.not_blank', groups: ["overall_funding"])]
    private ?string $agreedFunding = null; // 2proj_exp: Agreed funding, <this fund>

    #[ORM\Column(nullable: true, enumType: OnTrackRating::class)]
    #[NotNull(message: 'crsts_scheme_return.on_track_rating.not_null', groups: ["milestone_rating"])]
    private ?OnTrackRating $onTrackRating = null; // 4proj_milestones: On-track rating (delivery confidence assessment)

    #[ORM\Column(nullable: true, enumType: BusinessCase::class)]
    #[NotNull(message: 'crsts_scheme_return.business_case.not_null', groups: ["milestone_business_case"])]
    private ?BusinessCase $businessCase = null; // 4proj_milestones: Current business case

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[NotNull(message: 'crsts_scheme_return.expected_business_case_approval.not_null', groups: ["milestone_business_case"])]
    private ?\DateTimeInterface $expectedBusinessCaseApproval = null; // 4proj_milestones: Expected date of approval for current business case

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[NotBlank(message: 'crsts_scheme_return.progress_update.not_blank', groups: ["milestone_rating"])]
    private ?string $progressUpdate = null; // 4proj_milestones: Progress update (comment)

    /**
     * @var Collection<int, ExpenseEntry>
     */
    #[ORM\ManyToMany(targetEntity: ExpenseEntry::class)]
    private Collection $expenses;

    /**
     * @var Collection<int, Milestone>
     */
    #[ORM\ManyToMany(targetEntity: Milestone::class, cascade: ['persist'])]
    private Collection $milestones;

    #[Callback(groups: ['milestone_dates'])]
    public function validateMilestoneDates(ExecutionContextInterface $context): void
    {
        foreach($this->milestones as $i => $milestone) {
            if ($milestone->getDate() === null) {
                $context
                    ->buildViolation('common.date.not_null')
                    ->atPath($milestone->getType()->value)
                    ->addViolation();
            }
        }
    }

    public function __construct()
    {
        $this->expenses = new ArrayCollection();
        $this->milestones = new ArrayCollection();
    }


    public function getSchemeFund(): ?CrstsSchemeFund
    {
        $schemeFund = parent::getSchemeFund();

        if ($schemeFund !== null && !$schemeFund instanceof CrstsSchemeFund) {
            throw new \InvalidArgumentException('parent::getSchemeFund() returned non-CrstsSchemeFund');
        }

        return $schemeFund;
    }

    public function setSchemeFund(?SchemeFund $schemeFund): static
    {
        if ($schemeFund !== null && !$schemeFund instanceof CrstsSchemeFund) {
            throw new \InvalidArgumentException('setSchemeFund() called with non-CrstsSchemeFund');
        }

        return parent::setSchemeFund($schemeFund);
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
