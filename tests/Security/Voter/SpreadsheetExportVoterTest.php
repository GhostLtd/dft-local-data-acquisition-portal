<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Role;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Security\Voter\SpreadsheetExportVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SpreadsheetExportVoterTest extends TestCase
{
    /** @var AccessDecisionManagerInterface&MockObject */
    private AccessDecisionManagerInterface $accessDecisionManager;
    private SpreadsheetExportVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->voter = new SpreadsheetExportVoter($this->accessDecisionManager);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributeAndSubject(): void
    {
        $crstsFundReturn = $this->createMock(CrstsFundReturn::class);
        $crstsFundReturn->method('getState')->willReturn(FundReturn::STATE_OPEN);

        // Mock CAN_VIEW permission granted
        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [Role::CAN_VIEW], $crstsFundReturn)
            ->willReturn(true);

        // Should support CAN_EXPORT_SPREADSHEET on CrstsFundReturn
        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->token, $crstsFundReturn, [Role::CAN_EXPORT_SPREADSHEET])
        );
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $crstsFundReturn = $this->createMock(CrstsFundReturn::class);

        // Should abstain for other attributes
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $crstsFundReturn, [Role::CAN_VIEW])
        );
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notCrstsFundReturn = new \stdClass();

        // Should abstain for non-CrstsFundReturn subjects
        $this->assertEquals(
            VoterInterface::ACCESS_ABSTAIN,
            $this->voter->vote($this->token, $notCrstsFundReturn, [Role::CAN_EXPORT_SPREADSHEET])
        );
    }

    public function testDeniesAccessForInitialState(): void
    {
        $crstsFundReturn = $this->createMock(CrstsFundReturn::class);
        $crstsFundReturn->method('getState')->willReturn(FundReturn::STATE_INITIAL);

        // Should deny access for INITIAL state regardless of CAN_VIEW permission
        $this->accessDecisionManager
            ->expects($this->never())
            ->method('decide');

        $result = $this->voter->vote($this->token, $crstsFundReturn, [Role::CAN_EXPORT_SPREADSHEET]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testGrantsAccessForOpenStateWithViewPermission(): void
    {
        $crstsFundReturn = $this->createMock(CrstsFundReturn::class);
        $crstsFundReturn->method('getState')->willReturn(FundReturn::STATE_OPEN);

        // Mock CAN_VIEW permission granted
        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [Role::CAN_VIEW], $crstsFundReturn)
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $crstsFundReturn, [Role::CAN_EXPORT_SPREADSHEET]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeniesAccessForOpenStateWithoutViewPermission(): void
    {
        $crstsFundReturn = $this->createMock(CrstsFundReturn::class);
        $crstsFundReturn->method('getState')->willReturn(FundReturn::STATE_OPEN);

        // Mock CAN_VIEW permission denied
        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [Role::CAN_VIEW], $crstsFundReturn)
            ->willReturn(false);

        $result = $this->voter->vote($this->token, $crstsFundReturn, [Role::CAN_EXPORT_SPREADSHEET]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testGrantsAccessForSubmittedStateWithViewPermission(): void
    {
        $crstsFundReturn = $this->createMock(CrstsFundReturn::class);
        $crstsFundReturn->method('getState')->willReturn(FundReturn::STATE_SUBMITTED);

        // Mock CAN_VIEW permission granted
        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [Role::CAN_VIEW], $crstsFundReturn)
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $crstsFundReturn, [Role::CAN_EXPORT_SPREADSHEET]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * @dataProvider stateDataProvider
     */
    public function testAllNonInitialStatesFollowViewPermission(string $state, bool $canView, int $expectedResult): void
    {
        $crstsFundReturn = $this->createMock(CrstsFundReturn::class);
        $crstsFundReturn->method('getState')->willReturn($state);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [Role::CAN_VIEW], $crstsFundReturn)
            ->willReturn($canView);

        $result = $this->voter->vote($this->token, $crstsFundReturn, [Role::CAN_EXPORT_SPREADSHEET]);

        $this->assertEquals($expectedResult, $result);
    }

    public function stateDataProvider(): array
    {
        return [
            'Open state with view permission' => [FundReturn::STATE_OPEN, true, VoterInterface::ACCESS_GRANTED],
            'Open state without view permission' => [FundReturn::STATE_OPEN, false, VoterInterface::ACCESS_DENIED],
            'Submitted state with view permission' => [FundReturn::STATE_SUBMITTED, true, VoterInterface::ACCESS_GRANTED],
            'Submitted state without view permission' => [FundReturn::STATE_SUBMITTED, false, VoterInterface::ACCESS_DENIED],
        ];
    }
}