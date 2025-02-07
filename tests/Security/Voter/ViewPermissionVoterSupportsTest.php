<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\Authority;
use App\Security\Voter\ViewPermissionVoter;
use App\Tests\AbstractFunctionalTest;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use ReflectionClass;

class ViewPermissionVoterSupportsTest extends AbstractFunctionalTest
{
    protected ViewPermissionVoter $viewPermissionVoter;
    protected ReferenceRepository $referenceRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->viewPermissionVoter = $this->getFromContainer(ViewPermissionVoter::class, ViewPermissionVoter::class);
    }

    public function dataSupports(): array {
        return [
            [InternalRole::HAS_VALID_VIEW_PERMISSION, new Authority(), true],

            [InternalRole::HAS_VALID_VIEW_PERMISSION, new CrstsFundReturn(), true],
            [InternalRole::HAS_VALID_VIEW_PERMISSION, new CrstsSchemeReturn(), true],

            // Invalid subjects - things for which HAS_VALID_VIEW_PERMISSION is not valid
            [InternalRole::HAS_VALID_VIEW_PERMISSION, new Scheme(), false],
            [InternalRole::HAS_VALID_VIEW_PERMISSION, new FundAward(), false],

            // Roles not supported by this voter
            [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, new CrstsFundReturn(), false],
            [InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION, new CrstsFundReturn(), false],
            [InternalRole::HAS_VALID_EDIT_PERMISSION, new CrstsFundReturn(), false],
            [Role::CAN_SET_LEAD_CONTACT, new CrstsFundReturn(), false],
        ];
    }

    /**
     * @dataProvider dataSupports
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
        $reflClass = new ReflectionClass($this->viewPermissionVoter);
        $supports = $reflClass->getMethod('supports');

        $actualResult = $supports->invoke($this->viewPermissionVoter, $attribute, $subject);
        $this->assertEquals($expectedResult, $actualResult);
    }
}
