<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\SchemeFund\CrstsSchemeFund;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Security\Voter\SetLeadContactVoter;
use App\Tests\AbstractFunctionalTest;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use ReflectionClass;

class SetLeadContactVoterSupportsTest extends AbstractFunctionalTest
{
    protected SetLeadContactVoter $setLeadContactVoter;
    protected ReferenceRepository $referenceRepository;

    public function setUp(): void
    {
        parent::setUp();
        $this->setLeadContactVoter = $this->getFromContainer(SetLeadContactVoter::class, SetLeadContactVoter::class);
    }

    public function dataSupports(): array {
        return [
            [Role::CAN_SET_LEAD_CONTACT, new FundAward(), true],
            [Role::CAN_SET_LEAD_CONTACT, new CrstsFundReturn(), true],
            [Role::CAN_SET_LEAD_CONTACT, new CrstsSchemeReturn(), false],
            [Role::CAN_SET_LEAD_CONTACT, new CrstsSchemeFund(), false],
        ];
    }

    /**
     * @dataProvider dataSupports
     */
    public function testSupports(string $attribute, mixed $subject, bool $expectedResult): void
    {
        $reflClass = new ReflectionClass($this->setLeadContactVoter);
        $supports = $reflClass->getMethod('supports');

        $actualResult = $supports->invoke($this->setLeadContactVoter, $attribute, $subject);
        $this->assertEquals($expectedResult, $actualResult);
    }
}
