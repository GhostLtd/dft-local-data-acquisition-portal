<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\Fund;
use App\Entity\Enum\Role;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Repository\FundReturn\FundReturnRepository;
use App\Security\Voter\External\SchemeCriticalFieldVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Uid\Ulid;

class SchemeCriticalFieldVoterTest extends TestCase
{
    /** @var FundReturnRepository&MockObject */
    private FundReturnRepository $fundReturnRepository;
    private SchemeCriticalFieldVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->fundReturnRepository = $this->createMock(FundReturnRepository::class);
        $this->voter = new SchemeCriticalFieldVoter($this->fundReturnRepository);
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributesAndSubject(): void
    {
        $scheme = new Scheme();

        $supportedAttributes = [
            Role::CAN_DELETE_SCHEME,
            Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS,
            Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS,
            Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME,
        ];

        foreach ($supportedAttributes as $attribute) {
            $result = $this->voter->vote($this->token, $scheme, [$attribute]);
            $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result,
                "Should support attribute: {$attribute}");
        }
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $scheme = new Scheme();

        $result = $this->voter->vote($this->token, $scheme, [Role::CAN_VIEW]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notScheme = new \stdClass();

        $result = $this->voter->vote($this->token, $notScheme, [Role::CAN_DELETE_SCHEME]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testGrantsAccessWhenNoReturnsSignedOff(): void
    {
        $scheme = new Scheme();
        $scheme->setId(new Ulid());

        $this->fundReturnRepository
            ->expects($this->once())
            ->method('findFundReturnsContainingScheme')
            ->with($scheme)
            ->willReturn([]);

        $result = $this->voter->vote($this->token, $scheme, [Role::CAN_DELETE_SCHEME]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeniesAccessWhenAnyReturnSignedOff(): void
    {
        $scheme = new Scheme();
        $scheme->setId(new Ulid());

        $signedOffReturn = $this->createFundReturn(Fund::CRSTS1, FundReturn::STATE_SUBMITTED);
        $this->fundReturnRepository
            ->expects($this->once())
            ->method('findFundReturnsContainingScheme')
            ->with($scheme)
            ->willReturn([$signedOffReturn]);

        $result = $this->voter->vote($this->token, $scheme, [Role::CAN_DELETE_SCHEME]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testGrantsAccessWhenReturnsNotSignedOff(): void
    {
        $scheme = new Scheme();
        $scheme->setId(new Ulid());

        $openReturn = $this->createFundReturn(Fund::CRSTS1, FundReturn::STATE_OPEN);
        $initialReturn = $this->createFundReturn(Fund::CRSTS1, FundReturn::STATE_INITIAL);

        $this->fundReturnRepository
            ->expects($this->once())
            ->method('findFundReturnsContainingScheme')
            ->with($scheme)
            ->willReturn([$openReturn, $initialReturn]);

        $result = $this->voter->vote($this->token, $scheme, [Role::CAN_DELETE_SCHEME]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testCRSTSSpecificAttributesCheckOnlyCRSTSFund(): void
    {
        $scheme = new Scheme();
        $scheme->setId(new Ulid());

        // Only create one CRSTS return that's not signed off
        $crstsReturn = $this->createFundReturn(Fund::CRSTS1, FundReturn::STATE_OPEN);

        $this->fundReturnRepository
            ->expects($this->once())
            ->method('findFundReturnsContainingScheme')
            ->with($scheme)
            ->willReturn([$crstsReturn]);

        // CRSTS-specific attribute should grant access (CRSTS1 not signed off)
        $result = $this->voter->vote($this->token, $scheme, [Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testCRSTSSpecificAttributesDenyWhenCRSTSSignedOff(): void
    {
        $scheme = new Scheme();
        $scheme->setId(new Ulid());

        $crstsSignedOffReturn = $this->createFundReturn(Fund::CRSTS1, FundReturn::STATE_SUBMITTED);

        $this->fundReturnRepository
            ->expects($this->once())
            ->method('findFundReturnsContainingScheme')
            ->with($scheme)
            ->willReturn([$crstsSignedOffReturn]);

        // CRSTS-specific attribute should deny access
        $result = $this->voter->vote($this->token, $scheme, [Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testGeneralAttributesCheckAllFunds(): void
    {
        $scheme = new Scheme();
        $scheme->setId(new Ulid());

        $openReturn = $this->createFundReturn(Fund::CRSTS1, FundReturn::STATE_OPEN);
        $signedOffReturn = $this->createFundReturn(Fund::CRSTS1, FundReturn::STATE_SUBMITTED);

        $this->fundReturnRepository
            ->expects($this->once())
            ->method('findFundReturnsContainingScheme')
            ->with($scheme)
            ->willReturn([$openReturn, $signedOffReturn]);

        // General attribute should deny access (any fund signed off)
        $result = $this->voter->vote($this->token, $scheme, [Role::CAN_DELETE_SCHEME]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testCaching(): void
    {
        $scheme = new Scheme();
        $scheme->setId(new Ulid());

        $mockReturn = $this->createFundReturn(Fund::CRSTS1, FundReturn::STATE_OPEN);

        // Repository should only be called once due to caching
        $this->fundReturnRepository
            ->expects($this->once())
            ->method('findFundReturnsContainingScheme')
            ->with($scheme)
            ->willReturn([$mockReturn]);

        // First call
        $result1 = $this->voter->vote($this->token, $scheme, [Role::CAN_DELETE_SCHEME]);

        // Second call should use cached result
        $result2 = $this->voter->vote($this->token, $scheme, [Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result1);
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result2);
    }

    public function testHandlesSchemeWithoutId(): void
    {
        $scheme = new Scheme(); // New scheme has no ID

        // Should grant access for scheme without ID (new scheme)
        $result = $this->voter->vote($this->token, $scheme, [Role::CAN_DELETE_SCHEME]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    /**
     * @dataProvider attributeDataProvider
     */
    public function testAllSupportedAttributes(string $attribute, bool $hasSignedOffReturns, int $expectedResult): void
    {
        $scheme = new Scheme();
        $scheme->setId(new Ulid());

        if ($hasSignedOffReturns) {
            $signedOffReturn = $this->createFundReturn(Fund::CRSTS1, FundReturn::STATE_SUBMITTED);
            $returns = [$signedOffReturn];
        } else {
            $openReturn = $this->createFundReturn(Fund::CRSTS1, FundReturn::STATE_OPEN);
            $returns = [$openReturn];
        }

        $this->fundReturnRepository
            ->method('findFundReturnsContainingScheme')
            ->with($scheme)
            ->willReturn($returns);

        $result = $this->voter->vote($this->token, $scheme, [$attribute]);

        $this->assertEquals($expectedResult, $result);
    }

    public function attributeDataProvider(): array
    {
        return [
            'CAN_DELETE_SCHEME - no signoffs' => [Role::CAN_DELETE_SCHEME, false, VoterInterface::ACCESS_GRANTED],
            'CAN_DELETE_SCHEME - with signoffs' => [Role::CAN_DELETE_SCHEME, true, VoterInterface::ACCESS_DENIED],
            'CAN_EDIT_CRITICAL_SCHEME_FIELDS - no signoffs' => [Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS, false, VoterInterface::ACCESS_GRANTED],
            'CAN_EDIT_CRITICAL_SCHEME_FIELDS - with signoffs' => [Role::CAN_EDIT_CRITICAL_SCHEME_FIELDS, true, VoterInterface::ACCESS_DENIED],
            'CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS - no signoffs' => [Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS, false, VoterInterface::ACCESS_GRANTED],
            'CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS - with signoffs' => [Role::CAN_EDIT_CRITICAL_CRSTS_SCHEME_FIELDS, true, VoterInterface::ACCESS_DENIED],
            'CAN_REMOVE_CRSTS_FUND_FROM_SCHEME - no signoffs' => [Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME, false, VoterInterface::ACCESS_GRANTED],
            'CAN_REMOVE_CRSTS_FUND_FROM_SCHEME - with signoffs' => [Role::CAN_REMOVE_CRSTS_FUND_FROM_SCHEME, true, VoterInterface::ACCESS_DENIED],
        ];
    }

    private function createFundReturn(Fund $fund, string $state): CrstsFundReturn
    {
        $fundAward = new FundAward();
        $fundAward->setType($fund);

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setState($state);
        $fundReturn->setFundAward($fundAward);

        return $fundReturn;
    }
}