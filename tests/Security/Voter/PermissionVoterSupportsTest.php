<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Project;
use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Entity\Recipient;
use App\Security\Voter\PermissionVoter;
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
            [Role::CAN_SUBMIT, new CrstsFundReturn(), true],
            [Role::CAN_SUBMIT, ['subject' => new CrstsFundReturn(), 'section' => 'section_one'], false], // Can't submit sections
            [Role::CAN_SUBMIT, new CrstsProjectReturn(), false], // Can only submit fund returns

            [Role::CAN_COMPLETE, new CrstsFundReturn(), false],
            [Role::CAN_COMPLETE, ['subject' => new CrstsFundReturn(), 'section' => 'section_one'], true],
            [Role::CAN_COMPLETE, new CrstsProjectReturn(), false],
            [Role::CAN_COMPLETE, ['subject' => new CrstsProjectReturn(), 'section' => 'section_one'], true],

            [Role::CAN_EDIT, new CrstsFundReturn(), false],
            [Role::CAN_EDIT, ['subject' => new CrstsFundReturn(), 'section' => 'section_one'], true],
            [Role::CAN_EDIT, new CrstsProjectReturn(), false],
            [Role::CAN_EDIT, ['subject' => new CrstsProjectReturn(), 'section' => 'section_one'], true],

            // This voter doesn't support this attribute!
            [Role::CAN_VIEW, new CrstsFundReturn(), false],
            [Role::CAN_VIEW, ['subject' => new CrstsFundReturn(), 'section' => 'section_one'], false],
            [Role::CAN_VIEW, ['subject' => new CrstsProjectReturn(), 'section' => 'section_one'], false],
        ];

        foreach([new Project(), new Recipient(), new FundAward(), null] as $invalidSubject) {
            $testCases[] = [Role::CAN_SUBMIT, $invalidSubject, false];
            $testCases[] = [Role::CAN_COMPLETE, $invalidSubject, false];
            $testCases[] = [Role::CAN_EDIT, $invalidSubject, false];
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
