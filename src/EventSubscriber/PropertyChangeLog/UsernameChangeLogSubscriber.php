<?php

namespace App\EventSubscriber\PropertyChangeLog;

use App\Utility\PropertyChangeLog\Events\ChangeLogEntityCreatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UsernameChangeLogSubscriber implements EventSubscriberInterface
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
    }

    public function changeLogEntityCreated(ChangeLogEntityCreatedEvent $event): void
    {
        $changeLog = $event->getPropertyChangeLog();
        $value = $changeLog->getPropertyValue();

        if ($value instanceof UserInterface) {
            $changeLog->setPropertyValue($value->getUserIdentifier());
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ChangeLogEntityCreatedEvent::class => ['changeLogEntityCreated', 0],
        ];
    }
}
