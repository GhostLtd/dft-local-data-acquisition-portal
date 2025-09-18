<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Security\Voter\External\MarkSchemeAsReadyVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class MarkSchemeAsReadyVoterTest extends TestCase
{
    /** @var AccessDecisionManagerInterface&MockObject */
    private AccessDecisionManagerInterface $accessDecisionManager;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;
    private MarkSchemeAsReadyVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->voter = new MarkSchemeAsReadyVoter($this->accessDecisionManager, $this->logger);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributesAndSubject(): void
    {
        $schemeReturn = $this->createSchemeReturn(false, false); // not ready, not signed off

        $supportedAttributes = [
            Role::CAN_MARK_SCHEME_RETURN_AS_READY,
            Role::CAN_MARK_SCHEME_RETURN_AS_NOT_READY,
        ];

        foreach ($supportedAttributes as $attribute) {
            $this->accessDecisionManager
                ->method('decide')
                ->willReturn(true);

            $result = $this->voter->vote($this->token, $schemeReturn, [$attribute]);
            $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result,
                "Should support attribute: {$attribute}");
        }
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $schemeReturn = $this->createSchemeReturn(false, false);

        $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notSchemeReturn = new \stdClass();

        $result = $this->voter->vote($this->token, $notSchemeReturn, [Role::CAN_MARK_SCHEME_RETURN_AS_READY]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDeniesAccessWhenInternalRoleCheckFails(): void
    {
        $schemeReturn = $this->createSchemeReturn(false, false);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION], $schemeReturn)
            ->willReturn(false);

        $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_MARK_SCHEME_RETURN_AS_READY]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testDeniesAccessWhenFundReturnIsSignedOff(): void
    {
        $schemeReturn = $this->createSchemeReturn(false, true); // not ready, but signed off

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_MARK_SCHEME_RETURN_AS_READY]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }


    /**
     * @dataProvider readyStateDataProvider
     */
    public function testReadyStateLogic(string $attribute, bool $currentlyReady, bool $signedOff, int $expectedResult): void
    {
        $schemeReturn = $this->createSchemeReturn($currentlyReady, $signedOff);

        $this->accessDecisionManager
            ->method('decide')
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $schemeReturn, [$attribute]);

        $this->assertEquals($expectedResult, $result);
    }

    public function readyStateDataProvider(): array
    {
        return [
            // Mark as ready scenarios
            'Mark as ready: not ready, not signed off - grant' => [Role::CAN_MARK_SCHEME_RETURN_AS_READY, false, false, VoterInterface::ACCESS_GRANTED],
            'Mark as ready: already ready, not signed off - deny' => [Role::CAN_MARK_SCHEME_RETURN_AS_READY, true, false, VoterInterface::ACCESS_DENIED],
            'Mark as ready: not ready, signed off - deny' => [Role::CAN_MARK_SCHEME_RETURN_AS_READY, false, true, VoterInterface::ACCESS_DENIED],
            'Mark as ready: already ready, signed off - deny' => [Role::CAN_MARK_SCHEME_RETURN_AS_READY, true, true, VoterInterface::ACCESS_DENIED],

            // Mark as not ready scenarios
            'Mark as not ready: ready, not signed off - grant' => [Role::CAN_MARK_SCHEME_RETURN_AS_NOT_READY, true, false, VoterInterface::ACCESS_GRANTED],
            'Mark as not ready: not ready, not signed off - deny' => [Role::CAN_MARK_SCHEME_RETURN_AS_NOT_READY, false, false, VoterInterface::ACCESS_DENIED],
            'Mark as not ready: ready, signed off - deny' => [Role::CAN_MARK_SCHEME_RETURN_AS_NOT_READY, true, true, VoterInterface::ACCESS_DENIED],
            'Mark as not ready: not ready, signed off - deny' => [Role::CAN_MARK_SCHEME_RETURN_AS_NOT_READY, false, true, VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testUsesCorrectInternalRole(): void
    {
        $schemeReturn = $this->createSchemeReturn(false, false);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with(
                $this->identicalTo($this->token),
                $this->identicalTo([InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION]),
                $this->identicalTo($schemeReturn)
            )
            ->willReturn(true);

        $this->voter->vote($this->token, $schemeReturn, [Role::CAN_MARK_SCHEME_RETURN_AS_READY]);
    }

    public function testHandlesSchemeReturnWithoutFundReturn(): void
    {
        // Test edge case where fund return might be null
        $scheme = new Scheme();
        $schemeReturn = new CrstsSchemeReturn();
        $schemeReturn->setScheme($scheme);
        $schemeReturn->setReadyForSignoff(false);
        // Deliberately not setting fund return

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_MARK_SCHEME_RETURN_AS_READY]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    private function createSchemeReturn(bool $readyForSignoff, bool $fundReturnSignedOff): CrstsSchemeReturn
    {
        $scheme = new Scheme();

        $fundAward = new FundAward();

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setFundAward($fundAward);
        $fundReturn->setState($fundReturnSignedOff ? FundReturn::STATE_SUBMITTED : FundReturn::STATE_OPEN);

        $schemeReturn = new CrstsSchemeReturn();
        $schemeReturn->setScheme($scheme);
        $schemeReturn->setFundReturn($fundReturn);
        $schemeReturn->setReadyForSignoff($readyForSignoff);

        return $schemeReturn;
    }
}