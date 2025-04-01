<?php

namespace App\Utility\PropertyChangeLog\Events;

use App\Entity\Enum\ChangeLogAction;
use App\Entity\PropertyChangeLog;
use App\Utility\PropertyChangeLog\PropertyChangeSet;
use Symfony\Contracts\EventDispatcher\Event;

class ChangeLogEntityCreatedEvent extends Event
{
    public function __construct(
        protected PropertyChangeLog  $propertyChangeLog,
        protected mixed              $sourceEntity,
        protected ChangeLogAction    $action,
        protected ?PropertyChangeSet $changeSet = null,
    ) {}

    public function getPropertyChangeLog(): PropertyChangeLog
    {
        return $this->propertyChangeLog;
    }

    public function getSourceEntity(): mixed
    {
        return $this->sourceEntity;
    }

    public function getAction(): ChangeLogAction
    {
        return $this->action;
    }

    public function getChangeSet(): ?PropertyChangeSet
    {
        return $this->changeSet;
    }
}
