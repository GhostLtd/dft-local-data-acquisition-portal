<?php

namespace App\Tests\Security\Voter;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Permission;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Security\Voter\Internal\PermissionVoter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionVoterTest extends AbstractPermissionVoterTest
{
    const array ALL_ROLES = [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, InternalRole::HAS_VALID_EDIT_PERMISSION];
    const array ALL_BUT_SIGN_OFF = [InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, InternalRole::HAS_VALID_EDIT_PERMISSION];
    const array ALL_BUT_MARK_AS_READY = [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, InternalRole::HAS_VALID_EDIT_PERMISSION];
    const array ALL_BUT_EDIT = [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION];
    const array SIGN_OFF_ONLY = [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION];
    const array MANAGE_SCHEMES_ONLY = [InternalRole::HAS_VALID_MANAGE_SCHEME_PERMISSION];
    const array MARK_AS_READY_ONLY = [InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION];
    const array EDIT_ONLY = [InternalRole::HAS_VALID_EDIT_PERMISSION];
    const array SCHEME_EDIT_ONLY = [InternalRole::HAS_VALID_MANAGE_SCHEME_PERMISSION];
    const array ALL_PERMISSIONS = [Permission::SCHEME_MANAGER, Permission::SIGN_OFF, Permission::MARK_AS_READY, Permission::EDITOR, Permission::EDITOR];

    protected VoterInterface $permissionVoter;

    public function setUp(): void
    {
        parent::setUp();
        $this->permissionVoter = $this->getFromContainer(PermissionVoter::class, PermissionVoter::class);
    }

    public function dataAdmin(): \Generator
    {
        $testCases = [
            [self::ALL_ROLES, false, 'admin:1', Authority::class, 'authority:1'], // Invalid - can't sign_off/mark_as_ready/edit an authority
            [self::ALL_ROLES, false, 'admin:1', Authority::class, 'authority:2'],
            [self::ALL_ROLES, false, 'admin:1', Authority::class, 'authority:3'],

            [self::ALL_BUT_MARK_AS_READY, true, 'admin:1', CrstsFundReturn::class, 'authority:1/return:1'],
            [self::ALL_BUT_MARK_AS_READY, true, 'admin:1', CrstsFundReturn::class, 'authority:1/return:2'],
            [self::ALL_BUT_MARK_AS_READY, true, 'admin:1', CrstsFundReturn::class, 'authority:2/return:1'],
            [self::ALL_BUT_MARK_AS_READY, false, 'admin:1', CrstsFundReturn::class, 'authority:3/return:1'], // Not owned by admin:1
            [self::MARK_AS_READY_ONLY, false, 'admin:1', CrstsFundReturn::class, 'authority:1/return:1'], // Cannot mark_as_ready on a fund return
            [self::MARK_AS_READY_ONLY, false, 'admin:1', CrstsFundReturn::class, 'authority:1/return:2'], // (Only valid for a scheme return)
            [self::MARK_AS_READY_ONLY, false, 'admin:1', CrstsFundReturn::class, 'authority:2/return:1'],
            [self::MARK_AS_READY_ONLY, false, 'admin:1', CrstsFundReturn::class, 'authority:3/return:1'],

            [self::ALL_BUT_SIGN_OFF, true, 'admin:1', CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
            [self::ALL_BUT_SIGN_OFF, true, 'admin:1', CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
            [self::ALL_BUT_SIGN_OFF, true, 'admin:1', CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
            [self::ALL_BUT_SIGN_OFF, true, 'admin:1', CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
            [self::ALL_BUT_SIGN_OFF, false, 'admin:1', CrstsSchemeReturn::class, 'authority:3/return:1/project:1'], // Not owned by admin:1
            [self::SIGN_OFF_ONLY, false, 'admin:1', CrstsSchemeReturn::class, 'authority:1/return:1/project:1'], // Cannot sign_off a scheme return
            [self::SIGN_OFF_ONLY, false, 'admin:1', CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
            [self::SIGN_OFF_ONLY, false, 'admin:1', CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
            [self::SIGN_OFF_ONLY, false, 'admin:1', CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
            [self::SIGN_OFF_ONLY, false, 'admin:1', CrstsSchemeReturn::class, 'authority:3/return:1/project:1'],

            [self::MANAGE_SCHEMES_ONLY, true, 'admin:1', Authority::class, 'authority:1'],
            [self::MANAGE_SCHEMES_ONLY, true, 'admin:1', Authority::class, 'authority:2'],
            [self::MANAGE_SCHEMES_ONLY, false, 'admin:1', Authority::class, 'authority:3'],
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
     * @dataProvider dataAdmin
     */
    public function testAdmin(string $role, ?bool $expectedResult, string $userRef, string $subjectClass, string $subjectRef): void
    {
        $this->performTestOnSpecificVoter($this->permissionVoter, ...func_get_args());
    }

    public function getPermissionsAndTests(): array
    {
        return [
            // ----------------------------------------------------------------------------------------------------
            //  Test cases for "no permissions" (control)
            // ----------------------------------------------------------------------------------------------------
            [
                null,
                [
                    [self::ALL_ROLES, false, Authority::class, 'authority:1'],
                    [self::ALL_ROLES, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::ALL_ROLES, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::ALL_ROLES, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::ALL_ROLES, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::ALL_ROLES, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::ALL_ROLES, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::ALL_ROLES, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],

                    [self::MANAGE_SCHEMES_ONLY, false, Authority::class, 'authority:1'],
                ]
            ],

            // ----------------------------------------------------------------------------------------------------
            //  Test cases for SIGN_OFF permission
            // ----------------------------------------------------------------------------------------------------

            // SIGN_OFF permission on authority, HAS_VALID_SIGN_OFF_PERMISSION role
            [
                [[Permission::SIGN_OFF], Authority::class, Authority::class, 'authority:1'],
                [
                    [self::SIGN_OFF_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't sign_off an authority
                    [self::SIGN_OFF_ONLY, true, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::SIGN_OFF_ONLY, true, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'], // Not authority 1
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'], // Invalid subject - can't sign_off a scheme return
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
                ]
            ],
            // SIGN_OFF permission on authority, HAS_VALID_SIGN_OFF_PERMISSION role
            [
                [[Permission::SIGN_OFF], Authority::class, Authority::class, 'authority:1'],
                [
                    [self::MARK_AS_READY_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't sign_off an authority
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'], // Invalid subject - can't mark_as_ready a fund_return
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'], // Not authority 1
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority 1
                ]
            ],
            // SIGN_OFF permission on authority, HAS_VALID_EDIT_PERMISSION roles
            [
                [[Permission::SIGN_OFF], Authority::class, Authority::class, 'authority:1'],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't edit an authority
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'], // Not authority 1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority 1
                ]
            ],


            // SIGN_OFF permission on FundReturn, HAS_VALID_SIGN_OFF_PERMISSION role
            [
                [[Permission::SIGN_OFF], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1'],
                [
                    [self::SIGN_OFF_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't sign_off an authority
                    [self::SIGN_OFF_ONLY, true, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'], // Not return 1
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'], // Not authority 1
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'], // Invalid subject - can't sign_off a scheme return
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
                ]
            ],
            // SIGN_OFF permission on FundReturn, HAS_VALID_SIGN_OFF_PERMISSION role
            [
                [[Permission::SIGN_OFF], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1'],
                [
                    [self::MARK_AS_READY_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't sign_off an authority
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'], // Invalid subject - can't mark_as_ready a fund_return
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'], // Not return 1
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority 1
                ]
            ],
            // SIGN_OFF permission on FundReturn, HAS_VALID_EDIT_PERMISSION roles
            [
                [[Permission::SIGN_OFF], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1'],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't edit an authority
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'], // Not return 1
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'], // Not authority 1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'], // Not return 1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority 1
                ]
            ],

            // N.B. SUBMITTER permission is not allowed with Scheme or SchemeReturn entities

            // ----------------------------------------------------------------------------------------------------
            //  Test cases for MARK_AS_READY permission
            // ----------------------------------------------------------------------------------------------------

            // MARK_AS_READY permission on authority, CAN_SUBMIT role
            [
                [[Permission::MARK_AS_READY], Authority::class, Authority::class, 'authority:1'],
                [
                    [self::SIGN_OFF_ONLY, false, Authority::class, 'authority:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
                ]
            ],
            // MARK_AS_READY permission on authority, HAS_VALID_MARK_AS_READY_PERMISSION role
            [
                [[Permission::MARK_AS_READY], Authority::class, Authority::class, 'authority:1'],
                [
                    [self::MARK_AS_READY_ONLY, false, Authority::class, 'authority:1'], // Invalid subject
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'], // Invalid subject
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::MARK_AS_READY_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::MARK_AS_READY_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::MARK_AS_READY_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],
            // MARK_AS_READY permission on authority, HAS_VALID_EDIT_PERMISSION role
            [
                [[Permission::MARK_AS_READY], Authority::class, Authority::class, 'authority:1'],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1'], // Invalid subject
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'], // Not authority:1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],


            // MARK_AS_READY permission on FundReturn, HAS_VALID_SIGN_OFF_PERMISSION role
            [
                [[Permission::MARK_AS_READY], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1'],
                [
                    [self::SIGN_OFF_ONLY, false, Authority::class, 'authority:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
                ]
            ],
            // MARK_AS_READY permission on FundReturn, HAS_VALID_MARK_AS_READY_PERMISSION roles
            [
                [[Permission::MARK_AS_READY], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1'],
                [
                    [self::MARK_AS_READY_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't mark_as_ready an authority
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'], // Invalid subject - can't mark_as_ready a fund return
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::MARK_AS_READY_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'], // Not return:1
                    [self::MARK_AS_READY_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],
            // MARK_AS_READY permission on FundReturn, HAS_VALID_EDIT_PERMISSION roles
            [
                [[Permission::MARK_AS_READY], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1'],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't edit an authority
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'], // Not return:1
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'], // Not authority:1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'], // Not return:1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],


            // MARK_AS_READY permission on SchemeReturn, HAS_VALID_SIGN_OFF_PERMISSION role
            [
                [[Permission::MARK_AS_READY], CrstsSchemeReturn::class, SchemeReturn::class, 'authority:1/return:1/project:1'],
                [
                    [[InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], false, Authority::class, 'authority:1'],
                    [[InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [[InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [[InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [[InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [[InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [[InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [[InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
                ]
            ],
            // MARK_AS_READY permission on SchemeReturn, HAS_VALID_MARK_AS_READY_PERMISSION roles
            [
                [[Permission::MARK_AS_READY], CrstsSchemeReturn::class, SchemeReturn::class, 'authority:1/return:1/project:1'],
                [
                    [self::MARK_AS_READY_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't mark_as_ready an authority
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'], // Invalid subject - can't mark_as_ready a fund return
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::MARK_AS_READY_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'], // Not return:1
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'], // Not project:1
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],
            // MARK_AS_READY permission on SchemeReturn, HAS_VALID_EDIT_PERMISSION roles
            [
                [[Permission::MARK_AS_READY], CrstsSchemeReturn::class, SchemeReturn::class, 'authority:1/return:1/project:1'],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't edit an authority
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'], // Permission targeted at scheme, doesn't allow editing at fund level
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'], // Not return:1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'], // Not project:1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],


            // MARK_AS_READY permission on Scheme, HAS_VALID_SIGN_OFF_PERMISSION role
            [
                [[Permission::MARK_AS_READY], Scheme::class, Scheme::class, 'authority:1/project:1'],
                [
                    [self::SIGN_OFF_ONLY, false, Authority::class, 'authority:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::SIGN_OFF_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::SIGN_OFF_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
                ]
            ],
            // MARK_AS_READY permission on Scheme, HAS_VALID_MARK_AS_READY_PERMISSION roles
            [
                [[Permission::MARK_AS_READY], Scheme::class, Scheme::class, 'authority:1/project:1'],
                [
                    [self::MARK_AS_READY_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't mark_as_ready an authority
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'], // Invalid subject - can't mark_as_ready a fund return
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::MARK_AS_READY_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::MARK_AS_READY_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::MARK_AS_READY_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'], // Not project:1
                    [self::MARK_AS_READY_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],
            // MARK_AS_READY permission on Scheme, HAS_VALID_EDIT_PERMISSION roles
            [
                [[Permission::MARK_AS_READY], Scheme::class, Scheme::class, 'authority:1/project:1'],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't edit an authority
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'], // Permission targeted at scheme, doesn't allow editing at fund level
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'], // Not project:1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],

            // ----------------------------------------------------------------------------------------------------
            //  Test cases for MARK_AS_READY permission
            // ----------------------------------------------------------------------------------------------------

            // EDITOR permission on authority, HAS_VALID_MARK_AS_READY_PERMISSION / HAS_VALID_SIGN_OFF_PERMISSION roles
            [
                [[Permission::EDITOR], Authority::class, Authority::class, 'authority:1'],
                [
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
                ]
            ],
            // EDITOR permission on authority, HAS_VALID_EDIT_PERMISSION role
            [
                [[Permission::EDITOR], Authority::class, Authority::class, 'authority:1'],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't edit an authority
                    [self::EDIT_ONLY, true, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::EDIT_ONLY, true, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'], // Not authority:1
                    [self::EDIT_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::EDIT_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::EDIT_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],


            // EDITOR permission on FundReturn, HAS_VALID_MARK_AS_READY_PERMISSION / HAS_VALID_SIGN_OFF_PERMISSION roles
            [
                [[Permission::EDITOR], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1'],
                [
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
                ]
            ],
            // EDITOR permission on FundReturn, HAS_VALID_EDIT_PERMISSION role
            [
                [[Permission::EDITOR], CrstsFundReturn::class, FundReturn::class, 'authority:1/return:1'],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't edit an authority
                    [self::EDIT_ONLY, true, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'], // Not return:1
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'], // Not authority:1
                    [self::EDIT_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'], // Not return:1
                    [self::EDIT_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ],
            ],


            // EDITOR permission on SchemeReturn, HAS_VALID_MARK_AS_READY_PERMISSION / HAS_VALID_SIGN_OFF_PERMISSION roles
            [
                [[Permission::EDITOR], CrstsSchemeReturn::class, SchemeReturn::class, 'authority:1/return:1/project:1'],
                [
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
                ]
            ],
            // EDITOR permission on SchemeReturn, HAS_VALID_EDIT_PERMISSION role
            [
                [[Permission::EDITOR], CrstsSchemeReturn::class, SchemeReturn::class, 'authority:1/return:1/project:1'],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't edit an authority
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'], // Permission targets scheme return, which does not confer editing of fund return
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::EDIT_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'], // Not return:1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'], // Not project:1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],


            // EDITOR permission on Scheme, HAS_VALID_MARK_AS_READY_PERMISSION / HAS_VALID_SIGN_OFF_PERMISSION roles
            [
                [[Permission::EDITOR], Scheme::class, Scheme::class, 'authority:1/project:1'],
                [
                    [self::ALL_BUT_EDIT, false, Authority::class, 'authority:1'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::ALL_BUT_EDIT, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'],
                    [self::ALL_BUT_EDIT, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'],
                ]
            ],
            // EDITOR permission on Scheme, HAS_VALID_EDIT_PERMISSION role
            [
                [[Permission::EDITOR], Scheme::class, Scheme::class, 'authority:1/project:1'],
                [
                    [self::EDIT_ONLY, false, Authority::class, 'authority:1'], // Invalid subject - can't edit an authority
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'], // Permission targets scheme, which does not confer editing of fund return
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::EDIT_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::EDIT_ONLY, true, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'], // Not project:1
                    [self::EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ],
        ];
    }

    // These extra tests run only on testPermissionsForView (and not testPermissionsWithFundsForView)
    public function getPermissionsAndTestsForViewOnly(): array
    {
        return array_merge($this->getPermissionsAndTests(), [
            //  permission on Scheme, HAS_VALID_EDIT_PERMISSION role
            [
                [[Permission::SCHEME_MANAGER], Authority::class, Authority::class, 'authority:1'],
                [
                    [self::SCHEME_EDIT_ONLY, true, Authority::class, 'authority:1'],
                    [self::SCHEME_EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:1'],
                    [self::SCHEME_EDIT_ONLY, false, CrstsFundReturn::class, 'authority:1/return:2'],
                    [self::SCHEME_EDIT_ONLY, false, CrstsFundReturn::class, 'authority:2/return:1'],
                    [self::SCHEME_EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:1'],
                    [self::SCHEME_EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:2/project:1'],
                    [self::SCHEME_EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:1/return:1/project:2'], // Not project:1
                    [self::SCHEME_EDIT_ONLY, false, CrstsSchemeReturn::class, 'authority:2/return:1/project:1'], // Not authority:1
                ]
            ]
        ]);
    }

    public function dataPermissions(): \Generator
    {
        $userRef = 'user';
        foreach($this->getPermissionsAndTestsForViewOnly() as [$permissionSet, $tests]) {
            foreach($tests as $test) {
                [$attributes, $expectedResult, $subjectClass, $subjectRef] = $test;

                foreach($attributes as $attribute) {
                    $otherArgs = [$expectedResult, $userRef, $subjectClass, $subjectRef];

                    if ($permissionSet === null) {
                        yield array_merge([$attribute, null, null, null, null, null], $otherArgs);
                    } else {
                        [$permissions, $entityRefClass, $entityClass, $entityId] = $permissionSet;

                        foreach($permissions as $permission) {
                            yield array_merge([$attribute, $permission, $entityRefClass, $entityClass, $entityId, null], $otherArgs);
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

        ?bool       $expectedResult,
        string      $userRef,

        string      $subjectClass,
        string      $subjectRef,
    ): void
    {
        $this->createPermissionAndPerformTestOnSpecificVoter($this->permissionVoter, ...func_get_args());
    }

    public function dataPermissionsWithFunds(): \Generator
    {
        $userRef = 'user';

        // With this test we look at how adding fundTypes to the permissions affects the resulting voter response.

        // With CRSTS1, everything should be the same as previously, as all of the fixtures use CRSTS1 funds.
        // Whereas with BSIP specified, the voters should always return false.
        $fundsAndHowExpectedResultsAreModified = [
            [[Fund::CRSTS1], null],
            [[Fund::BSIP], false],
        ];

        foreach($this->getPermissionsAndTests() as [$permissionSet, $tests]) {
            foreach($tests as $test) {
                [$attributes, $expectedResult, $subjectClass, $subjectRef] = $test;

                if ($permissionSet === null) {
                    // Not needed - tested in testPermissionsForView
                    continue;
                }

                [$permissions, $entityRefClass, $entityClass, $entityId] = $permissionSet;

                if (!in_array($entityClass, [Authority::class, Scheme::class])) {
                    // FundTypes only valid in permissions targeting Authority or Scheme
                    continue;
                }

                foreach($fundsAndHowExpectedResultsAreModified as [$funds, $expectedResultModifier]) {
                    $funds = array_map(fn(Fund $f) => $f->value, $funds);
                    $calculatedExpectedResult = match ($expectedResultModifier) {
                        null => $expectedResult,
                        default => $expectedResultModifier,
                    };

                    foreach($attributes as $attribute) {
                        $otherArgs = [$calculatedExpectedResult, $userRef, $subjectClass, $subjectRef];

                        foreach($permissions as $permission) {
                            yield array_merge([$attribute, $permission, $entityRefClass, $entityClass, $entityId, $funds], $otherArgs);
                        }
                    }
                }
            }
        }
    }

    /**
     * @dataProvider dataPermissionsWithFunds
     */
    public function testPermissionsWithFundsForView(
        string      $attribute,

        ?Permission $permission,
        ?string     $permissionEntityReferenceClass,
        ?string     $permissionEntityClass,
        ?string     $permissionEntityId,
        ?array      $permissionFundTypes,

        bool        $expectedResult,
        string      $userRef,

        string      $subjectClass,
        string      $subjectRef,
    ): void
    {
        // The dataProvider for this test generates two permissions per entry in getPermissionsAndTests()
        //
        // One with fundType: CRSTS1, and one with fundType: BSIP
        //
        // The former is expected to pass/fail as expected by the test (as all fixtures are CRSTS1-based),
        // and the latter is always expected to fail
        $this->createPermissionAndPerformTestOnSpecificVoter($this->permissionVoter, ...func_get_args());
    }
}
