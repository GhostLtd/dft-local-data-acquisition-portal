<?php

namespace App\Entity\SchemeReturn;

use App\Entity\Enum\BusinessCase;
use App\Entity\Enum\Fund;
use App\Entity\Enum\MilestoneType;
use App\Entity\Enum\OnTrackRating;
use App\Entity\ExpensesContainerInterface;
use App\Entity\Milestone;
use App\Entity\ReturnExpenseTrait;
use App\Entity\Scheme;
use App\Entity\SchemeFund\BenefitCostRatio;
use App\Repository\SchemeReturn\CrstsSchemeReturnRepository;
use App\Validator\ExpensesValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ghost\GovUkCoreBundle\Validator\Constraint\Decimal;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CrstsSchemeReturnRepository::class)]
#[Callback([ExpensesValidator::class, 'validate'], groups: ['expenses'])]
class CrstsSchemeReturn extends SchemeReturn implements ExpensesContainerInterface
{
    use ReturnExpenseTrait;

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    #[Decimal(precision: 14, scale: 2, groups: ['overall_funding'])]
    #[NotBlank(message: 'crsts_scheme_return.total_cost.not_blank', groups: ["overall_funding"])]
    private ?string $totalCost = null; // 2proj_exp: Total cost of scheme (<this fund> plus other expenditure)

    #[ORM\Column(type: Types::DECIMAL, precision: 14, scale: 2, nullable: true)]
    #[Decimal(precision: 14, scale: 2, groups: ['overall_funding'])]
    #[NotBlank(message: 'crsts_scheme_return.agreed_funding.not_blank', groups: ["overall_funding"])]
    private ?string $agreedFunding = null; // 2proj_exp: Agreed funding, <this fund>

    #[ORM\Column(nullable: true, enumType: OnTrackRating::class)]
    #[NotNull(message: 'crsts_scheme_return.on_track_rating.not_null', groups: ["milestone_rating"])]
    private ?OnTrackRating $onTrackRating = null; // 4proj_milestones: On-track rating (delivery confidence assessment)

    #[ORM\Column(nullable: true, enumType: BusinessCase::class)]
    #[NotNull(message: 'crsts_scheme_return.business_case.not_null', groups: ["milestone_business_case"])]
    private ?BusinessCase $businessCase = null; // 4proj_milestones: Current business case

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Sequentially([
        new Assert\NotNull(message: 'crsts_scheme_return.expected_business_case_approval.not_null'),
        new Assert\Callback(callback: [self::class, 'validateExpectedBusinessCaseApproval']),
    ], groups: ["milestone_business_case"])]
    private ?\DateTimeInterface $expectedBusinessCaseApproval = null; // 4proj_milestones: Expected date of approval for current business case

    #[ORM\Column(type: Types::TEXT, length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, nullable: true)]
    #[Length(max: 16383, groups: ['milestone_rating'])]
    #[NotBlank(message: 'crsts_scheme_return.progress_update.not_blank', groups: ["milestone_rating"])]
    private ?string $progressUpdate = null; // 4proj_milestones: Progress update (comment)

    #[ORM\Embedded(class: BenefitCostRatio::class)]
    #[Valid(groups: ['overall_funding'])]
    private ?BenefitCostRatio $benefitCostRatio = null;

    #[ORM\Column(nullable: true)]
    #[NotNull(message: 'crsts_scheme_return.development_only.not_null', groups: ["milestone_dates"])]
    private ?bool $developmentOnly = null;

    /**
     * @var Collection<int, Milestone>
     */
    #[ORM\ManyToMany(targetEntity: Milestone::class, cascade: ['persist'])]
    private Collection $milestones;

    public static function validateExpectedBusinessCaseApproval(
        \DateTimeInterface $value,
        ExecutionContextInterface $context
    ): void {
        /** @var CrstsSchemeReturn $schemeReturn */
        $schemeReturn = $context->getObject();
        $businessCase = $schemeReturn->getBusinessCase();

        if ($businessCase === BusinessCase::NOT_APPLICABLE) {
            return;
        }

        $startOfNextQuarter = $schemeReturn->getFundReturn()->getFinancialQuarter()->getNextQuarter()->getStartDate();

        $params = [
            'start_of_next_quarter' => $startOfNextQuarter,
        ];

        if ($businessCase === BusinessCase::POST_FBC) {
            if ($value < $startOfNextQuarter) {
                return;
            }

            $message = 'crsts_scheme_return.expected_business_case_approval.end_of_quarter';
        } else {
            if ($value >= $startOfNextQuarter) {
                return;
            }

            $message = 'crsts_scheme_return.expected_business_case_approval.future';
        }

        $context
            ->buildViolation($message, $params)
            ->atPath($context->getPropertyPath())
            ->addViolation();
    }

    #[Callback(groups: ['milestone_dates'])]
    public function validateMilestoneDates(ExecutionContextInterface $context): void
    {
        foreach($this->milestones as $i => $milestone) {
            if ($milestone->getDate() === null) {
                $context
                    ->buildViolation('milestone.date.not_null', [
                        'milestone_type' => $milestone->getType()->value,
                    ])
                    ->atPath($milestone->getType()->value)
                    ->addViolation();
            }
        }
    }

    public function __construct()
    {
        $this->__expenseConstruct();
        $this->milestones = new ArrayCollection();
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

    public function getFund(): Fund
    {
        return Fund::CRSTS1;
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

    public function getDevelopmentOnly(): ?bool
    {
        return $this->developmentOnly;
    }

    public function setDevelopmentOnly(?bool $developmentOnly): static
    {
        $this->developmentOnly = $developmentOnly;
        return $this;
    }

    /**
     * @return Collection<int, Milestone>
     */
    public function getMilestones(): Collection
    {
        return $this->milestones;
    }

    public function getMilestoneByType(MilestoneType $type): ?Milestone
    {
        foreach($this->milestones as $milestone) {
            if ($milestone->getType() === $type) {
                return $milestone;
            }
        }

        return null;
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

    public function createSchemeReturnForNextQuarter(): static
    {
        $scheme = $this->getScheme();

        $nextSchemeReturn = new self();
        $nextSchemeReturn
            ->setScheme($scheme)
            ->setBenefitCostRatio($this->getBenefitCostRatio())

            ->setBusinessCase($this->getBusinessCase())
            ->setExpectedBusinessCaseApproval($this->getExpectedBusinessCaseApproval())
            ->setTotalCost($this->getTotalCost())
            ->setAgreedFunding($this->getAgreedFunding())
        ;

        $onTrackRating = $this->getOnTrackRating();
        if ($onTrackRating && $onTrackRating->shouldBePropagatedToFutureReturns()) {
            // Only copy ratings like cancelled/completed/merged/split
            $nextSchemeReturn->setOnTrackRating($onTrackRating);

            // Normally we wouldn't want to copy the textual progress update, but if the
            // scheme won't receive further updates, then we should
            $nextSchemeReturn->setProgressUpdate($this->getProgressUpdate());
        }

        // We now copy all expenses from the previous quarter, whether the scheme is retained or not.
        // (But for non-retained schemes that data may only be edited in Q4)
        $sourceFundReturn = $this->getFundReturn();
        $sourceExpenses = $this->expenses;
        $this->createExpensesForNextQuarter($sourceExpenses, $sourceFundReturn->getFinancialQuarter())
            ->map(fn($e) => $nextSchemeReturn->expenses->add($e));

        $this->milestones->map(fn($m) => $nextSchemeReturn->addMilestone((new Milestone())
            ->setType($m->getType())
            ->setDate($m->getDate())
        ));

        return $nextSchemeReturn;
    }

    public static function createInitialSchemeReturnFor(Scheme $scheme): static
    {
        $schemeReturn = new self();
        $schemeReturn->setScheme($scheme);

        return $schemeReturn;
    }
}
