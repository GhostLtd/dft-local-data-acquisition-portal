<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Security\Voter\CrstsReturnSignoffEligibilityVoter;
use App\Utility\SignoffHelper\CrstsSignoffHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class CrstsReturnSignoffEligibilityVoterTest extends TestCase
{
    /** @var CrstsSignoffHelper&MockObject */
    private CrstsSignoffHelper $crstsSignoffHelper;
    private CrstsReturnSignoffEligibilityVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->crstsSignoffHelper = $this->createMock(CrstsSignoffHelper::class);
        $this->voter = new CrstsReturnSignoffEligibilityVoter($this->crstsSignoffHelper);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributeAndSubject(): void
    {
        $fundReturn = new CrstsFundReturn();

        $this->crstsSignoffHelper
            ->expects($this->once())
            ->method('hasSignoffEligibilityProblems')
            ->with($fundReturn)
            ->willReturn(false);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_RETURN_BE_SIGNED_OFF]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $fundReturn = new CrstsFundReturn();

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notFundReturn = new \stdClass();

        $result = $this->voter->vote($this->token, $notFundReturn, [Role::CAN_RETURN_BE_SIGNED_OFF]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testGrantsAccessWhenNoSignoffEligibilityProblems(): void
    {
        $fundReturn = new CrstsFundReturn();

        $this->crstsSignoffHelper
            ->expects($this->once())
            ->method('hasSignoffEligibilityProblems')
            ->with($fundReturn)
            ->willReturn(false);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_RETURN_BE_SIGNED_OFF]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeniesAccessWhenSignoffEligibilityProblemsExist(): void
    {
        $fundReturn = new CrstsFundReturn();

        $this->crstsSignoffHelper
            ->expects($this->once())
            ->method('hasSignoffEligibilityProblems')
            ->with($fundReturn)
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_RETURN_BE_SIGNED_OFF]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testThrowsExceptionForNonCrstsFundReturnSubject(): void
    {
        // This tests the runtime exception in voteOnAttribute when supports() might have been bypassed
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Subject must be an instance of CrstsFundReturn');

        // Create a voter that bypasses supports() by directly calling voteOnAttribute
        $reflection = new \ReflectionClass($this->voter);
        $voteOnAttributeMethod = $reflection->getMethod('voteOnAttribute');

        $voteOnAttributeMethod->invoke(
            $this->voter,
            Role::CAN_RETURN_BE_SIGNED_OFF,
            new \stdClass(),
            $this->token
        );
    }

    /**
     * @dataProvider eligibilityDataProvider
     */
    public function testSignoffEligibilityLogic(bool $hasProblems, int $expectedResult): void
    {
        $fundReturn = new CrstsFundReturn();

        $this->crstsSignoffHelper
            ->expects($this->once())
            ->method('hasSignoffEligibilityProblems')
            ->with($fundReturn)
            ->willReturn($hasProblems);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_RETURN_BE_SIGNED_OFF]);

        $this->assertEquals($expectedResult, $result);
    }

    public function eligibilityDataProvider(): array
    {
        return [
            'No problems - grant access' => [false, VoterInterface::ACCESS_GRANTED],
            'Has problems - deny access' => [true, VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testOnlyCallsHelperForSupportedSubjects(): void
    {
        // Test that helper is not called for unsupported subjects
        $notFundReturn = new \stdClass();

        $this->crstsSignoffHelper
            ->expects($this->never())
            ->method('hasSignoffEligibilityProblems');

        $result = $this->voter->vote($this->token, $notFundReturn, [Role::CAN_RETURN_BE_SIGNED_OFF]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testOnlyCallsHelperForSupportedAttributes(): void
    {
        // Test that helper is not called for unsupported attributes
        $fundReturn = new CrstsFundReturn();

        $this->crstsSignoffHelper
            ->expects($this->never())
            ->method('hasSignoffEligibilityProblems');

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testHelperCalledWithExactFundReturnInstance(): void
    {
        $fundReturn = new CrstsFundReturn();

        $this->crstsSignoffHelper
            ->expects($this->once())
            ->method('hasSignoffEligibilityProblems')
            ->with($this->identicalTo($fundReturn))
            ->willReturn(false);

        $this->voter->vote($this->token, $fundReturn, [Role::CAN_RETURN_BE_SIGNED_OFF]);
    }
}