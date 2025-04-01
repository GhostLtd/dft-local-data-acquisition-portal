<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Utility\PropertyChangeLog\Events\ChangeSetRetrievedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class IdChangeLogSubscriber implements EventSubscriberInterface
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function changeSetRetrieved(ChangeSetRetrievedEvent $event): void
    {
        $changeSet = $event->getChangeSet();
        $changes = $changeSet->getChanges();

        if (isset($changes['id'])) {
            unset($changes['id']);
        }

        $changeSet->setChanges($changes);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChangeSetRetrievedEvent::class => ['changeSetRetrieved', 0],
        ];
    }
}
