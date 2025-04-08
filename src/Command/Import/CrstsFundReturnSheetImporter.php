<?php

namespace App\Command\Import;

use App\Entity\Authority;
use App\Entity\Enum\Rating;
use App\Entity\FundReturn\CrstsFundReturn;
use App\Entity\FundReturn\FundReturn;
use App\Entity\User;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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
        [$authorityName, $values] = $this->extractValueFromArray($values, 0);
        if (!($fundReturn = $this->findCrstsFundReturnByAuthorityName($authorityName))) {
            $this->io->error("FundAward for {$authorityName} does not exist");
            return;
        }

        $this->setColumnValues($fundReturn, $values);
//        $this->io->writeln("FundReturn for {$authorityName} updated");
    }
}