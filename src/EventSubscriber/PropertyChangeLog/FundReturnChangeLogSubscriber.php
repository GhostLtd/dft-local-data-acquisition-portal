<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Entity\FundReturn\FundReturn;
use App\Utility\PropertyChangeLog\Events\ChangeSetRetrievedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FundReturnChangeLogSubscriber implements EventSubscriberInterface
{
    public function changeSetRetrieved(ChangeSetRetrievedEvent $event): void
    {
        $entity = $event->getSourceEntity();

        if (!$entity instanceof FundReturn) {
            return;
        }

        $changeSet = $event->getChangeSet();
        $changes = $changeSet->getChanges();

        $fieldsBlacklist = [
            'fundAward',
            'signoffEmail', // Already present via signoffUser
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
            ChangeSetRetrievedEvent::class => ['ChangeSetRetrieved', 0],
        ];
    }
}
