<?php

namespace App\Tests\Security\Voter;

use App\Entity\Enum\InternalRole;
use App\Entity\FundAward;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Repository\SchemeReturn\SchemeReturnRepository;
use App\Security\SubjectResolver;
use App\Security\Voter\Internal\DenyBasedUponOnTrackRatingVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class DenyBasedUponOnTrackRatingVoterTest extends TestCase
{
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;
    /** @var SubjectResolver&MockObject */
    private SubjectResolver $subjectResolver;
    /** @var SchemeReturnRepository&MockObject */
    private SchemeReturnRepository $schemeReturnRepository;
    private DenyBasedUponOnTrackRatingVoter $voter;
    /** @var TokenInterface&MockObject */
    private TokenInterface $token;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->subjectResolver = $this->createMock(SubjectResolver::class);
        $this->schemeReturnRepository = $this->createMock(SchemeReturnRepository::class);
        $this->voter = new DenyBasedUponOnTrackRatingVoter(
            $this->logger,
            $this->subjectResolver,
            $this->schemeReturnRepository
        );
        $this->token = $this->createMock(TokenInterface::class);
    }

    public function testSupportsCorrectAttributesAndSubject(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn();

        $supportedAttributes = [
            InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION,
            InternalRole::HAS_VALID_EDIT_PERMISSION,
        ];

        foreach ($supportedAttributes as $attribute) {
            $this->subjectResolver
                ->expects($this->any())
                ->method('isValidSubjectForInternalRole')
                ->willReturn(true);

            $this->schemeReturnRepository
                ->expects($this->any())
                ->method('cachedFindPointWhereReturnBecameNonEditable')
                ->willReturn(null);

            $result = $this->voter->vote($this->token, $schemeReturn, [$attribute]);
            $this->assertNotEquals(VoterInterface::ACCESS_ABSTAIN, $result,
                "Should support attribute: {$attribute}");
        }
    }

    public function testDoesNotSupportWrongAttribute(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn();

        $result = $this->voter->vote($this->token, $schemeReturn, ['UNSUPPORTED_ATTRIBUTE']);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWrongSubject(): void
    {
        $notSchemeReturn = new \stdClass();

        $result = $this->voter->vote($this->token, $notSchemeReturn, [InternalRole::HAS_VALID_EDIT_PERMISSION]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testDoesNotSupportWhenSubjectResolverReturnsFalse(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn();

        $this->subjectResolver
            ->expects($this->once())
            ->method('isValidSubjectForInternalRole')
            ->with($schemeReturn, InternalRole::HAS_VALID_EDIT_PERMISSION)
            ->willReturn(false);

        $result = $this->voter->vote($this->token, $schemeReturn, [InternalRole::HAS_VALID_EDIT_PERMISSION]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testGrantsAccessWhenReturnIsEditable(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn();

        $this->subjectResolver
            ->expects($this->once())
            ->method('isValidSubjectForInternalRole')
            ->with($schemeReturn, InternalRole::HAS_VALID_EDIT_PERMISSION)
            ->willReturn(true);

        $this->schemeReturnRepository
            ->expects($this->once())
            ->method('cachedFindPointWhereReturnBecameNonEditable')
            ->with($schemeReturn)
            ->willReturn(null);

        $result = $this->voter->vote($this->token, $schemeReturn, [InternalRole::HAS_VALID_EDIT_PERMISSION]);

        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeniesAccessWhenReturnBecameNonEditable(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn();
        $nonEditablePoint = $this->createCrstsSchemeReturn(); // Some return that represents when it became non-editable

        $this->subjectResolver
            ->expects($this->once())
            ->method('isValidSubjectForInternalRole')
            ->with($schemeReturn, InternalRole::HAS_VALID_EDIT_PERMISSION)
            ->willReturn(true);

        $this->schemeReturnRepository
            ->expects($this->once())
            ->method('cachedFindPointWhereReturnBecameNonEditable')
            ->with($schemeReturn)
            ->willReturn($nonEditablePoint);

        $result = $this->voter->vote($this->token, $schemeReturn, [InternalRole::HAS_VALID_EDIT_PERMISSION]);

        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    /**
     * @dataProvider editabilityDataProvider
     */
    public function testEditabilityLogic(bool $hasNonEditablePoint, int $expectedResult): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn();
        $nonEditablePoint = $hasNonEditablePoint ? $this->createCrstsSchemeReturn() : null;

        $this->subjectResolver
            ->expects($this->once())
            ->method('isValidSubjectForInternalRole')
            ->willReturn(true);

        $this->schemeReturnRepository
            ->expects($this->once())
            ->method('cachedFindPointWhereReturnBecameNonEditable')
            ->with($schemeReturn)
            ->willReturn($nonEditablePoint);

        $result = $this->voter->vote($this->token, $schemeReturn, [InternalRole::HAS_VALID_EDIT_PERMISSION]);

        $this->assertEquals($expectedResult, $result);
    }

    public function editabilityDataProvider(): array
    {
        return [
            'No non-editable point - grant access' => [false, VoterInterface::ACCESS_GRANTED],
            'Has non-editable point - deny access' => [true, VoterInterface::ACCESS_DENIED],
        ];
    }

    public function testHandlesBothSupportedAttributes(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn();

        $attributes = [
            InternalRole::HAS_VALID_MARK_AS_READY_PERMISSION,
            InternalRole::HAS_VALID_EDIT_PERMISSION,
        ];

        foreach ($attributes as $attribute) {
            $this->subjectResolver
                ->expects($this->any())
                ->method('isValidSubjectForInternalRole')
                ->willReturn(true);

            $this->schemeReturnRepository
                ->expects($this->any())
                ->method('cachedFindPointWhereReturnBecameNonEditable')
                ->willReturn(null);

            $result = $this->voter->vote($this->token, $schemeReturn, [$attribute]);

            $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result,
                "Should handle attribute: {$attribute}");
        }
    }

    public function testUsesCorrectRepositoryMethod(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn();

        $this->subjectResolver
            ->expects($this->once())
            ->method('isValidSubjectForInternalRole')
            ->willReturn(true);

        $this->schemeReturnRepository
            ->expects($this->once())
            ->method('cachedFindPointWhereReturnBecameNonEditable')
            ->with($this->identicalTo($schemeReturn))
            ->willReturn(null);

        $this->voter->vote($this->token, $schemeReturn, [InternalRole::HAS_VALID_EDIT_PERMISSION]);
    }

    public function testOnlyChecksRepositoryForSupportedCombinations(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn();

        // Should not call repository for unsupported combinations
        $this->schemeReturnRepository
            ->expects($this->never())
            ->method('cachedFindPointWhereReturnBecameNonEditable');

        // Unsupported attribute
        $result1 = $this->voter->vote($this->token, $schemeReturn, ['UNSUPPORTED_ATTRIBUTE']);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result1);

        // Unsupported subject
        $result2 = $this->voter->vote($this->token, new \stdClass(), [InternalRole::HAS_VALID_EDIT_PERMISSION]);
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result2);
    }

    public function testRequiresSubjectResolverValidation(): void
    {
        $schemeReturn = $this->createCrstsSchemeReturn();

        // When subject resolver returns false, voter should abstain
        $this->subjectResolver
            ->expects($this->once())
            ->method('isValidSubjectForInternalRole')
            ->with($schemeReturn, InternalRole::HAS_VALID_EDIT_PERMISSION)
            ->willReturn(false);

        // Repository should not be called if subject resolver validation fails
        $this->schemeReturnRepository
            ->expects($this->never())
            ->method('cachedFindPointWhereReturnBecameNonEditable');

        $result = $this->voter->vote($this->token, $schemeReturn, [InternalRole::HAS_VALID_EDIT_PERMISSION]);

        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    private function createCrstsSchemeReturn(): CrstsSchemeReturn
    {
        $scheme = new Scheme();

        $fundAward = new FundAward();

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setFundAward($fundAward);

        $schemeReturn = new CrstsSchemeReturn();
        $schemeReturn->setScheme($scheme);
        $schemeReturn->setFundReturn($fundReturn);

        return $schemeReturn;
    }
}