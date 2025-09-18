<?php

namespace App\Tests\Utility\SignoffHelper;

use App\Entity\Enum\OnTrackRating;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\Scheme;
use App\Repository\SchemeReturn\SchemeReturnRepository;
use App\Utility\SignoffHelper\CrstsSignoffHelper;
use App\Utility\SignoffHelper\EligibilityProblemType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\Ulid;

class CrstsSignoffHelperTest extends TestCase
{
    /** @var SchemeReturnRepository&MockObject */
    private SchemeReturnRepository $schemeReturnRepository;
    /** @var UrlGeneratorInterface&MockObject */
    private UrlGeneratorInterface $urlGenerator;
    private CrstsSignoffHelper $helper;

    protected function setUp(): void
    {
        $this->schemeReturnRepository = $this->createMock(SchemeReturnRepository::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->helper = new CrstsSignoffHelper($this->schemeReturnRepository, $this->urlGenerator);
    }

    public function testSetUseAdminLinks(): void
    {
        $result = $this->helper->setUseAdminLinks(true);
        $this->assertSame($this->helper, $result);
    }

    public function testSupportsCrstsFundReturn(): void
    {
        $fundReturn = new CrstsFundReturn();
        $this->assertTrue($this->helper->supports($fundReturn));
    }

    public function testSupportsCrstsSchemeReturn(): void
    {
        $schemeReturn = new CrstsSchemeReturn();
        $this->assertTrue($this->helper->supports($schemeReturn));
    }

    public function testDoesNotSupportOtherReturnTypes(): void
    {
        $unsupportedReturn = $this->createMock(FundReturn::class);
        $this->assertFalse($this->helper->supports($unsupportedReturn));
    }


    public function testSchemeReturnWithMissingOnTrackRating(): void
    {
        $schemeReturn = $this->createSchemeReturnWithoutOnTrackRating();

        $this->schemeReturnRepository
            ->method('cachedFindPointWhereReturnBecameNonEditable')
            ->with($schemeReturn)
            ->willReturn(null);

        $this->urlGenerator
            ->method('generate')
            ->willReturn('/scheme-return/123');

        $status = $this->helper->getSignoffEligibilityStatus($schemeReturn);

        $this->assertFalse($status->isEligible);
        $this->assertCount(1, $status->problems);

        $problem = $status->problems[0];
        $this->assertEquals(EligibilityProblemType::ON_TRACK_RATING_EMPTY, $problem->type);
        $this->assertEquals('eligibility.scheme_return.name', $problem->message);
        $this->assertEquals('Test Scheme', $problem->messageParameters['schemeName']);
    }

    public function testSchemeReturnWithOnTrackRatingIsEligible(): void
    {
        $schemeReturn = $this->createSchemeReturnWithOnTrackRating();

        $this->schemeReturnRepository
            ->method('cachedFindPointWhereReturnBecameNonEditable')
            ->with($schemeReturn)
            ->willReturn(null);

        $status = $this->helper->getSignoffEligibilityStatus($schemeReturn);

        $this->assertTrue($status->isEligible);
        $this->assertEmpty($status->problems);
    }

    public function testSchemeReturnThatBecameNonEditableIsSkipped(): void
    {
        $schemeReturn = $this->createSchemeReturnWithoutOnTrackRating();
        $nonEditablePoint = new CrstsSchemeReturn();

        $this->schemeReturnRepository
            ->method('cachedFindPointWhereReturnBecameNonEditable')
            ->with($schemeReturn)
            ->willReturn($nonEditablePoint);

        $status = $this->helper->getSignoffEligibilityStatus($schemeReturn);

        $this->assertTrue($status->isEligible);
        $this->assertEmpty($status->problems);
    }

    public function testHasSignoffEligibilityProblems(): void
    {
        $schemeReturnWithProblems = $this->createSchemeReturnWithoutOnTrackRating();
        $schemeReturnWithoutProblems = $this->createSchemeReturnWithOnTrackRating();

        $this->schemeReturnRepository
            ->method('cachedFindPointWhereReturnBecameNonEditable')
            ->willReturn(null);

        $this->assertTrue($this->helper->hasSignoffEligibilityProblems($schemeReturnWithProblems));
        $this->assertFalse($this->helper->hasSignoffEligibilityProblems($schemeReturnWithoutProblems));
    }

    public function testUsesCorrectRoutesBasedOnAdminLinksFlag(): void
    {
        $schemeReturn = $this->createSchemeReturnWithoutOnTrackRating();

        $this->schemeReturnRepository
            ->method('cachedFindPointWhereReturnBecameNonEditable')
            ->willReturn(null);

        // Test admin links
        $this->helper->setUseAdminLinks(true);
        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('admin_scheme_return', $this->anything())
            ->willReturn('/admin/scheme-return/123');

        $this->helper->getSignoffEligibilityStatus($schemeReturn);

        // Reset for regular links test
        $this->setUp(); // Reset mocks
        $schemeReturn = $this->createSchemeReturnWithoutOnTrackRating();
        $this->schemeReturnRepository
            ->method('cachedFindPointWhereReturnBecameNonEditable')
            ->willReturn(null);

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('app_scheme_return', $this->anything())
            ->willReturn('/scheme-return/123');

        $this->helper->getSignoffEligibilityStatus($schemeReturn);
    }

    public function testThrowsExceptionForUnsupportedReturnType(): void
    {
        $unsupportedReturn = $this->createMock(FundReturn::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Expected.*got/');

        $this->helper->getSignoffEligibilityStatus($unsupportedReturn);
    }

    private function createSchemeReturnWithOnTrackRating(): CrstsSchemeReturn
    {
        $scheme = new Scheme();
        $scheme->setId(new Ulid());
        $scheme->setName('Test Scheme');

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setId(new Ulid());
        $fundReturn->setYear(2024);
        $fundReturn->setQuarter(1);

        $schemeReturn = new CrstsSchemeReturn();
        $schemeReturn->setScheme($scheme);
        $schemeReturn->setFundReturn($fundReturn);
        $schemeReturn->setOnTrackRating(OnTrackRating::GREEN);

        return $schemeReturn;
    }

    private function createSchemeReturnWithoutOnTrackRating(): CrstsSchemeReturn
    {
        $scheme = new Scheme();
        $scheme->setId(new Ulid());
        $scheme->setName('Test Scheme');

        $fundReturn = new CrstsFundReturn();
        $fundReturn->setId(new Ulid());
        $fundReturn->setYear(2024);
        $fundReturn->setQuarter(1);

        $schemeReturn = new CrstsSchemeReturn();
        $schemeReturn->setScheme($scheme);
        $schemeReturn->setFundReturn($fundReturn);
        $schemeReturn->setOnTrackRating(null);

        return $schemeReturn;
    }
}