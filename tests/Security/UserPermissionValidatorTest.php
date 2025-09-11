<?php

namespace App\Tests\Security;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\Enum\Permission;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\UserPermission;
use App\Security\UserPermissionValidator;
use PHPUnit\Framework\TestCase;

class UserPermissionValidatorTest extends TestCase
{
    private UserPermissionValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UserPermissionValidator();
    }

    public function signOffPermissionProvider(): array
    {
        return [
            'valid for Authority' => [Authority::class, [], true],
            'valid for FundReturn' => [FundReturn::class, [], true],
            'invalid for Scheme' => [Scheme::class, [], false],
            'invalid for SchemeReturn' => [SchemeReturn::class, [], false],
            'valid with fund types for Authority' => [Authority::class, [Fund::CRSTS1], true],
            'invalid with fund types for FundReturn' => [FundReturn::class, [Fund::CRSTS1], false],
        ];
    }

    /**
     * @dataProvider signOffPermissionProvider
     */
    public function testSignOffPermission(string $entityClass, array $fundTypes, bool $expectedValid): void
    {
        $permission = $this->createUserPermission(Permission::SIGN_OFF, $entityClass, $fundTypes);

        $this->assertEquals($expectedValid, $this->validator->isUserPermissionValid($permission));
    }

    public function markAsReadyEditorViewerPermissionProvider(): array
    {
        $permissions = [Permission::MARK_AS_READY, Permission::EDITOR, Permission::VIEWER];
        $validEntities = [Authority::class, FundReturn::class, Scheme::class, SchemeReturn::class];

        $cases = [];

        // All permissions valid for all supported entities without fund types
        foreach ($permissions as $permission) {
            foreach ($validEntities as $entity) {
                $cases["$permission->value valid for " . basename($entity)] = [$permission, $entity, [], true];
            }
        }

        // Fund types only valid for Authority and Scheme
        foreach ($permissions as $permission) {
            $cases["$permission->value with fund types valid for Authority"] = [$permission, Authority::class, [Fund::CRSTS1], true];
            $cases["$permission->value with fund types valid for Scheme"] = [$permission, Scheme::class, [Fund::CRSTS1], true];
            $cases["$permission->value with fund types invalid for FundReturn"] = [$permission, FundReturn::class, [Fund::CRSTS1], false];
            $cases["$permission->value with fund types invalid for SchemeReturn"] = [$permission, SchemeReturn::class, [Fund::CRSTS1], false];
        }

        return $cases;
    }

    /**
     * @dataProvider markAsReadyEditorViewerPermissionProvider
     */
    public function testMarkAsReadyEditorViewerPermissions(Permission $permission, string $entityClass, array $fundTypes, bool $expectedValid): void
    {
        $userPermission = $this->createUserPermission($permission, $entityClass, $fundTypes);

        $this->assertEquals($expectedValid, $this->validator->isUserPermissionValid($userPermission));
    }

    public function schemeManagerPermissionProvider(): array
    {
        return [
            'valid for Authority without fund types' => [Authority::class, [], true],
            'invalid for Authority with fund types' => [Authority::class, [Fund::CRSTS1], false],
            'invalid for Scheme' => [Scheme::class, [], false],
            'invalid for FundReturn' => [FundReturn::class, [], false],
            'invalid for SchemeReturn' => [SchemeReturn::class, [], false],
        ];
    }

    /**
     * @dataProvider schemeManagerPermissionProvider
     */
    public function testSchemeManagerPermission(string $entityClass, array $fundTypes, bool $expectedValid): void
    {
        $permission = $this->createUserPermission(Permission::SCHEME_MANAGER, $entityClass, $fundTypes);

        $this->assertEquals($expectedValid, $this->validator->isUserPermissionValid($permission));
    }

    public function testUnsupportedPermissionReturnsFalse(): void
    {
        $permission = new UserPermission();
        // Don't set any permission - it will be null
        $permission->setEntityClass(Authority::class);

        $this->assertFalse($this->validator->isUserPermissionValid($permission));
    }

    private function createUserPermission(Permission $permission, string $entityClass, array $fundTypes = []): UserPermission
    {
        $userPermission = new UserPermission();
        $userPermission->setPermission($permission);
        $userPermission->setEntityClass($entityClass);
        $userPermission->setFundTypes($fundTypes);

        return $userPermission;
    }
}
