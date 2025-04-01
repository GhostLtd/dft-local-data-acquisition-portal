<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Entity\SchemeReturn\SchemeReturn;
use App\Utility\PropertyChangeLog\Events\ChangeSetRetrievedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SchemeReturnChangeLogSubscriber implements EventSubscriberInterface
{
    public function changeSetRetrieved(ChangeSetRetrievedEvent $event): void
    {
        $entity = $event->getSourceEntity();

        if (!$entity instanceof SchemeReturn) {
            return;
        }

        $changeSet = $event->getChangeSet();
        $changes = $changeSet->getChanges();

        $fieldsBlacklist = [
            'benefitCostRatio',
            'fundReturn',
            'scheme',
        ];

        foreach($changes as $field => $_change) {
            if (in_array($field, $fieldsBlacklist)) {
                unset($changes[$field]);
            }
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
