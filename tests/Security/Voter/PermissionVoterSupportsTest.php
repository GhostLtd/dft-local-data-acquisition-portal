<?php

namespace App\Tests\Security\Voter;

use App\Entity\Authority;
use App\Entity\Enum\InternalRole;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Security\Voter\Internal\PermissionVoter;
use App\Tests\AbstractFunctionalTest;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use ReflectionClass;

class PermissionVoterSupportsTest extends AbstractFunctionalTest
{
    protected PermissionVoter $permissionVoter;
    protected ReferenceRepository $referenceRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->permissionVoter = $this->getFromContainer(PermissionVoter::class, PermissionVoter::class);
    }

    public function dataSupports(): array {
        $testCases = [
            [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, new CrstsFundReturn(), true],
            [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, new CrstsSchemeReturn(), false], // Can only submit fund returns
            [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, new Authority(), false],

            [InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, new CrstsFundReturn(), false],
            [InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, new CrstsSchemeReturn(), true],
            [InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, new Authority(), false],

            [InternalRole::HAS_VALID_EDIT_PERMISSION, new CrstsFundReturn(), true],
            [InternalRole::HAS_VALID_EDIT_PERMISSION, new CrstsSchemeReturn(), true],
            [InternalRole::HAS_VALID_EDIT_PERMISSION, new Authority(), false],

            // This voter doesn't support this attribute! (Handled by a different voter!)
            [InternalRole::HAS_VALID_VIEW_PERMISSION, new CrstsFundReturn(), false],
        ];

        foreach([new Scheme(), new Authority(), new FundAward(), null] as $invalidSubject) {
            $testCases[] = [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, $invalidSubject, false];
            $testCases[] = [InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, $invalidSubject, false];
            $testCases[] = [InternalRole::HAS_VALID_EDIT_PERMISSION, $invalidSubject, false];
        }

        return $testCases;
    }

    /**
     * @dataProvider dataSupports
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
        $reflClass = new ReflectionClass($this->permissionVoter);
        $supports = $reflClass->getMethod('supports');

        $actualResult = $supports->invoke($this->permissionVoter, $attribute, $subject);
        $this->assertEquals($expectedResult, $actualResult);
    }
}
