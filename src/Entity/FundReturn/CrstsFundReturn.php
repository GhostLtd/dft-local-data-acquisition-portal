<?php

namespace App\Entity\FundReturn;

use App\Entity\Enum\Fund;
use App\Entity\Enum\Rating;
use App\Entity\ExpensesContainerInterface;
use App\Entity\FundAward;
use App\Entity\ReturnExpenseTrait;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Repository\FundReturn\CrstsFundReturnRepository;
use App\Utility\FinancialQuarter;
use App\Utility\TypeHelper;
use App\Validator\ExpensesValidator;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

#[ORM\Entity(repositoryClass: CrstsFundReturnRepository::class)]
#[Callback([ExpensesValidator::class, 'validate'], groups: ['expenses'])]
class CrstsFundReturn extends FundReturn implements ExpensesContainerInterface
{
    use ReturnExpenseTrait;

    #[ORM\Column(type: Types::TEXT, length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, nullable: true)]
    #[Length(max: 16383, groups: ['overall_progress'])]
    #[NotBlank(message: 'crsts_fund_return.progress_summary.not_blank', groups: ["overall_progress"])]
    private ?string $progressSummary = null; // 1top_info: Programme level progress summary

    #[ORM\Column(type: Types::TEXT, length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, nullable: true)]
    #[Length(max: 16383, groups: ['overall_progress'])]
    #[NotBlank(message: 'crsts_fund_return.delivery_confidence.not_blank', groups: ["overall_progress"])]
    private ?string $deliveryConfidence = null; // 1top_info: Programme delivery confidence comment assessment

    #[ORM\Column(nullable: true, enumType: Rating::class)]
    #[NotNull(message: 'crsts_fund_return.overall_confidence.not_null', groups: ["overall_progress"])]
    private ?Rating $overallConfidence = null; // 1top_info: Overall confidence

    #[ORM\Column(type: Types::TEXT, length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, nullable: true)]
    #[Length(max: 16383, groups: ['local_and_rdel'])]
    #[NotBlank(message: 'crsts_fund_return.local_contribution.not_blank', groups: ["local_and_rdel"])]
    private ?string $localContribution = null; // 2top_exp: Local contribution.  Please provide a current breakdown of local contribution achieved, by source.

    #[ORM\Column(type: Types::TEXT, length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, nullable: true)]
    #[Length(max: 16383, groups: ['local_and_rdel'])]
    #[NotBlank(message: 'crsts_fund_return.resource_funding.not_blank', groups: ["local_and_rdel"])]
    private ?string $resourceFunding = null; // 2top_exp: Resource (RDEL) funding.  Please see Appendix A.

    #[ORM\Column(type: Types::TEXT, length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, nullable: true)]
    #[Length(max: 16383, groups: ['comments'])]
    private ?string $comments = null; // 2top_exp: Comment box.  Please provide some commentary on the programme expenditure table above.  Any expenditure post 26/27 MUST be explained.

    public function __construct()
    {
        parent::__construct();
        $this->__expenseConstruct();
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

    public function getFund(): Fund
    {
        return Fund::CRSTS1;
    }

    public function createFundReturnForNextQuarter(): static
    {
        /** @phpstan-ignore-next-line */
        $nextReturn = new static();
        $this->getFundAward()->addReturn($nextReturn);

        $nextQuarter = (new FinancialQuarter($this->getYear(), $this->getQuarter()))
            ->getNextQuarter();

        $nextReturn
            ->setQuarter($nextQuarter->quarter)
            ->setYear($nextQuarter->initialYear)
            ->setLocalContribution($this->getLocalContribution())
            ->setResourceFunding($this->getResourceFunding());

        $this->createExpensesForNextQuarter($this->expenses, $this->getFinancialQuarter())->map(
            fn($e) => $nextReturn->addExpense($e)
        );

        $this->getSchemeReturns()->map(
            fn(SchemeReturn $sr) => $nextReturn->addSchemeReturn($sr->createSchemeReturnForNextQuarter())
        );
        return $nextReturn;
    }

    public static function createInitialFundReturnStartingAt(FinancialQuarter $financialQuarter, FundAward $fundAward): static
    {
        /** @phpstan-ignore-next-line */
        $return = new static();
        $fundAward->addReturn($return);

        $return
            ->setQuarter($financialQuarter->quarter)
            ->setYear($financialQuarter->initialYear);

        $schemes = $fundAward->getAuthority()->getSchemesForFund($fundAward->getType());
        $schemes->map(fn(Scheme $s) => CrstsSchemeReturn::createInitialSchemeReturnFor($s));

        return $return;
    }

    // ----- Wrappers for more specific type-hinting + checks

    public function getSchemeReturnForScheme(Scheme $scheme): ?CrstsSchemeReturn
    {
        return TypeHelper::checkMatchesClassOrNull(CrstsSchemeReturn::class, parent::getSchemeReturnForScheme($scheme));
    }

    /**
     * @return Collection<int, CrstsSchemeReturn>
     * @phpstan-ignore-next-line
     */
    public function getSchemeReturns(): Collection
    {
        // PHPStan doesn't support covariant return types for generic collections,
        // but we know this collection only contains CrstsSchemeReturn instances.
        /** @var Collection<int, CrstsSchemeReturn> */
        return parent::getSchemeReturns();
    }

    public function addSchemeReturn(SchemeReturn $schemeReturn): static
    {
        TypeHelper::checkMatchesClass(CrstsSchemeReturn::class, $schemeReturn);
        return parent::addSchemeReturn($schemeReturn);
    }
}
