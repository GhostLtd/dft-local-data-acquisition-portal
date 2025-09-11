<?php

namespace App\Tests\Utility;

use App\Entity\Authority;
use App\Entity\Enum\Permission;
use App\Entity\PermissionsView;
use App\Entity\Scheme;
use App\Entity\User;
use App\Entity\UserPermission;
use App\Utility\DoctrineUlidHelper;
use App\Utility\UserReachableEntityResolver;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

class UserReachableEntityResolverTest extends TestCase
{
    private UserReachableEntityResolver $resolver;
    private EntityManagerInterface&MockObject $entityManager;

    protected function setUp(): void
    {
        $doctrineUlidHelper = $this->createMock(DoctrineUlidHelper::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->resolver = new UserReachableEntityResolver($doctrineUlidHelper, $this->entityManager);
    }

    public function testIsAuthorityReachableByReturnsTrueForAuthorityAdmin(): void
    {
        $authorityId = new Ulid();
        $authority = (new Authority())->setId($authorityId);
        $adminAuthority = (new Authority())->setId($authorityId);
        
        $user = (new User())->setId(new Ulid());
        $user->addAuthorityAdminOf($adminAuthority);
        
        $this->mockEmptyPermissionsView();
        
        $this->assertTrue($this->resolver->isAuthorityReachableBy($authority, $user));
    }

    public function testIsAuthorityReachableByReturnsFalseForDifferentAuthority(): void
    {
        $authorityId = new Ulid();
        $differentId = new Ulid();
        
        $authority = (new Authority())->setId($authorityId);
        $adminAuthority = (new Authority())->setId($differentId);
        
        $user = (new User())->setId(new Ulid());
        $user->addAuthorityAdminOf($adminAuthority);
        
        $this->mockEmptyPermissionsView();
        
        $this->assertFalse($this->resolver->isAuthorityReachableBy($authority, $user));
    }

    public function testReachableIdsWithDirectAuthorityPermission(): void
    {
        $authorityId = new Ulid();
        
        $permission = (new UserPermission())
            ->setEntityClass(Authority::class)
            ->setEntityId($authorityId);
        
        $user = (new User())->setId(new Ulid());
        $user->getPermissions()->add($permission);
        
        $this->mockEmptyPermissionsView();
        
        $result = $this->resolver->getReachableIdsInferredFromUserPermissions($user, Authority::class);
        
        $this->assertCount(1, $result);
        $this->assertEquals($authorityId, $result[0]);
    }

    public function interestingPermissionsProvider(): array
    {
        return [
            'SIGN_OFF permission' => [Permission::SIGN_OFF, true],
            'MARK_AS_READY permission' => [Permission::MARK_AS_READY, true],
            'EDITOR permission' => [Permission::EDITOR, true],
            'VIEWER permission' => [Permission::VIEWER, true],
            'SCHEME_MANAGER permission (not interesting)' => [Permission::SCHEME_MANAGER, false],
        ];
    }

    /**
     * @dataProvider interestingPermissionsProvider
     */
    public function testReachableIdsWithIndirectPermissions(Permission $permission, bool $shouldBeProcessed): void
    {
        $authorityId = new Ulid();
        $schemeId = new Ulid();
        
        $schemePermission = (new UserPermission())
            ->setPermission($permission)
            ->setEntityClass(Scheme::class)
            ->setEntityId($schemeId);
            
        $user = (new User())->setId(new Ulid());
        $user->getPermissions()->add($schemePermission);
        
        $permissionsView = $this->createMock(PermissionsView::class);
        $permissionsView->method('getAuthorityId')->willReturn($authorityId);
        
        $partialResolver = $this->createPartialMock(UserReachableEntityResolver::class, ['getPermissionsViewsFor']);
        $partialResolver->method('getPermissionsViewsFor')->willReturn($shouldBeProcessed ? [$permissionsView] : []);
        
        $reflection = new \ReflectionClass($partialResolver);
        $property = $reflection->getProperty('entityManager');
        $property->setValue($partialResolver, $this->entityManager);
        
        $result = $partialResolver->getReachableIdsInferredFromUserPermissions($user, Authority::class);
        
        if ($shouldBeProcessed) {
            $this->assertCount(1, $result);
            $this->assertEquals($authorityId, $result[0]);
        } else {
            $this->assertEmpty($result);
        }
    }

    public function testCachingBehaviorReturnsSameResult(): void
    {
        $user = (new User())->setId(new Ulid());
        
        $this->mockEmptyPermissionsView();
        
        $result1 = $this->resolver->getReachableIdsInferredFromUserPermissions($user, Authority::class);
        $result2 = $this->resolver->getReachableIdsInferredFromUserPermissions($user, Authority::class);
        
        $this->assertSame($result1, $result2);
    }

    private function mockEmptyPermissionsView(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);
        
        $repository->method('createQueryBuilder')->willReturn($queryBuilder);
        $queryBuilder->method('orWhere')->willReturn($queryBuilder);
        $queryBuilder->method('getQuery')->willReturn($query);
        $query->method('execute')->willReturn([]);
        
        $this->entityManager
            ->method('getRepository')
            ->with(PermissionsView::class)
            ->willReturn($repository);
    }
}