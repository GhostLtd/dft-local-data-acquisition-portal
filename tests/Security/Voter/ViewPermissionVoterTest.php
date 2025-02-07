<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Permission;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\Authority;
use App\Security\Voter\ViewPermissionVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ViewPermissionVoterTest extends AbstractPermissionVoterTest
{
    protected VoterInterface $viewPermissionVoter;

    public function setUp(): void
    {
        parent::setUp();
        $this->viewPermissionVoter = $this->getFromContainer(ViewPermissionVoter::class, ViewPermissionVoter::class);
    }

    public function dataAdmin(): array
    {
        return [
            [true,  'admin:1', Authority::class, 'authority:1'],
            [true,  'admin:1', Authority::class, 'authority:2'],
            [false, 'admin:1', Authority::class, 'authority:3'], // Not owned by admin:1

            [true,  'admin:1', CrstsFundReturn::class, 'authority:1/return:1'],
            [true,  'admin:1', CrstsFundReturn::class, 'authority:1/return:2'],
            [true,  'admin:1', CrstsFundReturn::class, 'authority:2/return:1'],
            [false, 'admin:1', CrstsFundReturn::class, 'authority:3/return:1'], // Not owned by admin:1

            [true,  'admin:1', CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
            [true,  'admin:1', CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
            [true,  'admin:1', CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
            [true,  'admin:1', CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
            [false, 'admin:1', CrstsSchemeReturn::class, 'authority:3/return:1/project:1'], // Not owned by admin:1
        ];
    }

    /**
     * @dataProvider dataAdmin
     */
    public function testAdmin(bool $expectedResult, string $userRef, string $subjectClass, string $subjectRef): void
    {
        $this->performTestOnSpecificVoter($this->viewPermissionVoter, InternalRole::HAS_VALID_VIEW_PERMISSION, ...func_get_args());
    }

    public function dataPermissionsForView(): \Generator
    {
        $userRef = 'user';

        $allRelevantPermissions = [Permission::SIGN_OFF, Permission::MARK_AS_READY, Permission::EDITOR, Permission::VIEWER];
        $allRelevantExceptSubmitter = array_filter($allRelevantPermissions, fn(Permission $p) => $p !== Permission::SIGN_OFF);

        $permissionsAndTests = [
            // Control - no permissions, can't access
            [
                null,
                [
                    [false, Authority::class, 'authority:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1', null],
                    [false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1', null],
                    [false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2', null],
                    [false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1', null],
                ]
            ],
            // Any (relevant) permissions on the authority
            [
                [$allRelevantPermissions, Authority::class, Authority::class, 'authority:1', null, null],
                [
                    [true,  Authority::class, 'authority:1', null],
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [true,  CrstsFundReturn::class, 'authority:1/return:2', null],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null], // Not authority 1
                    [true,  CrstsSchemeReturn::class, 'authority:1/return:1/project:1', null],
                    [true,  CrstsSchemeReturn::class, 'authority:1/return:2/project:1', null],
                    [true,  CrstsSchemeReturn::class, 'authority:1/return:1/project:2', null],
                    [false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1', null],
                ]
            ],
            // Any (relevant) permissions on the fund return
            [
                [$allRelevantPermissions, CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, null],
                [
                    [true,  Authority::class, 'authority:1', null],
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:2', null], // Not return:1
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null], // Not authority:1
                    [true,  CrstsSchemeReturn::class, 'authority:1/return:1/project:1', null],
                    [false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1', null], // Not return:1
                    [true,  CrstsSchemeReturn::class, 'authority:1/return:1/project:2', null],
                    [false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1', null], // Not authority:1
                ]
            ],
            // All relevant permissions (excl. SUBMITTER) on the project return
            [
                [$allRelevantExceptSubmitter, CrstsSchemeReturn::class, SchemeReturn::class, 'authority:1/return:1/project:1', null, null],
                [
                    [true,  Authority::class, 'authority:1', null],
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:2', null], // Not return:1
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null], // Not authority:1
                    [true,  CrstsSchemeReturn::class, 'authority:1/return:1/project:1', null],
                    [false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1', null], // Not return:1
                    [false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2', null], // Not project:1
                    [false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1', null], // Not authority:1
                ]
            ],
            // All relevant permissions (excl. SUBMITTER) on the project
            [
                [$allRelevantExceptSubmitter, Scheme::class, Scheme::class, 'authority:1/project:1', null, null],
                [
                    [true,  Authority::class, 'authority:1', null],
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [true,  CrstsFundReturn::class, 'authority:1/return:2', null],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null], // Not authority:1
                    [true,  CrstsSchemeReturn::class, 'authority:1/return:1/project:1', null],
                    [true,  CrstsSchemeReturn::class, 'authority:1/return:2/project:1', null],
                    [false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2', null], // Not project:1
                    [false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1', null], // Not authority:1
                ]
            ],
        ];

        foreach($permissionsAndTests as [$permissionSet, $tests]) {
            foreach($tests as $test) {
                $test = [$test[0], $userRef, ...array_slice($test, 1)];

                if ($permissionSet === null) {
                    yield array_merge([null, null, null, null, null], $test);
                } else {
                    [$permissions, $entityRefClass, $entityClass, $entityId, $fundTypes] = $permissionSet;

                    foreach($permissions as $permission) {
                        yield array_merge([$permission, $entityRefClass, $entityClass, $entityId, $fundTypes], $test);
                    }
                }
            }
        }
    }

    /**
     * @dataProvider dataPermissionsForView
     */
    public function testPermissionsForView(?Permission $permission, ?string $permissionEntityReferenceClass, ?string $permissionEntityClass, ?string $permissionEntityId, ?array $fundTypes, bool $expectedResult, string $userRef, string $subjectClass, string $subjectRef): void
    {
        $this->createPermissionAndPerformTestOnSpecificVoter($this->viewPermissionVoter, InternalRole::HAS_VALID_VIEW_PERMISSION, ...func_get_args());
    }
}
