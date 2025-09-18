<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\User;
use App\Entity\UserTypeRoles;
use App\Repository\FundReturn\FundReturnRepository;
use App\Security\Voter\Admin\ReOpenReturnVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ReOpenReturnVoterTest extends TestCase
{
    /** @var AccessDecisionManagerInterface&MockObject */
    private AccessDecisionManagerInterface $accessDecisionManager;
    /** @var FundReturnRepository&MockObject */
    private FundReturnRepository $fundReturnRepository;
    private ReOpenReturnVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->fundReturnRepository = $this->createMock(FundReturnRepository::class);
        $this->voter = new ReOpenReturnVoter($this->accessDecisionManager, $this->fundReturnRepository);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributeAndSubject(): void
    {
        $fundReturn = $this->createSignedOffFundReturn();
        $user = new User();

        $this->setupSuccessfulMocks($user, $fundReturn);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_REOPEN_RETURN]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $fundReturn = $this->createSignedOffFundReturn();

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notFundReturn = new \stdClass();

        $result = $this->voter->vote($this->token, $notFundReturn, [Role::CAN_REOPEN_RETURN]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }


    /**
     * @dataProvider accessControlDataProvider
     */
    public function testAccessControlLogic(bool $hasUser, bool $isSignedOff, bool $isAdmin, bool $isMostRecent, int $expectedResult): void
    {
        $fundReturn = $isSignedOff ? $this->createSignedOffFundReturn() : $this->createOpenFundReturn();
        $user = $hasUser ? new User() : null;

        $this->token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        if ($hasUser && $isSignedOff) {
            $this->accessDecisionManager
                ->expects($this->once())
                ->method('decide')
                ->willReturn($isAdmin);

            if ($isAdmin) {
                $this->fundReturnRepository
                    ->expects($this->once())
                    ->method('isMostRecentReturnForAward')
                    ->willReturn($isMostRecent);
            } else {
                $this->fundReturnRepository
                    ->expects($this->never())
                    ->method('isMostRecentReturnForAward');
            }
        } else {
            $this->accessDecisionManager
                ->expects($this->never())
                ->method('decide');
            $this->fundReturnRepository
                ->expects($this->never())
                ->method('isMostRecentReturnForAward');
        }

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_REOPEN_RETURN]);

        $this->assertEquals($expectedResult, $result);
    }

    public function accessControlDataProvider(): array
    {
        return [
            'All conditions met - grant' => [true, true, true, true, VoterInterface::ACCESS_GRANTED],
            'No user - deny' => [false, true, true, true, VoterInterface::ACCESS_DENIED],
            'Not signed off - deny' => [true, false, true, true, VoterInterface::ACCESS_DENIED],
            'Not admin - deny' => [true, true, false, true, VoterInterface::ACCESS_DENIED],
            'Not most recent - deny' => [true, true, true, false, VoterInterface::ACCESS_DENIED],
            'Multiple failures - deny' => [false, false, false, false, VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testUsesCorrectAdminRole(): void
    {
        $fundReturn = $this->createSignedOffFundReturn();
        $user = new User();

        $this->token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with(
                $this->identicalTo($this->token),
                $this->identicalTo([UserTypeRoles::ROLE_IAP_ADMIN])
            )
            ->willReturn(true);

        $this->fundReturnRepository
            ->expects($this->once())
            ->method('isMostRecentReturnForAward')
            ->willReturn(true);

        $this->voter->vote($this->token, $fundReturn, [Role::CAN_REOPEN_RETURN]);
    }

    public function testShortCircuitEvaluation(): void
    {
        // Test that evaluation stops early when conditions fail
        $fundReturn = $this->createOpenFundReturn(); // Not signed off
        $user = new User();

        $this->token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        // These should never be called because fund return is not signed off
        $this->accessDecisionManager
            ->expects($this->never())
            ->method('decide');

        $this->fundReturnRepository
            ->expects($this->never())
            ->method('isMostRecentReturnForAward');

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_REOPEN_RETURN]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUsesIsSignedOffMethod(): void
    {
        // Test that it correctly uses the isSignedOff() method
        $fundReturn = $this->createMock(CrstsFundReturn::class);
        $fundReturn
            ->expects($this->once())
            ->method('isSignedOff')
            ->willReturn(true);

        $user = new User();

        $this->token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->willReturn(true);

        $this->fundReturnRepository
            ->expects($this->once())
            ->method('isMostRecentReturnForAward')
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $fundReturn, [Role::CAN_REOPEN_RETURN]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    private function setupSuccessfulMocks(User $user, CrstsFundReturn $fundReturn): void
    {
        $this->token
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->willReturn(true);

        $this->fundReturnRepository
            ->expects($this->once())
            ->method('isMostRecentReturnForAward')
            ->willReturn(true);
    }

    private function createSignedOffFundReturn(): CrstsFundReturn
    {
        $fundAward = new FundAward();

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setFundAward($fundAward);
        $fundReturn->setState(FundReturn::STATE_SUBMITTED);

        return $fundReturn;
    }

    private function createOpenFundReturn(): CrstsFundReturn
    {
        $fundAward = new FundAward();

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setFundAward($fundAward);
        $fundReturn->setState(FundReturn::STATE_OPEN);

        return $fundReturn;
    }
}