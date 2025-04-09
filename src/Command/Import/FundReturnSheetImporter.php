<?php

namespace App\Command\Import;

use App\Entity\FundReturn\CrstsFundReturn;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

class FundReturnSheetImporter extends AbstractSheetImporter
{
    protected const array COLUMNS = [
        'authority' => 'ucla',
        'signoffName' => 'signoff_name',
        'signoffEmail' => 'signoff_role',
        'signoffDate' => 'signoff_date',
    ];

    protected function processRow(Row $row): void
    {
        $values = $this->getCellValues($row);
        $authorityName = $this->extractValueFromArray($values, 'authority');
        if ($this->findCrstsFundReturnByAuthorityName($authorityName)) {
            return;
        }

        $fundReturn = (new CrstsFundReturn())
            ->setYear($this->year)
            ->setQuarter($this->quarter)
            ->setFundAward($this->findCrstsFundAwardByAuthorityName($authorityName))
        ;
        $values['signoffDate'] = $this->attemptToFormatAsDate($values['signoffDate']);

        $this->setColumnValues($fundReturn, $values);

        $this->persist($fundReturn);
    }
}