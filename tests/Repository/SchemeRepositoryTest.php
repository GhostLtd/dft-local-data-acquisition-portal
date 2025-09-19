<?php

namespace App\Tests\Repository;

use App\Entity\Authority;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Repository\SchemeRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class SchemeRepositoryTest extends TestCase
{
    /** @var ManagerRegistry&MockObject */
    private ManagerRegistry $registry;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var QueryBuilder&MockObject */
    private QueryBuilder $queryBuilder;
    /** @var Query&MockObject */
    private Query $query;
    private SchemeRepository $repository;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->query = $this->createMock(Query::class);

        // Create a mock repository that extends SchemeRepository
        $this->repository = $this->getMockBuilder(SchemeRepository::class)
            ->setConstructorArgs([$this->registry])
            ->onlyMethods(['createQueryBuilder', 'getEntityManager'])
            ->getMock();

        // Don't set up getEntityManager globally - do it per test
    }

    /**
     * @dataProvider queryBuilderScenarioProvider
     */
    public function testQueryBuilderScenarios(
        string $method,
        array $methodArgs,
        bool $expectOrderBy,
        string $expectedReturnType,
        ?array $expectedResult = null
    ): void {
        $authority = $this->createMock(Authority::class);
        $authorityId = new Ulid();
        $authority->method('getId')->willReturn($authorityId);

        $this->setupQueryBuilderExpectations($authorityId, $expectOrderBy, $expectedResult);

        $result = $this->repository->$method($authority, ...$methodArgs);

        if ($expectedReturnType === 'QueryBuilder') {
            $this->assertSame($this->queryBuilder, $result);
        } elseif ($expectedReturnType === 'array') {
            $this->assertSame($expectedResult, $result);
        }
    }

    public function queryBuilderScenarioProvider(): array
    {
        $expectedSchemes = [
            $this->createMock(Scheme::class),
            $this->createMock(Scheme::class),
        ];

        return [
            'getSchemesForAuthority' => [
                'getSchemesForAuthority',
                [],
                true,
                'array',
                $expectedSchemes
            ],
            'getQueryBuilder with order' => [
                'getQueryBuilderForSchemesForAuthority',
                [false],
                true,
                'QueryBuilder'
            ],
            'getQueryBuilder without order' => [
                'getQueryBuilderForSchemesForAuthority',
                [true],
                false,
                'QueryBuilder'
            ],
        ];
    }

    private function setupQueryBuilderExpectations(Ulid $authorityId, bool $expectOrderBy, ?array $expectedResult = null): void
    {
        // For query builder tests, we don't need getEntityManager
        /** @phpstan-ignore-next-line */
        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('scheme')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())->method('join')->with('scheme.authority', 'authority')->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())->method('where')->with('authority.id = :authority_id')->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())->method('setParameter')->with('authority_id', $authorityId, 'ulid')->willReturn($this->queryBuilder);

        if ($expectOrderBy) {
            $this->queryBuilder->expects($this->once())->method('orderBy')->with('scheme.name', 'ASC')->willReturn($this->queryBuilder);
        } else {
            $this->queryBuilder->expects($this->never())->method('orderBy');
        }

        if ($expectedResult !== null) {
            $this->queryBuilder->expects($this->once())->method('getQuery')->willReturn($this->query);
            $this->query->expects($this->once())->method('getResult')->willReturn($expectedResult);
        }
    }



    /**
     * @dataProvider previousAndNextSchemesProvider
     */
    public function testGetPreviousAndNextSchemes(
        ?array $dbResult,
        bool $throwException,
        array $expectedResult
    ): void {
        $fundReturn = $this->createMock(FundReturn::class);
        $fundReturnId = new Ulid();
        $fundReturn->method('getId')->willReturn($fundReturnId);

        $currentScheme = $this->createMock(Scheme::class);
        $currentSchemeId = new Ulid();
        $currentScheme->method('getId')->willReturn($currentSchemeId);

        // For database connection tests, we need getEntityManager
        /** @phpstan-ignore-next-line */
        $this->repository->method('getEntityManager')
            ->willReturn($this->entityManager);

        $connection = $this->createMock(Connection::class);
        $this->entityManager->method('getConnection')->willReturn($connection);

        if ($throwException) {
            $connection->expects($this->once())
                ->method('fetchAssociative')
                ->willThrowException($this->createMock(DBALException::class));
        } else {
            $connection->expects($this->once())
                ->method('fetchAssociative')
                ->with(
                    $this->stringContains('LAG(s.id)'),
                    [
                        'fund_return_id' => $fundReturnId,
                        'current_scheme_id' => $currentSchemeId,
                    ],
                    [
                        'fund_return_id' => 'ulid',
                        'current_scheme_id' => 'ulid',
                    ]
                )
                ->willReturn($dbResult === null ? false : $dbResult);
        }

        $result = $this->repository->getPreviousAndNextSchemes($fundReturn, $currentScheme);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('previous', $result);
        $this->assertArrayHasKey('next', $result);

        if ($expectedResult['previous'] !== null) {
            $this->assertInstanceOf(Ulid::class, $result['previous']);
        } else {
            $this->assertNull($result['previous']);
        }

        if ($expectedResult['next'] !== null) {
            $this->assertInstanceOf(Ulid::class, $result['next']);
        } else {
            $this->assertNull($result['next']);
        }
    }

    public function previousAndNextSchemesProvider(): array
    {
        $prevId = (new Ulid())->toBinary();
        $nextId = (new Ulid())->toBinary();

        return [
            'valid data with both ids' => [
                ['prev_id' => $prevId, 'next_id' => $nextId],
                false,
                ['previous' => 'ulid', 'next' => 'ulid']
            ],
            'null values' => [
                ['prev_id' => null, 'next_id' => null],
                false,
                ['previous' => null, 'next' => null]
            ],
            'only previous id' => [
                ['prev_id' => $prevId, 'next_id' => null],
                false,
                ['previous' => 'ulid', 'next' => null]
            ],
            'only next id' => [
                ['prev_id' => null, 'next_id' => $nextId],
                false,
                ['previous' => null, 'next' => 'ulid']
            ],
            'database exception' => [
                null,
                true,
                ['previous' => null, 'next' => null]
            ],
            'no row found' => [
                null,
                false,
                ['previous' => null, 'next' => null]
            ],
        ];
    }



    /**
     * @dataProvider findForDashboardProvider
     */
    public function testFindForDashboard(string $schemeId, ?Scheme $expectedResult): void
    {
        $this->setupFindForDashboardExpectations($expectedResult);

        $result = $this->repository->findForDashboard($schemeId);

        if ($expectedResult) {
            $this->assertSame($expectedResult, $result);
        } else {
            $this->assertNull($result);
        }
    }

    public function findForDashboardProvider(): array
    {
        $schemeId = (string) new Ulid();
        $expectedScheme = $this->createMock(Scheme::class);

        return [
            'scheme found' => [$schemeId, $expectedScheme],
            'scheme not found' => [$schemeId, null],
        ];
    }

    private function setupFindForDashboardExpectations(?Scheme $expectedResult): void
    {
        // For query builder tests, we don't need getEntityManager
        /** @phpstan-ignore-next-line */
        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('scheme')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())->method('where')->with('scheme.id = :id')->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())->method('getQuery')->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('setParameter')
            ->with('id', $this->isInstanceOf(Ulid::class), 'ulid')
            ->willReturn($this->query);

        $this->query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($expectedResult);
    }

}