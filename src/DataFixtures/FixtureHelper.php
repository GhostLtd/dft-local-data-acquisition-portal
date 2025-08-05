<?php

namespace App\DataFixtures;

use App\DataFixtures\Definition\FundAwardDefinition;
use App\DataFixtures\Definition\FundReturn\CrstsFundReturnDefinition;
use App\DataFixtures\Definition\UserDefinition;
use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\AuthorityDefinition;
use App\DataFixtures\Definition\MilestoneDefinition;
use App\DataFixtures\Definition\SchemeDefinition;
use App\DataFixtures\Definition\SchemeReturn\CrstsSchemeReturnDefinition;
use App\Entity\Enum\Fund;
use App\Entity\ExpenseEntry;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\SchemeFund\BenefitCostRatio;
use App\Entity\Authority;
use App\Entity\Milestone;
use App\Entity\Scheme;
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
     * @param array<mixed> $objects
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

        $this->processSchemeAndFundDefinitions($authority, $definition->getSchemes(), $definition->getFundAwards());

        return $authority;
    }

    public function processSchemeAndFundDefinitions(Authority $authority, array $schemeDefinitions, array $fundAwardDefinitions): void
    {
        foreach($schemeDefinitions as $schemeDefinition) {
            $authority->addScheme($this->createScheme($schemeDefinition));
        }

        $schemes = $authority->getSchemes()->toArray();
        foreach($fundAwardDefinitions as $fundAwardDefinition) {
            $authority->addFundAward($this->createFundAward($fundAwardDefinition, $schemes));
        }
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

        $fundAward = (new FundAward())
            ->setType($fund);

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
            ->setSchemeIdentifier($definition->getSchemeIdentifier())
            ->setActiveTravelElement($definition->getActiveTravelElement())
            ->setTransportMode($definition->getTransportMode())
            ->setCrstsData($definition->getCrstsData())
            ->setFunds($definition->getFunds());

        $this->persist([$scheme]);

        return $scheme;
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

            $return->addSchemeReturn($this->createCrstsSchemeReturn($schemeReturnDefinition, $scheme));
        }

        $this->persist([$return]);

        return $return;
    }

    public function createCrstsSchemeReturn(CrstsSchemeReturnDefinition $definition, Scheme $scheme): CrstsSchemeReturn
    {
        $bcr = (new BenefitCostRatio())
            ->setType($definition->getBenefitCostRatioType())
            ->setValue($definition->getBenefitCostRatioValue());

        $return = (new CrstsSchemeReturn())
            ->setScheme($scheme)
            ->setBenefitCostRatio($bcr)
            ->setRisks($definition->getRisks())
            ->setTotalCost($definition->getTotalCost())
            ->setAgreedFunding($definition->getAgreeFunding())
            ->setOnTrackRating($definition->getOnTrackRating())
            ->setBusinessCase($definition->getBusinessCase())
            ->setExpectedBusinessCaseApproval($definition->getExpectedBusinessCaseApproval())
            ->setProgressUpdate($definition->getProgressUpdate())
            ->setReadyForSignoff($definition->getReadyForSignoff())
            ->setDevelopmentOnly($definition->getDevelopmentOnly());

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
