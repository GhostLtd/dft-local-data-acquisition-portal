<?php

namespace App\Utility\PropertyChangeLog\Events;

use App\Utility\PropertyChangeLog\PropertyChangeSet;
use Symfony\Contracts\EventDispatcher\Event;

class ChangeSetRetrievedEvent extends Event
{
    public function __construct(
        protected PropertyChangeSet $changeSet,
        protected mixed             $sourceEntity,
    ) {}

    public function getChangeSet(): PropertyChangeSet
    {
        return $this->changeSet;
    }

    public function getSourceEntity(): mixed
    {
        return $this->sourceEntity;
    }
}
