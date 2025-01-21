<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Permission;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Project;
use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Entity\Authority;

class ViewPermissionVoterTest extends AbstractPermissionVoterTest
{
    public function dataOwner(): array
    {
        return [
            [true,  'admin:1', Authority::class, 'authority:1', null],
            [true,  'admin:1', Authority::class, 'authority:2', null],
            [false, 'admin:1', Authority::class, 'authority:3', null],

            // N.B. [<Authority>, 'whatever'] is not a valid subject, as an authority doesn't have sections
            [false, 'admin:1', Authority::class, 'authority:1', 'whatever'],
            [false, 'admin:1', Authority::class, 'authority:2', 'whatever'],
            [false, 'admin:1', Authority::class, 'authority:3', 'whatever'],

            [true,  'admin:1', CrstsFundReturn::class, 'authority:1/return:1', null],
            [true,  'admin:1', CrstsFundReturn::class, 'authority:1/return:2', null],
            [true,  'admin:1', CrstsFundReturn::class, 'authority:2/return:1', null],
            [false, 'admin:1', CrstsFundReturn::class, 'authority:3/return:1', null],

            [true,  'admin:1', CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
            [true,  'admin:1', CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
            [true,  'admin:1', CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
            [false, 'admin:1', CrstsFundReturn::class, 'authority:3/return:1', 'whatever'],

            [true,  'admin:1', CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
            [true,  'admin:1', CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
            [true,  'admin:1', CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
            [true,  'admin:1', CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
            [false, 'admin:1', CrstsProjectReturn::class, 'authority:3/return:1/project:1', null],

            [true,  'admin:1', CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
            [true,  'admin:1', CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
            [true,  'admin:1', CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
            [true,  'admin:1', CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
            [false, 'admin:1', CrstsProjectReturn::class, 'authority:3/return:1/project:1', 'whatever'],
        ];
    }

    /**
     * @dataProvider dataOwner
     */
    public function testOwner(bool $expectedResult, string $userRef, string $subjectClass, string $subjectRef, ?string $sectionType): void
    {
        $this->performTest(Role::CAN_VIEW, ...func_get_args());
    }

    public function dataPermissionsForView(): \Generator
    {
        $userRef = 'user';

        $allRelevantPermissions = [Permission::SUBMITTER, Permission::CHECKER, Permission::EDITOR, Permission::EDITOR];
        $allRelevantExceptSubmitter = array_filter($allRelevantPermissions, fn(Permission $p) => $p !== Permission::SUBMITTER);

        $permissionsAndTests = [
            // Control - no permissions, can't access
            [
                null,
                [
                    [false, Authority::class, 'authority:1', null],
                    [false, Authority::class, 'authority:1', 'whatever'], // Invalid subject
                    [false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // Any (relevant) permissions on the recipient
            [
                [$allRelevantPermissions, Authority::class, Authority::class, 'authority:1', null, null],
                [
                    [true,  Authority::class, 'authority:1', null],
                    [false, Authority::class, 'authority:1', 'whatever'], // Invalid subject
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [true,  CrstsFundReturn::class, 'authority:1/return:2', null],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null], // Not recipient 1
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [true,  CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // Any (relevant) permissions on the fund return
            [
                [$allRelevantPermissions, CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, null],
                [
                    [true,  Authority::class, 'authority:1', null],
                    [false, Authority::class, 'authority:1', 'whatever'], // Invalid subject
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:2', null], // Not the right return
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // All relevant permissions (excl. SUBMITTER) on the project return
            [
                [$allRelevantExceptSubmitter, CrstsProjectReturn::class, ProjectReturn::class, 'authority:1/return:1/project:1', null, null],
                [
                    [true,  Authority::class, 'authority:1', null],
                    [false, Authority::class, 'authority:1', 'whatever'], // Invalid subject
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:2', null], // Not the right return
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'], // Access to a ProjectReturn doesn't grant access to FundReturn sections
                    [false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null], // Wrong project return
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // All relevant permissions (excl. SUBMITTER) on the project
            [
                [$allRelevantExceptSubmitter, Project::class, Project::class, 'authority:1/project:1', null, null],
                [
                    [true,  Authority::class, 'authority:1', null],
                    [false, Authority::class, 'authority:1', 'whatever'], // Invalid subject
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [true,  CrstsFundReturn::class, 'authority:1/return:2', null],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'], // Having access to a project shouldn't allow access to sections
                    [false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null], // Wrong project
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // All relevant permissions except SUBMITTER on the fund return, with sectionTypes specified
            [
                [$allRelevantExceptSubmitter, CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, ['section_one', 'section_two']],
                [
                    [true,  Authority::class, 'authority:1', null],
                    [false, Authority::class, 'authority:1', 'whatever'], // Invalid subject
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:2', null], // Not the right return
                    [false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'], // Not a section specified by the permission
                    [false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [true,  CrstsFundReturn::class, 'authority:1/return:1', 'section_two'],
                    [false, CrstsFundReturn::class, 'authority:1/return:2', 'section_two'], // Not the right return
                    [false, CrstsFundReturn::class, 'authority:2/return:1', 'section_two'],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'], // Permission targets a FundReturn, not a ProjectReturn
                    [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_one'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_one'],
                    [false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_two'],
                    [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_two'],
                ],
                // All relevant permissions except SUBMITTER on the project return, with sectionTypes specified
                [
                    [$allRelevantExceptSubmitter, CrstsProjectReturn::class, ProjectReturn::class, 'authority:1/return:1/project:1', null, ['section_one', 'section_two']],
                    [
                        [true,  Authority::class, 'authority:1', null],
                        [false, Authority::class, 'authority:1', 'whatever'], // Invalid subject
                        [true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                        [false, CrstsFundReturn::class, 'authority:1/return:2', null], // Not the right return
                        [false, CrstsFundReturn::class, 'authority:2/return:1', null],
                        [false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'], // Permission targets a ProjectReturn, not a FundReturn
                        [false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                        [false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                        [false, CrstsFundReturn::class, 'authority:1/return:1', 'section_two'],
                        [false, CrstsFundReturn::class, 'authority:1/return:2', 'section_two'],
                        [false, CrstsFundReturn::class, 'authority:2/return:1', 'section_two'],
                        [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                        [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null], // Not the specified project return
                        [false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                        [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                        [false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'], // Not a section specified by the permission
                        [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                        [false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                        [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                        [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_one'],
                        [false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_one'],
                        [true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_two'],
                        [false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_two'],
                    ]
                ],
            ],
        ];

        foreach($permissionsAndTests as [$permissionSet, $tests]) {
            foreach($tests as $test) {
                $test = [$test[0], $userRef, ...array_slice($test, 1)];

                if ($permissionSet === null) {
                    yield array_merge([null, null, null, null, null, null], $test);
                } else {
                    [$permissions, $entityRefClass, $entityClass, $entityId, $fundTypes, $sectionTypes] = $permissionSet;

                    foreach($permissions as $permission) {
                        yield array_merge([$permission, $entityRefClass, $entityClass, $entityId, $fundTypes, $sectionTypes], $test);
                    }
                }
            }
        }
    }

    /**
     * @dataProvider dataPermissionsForView
     */
    public function testPermissionsForView(?Permission $permission, ?string $permissionEntityReferenceClass, ?string $permissionEntityClass, ?string $permissionEntityId, ?array $fundTypes, ?array $sectionTypes, bool $expectedResult, string $userRef, string $subjectClass, string $subjectRef, ?string $sectionType
    ): void
    {
        $this->createPermissionAndPerformTest(Role::CAN_VIEW, ...func_get_args());
    }
}
