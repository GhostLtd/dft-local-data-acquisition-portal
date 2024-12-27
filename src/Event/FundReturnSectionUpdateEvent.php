<?php

namespace App\Event;

use App\Entity\Config\ExpenseDivision\DivisionConfiguration;
use App\Entity\Enum\CompletionStatus;
use App\Entity\Enum\FundLevelSection;
use App\Entity\FundReturn\FundReturn;
use App\Entity\FundReturn\FundReturnSectionStatus;

class FundReturnSectionUpdateEvent extends ReturnSectionUpdateEvent
{
    public function __construct(
        protected FundReturn                             $fundReturn,
        protected DivisionConfiguration|FundLevelSection $section,
        array                                            $options,
    )
    {
        parent::__construct($options);
    }

    public function getOrCreateSectionStatus(): FundReturnSectionStatus
    {
        $name = match($this->section::class) {
            DivisionConfiguration::class => $this->section->getKey(),
            FundLevelSection::class => $this->section->name,
        };

        $status = $this->fundReturn->getFundReturnSectionStatusForName($name);

        if (!$status) {
            $status = (new FundReturnSectionStatus())
                ->setStatus(CompletionStatus::NOT_STARTED)
                ->setName($name);

            $this->fundReturn->addSectionStatus($status);
        }

        return $status;
    }
}
