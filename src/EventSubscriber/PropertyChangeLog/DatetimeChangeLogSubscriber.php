<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Utility\PropertyChangeLog\Events\ChangeSetRetrievedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DatetimeChangeLogSubscriber implements EventSubscriberInterface
{
    public function changeSetRetrieved(ChangeSetRetrievedEvent $event): void
    {
        $changeSet = $event->getChangeSet();
        $changes = $changeSet->getChanges();

        $dateToString = fn(?\DateTime $d) => $d?->format('Y-m-d H:i:s');

        foreach($changes as $field => $data) {
            if ($data[0] instanceof \DateTime) {
                $changes[$field][0] = $dateToString($data[0]);
            }

            if ($data[1] instanceof \DateTime) {
                $changes[$field][1] = $dateToString($data[1]);
            }

            // These datetimes are the same (although different objects)
            if (($changes[$field][0] ?? null) === ($changes[$field][1] ?? null)) {
                unset($changes[$field]);
            }
        }

        $changeSet->setChanges($changes);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChangeSetRetrievedEvent::class => ['changeSetRetrieved', 100],
        ];
    }
}
