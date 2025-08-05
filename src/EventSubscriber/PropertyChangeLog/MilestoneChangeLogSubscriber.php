<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Entity\Milestone;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Utility\PropertyChangeLog\Events\ChangeLogEntityCreatedEvent;
use App\Utility\PropertyChangeLog\Events\ChangeSetRetrievedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MilestoneChangeLogSubscriber implements EventSubscriberInterface
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function changeSetRetrievedEvent(ChangeSetRetrievedEvent $event): void
    {
        if (!$event->getSourceEntity() instanceof Milestone) {
            return;
        }

        // We're only interested in the "date" field
        $blacklistedFields = [
            'type',
        ];

        $changeSet = $event->getChangeSet();
        $changes = $changeSet->getChanges();

        foreach($changes as $field => $_value) {
            if (in_array($field, $blacklistedFields)) {
                unset($changes[$field]);
            }
        }

        $changeSet->setChanges($changes);
    }

    public function changeLogEventCreated(ChangeLogEntityCreatedEvent $event): void
    {
        $changeLog = $event->getPropertyChangeLog();
        $milestone = $event->getSourceEntity();

        if (!$milestone instanceof Milestone) {
            return;
        }

        // The relationship between scheme returns and Milestones is one way, so we
        // need to look at the returns that the entity manager has, to figure
        // out which one this Milestone belongs to
        $idMap = $this->entityManager->getUnitOfWork()->getIdentityMap();

        $entityClass = null;
        $entityId = null;

        foreach($idMap[SchemeReturn::class] as $return) {
            $milestones = match ($return::class) {
                CrstsSchemeReturn::class => $return->getMilestones(),
                default => throw new \RuntimeException('Unsupported return type'),
            };

            if ($milestones->contains($milestone)) {
                $entityId = $return->getId();
                $entityClass = $return::class;
                break;
            }

            if (!$milestones instanceof PersistentCollection) {
                throw new \RuntimeException('Unexpected collection type');
            }

            // Naughty, but how else can I figure this out?
            foreach($milestones->getSnapshot() as $snapshotEntry) {
                if ($snapshotEntry === $milestone) {
                    $entityId = $return->getId();
                    $entityClass = $return::class;
                    break 2;
                }
            }
        }

        if (!$entityId || !$entityClass) {
            throw new \RuntimeException('Unable to match milestone to parent');
        }

        $type = $milestone->getType()->value;

        // e.g. milestone.start_construction
        $propertyName = "milestone.{$type}";

        $changeLog
            ->setEntityId($entityId)
            ->setEntityClass($entityClass)
            ->setPropertyName($propertyName);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChangeSetRetrievedEvent::class => 'changeSetRetrievedEvent',
            ChangeLogEntityCreatedEvent::class => 'changeLogEventCreated',
        ];
    }
}
