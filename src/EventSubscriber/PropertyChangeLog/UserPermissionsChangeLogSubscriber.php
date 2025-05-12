<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Entity\UserPermission;
use App\Utility\PropertyChangeLog\Events\ChangeLogEntityCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserPermissionsChangeLogSubscriber implements EventSubscriberInterface
{
    public function changeLogEntityCreated(ChangeLogEntityCreatedEvent $event): void
    {
        $changeLog = $event->getPropertyChangeLog();

        if (
            $changeLog->getEntityClass() !== UserPermission::class ||
            $changeLog->getPropertyName() !== 'fundTypes'
        ) {
            return;
        }

        $value = $changeLog->getPropertyValue();
        $changeLog->setPropertyValue(join(',', $value));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChangeLogEntityCreatedEvent::class => ['changeLogEntityCreated', 0],
        ];
    }
}
