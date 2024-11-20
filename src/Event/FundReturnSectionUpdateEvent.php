<?php

namespace App\Event;

use App\Entity\Enum\CompletionStatus;
use App\Entity\Enum\ExpenseType;
use App\Entity\Enum\FundLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\FundReturn\FundReturnSectionStatus;

class FundReturnSectionUpdateEvent extends ReturnSectionUpdateEvent
{
    public function __construct(
        protected FundReturn                   $fundReturn,
        protected ExpenseType|FundLevelSection $section,
        array                                  $options,
    )
    {
        parent::__construct($options);
    }

    public function getOrCreateSectionStatus(): FundReturnSectionStatus
    {
        $status = $this->fundReturn->getFundReturnSectionStatusForSection($this->section);

        if (!$status) {
            $status = (new FundReturnSectionStatus())
                ->setStatus(CompletionStatus::NOT_STARTED)
                ->setName($this->section->name);

            $this->fundReturn->addSectionStatus($status);
        }

        return $status;
    }
}
