<?php

namespace App\Utility\PropertyChangeLog;

class PropertyChangeSet
{
    public function __construct(
        protected array $changes,
    ) {}

    public function getChanges(): array
    {
        return $this->changes;
    }

    public function setChanges(array $changes): PropertyChangeSet
    {
        $this->changes = $changes;
        return $this;
    }
}
