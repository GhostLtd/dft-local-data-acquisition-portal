<?php

namespace App\DataFixtures;

use App\DataFixtures\Definition\FundAwardDefinition;
use App\DataFixtures\Definition\FundReturn\CrstsFundReturnDefinition;
use App\DataFixtures\Definition\UserDefinition;
use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\AuthorityDefinition;
use App\DataFixtures\Definition\MilestoneDefinition;
use App\DataFixtures\Definition\SchemeDefinition;
use App\DataFixtures\Definition\SchemeFund\CrstsSchemeFundDefinition;
use App\DataFixtures\Definition\SchemeReturn\CrstsSchemeReturnDefinition;
use App\Entity\Enum\Fund;
use App\Entity\ExpenseEntry;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\SchemeFund\BenefitCostRatio;
use App\Entity\Authority;
use App\Entity\Milestone;
use App\Entity\Scheme;
use App\Entity\SchemeFund\CrstsSchemeFund;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class FixtureHelper
{
    protected ?EntityManagerInterface $entityManager = null;
    protected array $users = [];

    public function setEntityManager(EntityManagerInterface $entityManager): static
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * @param array<mixed> $object
     */
    protected function persist(array $objects): void
    {
        if (!$this->entityManager) {
            return;
        }

        foreach($objects as $object) {
            $this->entityManager->persist($object);
        }
    }

    // ----------------------------------------------------------------------------------------------------

    public function createAuthority(AuthorityDefinition $definition): Authority
    {
        $authority = (new Authority())
            ->setName($definition->getName());

        $admin = $this->createUser($definition->getAdmin(), $authority);

        $authority->setAdmin($admin);
        $this->persist([$authority]);

        foreach($definition->getSchemes() as $schemeDefinition) {
            $authority->addScheme($this->createScheme($schemeDefinition));
        }

        $schemes = $authority->getSchemes()->toArray();
        foreach($definition->getFundAwards() as $fundAwardDefinition) {
            $authority->addFundAward($this->createFundAward($fundAwardDefinition, $schemes));
        }

        return $authority;
    }

    public function createUser(UserDefinition $definition, ?Authority $authority=null): User
    {
        $email = $definition->getEmail();

        $existingUser = $this->users[$email] ?? null;

        if ($existingUser) {
            if (
                $definition->getName() !== $existingUser->getName() ||
                $definition->getPhone() !== $existingUser->getPhone() ||
                $definition->getPosition() !== $existingUser->getPosition()
            ) {
                throw new \RuntimeException('User with given email already exists, albeit with different details');
            }

            return $existingUser;
        }

        $user = (new User())
            ->setName($definition->getName())
            ->setPosition($definition->getPosition())
            ->setPhone($definition->getPhone())
            ->setEmail($email);

        $authority?->setAdmin($user);

        $this->users[$email] = $user;
        $this->persist([$user]);
        return $user;
    }

    /**
     * @param array<Scheme> $schemes
     */
    public function createFundAward(FundAwardDefinition $fundAwardDefinition, array $schemes=[]): FundAward
    {
        $fund = $fundAwardDefinition->getFund();

        $leadContactDefinition = $fundAwardDefinition->getLeadContact();
        $leadContact = $leadContactDefinition ?
            $this->createUser($leadContactDefinition) :
            null;


        $fundAward = (new FundAward())
            ->setType($fund)
            ->setLeadContact($leadContact);

        $this->persist([$fundAward]);

        foreach($fundAwardDefinition->getReturns() as $returnDefinition) {
            if ($fund === Fund::CRSTS1 && $returnDefinition instanceof CrstsFundReturnDefinition) {
                $return = $this->createCrstsFundReturn($returnDefinition, $schemes);
            } else {
                throw new \RuntimeException("Unsupported returnDefinition type for fund {$fund->value}: ".$returnDefinition::class);
            }

            $signoffUserDefinition = $returnDefinition->getSignoffUser();
            $signoffUser = $signoffUserDefinition ?
                $this->createUser($signoffUserDefinition) :
                null;

            $return
                ->setSignoffUser($signoffUser)
                ->setSignoffName($signoffUser?->getName())
                ->setSignoffEmail($signoffUser?->getEmail())
                ->setSignoffDate($returnDefinition->getSignoffDate());

            $fundAward->addReturn($return);
        }

        return $fundAward;
    }

    public function createScheme(SchemeDefinition $definition): Scheme
    {
        $scheme = (new Scheme())
            ->setName($definition->getName())
            ->setDescription($definition->getDescription())
            ->setRisks($definition->getRisks())
            ->setSchemeIdentifier($definition->getSchemeIdentifier())
            ->setActiveTravelElement($definition->getActiveTravelElement())
            ->setIncludesChargingPoints($definition->getIncludesChargingPoints())
            ->setIncludesCleanAirElements($definition->getIncludesCleanAirElements())
            ->setTransportMode($definition->getTransportMode());

        $this->persist([$scheme]);

        foreach($definition->getSchemeFunds() as $schemeFundDefinition) {
            $class = $schemeFundDefinition::class;

            $schemeFund = match($class) {
                CrstsSchemeFundDefinition::class => $this->createCrstsSchemeFund($schemeFundDefinition),
                default => throw new \RuntimeException("Unsupported SchemeFund definition class - {$class}"),
            };

            $scheme->addSchemeFund($schemeFund);
        }

        return $scheme;
    }

    public function createCrstsSchemeFund(CrstsSchemeFundDefinition $definition): CrstsSchemeFund
    {
        $bcr = (new BenefitCostRatio())
            ->setType($definition->getBenefitCostRatioType())
            ->setValue($definition->getBenefitCostRatioValue());

        $schemeFund = (new CrstsSchemeFund())
            ->setRetained($definition->isRetained())
            ->setPreviouslyTcf($definition->getPreviouslyTcf())
            ->setFundedMostlyAs($definition->getFundedMostlyAs())
            ->setBenefitCostRatio($bcr);

        $this->persist([$schemeFund]);

        return $schemeFund;
    }

    /**
     * @param array<Scheme> $schemes
     */
    public function createCrstsFundReturn(CrstsFundReturnDefinition $definition, array $schemes=[]): CrstsFundReturn
    {
        $return = (new CrstsFundReturn())
            ->setComments($definition->getComments())
            ->setDeliveryConfidence($definition->getDeliveryConfidence())
            ->setLocalContribution($definition->getLocalContribution())
            ->setYear($definition->getYear())
            ->setQuarter($definition->getQuarter())
            ->setOverallConfidence($definition->getOverallConfidence())
            ->setProgressSummary($definition->getProgressSummary())
            ->setRagProgressRating($definition->getRagProgressRating())
            ->setRagProgressSummary($definition->getRagProgressSummary())
            ->setResourceFunding($definition->getResourceFunding())
        ;

        foreach($definition->getExpenses() as $expenseDefinition) {
            $return->addExpense($this->createExpense($expenseDefinition));
        }

        foreach($definition->getSchemeReturns() as $schemeName => $schemeReturnDefinition) {
            $scheme = null;
            foreach($schemes as $loopScheme) {
                if ($schemeName === $loopScheme->getName()) {
                    $scheme = $loopScheme;
                    break;
                }
            }

            if (!$scheme) {
                throw new \RuntimeException("Scheme referenced by return not found: {$schemeName}");
            }

            $schemeFund = null;
            foreach($scheme->getSchemeFunds() as $loopFund) {
                $schemeFund = match(true) {
                    $loopFund instanceof CrstsSchemeFund => $loopFund,
                    default => null,
                };

                if ($schemeFund) {
                    break;
                }
            }

            if (!$schemeFund) {
                throw new \RuntimeException("Scheme referenced by return is not funded by CRSTS: {$schemeName}");
            }

            $return->addSchemeReturn($this->createCrstsSchemeReturn($schemeReturnDefinition, $schemeFund));
        }

        $this->persist([$return]);

        return $return;
    }

    public function createCrstsSchemeReturn(CrstsSchemeReturnDefinition $definition, CrstsSchemeFund $schemeFund): CrstsSchemeReturn
    {
        $return = (new CrstsSchemeReturn())
            ->setSchemeFund($schemeFund)
            ->setTotalCost($definition->getTotalCost())
            ->setAgreedFunding($definition->getAgreeFunding())
            ->setOnTrackRating($definition->getOnTrackRating())
            ->setBusinessCase($definition->getBusinessCase())
            ->setExpectedBusinessCaseApproval($definition->getExpectedBusinessCaseApproval())
            ->setProgressUpdate($definition->getProgressUpdate())
            ->setReadyForSignoff($definition->getReadyForSignoff());

        foreach($definition->getMilestones() as $milestoneDefinition) {
            $return->addMilestone($this->createMilestone($milestoneDefinition));
        }

        foreach($definition->getExpenses() as $expenseDefinition) {
            $return->addExpense($this->createExpense($expenseDefinition));
        }

        $this->persist([$return]);

        return $return;
    }

    public function createMilestone(MilestoneDefinition $definition): Milestone
    {
        $milestone = (new Milestone())
            ->setDate($definition->getDate())
            ->setType($definition->getType());

        $this->persist([$milestone]);

        return $milestone;
    }

    public function createExpense(ExpenseDefinition $definition): ExpenseEntry
    {
        $expense = (new ExpenseEntry())
            ->setType($definition->getType())
            ->setDivision($definition->getDivision())
            ->setColumn($definition->getColumn())
            ->setForecast($definition->isForecast())
            ->setValue($definition->getValue());

        $this->persist([$expense]);

        return $expense;
    }
}
