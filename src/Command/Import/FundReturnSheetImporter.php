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
        [$authorityName, $values] = $this->extractValueFromArray($values, 0);
        if ($this->findCrstsFundReturnByAuthorityName($authorityName)) {
//            $this->io->writeln("FundAward for {$authorityName} already exists: skipping...");
            return;
        }

        $fundReturn = (new CrstsFundReturn())
            ->setYear($this->year)
            ->setQuarter($this->quarter)
            ->setFundAward($this->findCrstsFundAwardByAuthorityName($authorityName))
        ;
        $this->setColumnValues($fundReturn, $values);

        $this->persist($fundReturn);
//        $this->io->writeln("FundReturn for {$authorityName} added");

    }
}