<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Project;
use App\Entity\ProjectReturn\CrstsProjectReturn;
use App\Entity\Recipient;
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
            [Role::CAN_VIEW, new Recipient(), true],
            [Role::CAN_VIEW, ['subject' => new Recipient(), 'section' => 'section_one'], false], // Invalid subject, Recipients don't have sections

            [Role::CAN_VIEW, new CrstsFundReturn(), true],
            [Role::CAN_VIEW, ['subject' => new CrstsFundReturn(), 'section' => 'section_one'], true],
            [Role::CAN_VIEW, new CrstsProjectReturn(), true],
            [Role::CAN_VIEW, ['subject' => new CrstsProjectReturn(), 'section' => 'section_one'], true],

            // Invalid subjects - things for which CAN_VIEW is not valid
            [Role::CAN_VIEW, new Project(), false],
            [Role::CAN_VIEW, ['subject' => new Project(), 'section' => 'section_one'], false],
            [Role::CAN_VIEW, new FundAward(), false],
            [Role::CAN_VIEW, ['subject' => new FundAward(), 'section' => 'section_one'], false],

            // Roles not supported by this voter
            [Role::CAN_SUBMIT, new CrstsFundReturn(), false],
            [Role::CAN_COMPLETE, new CrstsFundReturn(), false],
            [Role::CAN_EDIT, new CrstsFundReturn(), false],
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
