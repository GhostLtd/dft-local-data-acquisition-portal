<?php

namespace App\Tests\Utility\SchemeReturnHelper;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\FundReturn\FundReturn;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Utility\SchemeReturnHelper\SchemeReturnHelper;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SchemeReturnHelperTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var ClassMetadataFactory&MockObject */
    private ClassMetadataFactory $metadataFactory;
    /** @var UnitOfWork&MockObject */
    private UnitOfWork $unitOfWork;
    private SchemeReturnHelper $helper;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);

        $this->entityManager->method('getMetadataFactory')->willReturn($this->metadataFactory);
        $this->entityManager->method('getUnitOfWork')->willReturn($this->unitOfWork);

        $this->helper = new SchemeReturnHelper();
        $this->helper->setEntityManager($this->entityManager);
    }

    public function testSchemeAddedToFundsCallsAddForEachFund(): void
    {
        $scheme = $this->createMock(Scheme::class);
        $funds = [Fund::CRSTS1];

        $authority = $this->createMock(Authority::class);
        $fundAward = $this->createMock(FundAward::class);
        $fundReturn = $this->createMock(FundReturn::class);

        $scheme->method('getAuthority')->willReturn($authority);
        $authority->method('getFundAwards')->willReturn(new ArrayCollection([$fundAward]));
        $fundAward->method('getType')->willReturn(Fund::CRSTS1);
        $fundAward->method('getReturns')->willReturn(new ArrayCollection([$fundReturn]));
        $fundReturn->method('isSignedOff')->willReturn(false);
        $fundReturn->expects($this->once())->method('addSchemeReturn');

        $metadata = $this->createMock(ClassMetadata::class);
        $this->metadataFactory->method('getMetadataFor')->willReturn($metadata);

        $this->helper->schemeAddedToFunds($scheme, $funds);
    }

    public function testSchemeRemovedFromFundsCallsRemoveForEachFund(): void
    {
        $scheme = $this->createMock(Scheme::class);
        $funds = [Fund::CRSTS1];

        $authority = $this->createMock(Authority::class);
        $fundAward = $this->createMock(FundAward::class);
        $fundReturn = $this->createMock(FundReturn::class);
        $schemeReturn = $this->createMock(CrstsSchemeReturn::class);

        $scheme->method('getAuthority')->willReturn($authority);
        $authority->method('getFundAwards')->willReturn(new ArrayCollection([$fundAward]));
        $fundAward->method('getType')->willReturn(Fund::CRSTS1);
        $fundAward->method('getReturns')->willReturn(new ArrayCollection([$fundReturn]));
        $fundReturn->method('isSignedOff')->willReturn(false);
        $fundReturn->method('getSchemeReturns')->willReturn(new ArrayCollection([$schemeReturn]));
        $schemeReturn->method('getScheme')->willReturn($scheme);

        $fundReturn->expects($this->once())->method('removeSchemeReturn')->with($schemeReturn);
        $this->entityManager->expects($this->once())->method('remove')->with($schemeReturn);

        $this->helper->schemeRemovedFromFunds($scheme, $funds);
    }

    /**
     * @dataProvider unsupportedFundProvider
     */
    public function testUnsupportedFundThrowsException(string $method, Fund $unsupportedFund): void
    {
        $scheme = $this->createMock(Scheme::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unsupported fund');

        $this->helper->$method($scheme, $unsupportedFund);
    }

    public function unsupportedFundProvider(): array
    {
        return [
            'schemeAddedToFund with CRSTS2' => ['schemeAddedToFund', Fund::CRSTS2],
            'schemeRemoveFromFund with CRSTS2' => ['schemeRemoveFromFund', Fund::CRSTS2],
        ];
    }

    public function testSchemeAddedToCrsts1CreatesSchemeReturnsForOpenReturns(): void
    {
        $scheme = $this->createMock(Scheme::class);
        $authority = $this->createMock(Authority::class);
        $fundAward = $this->createMock(FundAward::class);
        $fundReturn = $this->createMock(FundReturn::class);

        $scheme->method('getAuthority')->willReturn($authority);
        $authority->method('getFundAwards')->willReturn(new ArrayCollection([$fundAward]));
        $fundAward->method('getType')->willReturn(Fund::CRSTS1);
        $fundAward->method('getReturns')->willReturn(new ArrayCollection([$fundReturn]));
        $fundReturn->method('isSignedOff')->willReturn(false);

        $fundReturn->expects($this->once())->method('addSchemeReturn')
            ->with($this->isInstanceOf(CrstsSchemeReturn::class));

        $metadata = $this->createMock(ClassMetadata::class);
        $this->metadataFactory->method('getMetadataFor')->willReturn($metadata);
        $this->entityManager->expects($this->once())->method('persist');
        $this->unitOfWork->expects($this->once())->method('computeChangeSet');
        $this->unitOfWork->expects($this->once())->method('recomputeSingleEntityChangeSet');

        $this->helper->schemeAddedToFund($scheme, Fund::CRSTS1);
    }

    public function testSchemeRemovedFromCrsts1RemovesMatchingSchemeReturns(): void
    {
        $scheme = $this->createMock(Scheme::class);
        $authority = $this->createMock(Authority::class);
        $fundAward = $this->createMock(FundAward::class);
        $fundReturn = $this->createMock(FundReturn::class);
        $schemeReturn = $this->createMock(CrstsSchemeReturn::class);

        $scheme->method('getAuthority')->willReturn($authority);
        $authority->method('getFundAwards')->willReturn(new ArrayCollection([$fundAward]));
        $fundAward->method('getType')->willReturn(Fund::CRSTS1);
        $fundAward->method('getReturns')->willReturn(new ArrayCollection([$fundReturn]));
        $fundReturn->method('isSignedOff')->willReturn(false);
        $fundReturn->method('getSchemeReturns')->willReturn(new ArrayCollection([$schemeReturn]));
        $schemeReturn->method('getScheme')->willReturn($scheme);

        $fundReturn->expects($this->once())->method('removeSchemeReturn')->with($schemeReturn);
        $this->entityManager->expects($this->once())->method('remove')->with($schemeReturn);

        $this->helper->schemeRemoveFromFund($scheme, Fund::CRSTS1);
    }

    public function testGetUnsubmittedReturnsForSchemeAndFundFiltersSignedOffReturns(): void
    {
        $scheme = $this->createMock(Scheme::class);
        $authority = $this->createMock(Authority::class);
        $fundAward = $this->createMock(FundAward::class);
        $signedOffReturn = $this->createMock(FundReturn::class);
        $openReturn = $this->createMock(FundReturn::class);

        $scheme->method('getAuthority')->willReturn($authority);
        $authority->method('getFundAwards')->willReturn(new ArrayCollection([$fundAward]));
        $fundAward->method('getType')->willReturn(Fund::CRSTS1);
        $fundAward->method('getReturns')->willReturn(new ArrayCollection([$signedOffReturn, $openReturn]));

        $signedOffReturn->method('isSignedOff')->willReturn(true);
        $openReturn->method('isSignedOff')->willReturn(false);

        $result = $this->helper->getUnsubmittedReturnsForSchemeAndFund($scheme, Fund::CRSTS1);

        $this->assertCount(1, $result);
        $this->assertContains($openReturn, $result);
        $this->assertNotContains($signedOffReturn, $result);
    }

    public function testGetReturnsForSchemeAndFundReturnsEmptyWhenNoFundAward(): void
    {
        $scheme = $this->createMock(Scheme::class);
        $authority = $this->createMock(Authority::class);

        $scheme->method('getAuthority')->willReturn($authority);
        $authority->method('getFundAwards')->willReturn(new ArrayCollection([]));

        $result = $this->helper->getReturnsForSchemeAndFund($scheme, Fund::CRSTS1);

        $this->assertEmpty($result);
    }

    public function testGetFundAwardForSchemeAndFundReturnsMatchingAward(): void
    {
        $scheme = $this->createMock(Scheme::class);
        $authority = $this->createMock(Authority::class);
        $crsts1Award = $this->createMock(FundAward::class);
        $crsts2Award = $this->createMock(FundAward::class);

        $scheme->method('getAuthority')->willReturn($authority);
        $authority->method('getFundAwards')->willReturn(new ArrayCollection([$crsts1Award, $crsts2Award]));
        $crsts1Award->method('getType')->willReturn(Fund::CRSTS1);
        $crsts2Award->method('getType')->willReturn(Fund::CRSTS2);

        $result = $this->helper->getFundAwardForSchemeAndFund($scheme, Fund::CRSTS1);

        $this->assertSame($crsts1Award, $result);
    }

    public function testGetFundAwardForSchemeAndFundReturnsNullWhenNoMatch(): void
    {
        $scheme = $this->createMock(Scheme::class);
        $authority = $this->createMock(Authority::class);
        $crsts2Award = $this->createMock(FundAward::class);

        $scheme->method('getAuthority')->willReturn($authority);
        $authority->method('getFundAwards')->willReturn(new ArrayCollection([$crsts2Award]));
        $crsts2Award->method('getType')->willReturn(Fund::CRSTS2);

        $result = $this->helper->getFundAwardForSchemeAndFund($scheme, Fund::CRSTS1);

        $this->assertNull($result);
    }
}
