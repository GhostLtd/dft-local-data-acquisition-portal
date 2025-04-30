<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Entity\FundAward;
use App\Utility\PropertyChangeLog\Events\ChangeSetRetrievedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FundAwardChangeLogSubscriber implements EventSubscriberInterface
{
    public function changeSetRetrieved(ChangeSetRetrievedEvent $event): void
    {
        $entity = $event->getSourceEntity();

        if (!$entity instanceof FundAward) {
            return;
        }

        $changeSet = $event->getChangeSet();
        $changes = $changeSet->getChanges();

        $fieldsBlacklist = [
            'authority',
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
