<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Entity\Scheme;
use App\Utility\PropertyChangeLog\Events\ChangeSetRetrievedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SchemeChangeLogSubscriber implements EventSubscriberInterface
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function changeSetRetrieved(ChangeSetRetrievedEvent $event): void
    {
        $entity = $event->getSourceEntity();

        if (!$entity instanceof Scheme) {
            return;
        }

        $changeSet = $event->getChangeSet();
        $changes = $changeSet->getChanges();

        $fieldsBlacklist = [
            'authority',
            'crstsData', // Individual fields will still be listed separately
        ];

        foreach($changes as $field => $_change) {
            if (in_array($field, $fieldsBlacklist)) {
                unset($changes[$field]);
            }
        }

        if (isset($changes['funds'])) {
            $fundsToList = fn(?array $funds) => empty($funds) ? null : join(',', $funds);
            $changes['funds'][0] = $fundsToList($changes['funds'][0]);
            $changes['funds'][1] = $fundsToList($changes['funds'][1]);
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
