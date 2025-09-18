<?php

namespace App\Tests\EventListener;

use App\Entity\Enum\Fund;
use App\Entity\Scheme;
use App\EventListener\SchemeFundChangeListener;
use App\Utility\SchemeReturnHelper\SchemeReturnHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SchemeFundChangeListenerTest extends TestCase
{
    /** @var SchemeReturnHelper&MockObject */
    private SchemeReturnHelper $schemeReturnHelper;
    private SchemeFundChangeListener $listener;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;
    /** @var UnitOfWork&MockObject */
    private UnitOfWork $unitOfWork;
    /** @var OnFlushEventArgs&MockObject */
    private OnFlushEventArgs $args;

    protected function setUp(): void
    {
        $this->schemeReturnHelper = $this->createMock(SchemeReturnHelper::class);
        $this->listener = new SchemeFundChangeListener($this->schemeReturnHelper);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->args = $this->createMock(OnFlushEventArgs::class);

        $this->args->method('getObjectManager')->willReturn($this->entityManager);
        $this->entityManager->method('getUnitOfWork')->willReturn($this->unitOfWork);
    }

    public function testSetsEntityManagerOnHelper(): void
    {
        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        $this->schemeReturnHelper
            ->expects($this->once())
            ->method('setEntityManager')
            ->with($this->entityManager);

        $this->listener->onFlush($this->args);
    }

    public function testSchemeInsertionCallsHelperWithAllFunds(): void
    {
        $scheme = new Scheme();
        $scheme->setFunds([Fund::CRSTS1]);

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([$scheme]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        $this->schemeReturnHelper
            ->expects($this->once())
            ->method('schemeAddedToFunds')
            ->with($scheme, [Fund::CRSTS1]);

        $this->listener->onFlush($this->args);
    }

    public function testSchemeInsertionIgnoresNonSchemeEntities(): void
    {
        $nonScheme = new \stdClass();

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([$nonScheme]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        $this->schemeReturnHelper
            ->expects($this->never())
            ->method('schemeAddedToFunds');

        $this->listener->onFlush($this->args);
    }

    public function testSchemeUpdateWithFundChangesCallsHelper(): void
    {
        $scheme = new Scheme();

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([$scheme]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        // Mock change set: from ['CRSTS1'] to ['CRSTS1', 'CRSTS2']
        $changeSet = [
            'funds' => [['CRSTS1'], ['CRSTS1', 'CRSTS2']]
        ];
        $this->unitOfWork
            ->method('getEntityChangeSet')
            ->with($scheme)
            ->willReturn($changeSet);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        $this->schemeReturnHelper
            ->expects($this->once())
            ->method('schemeAddedToFunds')
            ->with($scheme, $this->callback(function($funds) {
                return count($funds) === 1 && in_array(Fund::CRSTS2, $funds);
            }));

        $this->schemeReturnHelper
            ->expects($this->once())
            ->method('schemeRemovedFromFunds')
            ->with($scheme, $this->callback(function($funds) {
                return count($funds) === 0; // No funds removed
            }));

        $this->listener->onFlush($this->args);
    }

    public function testSchemeUpdateWithRemovedFundsCallsHelper(): void
    {
        $scheme = new Scheme();

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([$scheme]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        // Mock change set: from ['CRSTS1', 'CRSTS2'] to ['CRSTS1']
        $changeSet = [
            'funds' => [['CRSTS1', 'CRSTS2'], ['CRSTS1']]
        ];
        $this->unitOfWork
            ->method('getEntityChangeSet')
            ->with($scheme)
            ->willReturn($changeSet);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        $this->schemeReturnHelper
            ->expects($this->once())
            ->method('schemeAddedToFunds')
            ->with($scheme, $this->callback(function($funds) {
                return count($funds) === 0; // No funds added
            }));

        $this->schemeReturnHelper
            ->expects($this->once())
            ->method('schemeRemovedFromFunds')
            ->with($scheme, $this->callback(function($funds) {
                return count($funds) === 1 && in_array(Fund::CRSTS2, $funds);
            }));

        $this->listener->onFlush($this->args);
    }

    public function testSchemeUpdateWithBothAddedAndRemovedFunds(): void
    {
        $scheme = new Scheme();

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([$scheme]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        // Mock change set: from ['CRSTS1'] to ['CRSTS2'] (complete replacement)
        $changeSet = [
            'funds' => [['CRSTS1'], ['CRSTS2']]
        ];
        $this->unitOfWork
            ->method('getEntityChangeSet')
            ->with($scheme)
            ->willReturn($changeSet);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        $this->schemeReturnHelper
            ->expects($this->once())
            ->method('schemeAddedToFunds')
            ->with($scheme, $this->callback(function($funds) {
                return count($funds) === 1 && in_array(Fund::CRSTS2, $funds);
            }));

        $this->schemeReturnHelper
            ->expects($this->once())
            ->method('schemeRemovedFromFunds')
            ->with($scheme, $this->callback(function($funds) {
                return count($funds) === 1 && in_array(Fund::CRSTS1, $funds);
            }));

        $this->listener->onFlush($this->args);
    }

    public function testSchemeUpdateWithoutFundChangesDoesNothing(): void
    {
        $scheme = new Scheme();

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([$scheme]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        // Mock change set without fund changes
        $changeSet = [
            'name' => ['Old Name', 'New Name']
        ];
        $this->unitOfWork
            ->method('getEntityChangeSet')
            ->with($scheme)
            ->willReturn($changeSet);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        $this->schemeReturnHelper
            ->expects($this->never())
            ->method('schemeAddedToFunds');

        $this->schemeReturnHelper
            ->expects($this->never())
            ->method('schemeRemovedFromFunds');

        $this->listener->onFlush($this->args);
    }

    public function testSchemeUpdateIgnoresNonSchemeEntities(): void
    {
        $nonScheme = new \stdClass();

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([$nonScheme]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        $this->unitOfWork
            ->expects($this->never())
            ->method('getEntityChangeSet');

        $this->schemeReturnHelper
            ->expects($this->never())
            ->method('schemeAddedToFunds');

        $this->listener->onFlush($this->args);
    }

    public function testSchemeDeletionCallsHelperWithAllFunds(): void
    {
        $scheme = new Scheme();
        $scheme->setFunds([Fund::CRSTS1]);

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([$scheme]);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        $this->schemeReturnHelper
            ->expects($this->once())
            ->method('schemeRemovedFromFunds')
            ->with($scheme, [Fund::CRSTS1]);

        $this->listener->onFlush($this->args);
    }

    public function testSchemeDeletionIgnoresNonSchemeEntities(): void
    {
        $nonScheme = new \stdClass();

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([$nonScheme]);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        $this->schemeReturnHelper
            ->expects($this->never())
            ->method('schemeRemovedFromFunds');

        $this->listener->onFlush($this->args);
    }

    public function testHandlesMultipleEntitiesInSingleFlush(): void
    {
        $insertedScheme = new Scheme();
        $insertedScheme->setFunds([Fund::CRSTS1]);

        $updatedScheme = new Scheme();
        $deletedScheme = new Scheme();
        $deletedScheme->setFunds([Fund::CRSTS1]);

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([$insertedScheme]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([$updatedScheme]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([$deletedScheme]);

        $changeSet = [
            'funds' => [[], ['CRSTS1']]
        ];
        $this->unitOfWork
            ->method('getEntityChangeSet')
            ->with($updatedScheme)
            ->willReturn($changeSet);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        // Should handle insertion and update
        $this->schemeReturnHelper
            ->expects($this->exactly(2))
            ->method('schemeAddedToFunds')
            ->willReturnCallback(function($scheme, $funds) use ($insertedScheme, $updatedScheme) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertSame($insertedScheme, $scheme);
                    $this->assertCount(1, $funds);
                    $this->assertContains(Fund::CRSTS1, $funds);
                } else {
                    $this->assertSame($updatedScheme, $scheme);
                    $this->assertCount(1, $funds);
                    $this->assertContains(Fund::CRSTS1, $funds);
                }
            });

        // Should handle deletion and update removal
        $this->schemeReturnHelper
            ->expects($this->exactly(2))
            ->method('schemeRemovedFromFunds')
            ->willReturnCallback(function($scheme, $funds) use ($updatedScheme, $deletedScheme) {
                static $callCount = 0;
                $callCount++;

                if ($callCount === 1) {
                    $this->assertSame($updatedScheme, $scheme);
                    $this->assertCount(0, $funds); // Update removes no funds
                } else {
                    $this->assertSame($deletedScheme, $scheme);
                    $this->assertCount(1, $funds);
                    $this->assertContains(Fund::CRSTS1, $funds);
                }
            });

        $this->listener->onFlush($this->args);
    }

    public function testHandlesEmptyFundArrays(): void
    {
        $scheme = new Scheme();
        // setFunds([]) results in empty array

        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([$scheme]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        $this->schemeReturnHelper
            ->method('setEntityManager')
            ->willReturn($this->schemeReturnHelper);

        $this->schemeReturnHelper
            ->expects($this->once())
            ->method('schemeAddedToFunds')
            ->with($scheme, []); // Empty array should be passed through

        $this->listener->onFlush($this->args);
    }
}