<?php

namespace App\Event;

use App\Entity\Enum\CompletionStatus;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\ProjectLevelSection;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Entity\ProjectReturn\ProjectReturnSectionStatus;

class ProjectReturnSectionUpdateEvent extends ReturnSectionUpdateEvent
{
    public function __construct(
        protected ProjectReturn                   $projectReturn,
        protected ExpenseType|ProjectLevelSection $section,
        array                                     $options,
    )
    {
        parent::__construct($options);
    }

    public function getOrCreateSectionStatus(): ProjectReturnSectionStatus
    {
        $status = $this->projectReturn->getProjectReturnSectionStatusForSection($this->section);

        if (!$status) {
            $status = (new ProjectReturnSectionStatus())
                ->setStatus(CompletionStatus::NOT_STARTED)
                ->setName($this->section->name);

            $this->projectReturn->addSectionStatus($status);
        }

        return $status;
    }
}
