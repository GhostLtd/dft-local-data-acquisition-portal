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

class PermissionVoterTest extends AbstractPermissionVoterTest
{
    const array ALL_ROLES = [Role::CAN_SUBMIT, Role::CAN_COMPLETE, Role::CAN_EDIT];
    const array ALL_BUT_SUBMIT = [Role::CAN_COMPLETE, Role::CAN_EDIT];
    const array ALL_BUT_EDIT = [Role::CAN_SUBMIT, Role::CAN_COMPLETE];
    const array SUBMIT_ONLY = [Role::CAN_SUBMIT];
    const array EDIT_ONLY = [Role::CAN_EDIT];
    const array ALL_PERMISSIONS = [Permission::SUBMITTER, Permission::CHECKER, Permission::EDITOR, Permission::EDITOR];

    public function dataOwner(): \Generator
    {
        $testCases = [
            [self::ALL_ROLES, false, 'admin:1', Authority::class, 'authority:1', null], // Invalid - can't submit/complete/edit a authority
            [self::ALL_ROLES, false, 'admin:1', Authority::class, 'authority:2', null],
            [self::ALL_ROLES, false, 'admin:1', Authority::class, 'authority:3', null],
            [self::ALL_ROLES, false, 'admin:1', Authority::class, 'authority:1', 'whatever'],
            [self::ALL_ROLES, false, 'admin:1', Authority::class, 'authority:2', 'whatever'],
            [self::ALL_ROLES, false, 'admin:1', Authority::class, 'authority:3', 'whatever'],
            [self::SUBMIT_ONLY, true, 'admin:1', CrstsFundReturn::class, 'authority:1/return:1', null],
            [self::SUBMIT_ONLY, true, 'admin:1', CrstsFundReturn::class, 'authority:1/return:2', null],
            [self::SUBMIT_ONLY, true, 'admin:1', CrstsFundReturn::class, 'authority:2/return:1', null],
            [self::ALL_BUT_SUBMIT, false, 'admin:1', CrstsFundReturn::class, 'authority:1/return:1', null], // Cannot complete/edit a return (need to specify a section)
            [self::ALL_BUT_SUBMIT, false, 'admin:1', CrstsFundReturn::class, 'authority:1/return:2', null],
            [self::ALL_BUT_SUBMIT, false, 'admin:1', CrstsFundReturn::class, 'authority:2/return:1', null],
            [self::ALL_ROLES, false, 'admin:1', CrstsFundReturn::class, 'authority:3/return:1', null], // Not owned by admin:1
            [self::ALL_BUT_SUBMIT, true, 'admin:1', CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
            [self::ALL_BUT_SUBMIT, true, 'admin:1', CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
            [self::ALL_BUT_SUBMIT, true, 'admin:1', CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
            [self::ALL_BUT_SUBMIT, false, 'admin:1', CrstsFundReturn::class, 'authority:3/return:1', 'whatever'],
            [self::SUBMIT_ONLY, false, 'admin:1', CrstsFundReturn::class, 'authority:1/return:1', 'whatever'], // Cannot submit an individual section
            [self::SUBMIT_ONLY, false, 'admin:1', CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
            [self::SUBMIT_ONLY, false, 'admin:1', CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
            [self::SUBMIT_ONLY, false, 'admin:1', CrstsFundReturn::class, 'authority:3/return:1', 'whatever'],


            [self::ALL_ROLES, false, 'admin:1', CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Cannot submit project returns, and cannot ...
            [self::ALL_ROLES, false, 'admin:1', CrstsProjectReturn::class, 'authority:1/return:2/project:1', null], // ... complete/edit a project return (need to specify a section)
            [self::ALL_ROLES, false, 'admin:1', CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
            [self::ALL_ROLES, false, 'admin:1', CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
            [self::ALL_ROLES, false, 'admin:1', CrstsProjectReturn::class, 'authority:3/return:1/project:1', null],

            [self::ALL_BUT_SUBMIT, true, 'admin:1', CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
            [self::ALL_BUT_SUBMIT, true, 'admin:1', CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
            [self::ALL_BUT_SUBMIT, true, 'admin:1', CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
            [self::ALL_BUT_SUBMIT, true, 'admin:1', CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
            [self::ALL_BUT_SUBMIT, false, 'admin:1', CrstsProjectReturn::class, 'authority:3/return:1/project:1', 'whatever'], // Not owned by admin:1
            [self::SUBMIT_ONLY, false, 'admin:1', CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'], // Cannot submit project returns
            [self::SUBMIT_ONLY, false, 'admin:1', CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
            [self::SUBMIT_ONLY, false, 'admin:1', CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
            [self::SUBMIT_ONLY, false, 'admin:1', CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
            [self::SUBMIT_ONLY, false, 'admin:1', CrstsProjectReturn::class, 'authority:3/return:1/project:1', 'whatever'],
        ];

        foreach($testCases as $testCase) {
            $roles = $testCase[0];
            $otherArgs = array_slice($testCase, 1);
            foreach($roles as $role) {
                yield [$role, ...$otherArgs];
            }
        }
    }

    /**
     * @dataProvider dataOwner
     */
    public function testOwner(string $role, bool $expectedResult, string $userRef, string $subjectClass, string $subjectRef, ?string $sectionType): void
    {
        $this->performTest(...func_get_args());
    }

    public function dataPermissions(): \Generator
    {
        $userRef = 'user';

        $permissionsAndTests = [

            // ----------------------------------------------------------------------------------------------------
            //  Test cases for "no permissions" (control)
            // ----------------------------------------------------------------------------------------------------
            [
                null,
                [
                    [self::ALL_ROLES, false, Authority::class, 'authority:1', null],
                    [self::ALL_ROLES, false, Authority::class, 'authority:1', 'whatever'], // Invalid subject
                    [self::ALL_ROLES, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::ALL_ROLES, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_ROLES, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_ROLES, false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_ROLES, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_ROLES, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_ROLES, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [self::ALL_ROLES, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_ROLES, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_ROLES, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_ROLES, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_ROLES, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_ROLES, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_ROLES, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],

            // ----------------------------------------------------------------------------------------------------
            //  Test cases for SUBMITTER permission
            // ----------------------------------------------------------------------------------------------------

            // SUBMITTER permission on authority, CAN_SUBMIT role
            [
                [[Permission::SUBMITTER], Authority::class, Authority::class, 'authority:1', null, null],
                [
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', null], // Invalid subject - can't submit a authority
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', 'whatever'],
                    [[Role::CAN_SUBMIT], true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [[Role::CAN_SUBMIT], true,  CrstsFundReturn::class, 'authority:1/return:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', null], // Not authority 1
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'], // Invalid subject - can't submit an individual section
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - can't submit a project return
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // SUBMITTER permission on authority, CAN_COMPLETE / CAN_EDIT roles
            [
                [[Permission::SUBMITTER], Authority::class, Authority::class, 'authority:1', null, null],
                [
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', null], // Invalid subject - can't complete or edit a authority
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', null], // Invalid subject - can't complete or edit a (whole) fund return
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, true,  CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'], // Wrong authority
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - can't complete or edit a (whole) project return
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'], // Wrong authority
                ]
            ],
            // SUBMITTER permission on FundReturn, CAN_SUBMIT role
            [
                [[Permission::SUBMITTER], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, null],
                [
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', null], // Invalid subject - can't submit a authority
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', 'whatever'],
                    [[Role::CAN_SUBMIT], true,  CrstsFundReturn::class, 'authority:1/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', null], // Wrong return
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'], // Invalid subject - can't submit an individual section
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - can't submit a project return
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // SUBMITTER permission on FundReturn, CAN_COMPLETE / CAN_EDIT roles
            [
                [[Permission::SUBMITTER], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, null],
                [
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', null], // Invalid subject - can't complete or edit a authority
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', null], // Invalid subject - can't complete or edit a (whole) fund return
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'], // Wrong return
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - can't complete or edit a (whole) project return
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'], // Wrong return
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'], // Wrong authority
                ]
            ],
            // N.B. SUBMITTER permission is not allowed with Project or ProjectReturn entities

            // ----------------------------------------------------------------------------------------------------
            //  Test cases for CHECKER permission
            // ----------------------------------------------------------------------------------------------------

            // CHECKER permission on authority, CAN_SUBMIT role
            [
                [[Permission::CHECKER], Authority::class, Authority::class, 'authority:1', null, null],
                [
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', null],
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // CHECKER permission on authority, CAN_COMPLETE / CAN_EDIT roles
            [
                [[Permission::CHECKER], Authority::class, Authority::class, 'authority:1', null, null],
                [
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', null], // Invalid subject - can't complete or edit a authority
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', null], // Invalid subject - can't complete or edit a (whole) fund return
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, true,  CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'], // Wrong authority
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - can't complete or edit a (whole) project return
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'], // Wrong authority
                ]
            ],
            // CHECKER permission on FundReturn, CAN_SUBMIT role
            [
                [[Permission::CHECKER], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, null],
                [
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', null],
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // CHECKER permission on FundReturn, CAN_COMPLETE / CAN_EDIT roles
            [
                [[Permission::CHECKER], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, null],
                [
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', null], // Invalid subject - can't complete or edit a authority
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', null], // Invalid subject - can't complete or edit a (whole) fund return
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'], // Wrong return
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - can't complete or edit a (whole) project return
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'], // Wrong return
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'], // Wrong authority
                ]
            ],
            // CHECKER permission on FundReturn, for specific sections, CAN_SUBMIT role
            [
                [[Permission::CHECKER], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, ['section_one', 'section_two']],
                [
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', null],
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', 'section_two'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', 'section_three'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_two'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_two'],
                ]
            ],
            // CHECKER permission on FundReturn, for specific sections, CAN_COMPLETE / CAN_EDIT roles
            [
                [[Permission::CHECKER], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, ['section_one', 'section_two']],
                [
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', null], // Invalid subject - can't complete or edit a authority
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', null], // Invalid subject - can't complete or edit a (whole) fund return
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsFundReturn::class, 'authority:1/return:1', 'section_one'],
                    [self::ALL_BUT_SUBMIT, true,  CrstsFundReturn::class, 'authority:1/return:1', 'section_two'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'section_three'], // Wrong section
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'section_one'], // Wrong return
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'section_one'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - can't complete or edit a (whole) project return
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_one'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_one'], // Wrong return
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_two'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_two'], // Wrong authority
                ]
            ],
            // CHECKER permission on ProjectReturn, CAN_SUBMIT role
            [
                [[Permission::CHECKER], CrstsProjectReturn::class, ProjectReturn::class, 'authority:1/return:1/project:1', null, null],
                [
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', null],
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // CHECKER permission on ProjectReturn, CAN_COMPLETE / CAN_EDIT roles
            [
                [[Permission::CHECKER], CrstsProjectReturn::class, ProjectReturn::class, 'authority:1/return:1/project:1', null, null],
                [
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', null],
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - Can't CHECK / EDIT a ProjectReturn as a whole (only sections thereof)
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // CHECKER permission on ProjectReturn for specific sections, CAN_SUBMIT role
            [
                [[Permission::CHECKER], CrstsProjectReturn::class, ProjectReturn::class, 'authority:1/return:1/project:1', null, ['section_one', 'section_two']],
                [
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', null],
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - Can't CHECK / EDIT a ProjectReturn as a whole (only sections thereof)
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_one'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_two'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_two'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_two'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_two'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'], // Section not mentioned in permission
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // CHECKER permission on ProjectReturn for specific sections, CAN_COMPLETE / CAN_EDIT roles
            [
                [[Permission::CHECKER], CrstsProjectReturn::class, ProjectReturn::class, 'authority:1/return:1/project:1', null, ['section_one', 'section_two']],
                [
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', null],
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', 'section_one'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'section_one'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - Can't CHECK / EDIT a ProjectReturn as a whole (only sections thereof)
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_one'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_one'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_one'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_one'],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_two'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_two'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_two'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_two'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'], // Section not mentioned in permission
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // CHECKER permission on Project, CAN_SUBMIT role
            [
                [[Permission::CHECKER], Project::class, Project::class, 'authority:1/project:1', null, null],
                [
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', null],
                    [[Role::CAN_SUBMIT], false, Authority::class, 'authority:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [[Role::CAN_SUBMIT], false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // CHECKER permission on Project, CAN_COMPLETE / CAN_EDIT roles
            [
                [[Permission::CHECKER], Project::class, Project::class, 'authority:1/project:1', null, null],
                [
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', null],
                    [self::ALL_BUT_SUBMIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - Can't CHECK / EDIT a ProjectReturn as a whole (only sections thereof)
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, true,  CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_SUBMIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],

            // ----------------------------------------------------------------------------------------------------
            //  Test cases for CHECKER permission
            // ----------------------------------------------------------------------------------------------------

            // EDITOR permission on authority, CAN_COMPLETE / CAN_SUBMIT roles
            [
                [[Permission::EDITOR], Authority::class, Authority::class, 'authority:1', null, null],
                [
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', null],
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // EDITOR permission on authority, CAN_EDIT role
            [
                [[Permission::EDITOR], Authority::class, Authority::class, 'authority:1', null, null],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', null], // Invalid subject - can't edit a authority
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1', null], // Invalid subject - can't edit a (whole) fund return
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::EDIT_ONLY, true,  CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::EDIT_ONLY, true,  CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'], // Wrong authority
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - can't edit a (whole) project return
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::EDIT_ONLY, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::EDIT_ONLY, true,  CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::EDIT_ONLY, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'], // Wrong authority
                ]
            ],
            // EDITOR permission on FundReturn, CAN_COMPLETE / CAN_SUBMIT roles
            [
                [[Permission::EDITOR], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, null],
                [
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', null],
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // EDITOR permission on FundReturn, CAN_EDIT role
            [
                [[Permission::EDITOR], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, null],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', null], // Invalid subject - can't edit a authority
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1', null], // Invalid subject - can't edit a (whole) fund return
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::EDIT_ONLY, true,  CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'], // Wrong return
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - can't edit a (whole) project return
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::EDIT_ONLY, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'], // Wrong return
                    [self::EDIT_ONLY, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'], // Wrong authority
                ]
            ],
            // EDITOR permission on FundReturn, for specific sections, CAN_COMPLETE / CAN_SUBMIT roles
            [
                [[Permission::EDITOR], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, ['section_one', 'section_two']],
                [
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', null],
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'section_two'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'section_three'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_two'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_two'],
                ]
            ],
            // EDITOR permission on FundReturn, for specific sections, CAN_EDIT roles
            [
                [[Permission::EDITOR], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1', null, ['section_one', 'section_two']],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', null], // Invalid subject - can't edit a authority
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1', null], // Invalid subject - can't edit a (whole) fund return
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::EDIT_ONLY, true,  CrstsFundReturn::class, 'authority:1/return:1', 'section_one'],
                    [self::EDIT_ONLY, true,  CrstsFundReturn::class, 'authority:1/return:1', 'section_two'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1', 'section_three'], // Wrong section
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', 'section_one'], // Wrong return
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', 'section_one'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - can't edit a (whole) project return
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_one'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_one'], // Wrong return
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_two'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_two'], // Wrong authority
                ]
            ],
            // EDITOR permission on ProjectReturn, CAN_COMPLETE / CAN_SUBMIT roles
            [
                [[Permission::EDITOR], CrstsProjectReturn::class, ProjectReturn::class, 'authority:1/return:1/project:1', null, null],
                [
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', null],
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // EDITOR permission on ProjectReturn, CAN_EDIT role
            [
                [[Permission::EDITOR], CrstsProjectReturn::class, ProjectReturn::class, 'authority:1/return:1/project:1', null, null],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', null],
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - Can't CHECK / EDIT a ProjectReturn as a whole (only sections thereof)
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::EDIT_ONLY, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // EDITOR permission on ProjectReturn for specific sections, CAN_COMPLETE / CAN_SUBMIT roles
            [
                [[Permission::EDITOR], CrstsProjectReturn::class, ProjectReturn::class, 'authority:1/return:1/project:1', null, ['section_one', 'section_two']],
                [
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', null],
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - Can't CHECK / EDIT a ProjectReturn as a whole (only sections thereof)
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_one'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_two'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_two'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_two'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_two'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'], // Section not mentioned in permission
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // EDITOR permission on ProjectReturn for specific sections, CAN_EDIT role
            [
                [[Permission::EDITOR], CrstsProjectReturn::class, ProjectReturn::class, 'authority:1/return:1/project:1', null, ['section_one', 'section_two']],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', null],
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', 'section_one'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1', 'section_one'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - Can't CHECK / EDIT a ProjectReturn as a whole (only sections thereof)
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::EDIT_ONLY, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_one'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_one'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_one'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_one'],
                    [self::EDIT_ONLY, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'section_two'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'section_two'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'section_two'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'section_two'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'], // Section not mentioned in permission
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // EDITOR permission on Project, CAN_COMPLETE / CAN_SUBMIT roles
            [
                [[Permission::EDITOR], Project::class, Project::class, 'authority:1/project:1', null, null],
                [
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', null],
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::ALL_BUT_EDIT, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
            // EDITOR permission on Project, CAN_EDIT role
            [
                [[Permission::EDITOR], Project::class, Project::class, 'authority:1/project:1', null, null],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', null],
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', null],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:1', null], // Invalid subject - Can't CHECK / EDIT a ProjectReturn as a whole (only sections thereof)
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:2/project:1', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', null],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', null],
                    [self::EDIT_ONLY, true,  CrstsProjectReturn::class, 'authority:1/return:1/project:1', 'whatever'],
                    [self::EDIT_ONLY, true,  CrstsProjectReturn::class, 'authority:1/return:2/project:1', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:1/return:1/project:2', 'whatever'],
                    [self::EDIT_ONLY, false, CrstsProjectReturn::class, 'authority:2/return:1/project:1', 'whatever'],
                ]
            ],
        ];

        foreach($permissionsAndTests as [$permissionSet, $tests]) {
            foreach($tests as $test) {
                $attributes = $test[0];
                foreach($attributes as $attribute) {
                    $otherArgs = [$test[1], $userRef, ...array_slice($test, 2)];

                    if ($permissionSet === null) {
                        yield array_merge([$attribute, null, null, null, null, null, null], $otherArgs);
                    } else {
                        [$permissions, $entityRefClass, $entityClass, $entityId, $fundTypes, $sectionTypes] = $permissionSet;

                        foreach($permissions as $permission) {
                            yield array_merge([$attribute, $permission, $entityRefClass, $entityClass, $entityId, $fundTypes, $sectionTypes], $otherArgs);
                        }
                    }
                }
            }
        }
    }

    /**
     * @dataProvider dataPermissions
     */
    public function testPermissionsForView(
        string      $attribute,

        ?Permission $permission,
        ?string     $permissionEntityReferenceClass,
        ?string     $permissionEntityClass,
        ?string     $permissionEntityId,
        ?array      $permissionFundTypes,
        ?array      $permissionSectionTypes,

        bool        $expectedResult,
        string      $userRef,

        string      $subjectClass,
        string      $subjectRef,
        ?string     $subjectSectionType
    ): void
    {
        $this->createPermissionAndPerformTest(...func_get_args());
    }
}
