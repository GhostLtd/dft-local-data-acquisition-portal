<?php

namespace App\Tests\Unit;

use App\DataFixtures\Definition\AuthorityDefinition;
use App\DataFixtures\Definition\Expense\ExpenseDefinition;
use App\DataFixtures\Definition\FundAwardDefinition;
use App\DataFixtures\Definition\FundReturn\CrstsFundReturnDefinition;
use App\DataFixtures\Definition\MilestoneDefinition;
use App\DataFixtures\Definition\SchemeDefinition;
use App\DataFixtures\Definition\SchemeReturn\CrstsSchemeReturnDefinition;
use App\DataFixtures\Definition\UserDefinition;
use App\DataFixtures\FixtureHelper;
use App\Entity\Enum\BenefitCostRatioType;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\Fund;
use App\Entity\Enum\MilestoneType;
use App\Entity\ExpenseEntry;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Milestone;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FixtureHelperTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    private FixtureHelper $helper;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->helper = new FixtureHelper();
        $this->helper->setEntityManager($this->entityManager);
    }

    public function testSetEntityManager(): void
    {
        $newEntityManager = $this->createMock(EntityManagerInterface::class);
        $result = $this->helper->setEntityManager($newEntityManager);

        $this->assertSame($this->helper, $result);
    }

    /**
     * @dataProvider userDefinitionProvider
     */
    public function testCreateUser(
        string $name,
        string $email,
        string $phone,
        string $position
    ): void {
        $definition = new UserDefinition($name, $position, $phone, $email);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(User::class));

        $user = $this->helper->createUser($definition);

        $this->assertSame($name, $user->getName());
        $this->assertSame($email, $user->getEmail());
        $this->assertSame($phone, $user->getPhone());
        $this->assertSame($position, $user->getPosition());
    }

    public function userDefinitionProvider(): array
    {
        return [
            'standard user' => ['John Doe', 'john@example.com', '01234567890', 'Manager'],
            'user with long name' => ['John Smith-Johnson', 'john.smith@example.com', '07123456789', 'Senior Analyst'],
            'user with unicode' => ['José García', 'jose@example.com', '02345678901', 'Administrador'],
        ];
    }

    public function testCreateUserWithDuplicateEmail(): void
    {
        $definition1 = new UserDefinition('John Doe', 'Manager', '01234567890', 'john@example.com');
        $definition2 = new UserDefinition('John Doe', 'Manager', '01234567890', 'john@example.com');

        $this->entityManager->expects($this->once())
            ->method('persist');

        $user1 = $this->helper->createUser($definition1);
        $user2 = $this->helper->createUser($definition2);

        $this->assertSame($user1, $user2);
    }

    public function testCreateUserWithDuplicateEmailButDifferentDetails(): void
    {
        $definition1 = new UserDefinition('John Doe', 'Manager', '01234567890', 'john@example.com');
        $definition2 = new UserDefinition('Jane Doe', 'Manager', '01234567890', 'john@example.com');

        $this->entityManager->expects($this->once())
            ->method('persist');

        $this->helper->createUser($definition1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User with given email already exists, albeit with different details');

        $this->helper->createUser($definition2);
    }

    /**
     * @dataProvider expenseDefinitionProvider
     */
    public function testCreateExpense(
        ExpenseType $type,
        string $division,
        string $column,
        bool $forecast,
        ?string $value
    ): void {
        $definition = new ExpenseDefinition($type, $division, $column, $forecast, $value);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ExpenseEntry::class));

        $expense = $this->helper->createExpense($definition);

        $this->assertSame($type, $expense->getType());
        $this->assertSame($division, $expense->getDivision());
        $this->assertSame($column, $expense->getColumn());
        $this->assertSame($forecast, $expense->isForecast());
        $this->assertSame($value, $expense->getValue());
    }

    public function expenseDefinitionProvider(): array
    {
        return [
            'capital expenditure' => [
                ExpenseType::FUND_CAPITAL_EXPENDITURE,
                '2024-25',
                'Q1',
                false,
                '1000.00'
            ],
            'resource expenditure forecast' => [
                ExpenseType::FUND_RESOURCE_EXPENDITURE,
                '2025-26',
                'Q2',
                true,
                '2500.50'
            ],
            'null value expense' => [
                ExpenseType::FUND_CAPITAL_LOCAL_CONTRIBUTION,
                '2024-25',
                'total',
                false,
                null
            ],
        ];
    }

    /**
     * @dataProvider milestoneDefinitionProvider
     */
    public function testCreateMilestone(
        \DateTime $date,
        MilestoneType $type
    ): void {
        $definition = new MilestoneDefinition($type, $date);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Milestone::class));

        $milestone = $this->helper->createMilestone($definition);

        $this->assertSame($date, $milestone->getDate());
        $this->assertSame($type, $milestone->getType());
    }

    public function milestoneDefinitionProvider(): array
    {
        return [
            'construction start' => [
                new \DateTime('2024-06-01'),
                MilestoneType::START_CONSTRUCTION
            ],
            'development end' => [
                new \DateTime('2025-12-31'),
                MilestoneType::END_DEVELOPMENT
            ],
        ];
    }

    public function testCreateScheme(): void
    {
        $crstsData = new \App\Entity\SchemeData\CrstsData();
        $definition = new SchemeDefinition(
            $crstsData,
            'Test Scheme',
            'Test Description',
            null,
            null,
            'SCH123',
            [Fund::CRSTS1]
        );

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Scheme::class));

        $scheme = $this->helper->createScheme($definition);

        $this->assertSame('Test Scheme', $scheme->getName());
        $this->assertSame('Test Description', $scheme->getDescription());
        $this->assertNull($scheme->getActiveTravelElement());
        $this->assertSame([Fund::CRSTS1], $scheme->getFunds());
    }

    public function testCreateAuthorityWithAdmin(): void
    {
        $adminDefinition = new UserDefinition('Admin User', 'Administrator', '01234567890', 'admin@example.com');
        $definition = new AuthorityDefinition('Test Authority', $adminDefinition, [], []);

        $this->entityManager->expects($this->exactly(2))
            ->method('persist');

        $authority = $this->helper->createAuthority($definition);

        $this->assertSame('Test Authority', $authority->getName());
        $this->assertInstanceOf(User::class, $authority->getAdmin());
        $this->assertSame('admin@example.com', $authority->getAdmin()->getEmail());
    }

    public function testCreateFundAwardWithUnsupportedReturnType(): void
    {
        $definition = $this->createMock(FundAwardDefinition::class);
        $definition->method('getFund')->willReturn(Fund::CRSTS1);

        $invalidReturnDefinition = $this->createMock(\stdClass::class);
        $definition->method('getReturns')->willReturn([$invalidReturnDefinition]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported returnDefinition type for fund CRSTS1:');

        $this->helper->createFundAward($definition);
    }

    public function testCreateCrstsFundReturnWithMissingScheme(): void
    {
        $definition = $this->createMock(CrstsFundReturnDefinition::class);
        $definition->method('getComments')->willReturn('Test comments');
        $definition->method('getDeliveryConfidence')->willReturn(null);
        $definition->method('getLocalContribution')->willReturn(null);
        $definition->method('getYear')->willReturn(2024);
        $definition->method('getQuarter')->willReturn(1);
        $definition->method('getOverallConfidence')->willReturn(null);
        $definition->method('getProgressSummary')->willReturn('Test progress');
        $definition->method('getResourceFunding')->willReturn(null);
        $definition->method('getExpenses')->willReturn([]);
        $definition->method('getSchemeReturns')->willReturn(['NonexistentScheme' => $this->createMock(CrstsSchemeReturnDefinition::class)]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Scheme referenced by return not found: NonexistentScheme');

        $this->helper->createCrstsFundReturn($definition, []);
    }

    public function testCreateCrstsSchemeReturn(): void
    {
        $scheme = $this->createMock(Scheme::class);
        $scheme->method('getName')->willReturn('Test Scheme');

        $definition = $this->createMock(CrstsSchemeReturnDefinition::class);
        $definition->method('getBenefitCostRatioType')->willReturn(BenefitCostRatioType::VALUE);
        $definition->method('getBenefitCostRatioValue')->willReturn('2.5');
        $definition->method('getRisks')->willReturn('Test risks');
        $definition->method('getTotalCost')->willReturn('1000000');
        $definition->method('getAgreeFunding')->willReturn('500000');
        $definition->method('getOnTrackRating')->willReturn(null);
        $definition->method('getBusinessCase')->willReturn(null);
        $definition->method('getExpectedBusinessCaseApproval')->willReturn(null);
        $definition->method('getProgressUpdate')->willReturn('Test progress');
        $definition->method('getReadyForSignoff')->willReturn(false);
        $definition->method('getDevelopmentOnly')->willReturn(false);
        $definition->method('getMilestones')->willReturn([]);
        $definition->method('getExpenses')->willReturn([]);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(CrstsSchemeReturn::class));

        $schemeReturn = $this->helper->createCrstsSchemeReturn($definition, $scheme);

        $this->assertSame($scheme, $schemeReturn->getScheme());
        $this->assertSame('Test risks', $schemeReturn->getRisks());
        $this->assertSame('1000000', $schemeReturn->getTotalCost());
    }
}
