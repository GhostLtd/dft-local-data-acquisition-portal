<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Security\Voter\External\SignOffReturnVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SignOffReturnVoterTest extends TestCase
{
    /** @var AccessDecisionManagerInterface&MockObject */
    private AccessDecisionManagerInterface $accessDecisionManager;
    private SignOffReturnVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->voter = new SignOffReturnVoter($this->accessDecisionManager);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributeAndSubject(): void
    {
        $fundReturn = new CrstsFundReturn();

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], $fundReturn)
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_SIGN_OFF_RETURN]);

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

        $result = $this->voter->vote($this->token, $notFundReturn, [Role::CAN_SIGN_OFF_RETURN]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testGrantsAccessWhenAccessDecisionManagerAllows(): void
    {
        $fundReturn = new CrstsFundReturn();

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], $fundReturn)
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_SIGN_OFF_RETURN]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeniesAccessWhenAccessDecisionManagerDenies(): void
    {
        $fundReturn = new CrstsFundReturn();

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [InternalRole::HAS_VALID_SIGN_OFF_PERMISSION], $fundReturn)
            ->willReturn(false);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_SIGN_OFF_RETURN]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUsesCorrectInternalRole(): void
    {
        $fundReturn = new CrstsFundReturn();

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with(
                $this->identicalTo($this->token),
                $this->identicalTo([InternalRole::HAS_VALID_SIGN_OFF_PERMISSION]),
                $this->identicalTo($fundReturn)
            )
            ->willReturn(true);

        $this->voter->vote($this->token, $fundReturn, [Role::CAN_SIGN_OFF_RETURN]);
    }

    /**
     * @dataProvider accessDecisionDataProvider
     */
    public function testAccessDecisionDelegation(bool $accessDecisionResult, int $expectedVoterResult): void
    {
        $fundReturn = new CrstsFundReturn();

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->willReturn($accessDecisionResult);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_SIGN_OFF_RETURN]);

        $this->assertEquals($expectedVoterResult, $result);
    }

    public function accessDecisionDataProvider(): array
    {
        return [
            'Access decision grants - voter grants' => [true, VoterInterface::ACCESS_GRANTED],
            'Access decision denies - voter denies' => [false, VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testOnlyCallsAccessDecisionManagerForSupportedCombination(): void
    {
        // Test that AccessDecisionManager is not called for unsupported combinations
        $fundReturn = new CrstsFundReturn();

        $this->accessDecisionManager
            ->expects($this->never())
            ->method('decide');

        // Wrong attribute
        $result1 = $this->voter->vote($this->token, $fundReturn, [Role::CAN_VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result1);

        // Wrong subject
        $result2 = $this->voter->vote($this->token, new \stdClass(), [Role::CAN_SIGN_OFF_RETURN]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result2);
    }
}