<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\UserTypeRoles;
use App\Security\Voter\Admin\EditBaselinesVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EditBaselinesVoterTest extends TestCase
{
    /** @var AccessDecisionManagerInterface&MockObject */
    private AccessDecisionManagerInterface $accessDecisionManager;
    private EditBaselinesVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->voter = new EditBaselinesVoter($this->accessDecisionManager);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributeAndSubject(): void
    {
        $fundReturn = $this->createFundReturn(FundReturn::STATE_INITIAL);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [UserTypeRoles::ROLE_IAP_ADMIN])
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_EDIT_BASELINES]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $fundReturn = $this->createFundReturn(FundReturn::STATE_INITIAL);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notFundReturn = new \stdClass();

        $result = $this->voter->vote($this->token, $notFundReturn, [Role::CAN_EDIT_BASELINES]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }


    /**
     * @dataProvider accessControlDataProvider
     */
    public function testAccessControl(bool $isAdmin, string $fundReturnState, int $expectedResult): void
    {
        $fundReturn = $this->createFundReturn($fundReturnState);

        $this->accessDecisionManager
            ->method('decide')
            ->willReturn($isAdmin);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_EDIT_BASELINES]);

        $this->assertEquals($expectedResult, $result);
    }

    public function accessControlDataProvider(): array
    {
        return [
            'Admin + Initial state - grant' => [true, FundReturn::STATE_INITIAL, VoterInterface::ACCESS_GRANTED],
            'Admin + Open state - deny' => [true, FundReturn::STATE_OPEN, VoterInterface::ACCESS_DENIED],
            'Admin + Submitted state - deny' => [true, FundReturn::STATE_SUBMITTED, VoterInterface::ACCESS_DENIED],
            'Non-admin + Initial state - deny' => [false, FundReturn::STATE_INITIAL, VoterInterface::ACCESS_DENIED],
            'Non-admin + Open state - deny' => [false, FundReturn::STATE_OPEN, VoterInterface::ACCESS_DENIED],
            'Non-admin + Submitted state - deny' => [false, FundReturn::STATE_SUBMITTED, VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testUsesCorrectAdminRole(): void
    {
        $fundReturn = $this->createFundReturn(FundReturn::STATE_INITIAL);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with(
                $this->identicalTo($this->token),
                $this->identicalTo([UserTypeRoles::ROLE_IAP_ADMIN])
            )
            ->willReturn(true);

        $this->voter->vote($this->token, $fundReturn, [Role::CAN_EDIT_BASELINES]);
    }

    public function testRequiresBothAdminRoleAndInitialState(): void
    {
        // Test that BOTH conditions must be met
        $fundReturn = $this->createFundReturn(FundReturn::STATE_INITIAL);

        // Even with admin role, if state check fails, it should deny
        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->willReturn(true);

        // Change state after setting up the expectation
        $fundReturn->setState(FundReturn::STATE_OPEN);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_EDIT_BASELINES]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testOnlyCallsAccessDecisionManagerForSupportedCombination(): void
    {
        // Should not call AccessDecisionManager for unsupported combinations
        $this->accessDecisionManager
            ->expects($this->never())
            ->method('decide');

        $fundReturn = $this->createFundReturn(FundReturn::STATE_INITIAL);

        // Wrong attribute
        $result1 = $this->voter->vote($this->token, $fundReturn, [Role::CAN_VIEW]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result1);

        // Wrong subject
        $result2 = $this->voter->vote($this->token, new \stdClass(), [Role::CAN_EDIT_BASELINES]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result2);
    }

    public function testHandlesSubjectTypeCheckInVoteOnAttribute(): void
    {
        // The voteOnAttribute method has an additional instanceof check
        $fundReturn = $this->createFundReturn(FundReturn::STATE_INITIAL);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_EDIT_BASELINES]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    private function createFundReturn(string $state): CrstsFundReturn
    {
        $fundAward = new FundAward();

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setFundAward($fundAward);
        $fundReturn->setState($state);

        return $fundReturn;
    }
}