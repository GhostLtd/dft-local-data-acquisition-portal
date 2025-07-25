<?php

namespace App\Entity\FundReturn;

use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\PropertyChangeLoggableInterface;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\Traits\IdTrait;
use App\Entity\User;
use App\Repository\FundReturn\FundReturnRepository;
use App\Utility\FinancialQuarter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FundReturnRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::CRSTS1->value => CrstsFundReturn::class,
])]
abstract class FundReturn implements PropertyChangeLoggableInterface
{
    public const string STATE_INITIAL = 'initial';
    public const string STATE_OPEN = 'open';
    public const string STATE_SUBMITTED = 'submitted';

    public const string TRANSITION_OPEN_RETURN = 'open_return';
    public const string TRANSITION_REOPEN_RETURN = 'reopen_return';
    public const string TRANSITION_SUBMIT_RETURN = 'submit_return';

    use IdTrait;

    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $quarter = null;

    #[ORM\Column(length: 10)]
    private string $state;

    #[ORM\ManyToOne(inversedBy: 'returns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FundAward $fundAward = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signoffName = null; // top_signoff

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $signoffEmail = null; // top_signoff

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $signoffDate = null; // top_signoff

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?User $signoffUser = null; // top_signoff

    /**
     * @var Collection<int, SchemeReturn>
     */
    #[ORM\OneToMany(targetEntity: SchemeReturn::class, mappedBy: 'fundReturn', cascade: ['persist'], orphanRemoval: true)]
    private Collection $schemeReturns;

    public function __construct()
    {
        $this->schemeReturns = new ArrayCollection();
        $this->state = self::STATE_INITIAL;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;
        return $this;
    }

    public function signoff(User $user): static
    {
        $this
            ->setSignoffUser($user)
            ->setSignoffDate(new \DateTime())
            ->setSignoffEmail($user->getEmail())
            ->setSignoffName($user->getName());
        return $this;
    }

    public function reOpen(): static
    {
        $this
            ->setSignoffUser(null)
            ->setSignoffDate(null)
            ->setSignoffEmail(null)
            ->setSignoffName(null);
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

    public function getFundAward(): ?FundAward
    {
        return $this->fundAward;
    }

    public function setFundAward(?FundAward $fundAward): static
    {
        $this->fundAward = $fundAward;
        return $this;
    }

    public function getSignoffName(): ?string
    {
        return $this->signoffName;
    }

    public function setSignoffName(?string $signoffName): static
    {
        $this->signoffName = $signoffName;
        return $this;
    }

    public function getSignoffEmail(): ?string
    {
        return $this->signoffEmail;
    }

    public function setSignoffEmail(?string $signoffEmail): static
    {
        $this->signoffEmail = $signoffEmail;
        return $this;
    }

    public function getSignoffDate(): ?\DateTimeInterface
    {
        return $this->signoffDate;
    }

    public function setSignoffDate(?\DateTimeInterface $signoffDate): static
    {
        $this->signoffDate = $signoffDate;
        return $this;
    }

    public function getSignoffUser(): ?User
    {
        return $this->signoffUser;
    }

    public function setSignoffUser(?User $signoffUser): static
    {
        $this->signoffUser = $signoffUser;
        return $this;
    }

    /**
     * @return Collection<int, SchemeReturn>
     */
    public function getSchemeReturns(): Collection
    {
        return $this->schemeReturns;
    }

    public function addSchemeReturn(SchemeReturn $schemeReturn): static
    {
        if (!$this->schemeReturns->contains($schemeReturn)) {
            $this->schemeReturns->add($schemeReturn);
            $schemeReturn->setFundReturn($this);
        }

        return $this;
    }

    public function removeSchemeReturn(SchemeReturn $schemeReturn): static
    {
        if ($this->schemeReturns->removeElement($schemeReturn)) {
            // set the owning side to null (unless already changed)
            if ($schemeReturn->getFundReturn() === $this) {
                $schemeReturn->setFundReturn(null);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------------

    abstract public function getFund(): Fund;
    abstract public function createFundReturnForNextQuarter(): static;
    abstract public static function createInitialFundReturnStartingAt(FinancialQuarter $financialQuarter, FundAward $fundAward): static;

    public function getSchemeReturnForScheme(Scheme $scheme): ?SchemeReturn
    {
        foreach($this->getSchemeReturns() as $schemeReturn) {
            if ($schemeReturn->getScheme() === $scheme) {
                return $schemeReturn;
            }
        }

        return null;
    }

    public function getNextYearAsTwoDigits(): ?string
    {
        return $this->year ?
            substr(strval($this->year + 1), 2) :
            null;
    }

    public function isSignedOff(): bool
    {
        return $this->state === self::STATE_SUBMITTED;
    }

    public function getFinancialQuarter(): FinancialQuarter
    {
        return new FinancialQuarter($this->year, $this->quarter);
    }
}
