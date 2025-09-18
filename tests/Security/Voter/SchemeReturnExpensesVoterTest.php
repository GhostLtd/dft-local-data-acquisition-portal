<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeData\CrstsData;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Security\Voter\External\SchemeReturnExpensesVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class SchemeReturnExpensesVoterTest extends TestCase
{
    /** @var AccessDecisionManagerInterface&MockObject */
    private AccessDecisionManagerInterface $accessDecisionManager;
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;
    private SchemeReturnExpensesVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->accessDecisionManager = $this->createMock(AccessDecisionManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->voter = new SchemeReturnExpensesVoter($this->accessDecisionManager, $this->logger);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributesAndSubject(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn(4, true);

        $supportedAttributes = [
            Role::CAN_EDIT_SCHEME_RETURN_EXPENSES,
            Role::CAN_VIEW_SCHEME_RETURN_EXPENSES,
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
        $schemeReturn = $this->createCrstsSchemeReturn(4, true);

        $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notSchemeReturn = new \stdClass();

        $result = $this->voter->vote($this->token, $notSchemeReturn, [Role::CAN_EDIT_SCHEME_RETURN_EXPENSES]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDeniesAccessWhenInternalRoleCheckFails(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn(4, true);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [InternalRole::HAS_VALID_EDIT_PERMISSION], $schemeReturn)
            ->willReturn(false);

        $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_EDIT_SCHEME_RETURN_EXPENSES]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testEditPermissionGrantedForRetainedSchemeAnyQuarter(): void
    {
        for ($quarter = 1; $quarter <= 4; $quarter++) {
            $schemeReturn = $this->createCrstsSchemeReturn($quarter, true); // retained = true

            $this->accessDecisionManager
                ->expects($this->any())
                ->method('decide')
                ->willReturn(true);

            $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_EDIT_SCHEME_RETURN_EXPENSES]);

            $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result,
                "Retained scheme should allow editing in quarter {$quarter}");
        }
    }

    public function testEditPermissionDeniedForNonRetainedSchemeInQuarters123(): void
    {
        for ($quarter = 1; $quarter <= 3; $quarter++) {
            $schemeReturn = $this->createCrstsSchemeReturn($quarter, false); // retained = false

            $this->accessDecisionManager
                ->expects($this->any())
                ->method('decide')
                ->willReturn(true);

            $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_EDIT_SCHEME_RETURN_EXPENSES]);

            $this->assertEquals(VoterInterface::ACCESS_DENIED, $result,
                "Non-retained scheme should deny editing in quarter {$quarter}");
        }
    }

    public function testEditPermissionGrantedForNonRetainedSchemeInQuarter4(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn(4, false); // retained = false

        $this->accessDecisionManager
            ->method('decide')
            ->with($this->token, [InternalRole::HAS_VALID_EDIT_PERMISSION], $schemeReturn)
            ->willReturn(true);

        $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_EDIT_SCHEME_RETURN_EXPENSES]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testViewPermissionAlwaysGrantedWhenInternalRoleCheckPasses(): void
    {
        // Test both retained and non-retained schemes across all quarters
        $testCases = [
            [1, true], [2, true], [3, true], [4, true],   // retained
            [1, false], [2, false], [3, false], [4, false], // non-retained
        ];

        foreach ($testCases as [$quarter, $retained]) {
            $schemeReturn = $this->createCrstsSchemeReturn($quarter, $retained);

            $this->accessDecisionManager
                ->expects($this->any())
                ->method('decide')
                ->willReturn(true);

            $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_VIEW_SCHEME_RETURN_EXPENSES]);

            $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result,
                "View should be granted for quarter {$quarter}, retained: " . ($retained ? 'true' : 'false'));
        }
    }

    public function testViewPermissionDeniedWhenInternalRoleCheckFails(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn(4, true);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [InternalRole::HAS_VALID_VIEW_PERMISSION], $schemeReturn)
            ->willReturn(false);

        $result = $this->voter->vote($this->token, $schemeReturn, [Role::CAN_VIEW_SCHEME_RETURN_EXPENSES]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * @dataProvider attributeAndPermissionDataProvider
     */
    public function testInternalRoleMapping(string $attribute, string $expectedInternalRole): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn(4, true);

        $this->accessDecisionManager
            ->expects($this->once())
            ->method('decide')
            ->with($this->token, [$expectedInternalRole], $schemeReturn)
            ->willReturn(true);

        $this->voter->vote($this->token, $schemeReturn, [$attribute]);
    }

    public function attributeAndPermissionDataProvider(): array
    {
        return [
            'Edit maps to edit permission' => [Role::CAN_EDIT_SCHEME_RETURN_EXPENSES, InternalRole::HAS_VALID_EDIT_PERMISSION],
            'View maps to view permission' => [Role::CAN_VIEW_SCHEME_RETURN_EXPENSES, InternalRole::HAS_VALID_VIEW_PERMISSION],
        ];
    }

    private function createCrstsSchemeReturn(int $quarter, bool $retained): CrstsSchemeReturn
    {
        $crstsData = new CrstsData();
        $crstsData->setRetained($retained);

        $scheme = new Scheme();
        $scheme->setCrstsData($crstsData);

        $fundAward = new FundAward();

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setQuarter($quarter);
        $fundReturn->setFundAward($fundAward);

        $schemeReturn = new CrstsSchemeReturn();
        $schemeReturn->setScheme($scheme);
        $schemeReturn->setFundReturn($fundReturn);

        return $schemeReturn;
    }
}