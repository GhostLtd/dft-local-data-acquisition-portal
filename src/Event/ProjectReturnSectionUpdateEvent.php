<?php

namespace App\Event;

use App\Entity\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\CompletionStatus;
use App\Entity\Enum\ProjectLevelSection;
use App\Entity\ProjectReturn\ProjectReturn;
use App\Entity\ProjectReturn\ProjectReturnSectionStatus;

class ProjectReturnSectionUpdateEvent extends ReturnSectionUpdateEvent
{
    public function __construct(
        protected ProjectReturn                   $projectReturn,
        protected DivisionConfiguration|ProjectLevelSection $section,
        array                                     $options,
    )
    {
        parent::__construct($options);
    }

    public function getOrCreateSectionStatus(): ProjectReturnSectionStatus
    {
        $name = match($this->section::class) {
            DivisionConfiguration::class => $this->section->getTitle(),
            ProjectLevelSection::class => $this->section->name,
        };

        $status = $this->projectReturn->getProjectReturnSectionStatusForName($name);

        if (!$status) {
            $status = (new ProjectReturnSectionStatus())
                ->setStatus(CompletionStatus::NOT_STARTED)
                ->setName($name);

            $this->projectReturn->addSectionStatus($status);
        }

        return $status;
    }
}
