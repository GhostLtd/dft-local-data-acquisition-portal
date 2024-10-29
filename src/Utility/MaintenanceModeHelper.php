<?php

namespace App\Utility;

use App\Entity\Utility\MaintenanceLock;
use App\Repository\Utility\MaintenanceLockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MaintenanceModeHelper
{
    public const string MAINTENANCE_INFO_CACHE_KEY = 'app.maintenance_mode_info';
    public const int MAINTENANCE_INFO_CACHE_EXPIRY = 3600;

    /** @var array<string> */
    public const array ROUTE_WHITELIST = [
        'app_dashboard',
    ];

    protected ?bool $isActive = null;
    protected ?array $whitelistedIps = null;

    public function __construct(
        #[Autowire('@cache.app')]
        protected CacheInterface            $cache,
        protected EntityManagerInterface    $entityManager,
        protected MaintenanceLockRepository $maintenanceLockRepository,
    ) {}

    public function isMaintenanceModeEnabledForRouteAndIp(string $route, string $clientIp): bool
    {
        if (!$this->isMaintenanceModeEnabled()) {
            return false;
        }

        if ($this->isWhitelistedRoute($route)) {
            return false;
        }

        return !in_array($clientIp, $this->getWhitelistedIps());
    }

    public function isMaintenanceModeEnabled(): bool
    {
        $this->loadState();
        return $this->isActive;
    }

    public function getWhitelistedIps(): array
    {
        $this->loadState();
        return $this->whitelistedIps;
    }

    public function isWhitelistedRoute(?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }

        if ($routeName === '_wdt') {
            return true;
        }

        if (1 === preg_match('/^_profiler/', $routeName)) {
            return true;
        }

        if (in_array($routeName, static::ROUTE_WHITELIST)) {
            return true;
        }

        return false;
    }

    public function enableMaintenanceMode(?array $whitelistIps): void
    {
        try {
            $this->entityManager->beginTransaction();
            $this->entityManager->createQueryBuilder()
                ->delete()
                ->from(MaintenanceLock::class, 'm')
                ->getQuery()
                ->execute();

            $lock = (new MaintenanceLock())
                ->setWhitelistedIps($whitelistIps);

            $this->entityManager->persist($lock);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->isActive = true;
            $this->whitelistedIps = $whitelistIps;
        }
        catch(\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function disableMaintenanceMode(): void
    {
        $this->entityManager->createQueryBuilder()
            ->delete()
            ->from(MaintenanceLock::class, 'm')
            ->getQuery()
            ->execute();

        try {
            $this->cache->delete(self::MAINTENANCE_INFO_CACHE_KEY);
        } catch (InvalidArgumentException) {}

        $this->isActive = false;
        $this->whitelistedIps = [];
    }

    protected function loadState(): void
    {
        if ($this->isActive === null) {
            [$this->isActive, $this->whitelistedIps] =
                $this->cache->get(
                    static::MAINTENANCE_INFO_CACHE_KEY,
                    function (ItemInterface $item) {
                        $item->expiresAfter(static::MAINTENANCE_INFO_CACHE_EXPIRY);
                        return $this->maintenanceLockRepository->getIsActiveAndWhitelistedIps();
                    },
                );
        }
    }
}
