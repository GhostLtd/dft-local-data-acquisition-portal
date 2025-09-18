<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\UserTypeRoles;
use App\Security\ResolvedSubject;
use App\Security\SubjectResolver;
use App\Security\Voter\Internal\DenyActionsReturnVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DenyActionsReturnSimpleVoterTest extends TestCase
{
    /** @var AuthorizationCheckerInterface&MockObject */
    private AuthorizationCheckerInterface $authorizationChecker;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;
    /** @var SubjectResolver&MockObject */
    private SubjectResolver $subjectResolver;
    private DenyActionsReturnVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subjectResolver = $this->createMock(SubjectResolver::class);
        $this->voter = new DenyActionsReturnVoter(
            $this->authorizationChecker,
            $this->logger,
            $this->subjectResolver
        );
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testDoesNotSupportWhenSubjectResolverReturnsFalse(): void
    {
        $fundReturn = $this->createFundReturn(FundReturn::STATE_OPEN);

        $this->subjectResolver
            ->expects($this->once())
            ->method('isValidSubjectForInternalRole')
            ->willReturn(false);

        $result = $this->voter->vote($this->token, $fundReturn, [InternalRole::HAS_VALID_EDIT_PERMISSION]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notValidSubject = new \stdClass();

        $result = $this->voter->vote($this->token, $notValidSubject, [InternalRole::HAS_VALID_EDIT_PERMISSION]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }


    public function testDeniesNonViewActionsWhenStateNotOpen(): void
    {
        // Test that non-view actions are denied when fund return state is not OPEN
        $nonViewAttributes = [
            InternalRole::HAS_VALID_EDIT_PERMISSION,
            InternalRole::HAS_VALID_SIGN_OFF_PERMISSION,
            InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION,
        ];

        $nonOpenStates = [
            FundReturn::STATE_INITIAL,
            FundReturn::STATE_SUBMITTED,
        ];

        foreach ($nonOpenStates as $state) {
            foreach ($nonViewAttributes as $attribute) {
                $fundReturn = $this->createFundReturn($state);
                $this->setupValidSubjectAndResolver($fundReturn, $attribute);

                $result = $this->voter->vote($this->token, $fundReturn, [$attribute]);

                $this->assertEquals(VoterInterface::ACCESS_DENIED, $result,
                    "Should deny {$attribute} for state {$state}");
            }
        }
    }

    public function testHandlesSchemeReturnSubject(): void
    {
        $schemeReturn = $this->createSchemeReturn(FundReturn::STATE_OPEN);

        $this->subjectResolver
            ->expects($this->once())
            ->method('isValidSubjectForInternalRole')
            ->willReturn(true);

        $this->subjectResolver
            ->expects($this->once())
            ->method('resolveSubjectForRole')
            ->willReturn(new ResolvedSubject(SchemeReturn::class, $schemeReturn, null, null, null));

        $result = $this->voter->vote($this->token, $schemeReturn, [InternalRole::HAS_VALID_EDIT_PERMISSION]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * @dataProvider stateAndAttributeDataProvider
     */
    public function testStateBasedAccessControl(string $state, string $attribute, bool $isAdmin, int $expectedResult): void
    {
        $fundReturn = $this->createFundReturn($state);

        $this->setupValidSubjectAndResolver($fundReturn, $attribute);

        $this->authorizationChecker
            ->method('isGranted')
            ->willReturn($isAdmin);

        $result = $this->voter->vote($this->token, $fundReturn, [$attribute]);

        $this->assertEquals($expectedResult, $result);
    }

    public function stateAndAttributeDataProvider(): array
    {
        return [
            // Open state - always grants for any attribute
            'Open + Edit - grant' => [FundReturn::STATE_OPEN, InternalRole::HAS_VALID_EDIT_PERMISSION, false, VoterInterface::ACCESS_GRANTED],
            'Open + View - grant' => [FundReturn::STATE_OPEN, InternalRole::HAS_VALID_VIEW_PERMISSION, false, VoterInterface::ACCESS_GRANTED],
            'Open + Sign off - grant' => [FundReturn::STATE_OPEN, InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, false, VoterInterface::ACCESS_GRANTED],

            // Submitted state - grants only for view
            'Submitted + View - grant' => [FundReturn::STATE_SUBMITTED, InternalRole::HAS_VALID_VIEW_PERMISSION, false, VoterInterface::ACCESS_GRANTED],
            'Submitted + Edit - deny' => [FundReturn::STATE_SUBMITTED, InternalRole::HAS_VALID_EDIT_PERMISSION, false, VoterInterface::ACCESS_DENIED],
            'Submitted + Sign off - deny' => [FundReturn::STATE_SUBMITTED, InternalRole::HAS_VALID_SIGN_OFF_PERMISSION, false, VoterInterface::ACCESS_DENIED],

            // Initial state - admin check for view, deny for others
            'Initial + View + Admin - grant' => [FundReturn::STATE_INITIAL, InternalRole::HAS_VALID_VIEW_PERMISSION, true, VoterInterface::ACCESS_GRANTED],
            'Initial + View + Non-admin - deny' => [FundReturn::STATE_INITIAL, InternalRole::HAS_VALID_VIEW_PERMISSION, false, VoterInterface::ACCESS_DENIED],
            'Initial + Edit - deny' => [FundReturn::STATE_INITIAL, InternalRole::HAS_VALID_EDIT_PERMISSION, false, VoterInterface::ACCESS_DENIED],
        ];
    }

    private function setupValidSubjectAndResolver($subject, string $attribute): void
    {
        $this->subjectResolver
            ->expects($this->any())
            ->method('isValidSubjectForInternalRole')
            ->willReturn(true);

        if ($subject instanceof CrstsSchemeReturn) {
            $resolvedSubject = new ResolvedSubject(SchemeReturn::class, $subject, null, null, null);
        } else {
            $resolvedSubject = new ResolvedSubject(FundReturn::class, $subject, null, null, null);
        }

        $this->subjectResolver
            ->expects($this->any())
            ->method('resolveSubjectForRole')
            ->willReturn($resolvedSubject);
    }

    private function createFundReturn(string $state): CrstsFundReturn
    {
        $fundAward = new FundAward();

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setFundAward($fundAward);
        $fundReturn->setState($state);

        return $fundReturn;
    }

    private function createSchemeReturn(string $fundReturnState): CrstsSchemeReturn
    {
        $scheme = new Scheme();
        $fundReturn = $this->createFundReturn($fundReturnState);

        $schemeReturn = new CrstsSchemeReturn();
        $schemeReturn->setScheme($scheme);
        $schemeReturn->setFundReturn($fundReturn);

        return $schemeReturn;
    }
}