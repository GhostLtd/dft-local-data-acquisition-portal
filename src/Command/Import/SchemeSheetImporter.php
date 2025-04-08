<?php

namespace App\Command\Import;

use App\Entity\Authority;
use App\Entity\Enum\Fund;
use App\Entity\FundAward;
use App\Entity\Scheme;
use App\Entity\SchemeReturn\CrstsSchemeReturn;
use App\Entity\SchemeReturn\SchemeReturn;
use App\Entity\User;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Console\Style\SymfonyStyle;

class SchemeSheetImporter extends AbstractSheetImporter
{
    protected const array COLUMNS = [
        'name' => 'name',
        'authorityName' => 'location',
        'crstsData.isRetained' => 'crsts_data_is_retained',
        'description' => 'description',
        'crstsData.isPreviouslyTcf' => 'crsts_data_is_previously_tcf',
        'transportMode' => 'transport_mode',
    ];

    protected function processRow(Row $row): void
    {
        $values = $this->getCellValues($row);
        [$authorityName, $values] = $this->extractValueFromArray($values, 1);
        if ($this->findSchemeByName($values[0], $authorityName)) {
            $this->io->writeln("Scheme {$authorityName}/{$values[0]} already not exists: skipping...");
        }

        $scheme = (new Scheme())
            ->setAuthority($this->findAuthorityByName($authorityName))
            ;
//        $return = $this->findCrstsFundReturnByAuthorityName($authorityName);
//        $return->addSchemeReturn($schemeReturn = (new CrstsSchemeReturn())->setScheme($scheme));
        $this->setColumnValues($scheme, $values);

        $this->persist($scheme);
    }
}