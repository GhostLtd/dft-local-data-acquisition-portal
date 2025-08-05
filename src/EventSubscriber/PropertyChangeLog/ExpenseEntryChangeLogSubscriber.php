<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Entity\ExpenseEntry;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Utility\PropertyChangeLog\Events\ChangeLogEntityCreatedEvent;
use App\Utility\PropertyChangeLog\Events\ChangeSetRetrievedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExpenseEntryChangeLogSubscriber implements EventSubscriberInterface
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function changeSetRetrievedEvent(ChangeSetRetrievedEvent $event): void
    {
        if (!$event->getSourceEntity() instanceof ExpenseEntry) {
            return;
        }

        // We're only interested in the "value" field
        $blacklistedFields = [
            'type',
            'division',
            'column',
            'forecast',
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
        $expenseType = $event->getSourceEntity();

        if (!$expenseType instanceof ExpenseEntry) {
            return;
        }

        // The relationship between returns and ExpenseType is one way, so we
        // need to look at the returns that the entity manager has, to figure
        // out which one this ExpenseType belongs to
        $idMap = $this->entityManager->getUnitOfWork()->getIdentityMap();

        $entityClass = null;
        $entityId = null;

        $returns = array_merge(
            $idMap[FundReturn::class] ?? [],
            $idMap[SchemeReturn::class] ?? [],
        );

        foreach($returns as $return) {
            $expenses = match ($return::class) {
                CrstsFundReturn::class,
                CrstsSchemeReturn::class => $return->getExpenses(),
                default => throw new \RuntimeException('Unsupported return type'),
            };

            if ($expenses->contains($expenseType)) {
                $entityId = $return->getId();
                $entityClass = $return::class;
                break;
            }

            if (!$expenses instanceof PersistentCollection) {
                throw new \RuntimeException('Unexpected collection type');
            }

            foreach($expenses->getSnapshot() as $snapshotEntry) {
                if ($snapshotEntry === $expenseType) {
                    $entityId = $return->getId();
                    $entityClass = $return::class;
                    break 2;
                }
            }
        }

        if (!$entityId || !$entityClass) {
            throw new \RuntimeException('Unable to match expense to parent');
        }

        $changeLog
            ->setEntityId($entityId)
            ->setEntityClass($entityClass)
            ->setPropertyName($expenseType->getPropertyChangeLogPropertyName());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChangeSetRetrievedEvent::class => 'changeSetRetrievedEvent',
            ChangeLogEntityCreatedEvent::class => 'changeLogEventCreated',
        ];
    }
}
