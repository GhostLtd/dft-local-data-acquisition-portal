<?php

namespace App\Tests\Utility;

use App\Entity\Authority;
use App\Entity\Enum\Permission;
use App\Entity\PermissionsView;
use App\Entity\User;
use App\Repository\PermissionsViewRepository;
use App\Utility\SimplifiedPermissionsHelper;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class SimplifiedPermissionsHelperTest extends TestCase
{
    /** @var PermissionsViewRepository&MockObject */
    private PermissionsViewRepository $repository;
    private SimplifiedPermissionsHelper $helper;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PermissionsViewRepository::class);
        $this->helper = new SimplifiedPermissionsHelper($this->repository);
    }

    public function testGetSimplifiedPermissionStringsReturnsAdminForAdminUser(): void
    {
        $user = $this->createMock(User::class);
        $authority = $this->createMock(Authority::class);

        $authority->expects($this->once())
            ->method('getAdmin')
            ->willReturn($user);

        $result = $this->helper->getSimplifiedPermissionStrings($user, $authority);

        $this->assertEquals(['admin'], $result);
    }

    /**
     * @dataProvider permissionStringsProvider
     */
    public function testGetSimplifiedPermissionStringsForNonAdminUser(array $permissions, array $expectedStrings): void
    {
        $user = $this->createMock(User::class);
        $authority = $this->createMock(Authority::class);
        $userId = new Ulid();
        $authorityId = new Ulid();

        $user->method('getId')->willReturn($userId);
        $authority->method('getId')->willReturn($authorityId);
        $authority->method('getAdmin')->willReturn($this->createMock(User::class)); // Different user

        $this->mockRepositoryQuery($permissions);

        $result = $this->helper->getSimplifiedPermissionStrings($user, $authority);

        $this->assertEquals($expectedStrings, $result);
    }

    public function permissionStringsProvider(): array
    {
        return [
            'single permission' => [
                'permissions' => [Permission::VIEWER],
                'expectedStrings' => ['viewer'],
            ],
            'multiple permissions' => [
                'permissions' => [Permission::VIEWER, Permission::EDITOR, Permission::SCHEME_MANAGER],
                'expectedStrings' => ['viewer', 'editor', 'scheme_manager'],
            ],
            'no permissions' => [
                'permissions' => [],
                'expectedStrings' => [],
            ],
            'all permissions' => [
                'permissions' => Permission::cases(),
                'expectedStrings' => ['scheme_manager', 'sign_off', 'mark_as_ready', 'editor', 'viewer'],
            ],
        ];
    }

    public function testGetPermissionViewsCallsRepositoryWithCorrectParameters(): void
    {
        $user = $this->createMock(User::class);
        $authority = $this->createMock(Authority::class);
        $userId = new Ulid();
        $authorityId = new Ulid();

        $user->method('getId')->willReturn($userId);
        $authority->method('getId')->willReturn($authorityId);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('pv')
            ->willReturn($queryBuilder);

        // Verify the correct parameters are set
        $expectedCalls = [
            ['user_id', $userId, 'ulid'],
            ['authority_id', $authorityId, 'ulid'],
            ['entity_class', Authority::class]
        ];
        $callCount = 0;

        $queryBuilder->expects($this->exactly(3))
            ->method('setParameter')
            ->willReturnCallback(function($key, $value, $type = null) use (&$expectedCalls, &$callCount, $queryBuilder) {
                $this->assertEquals($expectedCalls[$callCount][0], $key);
                $this->assertEquals($expectedCalls[$callCount][1], $value);
                if (isset($expectedCalls[$callCount][2])) {
                    $this->assertEquals($expectedCalls[$callCount][2], $type);
                }
                $callCount++;
                return $queryBuilder;
            });

        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn([]);

        $this->helper->getPermissionViews($user, $authority);
    }

    private function mockRepositoryQuery(array $permissions): void
    {
        $permissionViews = array_map(function($permission) {
            $view = $this->createMock(PermissionsView::class);
            $view->method('getPermission')->willReturn($permission);
            return $view;
        }, $permissions);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        $this->repository->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('where')->willReturn($queryBuilder);
        $queryBuilder->method('andWhere')->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('getResult')->willReturn($permissionViews);
    }
}
