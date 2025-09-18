<?php

namespace App\Tests\Utility;

use App\Entity\Utility\MaintenanceLock;
use App\Repository\Utility\MaintenanceLockRepository;
use App\Utility\MaintenanceModeHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MaintenanceModeHelperTest extends TestCase
{
    /** @var CacheInterface&MockObject */
    private CacheInterface $cache;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var MaintenanceLockRepository&MockObject */
    private MaintenanceLockRepository $repository;
    private MaintenanceModeHelper $helper;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(MaintenanceLockRepository::class);
        $this->helper = new MaintenanceModeHelper($this->cache, $this->entityManager, $this->repository);
    }

    /**
     * @dataProvider maintenanceModeStatusProvider
     */
    public function testIsMaintenanceModeEnabledForRouteAndIp(
        bool $isEnabled,
        array $whitelistedIps,
        string $route,
        string $clientIp,
        bool $expectedResult
    ): void {
        $this->mockCacheGet($isEnabled, $whitelistedIps);

        $result = $this->helper->isMaintenanceModeEnabledForRouteAndIp($route, $clientIp);

        $this->assertSame($expectedResult, $result);
    }

    public function maintenanceModeStatusProvider(): array
    {
        return [
            'returns false when disabled' => [
                'isEnabled' => false,
                'whitelistedIps' => [],
                'route' => 'app_test',
                'clientIp' => '192.168.1.1',
                'expectedResult' => false,
            ],
            'returns false for whitelisted route' => [
                'isEnabled' => true,
                'whitelistedIps' => [],
                'route' => 'app_dashboard',
                'clientIp' => '192.168.1.1',
                'expectedResult' => false,
            ],
            'returns false for whitelisted IP' => [
                'isEnabled' => true,
                'whitelistedIps' => ['192.168.1.1', '10.0.0.1'],
                'route' => 'app_test',
                'clientIp' => '192.168.1.1',
                'expectedResult' => false,
            ],
            'returns true for non-whitelisted' => [
                'isEnabled' => true,
                'whitelistedIps' => ['10.0.0.1'],
                'route' => 'app_test',
                'clientIp' => '192.168.1.1',
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @dataProvider routeWhitelistProvider
     */
    public function testIsWhitelistedRoute(?string $route, bool $expected): void
    {
        $this->assertSame($expected, $this->helper->isWhitelistedRoute($route));
    }

    public function routeWhitelistProvider(): array
    {
        return [
            'profiler toolbar' => ['_wdt', true],
            'profiler base' => ['_profiler', true],
            'profiler home' => ['_profiler_home', true],
            'profiler search' => ['_profiler_search', true],
            'dashboard route' => ['app_dashboard', true],
            'other route' => ['app_other', false],
            'null route' => [null, false],
        ];
    }

    public function testEnableMaintenanceModeCreatesLockAndClearsCache(): void
    {
        $whitelistIps = ['192.168.1.1', '10.0.0.1'];

        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('delete')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(MaintenanceLock::class));

        $this->entityManager->expects($this->once())->method('flush');
        $this->entityManager->expects($this->once())->method('commit');

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with(MaintenanceModeHelper::MAINTENANCE_INFO_CACHE_KEY);

        $this->helper->enableMaintenanceMode($whitelistIps);

        $this->assertTrue($this->helper->isMaintenanceModeEnabled());
        $this->assertEquals($whitelistIps, $this->helper->getWhitelistedIps());
    }

    public function testEnableMaintenanceModeRollsBackOnException(): void
    {
        $whitelistIps = ['192.168.1.1'];

        $this->entityManager
            ->expects($this->once())
            ->method('beginTransaction');

        $this->entityManager
            ->method('createQueryBuilder')
            ->willThrowException(new \RuntimeException('DB Error'));

        $this->entityManager
            ->expects($this->once())
            ->method('rollback');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('DB Error');

        $this->helper->enableMaintenanceMode($whitelistIps);
    }

    public function testDisableMaintenanceModeDeletesLockAndClearsCache(): void
    {
        $query = $this->createMock(Query::class);
        $query->expects($this->once())->method('execute');

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('delete')->willReturn($queryBuilder);
        $queryBuilder->method('from')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);

        $this->entityManager
            ->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->cache
            ->expects($this->once())
            ->method('delete')
            ->with(MaintenanceModeHelper::MAINTENANCE_INFO_CACHE_KEY);

        $this->helper->disableMaintenanceMode();

        $this->assertFalse($this->helper->isMaintenanceModeEnabled());
        $this->assertEquals([], $this->helper->getWhitelistedIps());
    }

    public function testCachesMaintenanceStateFromRepository(): void
    {
        $expectedState = [true, ['192.168.1.1']];

        $this->cache
            ->expects($this->once())
            ->method('get')
            ->willReturnCallback(function($key, $callback) use ($expectedState) {
                $this->assertEquals(MaintenanceModeHelper::MAINTENANCE_INFO_CACHE_KEY, $key);

                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(MaintenanceModeHelper::MAINTENANCE_INFO_CACHE_EXPIRY);

                $this->repository
                    ->expects($this->once())
                    ->method('getIsActiveAndWhitelistedIps')
                    ->willReturn($expectedState);

                return $callback($item);
            });

        $this->assertTrue($this->helper->isMaintenanceModeEnabled());
        $this->assertEquals(['192.168.1.1'], $this->helper->getWhitelistedIps());
    }

    private function mockCacheGet(bool $isActive, array $whitelistedIps): void
    {
        $this->cache
            ->method('get')
            ->willReturn([$isActive, $whitelistedIps]);
    }
}
