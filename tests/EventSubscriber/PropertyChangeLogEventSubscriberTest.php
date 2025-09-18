<?php

namespace App\Tests\EventSubscriber;

use App\Entity\PropertyChangeLog;
use App\Entity\PropertyChangeLoggableInterface;
use App\EventSubscriber\PropertyChangeLogEventSubscriber;
use App\Utility\PropertyChangeLog\Events\ChangeLogEntityCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Uid\Ulid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PropertyChangeLogEventSubscriberTest extends TestCase
{
    /** @var Security&MockObject */
    private Security $security;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $defaultEntityManager;
    /** @var EventDispatcherInterface&MockObject */
    private EventDispatcherInterface $eventDispatcher;
    private PropertyChangeLogEventSubscriber $subscriber;
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $eventEntityManager;
    /** @var UnitOfWork&MockObject */
    private UnitOfWork $unitOfWork;
    /** @var OnFlushEventArgs&MockObject */
    private OnFlushEventArgs $eventArgs;

    protected function setUp(): void
    {
        $this->security = $this->createMock(Security::class);
        $this->defaultEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->subscriber = new PropertyChangeLogEventSubscriber(
            $this->security,
            $this->defaultEntityManager,
            $this->eventDispatcher
        );

        $this->eventEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->eventArgs = $this->createMock(OnFlushEventArgs::class);

        $this->eventArgs->method('getObjectManager')->willReturn($this->eventEntityManager);
        $this->eventEntityManager->method('getUnitOfWork')->willReturn($this->unitOfWork);

        $metadata = $this->createMock(ClassMetadata::class);
        $this->defaultEntityManager->method('getClassMetadata')->willReturn($metadata);
    }

    public function testLogsInsertActionForLoggableEntity(): void
    {
        $entity = $this->createMock(PropertyChangeLoggableInterface::class);
        $entity->method('getId')->willReturn(new Ulid());

        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('user@example.com');

        $token = $this->createMock(PostAuthenticationToken::class);
        $token->method('getUser')->willReturn($user);
        $token->method('getFirewallName')->willReturn('main');

        $this->security->method('getToken')->willReturn($token);
        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([$entity]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);
        $this->unitOfWork->method('getEntityChangeSet')->willReturn(['id' => [null, $entity->getId()]]);

        $this->defaultEntityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PropertyChangeLog::class));

        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch');

        $this->subscriber->onFlush($this->eventArgs);
    }

    public function testLogsUpdateActionWithChangeSet(): void
    {
        $entity = $this->createMock(PropertyChangeLoggableInterface::class);
        $entity->method('getId')->willReturn(new Ulid());

        $changeSet = [
            'name' => ['Old Name', 'New Name'],
            'status' => ['active', 'inactive']
        ];

        $this->security->method('getToken')->willReturn(null);
        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([$entity]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);
        $this->unitOfWork->method('getEntityChangeSet')->with($entity)->willReturn($changeSet);

        $this->defaultEntityManager
            ->expects($this->exactly(2)) // One for each changed field
            ->method('persist')
            ->with($this->isInstanceOf(PropertyChangeLog::class));

        $this->eventDispatcher
            ->expects($this->atLeastOnce())
            ->method('dispatch');

        $this->subscriber->onFlush($this->eventArgs);
    }

    public function testLogsDeleteAction(): void
    {
        $entity = $this->createMock(PropertyChangeLoggableInterface::class);
        $entity->method('getId')->willReturn(new Ulid());

        $this->security->method('getToken')->willReturn(null);
        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([$entity]);

        $this->defaultEntityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PropertyChangeLog::class));

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(ChangeLogEntityCreatedEvent::class));

        $this->subscriber->onFlush($this->eventArgs);
    }

    public function testIgnoresNonLoggableEntities(): void
    {
        $nonLoggableEntity = new \stdClass();

        $this->security->method('getToken')->willReturn(null);
        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([$nonLoggableEntity]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);

        $this->defaultEntityManager
            ->expects($this->never())
            ->method('persist');

        $this->subscriber->onFlush($this->eventArgs);
    }

    public function testUsesDefaultSourceWhenNoUser(): void
    {
        $entity = $this->createMock(PropertyChangeLoggableInterface::class);
        $entity->method('getId')->willReturn(new Ulid());

        $this->subscriber->setDefaultSource('cron-job');

        $this->security->method('getToken')->willReturn(null);
        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([$entity]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);
        $this->unitOfWork->method('getEntityChangeSet')->willReturn(['id' => [null, $entity->getId()]]);

        $this->defaultEntityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback(function(PropertyChangeLog $log) {
                return $log->getSource() === 'cron-job';
            }));

        $this->subscriber->onFlush($this->eventArgs);
    }

    public function testHandlesMultipleEntitiesInSingleFlush(): void
    {
        $insertedEntity = $this->createMock(PropertyChangeLoggableInterface::class);
        $insertedEntity->method('getId')->willReturn(new Ulid());

        $updatedEntity = $this->createMock(PropertyChangeLoggableInterface::class);
        $updatedEntity->method('getId')->willReturn(new Ulid());

        $deletedEntity = $this->createMock(PropertyChangeLoggableInterface::class);
        $deletedEntity->method('getId')->willReturn(new Ulid());

        $this->security->method('getToken')->willReturn(null);
        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([$insertedEntity]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([$updatedEntity]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([$deletedEntity]);
        $this->unitOfWork->method('getEntityChangeSet')->willReturn(['field' => ['old', 'new']]);

        $this->defaultEntityManager
            ->expects($this->exactly(3)) // 1 insert + 1 update (with 1 field change) + 1 delete
            ->method('persist')
            ->with($this->isInstanceOf(PropertyChangeLog::class));

        $this->subscriber->onFlush($this->eventArgs);
    }

    public function testComputesChangeSetForChangeLogEntities(): void
    {
        $entity = $this->createMock(PropertyChangeLoggableInterface::class);
        $entity->method('getId')->willReturn(new Ulid());

        $this->security->method('getToken')->willReturn(null);
        $this->unitOfWork->method('getScheduledEntityInsertions')->willReturn([$entity]);
        $this->unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $this->unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);
        $this->unitOfWork->method('getEntityChangeSet')->willReturn(['id' => [null, $entity->getId()]]);

        $metadata = $this->createMock(ClassMetadata::class);
        $this->defaultEntityManager->method('getClassMetadata')->willReturn($metadata);

        $this->unitOfWork
            ->expects($this->once())
            ->method('computeChangeSet')
            ->with($metadata, $this->isInstanceOf(PropertyChangeLog::class));

        $this->subscriber->onFlush($this->eventArgs);
    }
}
