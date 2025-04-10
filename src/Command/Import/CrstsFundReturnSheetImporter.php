<?php

namespace App\Command\Import;

use App\Entity\Enum\Rating;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

class CrstsFundReturnSheetImporter extends AbstractSheetImporter
{
    protected const array COLUMNS = [
        'authority' => 'ucla',
        'progressSummary' => 'progress_summary',
        'deliveryConfidence' => 'delivery_confidence',
        'overallConfidence' => 'overall_confidence',
        'localContribution' => 'local_contribution',
        'comments' => 'comments',
        'resourceFunding' => 'resource_funding',
    ];

    protected function processRow(Row $row): void
    {
        $values = $this->getCellValues($row);
        $authorityName = $this->extractValueFromArray($values, 'authority');
        if (!($fundReturn = $this->findCrstsFundReturnByAuthorityName($authorityName))) {
            $this->logger->error("FundAward does not exist", [$authorityName]);
            return;
        }

        $values['overallConfidence'] = $this->attemptToFormatAsEnum(Rating::class, $values['overallConfidence']);

        $this->setColumnValues($fundReturn, $values);
    }
}