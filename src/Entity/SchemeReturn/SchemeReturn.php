<?php

namespace App\Entity\SchemeReturn;

use App\Entity\Enum\Fund;
use App\Entity\FundReturn\FundReturn;
use App\Entity\PropertyChangeLoggableInterface;
use App\Entity\Scheme;
use App\Entity\Traits\IdTrait;
use App\Repository\SchemeReturn\SchemeReturnRepository;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\Length;

#[ORM\Entity(repositoryClass: SchemeReturnRepository::class)]
#[ORM\InheritanceType('JOINED')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string')]
#[ORM\DiscriminatorMap([
    Fund::CRSTS1->value => CrstsSchemeReturn::class,
])]
abstract class SchemeReturn implements PropertyChangeLoggableInterface
{
    use IdTrait;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Scheme $scheme = null;

    #[ORM\ManyToOne(inversedBy: 'schemeReturns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FundReturn $fundReturn = null;

    #[ORM\Column(type: Types::TEXT, length: AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, nullable: true)]
    #[Length(max: 16383, groups: ['milestone_rating'])]
    private ?string $risks = null; // josh_4/2: Scheme level risks

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $readyForSignoff = false;

    public function getScheme(): ?Scheme
    {
        return $this->scheme;
    }

    public function setScheme(?Scheme $scheme): static
    {
        $this->scheme = $scheme;
        return $this;
    }

    public function getRisks(): ?string
    {
        return $this->risks;
    }

    public function setRisks(?string $risks): static
    {
        $this->risks = $risks;
        return $this;
    }

    public function getFundReturn(): ?FundReturn
    {
        return $this->fundReturn;
    }

    public function setFundReturn(?FundReturn $fundReturn): static
    {
        $this->fundReturn = $fundReturn;
        return $this;
    }

    public function getReadyForSignoff(): bool
    {
        return $this->readyForSignoff;
    }

    public function setReadyForSignoff(bool $readyForSignoff): static
    {
        $this->readyForSignoff = $readyForSignoff;
        return $this;
    }

    // --------------------------------------------------------------------------------

    abstract public function getFund(): Fund;
    abstract public function createSchemeReturnForNextQuarter(): static;
    abstract public static function createInitialSchemeReturnFor(Scheme $scheme): static;
}
